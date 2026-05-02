<?php

namespace App\Services;

/**
 * Lógica de negocio para normalización fiscal de cotizaciones.
 *
 * Principio de Separación de Responsabilidades (SoC):
 * La lógica fiscal NO pertenece al parser (capa de datos)
 * ni al wizard (capa de UI). Este servicio es la única
 * fuente de verdad sobre cómo se normaliza el IVA.
 *
 * Regla rector: Priorizar los montos del proveedor.
 * - Si el proveedor desglosa IVA por producto → usar tal cual.
 * - Si indica IVA global → calcular por línea.
 * - Si no indica IVA → marcar para resolución manual.
 *
 * Nota: La tasa IVA es 16% fija (LIVA Art. 1, México).
 * Si en el futuro cambia, se modifica solo esta constante.
 *
 * CONVENCIÓN: tax_amount = IVA TOTAL de línea (no unitario).
 * Esto evita errores de redondeo al dividir entre cantidad.
 */
class TaxNormalizerService
{
    /** Tasa de IVA vigente en México (LIVA Art. 1). */
    public const TAX_RATE = 0.16;

    /**
     * Normaliza los datos de una cotización parseada, resolviendo IVA.
     *
     * Recibe los datos crudos del parser (con tax_info e items)
     * y devuelve los datos con unit_price normalizado a "sin IVA"
     * + campos fiscales (tax_amount como IVA de línea, tax_source,
     * unit_price_original, line_subtotal, line_total).
     *
     * @param  array $parsedData Datos crudos del parser
     * @return array Datos con campos fiscales resueltos
     */
    public function normalize(array $parsedData): array
    {
        $taxInfo = $parsedData['tax_info'] ?? [];
        $items   = $parsedData['items'] ?? [];

        $normalizedItems = array_map(
            fn (array $item) => $this->normalizeItem($item, $taxInfo),
            $items,
        );

        $parsedData['items'] = $normalizedItems;

        return $parsedData;
    }

    /**
     * Resuelve los campos fiscales cuando el usuario indica manualmente
     * si los precios incluyen IVA o no.
     *
     * Se usa desde el QuotationWizard cuando Gemini no pudo detectar
     * la inclusión de IVA y el usuario lo confirma vía toggle.
     *
     * @param  float $originalPrice  Precio tal como vino en la cotización (unitario)
     * @param  float $quantity       Cantidad del ítem
     * @param  bool  $includesTax    true = el precio YA incluye IVA
     * @return array{unit_price: float, tax_amount: float, tax_source: string, line_subtotal: float, line_total: float}
     */
    public function resolveForUserChoice(float $originalPrice, float $quantity, bool $includesTax): array
    {
        $qty = max($quantity, 1.0);

        if ($includesTax) {
            $netPrice     = round($originalPrice / (1 + self::TAX_RATE), 2);
            $lineSubtotal = round($netPrice * $qty, 2);
            $lineTotal    = round($originalPrice * $qty, 2);
            $taxAmount    = round($lineTotal - $lineSubtotal, 2);

            return [
                'unit_price'    => $netPrice,
                'tax_amount'    => $taxAmount,
                'tax_source'    => 'user_confirmed',
                'line_subtotal' => $lineSubtotal,
                'line_total'    => $lineTotal,
            ];
        }

        $lineSubtotal = round($originalPrice * $qty, 2);
        $taxAmount    = round($lineSubtotal * self::TAX_RATE, 2);
        $lineTotal    = round($lineSubtotal + $taxAmount, 2);

        return [
            'unit_price'    => round($originalPrice, 2),
            'tax_amount'    => $taxAmount,
            'tax_source'    => 'user_confirmed',
            'line_subtotal' => $lineSubtotal,
            'line_total'    => $lineTotal,
        ];
    }

    /**
     * Normaliza un ítem individual según su contexto fiscal.
     *
     * Prioridad:
     * 1. Si el proveedor desglosa IVA por producto → usar tal cual
     * 2. Si indica globalmente que los precios incluyen IVA → calcular
     * 3. Si no se detecta → dejar pendiente para resolución manual
     */
    private function normalizeItem(array $item, array $taxInfo): array
    {
        $rawPrice     = (float) ($item['unit_price'] ?? 0);
        $quantity     = (float) ($item['quantity'] ?? 1);
        $itemTax      = $item['tax_amount'] ?? null;
        $itemIncludes = $item['price_includes_tax'] ?? null;
        $globalIncludes = $taxInfo['prices_include_tax'] ?? null;
        $taxDetected  = $taxInfo['tax_detected'] ?? false;

        // Guardar siempre el precio original para trazabilidad
        $item['unit_price_original'] = $rawPrice;

        // Preservar line_subtotal y line_total del proveedor si existen
        $providerLineSubtotal = isset($item['line_subtotal']) ? (float) $item['line_subtotal'] : null;
        $providerLineTotal    = isset($item['line_total']) ? (float) $item['line_total'] : null;

        // Caso 1: El proveedor desglosa IVA por producto
        if ($itemTax !== null && $itemTax > 0) {
            return $this->applySupplierPerItemTax(
                $item, $rawPrice, $quantity, (float) $itemTax,
                $itemIncludes, $providerLineSubtotal, $providerLineTotal
            );
        }

        // Caso 2: Información global disponible
        if ($taxDetected && $globalIncludes !== null) {
            return $this->applyGlobalTaxContext(
                $item, $rawPrice, $quantity, $globalIncludes,
                $providerLineSubtotal, $providerLineTotal
            );
        }

        // Caso 3: Información por ítem pero sin monto específico
        if ($itemIncludes !== null) {
            return $this->applyGlobalTaxContext(
                $item, $rawPrice, $quantity, $itemIncludes,
                $providerLineSubtotal, $providerLineTotal
            );
        }

        // Caso 4: No se detectó IVA → dejar pendiente
        $item['tax_amount'] = null;
        $item['tax_source'] = null;
        $item['line_subtotal'] = $providerLineSubtotal;
        $item['line_total']    = $providerLineTotal;

        return $item;
    }

    /**
     * Caso 1: El proveedor da el desglose de IVA por producto.
     * Se priorizan sus montos exactos para evitar variaciones de centavos.
     *
     * tax_amount aquí viene como IVA de línea (total), NO unitario.
     */
    private function applySupplierPerItemTax(
        array $item,
        float $rawPrice,
        float $quantity,
        float $supplierTaxAmount,
        ?bool $itemIncludesTax,
        ?float $providerLineSubtotal,
        ?float $providerLineTotal,
    ): array {
        $qty = max($quantity, 1.0);

        if ($itemIncludesTax === true) {
            // El precio incluye IVA → calcular precio neto
            // Si tenemos line_subtotal del proveedor, deducir de ahí
            if ($providerLineSubtotal !== null) {
                $item['unit_price'] = round($providerLineSubtotal / $qty, 2);
            } else {
                $item['unit_price'] = round($rawPrice - ($supplierTaxAmount / $qty), 2);
            }
        } else {
            // El precio NO incluye IVA → ya es el neto
            $item['unit_price'] = round($rawPrice, 2);
        }

        $item['tax_amount'] = round($supplierTaxAmount, 2);
        $item['tax_source'] = 'supplier_per_item';

        // Totales de línea: priorizar valores del proveedor
        $item['line_subtotal'] = $providerLineSubtotal ?? round($item['unit_price'] * $qty, 2);
        $item['line_total']    = $providerLineTotal ?? round($item['line_subtotal'] + $supplierTaxAmount, 2);

        return $item;
    }

    /**
     * Caso 2: Se sabe si incluye IVA pero no hay desglose por producto.
     * Se calcula el IVA como IVA de línea (total) a partir de la tasa fija.
     */
    private function applyGlobalTaxContext(
        array $item,
        float $rawPrice,
        float $quantity,
        bool $includesTax,
        ?float $providerLineSubtotal,
        ?float $providerLineTotal,
    ): array {
        $qty = max($quantity, 1.0);

        if ($includesTax) {
            // Precio incluye IVA → desglosar
            $netPrice     = round($rawPrice / (1 + self::TAX_RATE), 2);
            $lineSubtotal = $providerLineSubtotal ?? round($netPrice * $qty, 2);
            $lineTotal    = $providerLineTotal ?? round($rawPrice * $qty, 2);
            $taxAmount    = round($lineTotal - $lineSubtotal, 2);

            $item['unit_price']    = $netPrice;
            $item['tax_amount']    = $taxAmount;
            $item['line_subtotal'] = $lineSubtotal;
            $item['line_total']    = $lineTotal;
        } else {
            // Precio ya es sin IVA → calcular IVA
            $lineSubtotal = $providerLineSubtotal ?? round($rawPrice * $qty, 2);
            $taxAmount    = round($lineSubtotal * self::TAX_RATE, 2);
            $lineTotal    = $providerLineTotal ?? round($lineSubtotal + $taxAmount, 2);

            $item['unit_price']    = round($rawPrice, 2);
            $item['tax_amount']    = $taxAmount;
            $item['line_subtotal'] = $lineSubtotal;
            $item['line_total']    = $lineTotal;
        }

        $item['tax_source'] = 'supplier_global';

        return $item;
    }
}
