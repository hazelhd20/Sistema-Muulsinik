<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Lógica de negocio para normalización de descuentos en cotizaciones.
 *
 * Principio de Separación de Responsabilidades (SoC):
 * La lógica de descuentos NO pertenece al parser (capa de datos),
 * ni al TaxNormalizerService (responsabilidad fiscal),
 * ni al wizard (capa de UI).
 *
 * Este servicio se ejecuta ANTES de TaxNormalizerService en el pipeline:
 *   Precio Original → Descuento → Precio Neto (unit_price) → IVA → Total
 *
 * Reglas implementadas:
 *   R1: Prioridad de descuentos por partida (datos a nivel producto).
 *   R4: Calcular descuento desde porcentaje + precio original.
 *   R5: Calcular porcentaje desde precio original + precio con descuento.
 *   R6: Inferir precio unitario desde importe y cantidad.
 *   R7: Prioridad de datos (explícitos > matemáticos > calculados).
 *   R9: Identificación semántica por relaciones matemáticas.
 */
class DiscountNormalizerService
{
    /**
     * Normaliza los datos de descuento de una cotización parseada.
     *
     * Recibe los datos crudos del parser (con items) y devuelve los datos
     * con campos de descuento resueltos y unit_price ajustado al precio neto.
     *
     * @param  array  $parsedData  Datos crudos del parser
     * @return array Datos con descuentos normalizados
     */
    public function normalize(array $parsedData): array
    {
        $items = $parsedData['items'] ?? [];

        $normalizedItems = array_map(
            fn (array $item) => $this->normalizeItem($item),
            $items,
        );

        $parsedData['items'] = $normalizedItems;

        return $parsedData;
    }

    /**
     * Normaliza los campos de descuento de un ítem individual.
     *
     * Aplica las reglas de prioridad (R4, R5, R6, R7, R9) para resolver
     * el porcentaje de descuento y ajustar el unit_price al precio neto.
     *
     * @param  array  $item  Datos del ítem extraídos por la IA
     * @return array Ítem con campos de descuento resueltos
     */
    private function normalizeItem(array $item): array
    {
        $unitPrice = isset($item['unit_price']) ? (float) $item['unit_price'] : null;
        $discountPercent = isset($item['discount_percent']) ? (float) $item['discount_percent'] : null;
        $discountAmount = isset($item['discount_amount']) ? (float) $item['discount_amount'] : null;
        $unitPriceWithDiscount = isset($item['unit_price_with_discount']) ? (float) $item['unit_price_with_discount'] : null;
        $quantity = isset($item['quantity']) ? (float) $item['quantity'] : null;
        $lineSubtotal = isset($item['line_subtotal']) ? (float) $item['line_subtotal'] : null;

        // Si no hay ningún indicador de descuento, retornar sin cambios
        if (! $this->hasAnyDiscountData($discountPercent, $discountAmount, $unitPriceWithDiscount, $unitPrice)) {
            $item['discount_percent'] = null;

            return $item;
        }

        // Si tenemos cantidad e importe neto del proveedor, inferimos el precio unitario descontado exacto
        // para dar máxima prioridad a sus cálculos y evitar discrepancias de redondeo por porcentaje
        if ($unitPriceWithDiscount === null && $quantity !== null && $quantity > 0 && $lineSubtotal !== null && $lineSubtotal > 0) {
            $inferredPrice = round($lineSubtotal / $quantity, 2);
            if ($unitPrice !== null && $unitPrice > 0 && $inferredPrice < $unitPrice) {
                $unitPriceWithDiscount = $inferredPrice;
            }
        }

        // R5: Precio original + precio con descuento (incluso si hay %) → priorizar diferencia explícita de precios para exactitud del proveedor
        if ($unitPrice !== null && $unitPrice > 0 && $unitPriceWithDiscount !== null && $unitPriceWithDiscount > 0) {
            return $this->resolveFromPriceDifference($item, $unitPrice, $unitPriceWithDiscount);
        }

        // R4: Porcentaje explícito + precio original → calcular monto y precio neto
        if ($discountPercent !== null && $discountPercent > 0 && $unitPrice !== null && $unitPrice > 0) {
            return $this->resolveFromPercentage($item, $unitPrice, $discountPercent);
        }

        // R5 variante: Precio original + monto de descuento (sin %) → calcular porcentaje
        if ($unitPrice !== null && $unitPrice > 0 && $discountAmount !== null && $discountAmount > 0) {
            return $this->resolveFromDiscountAmount($item, $unitPrice, $discountAmount);
        }

        // R6: Solo cantidad + importe (sin precio unitario descontado) → inferir precio
        if ($unitPriceWithDiscount === null && $quantity !== null && $quantity > 0 && $lineSubtotal !== null && $lineSubtotal > 0) {
            $inferredPrice = round($lineSubtotal / $quantity, 2);

            // Si el precio inferido difiere del unit_price, puede haber descuento implícito
            if ($unitPrice !== null && $unitPrice > 0 && $inferredPrice < $unitPrice) {
                return $this->resolveFromPriceDifference($item, $unitPrice, $inferredPrice);
            }
        }

        // R9: Identificación semántica — verificar coherencia
        if ($unitPrice !== null && $quantity !== null && $lineSubtotal !== null) {
            $resolved = $this->resolveFromSemantics($item, $unitPrice, $quantity, $lineSubtotal);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        // Sin descuento identificable
        $item['discount_percent'] = null;

        return $item;
    }

    /**
     * R4: Calcular descuento desde porcentaje explícito.
     *
     * Cuando el proveedor proporciona:
     *   - Precio unitario original
     *   - Porcentaje de descuento
     *
     * Se calcula:
     *   discount_amount = unit_price × (% / 100)
     *   unit_price_neto = unit_price - discount_amount
     */
    private function resolveFromPercentage(array $item, float $originalPrice, float $percent): array
    {
        // Validar porcentaje razonable
        if ($percent > 100 || $percent < 0) {
            Log::info('DiscountNormalizer: Porcentaje de descuento fuera de rango, ignorado.', [
                'discount_percent' => $percent,
                'unit_price' => $originalPrice,
            ]);
            $item['discount_percent'] = null;

            return $item;
        }

        $discountAmount = round($originalPrice * ($percent / 100), 2);
        $netPrice = round($originalPrice - $discountAmount, 2);

        // R7: Preservar el precio original del proveedor
        $item['unit_price_original'] = $originalPrice;
        $item['unit_price'] = $netPrice;
        $item['discount_percent'] = round($percent, 4);

        Log::info('DiscountNormalizer: Descuento resuelto desde porcentaje.', [
            'original' => $originalPrice,
            'percent' => $percent,
            'net_price' => $netPrice,
        ]);

        return $item;
    }

    /**
     * R5: Calcular porcentaje desde diferencia de precios.
     *
     * Cuando el proveedor proporciona:
     *   - Precio unitario original
     *   - Precio unitario con descuento
     *
     * Se calcula:
     *   discount_amount = original - descontado
     *   discount_percent = (discount_amount / original) × 100
     */
    private function resolveFromPriceDifference(array $item, float $originalPrice, float $discountedPrice): array
    {
        // El precio descontado no puede ser mayor o igual al original
        if ($discountedPrice >= $originalPrice) {
            $item['discount_percent'] = null;

            return $item;
        }

        $discountAmount = round($originalPrice - $discountedPrice, 2);

        // Si ya hay un porcentaje explícito en el ítem, lo preservamos
        $explicitPercent = isset($item['discount_percent']) ? (float) $item['discount_percent'] : null;
        if ($explicitPercent !== null && $explicitPercent > 0) {
            $percent = $explicitPercent;
        } else {
            $percent = round(($discountAmount / $originalPrice) * 100, 4);
        }

        $item['unit_price_original'] = $originalPrice;
        $item['unit_price'] = $discountedPrice;
        $item['discount_percent'] = $percent;

        Log::info('DiscountNormalizer: Descuento resuelto desde diferencia de precios.', [
            'original' => $originalPrice,
            'discounted' => $discountedPrice,
            'percent' => $percent,
        ]);

        return $item;
    }

    /**
     * R5 variante: Calcular porcentaje desde monto de descuento.
     *
     * Cuando el proveedor proporciona:
     *   - Precio unitario original
     *   - Monto de descuento (sin porcentaje)
     */
    private function resolveFromDiscountAmount(array $item, float $originalPrice, float $discountAmount): array
    {
        // El descuento no puede ser mayor que el precio
        if ($discountAmount >= $originalPrice) {
            Log::info('DiscountNormalizer: Monto de descuento mayor al precio, ignorado.', [
                'unit_price' => $originalPrice,
                'discount_amount' => $discountAmount,
            ]);
            $item['discount_percent'] = null;

            return $item;
        }

        $percent = round(($discountAmount / $originalPrice) * 100, 4);
        $netPrice = round($originalPrice - $discountAmount, 2);

        $item['unit_price_original'] = $originalPrice;
        $item['unit_price'] = $netPrice;
        $item['discount_percent'] = $percent;

        Log::info('DiscountNormalizer: Descuento resuelto desde monto de descuento.', [
            'original' => $originalPrice,
            'discount_amount' => $discountAmount,
            'percent' => $percent,
        ]);

        return $item;
    }

    /**
     * R9: Identificación semántica por relaciones matemáticas.
     *
     * Si una columna cumple: Cantidad × Valor ≈ Importe,
     * probablemente representa el precio unitario neto,
     * independientemente de cómo haya sido nombrada.
     *
     * Detecta si el unit_price ya es el precio descontado al verificar
     * que qty × unit_price ≈ line_subtotal, y si existe un precio mayor
     * en otro campo que sugiera un precio original.
     *
     * @return array|null Ítem normalizado, o null si no se detecta descuento semántico
     */
    private function resolveFromSemantics(array $item, float $unitPrice, float $quantity, float $lineSubtotal): ?array
    {
        if ($quantity <= 0) {
            return null;
        }

        $expectedSubtotal = $unitPrice * $quantity;
        $relativeDiff = abs($expectedSubtotal - $lineSubtotal) / max($lineSubtotal, 1);

        // Si qty × unit_price NO coincide con line_subtotal,
        // el unit_price podría ser el precio original y line_subtotal refleja descuento
        if ($relativeDiff > 0.02 && $lineSubtotal < $expectedSubtotal) {
            $inferredNetPrice = round($lineSubtotal / $quantity, 2);

            $item['unit_price_original'] = $unitPrice;
            $item['unit_price'] = $inferredNetPrice;
            $item['discount_percent'] = round((($unitPrice - $inferredNetPrice) / $unitPrice) * 100, 4);

            Log::info('DiscountNormalizer: Descuento detectado semánticamente.', [
                'unit_price_original' => $unitPrice,
                'inferred_net_price' => $inferredNetPrice,
                'line_subtotal' => $lineSubtotal,
                'discount_percent' => $item['discount_percent'],
            ]);

            return $item;
        }

        return null;
    }

    /**
     * Determina si existen datos que sugieran la presencia de descuento.
     */
    private function hasAnyDiscountData(
        ?float $discountPercent,
        ?float $discountAmount,
        ?float $unitPriceWithDiscount,
        ?float $unitPrice,
    ): bool {
        if ($discountPercent !== null && $discountPercent > 0) {
            return true;
        }
        if ($discountAmount !== null && $discountAmount > 0) {
            return true;
        }
        if ($unitPriceWithDiscount !== null && $unitPriceWithDiscount > 0 && $unitPrice !== null && $unitPriceWithDiscount < $unitPrice) {
            return true;
        }

        return false;
    }
}
