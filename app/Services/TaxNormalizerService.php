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
     * + campos fiscales (tax_amount, tax_source, unit_price_original).
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
     * @param  float $originalPrice  Precio tal como vino en la cotización
     * @param  bool  $includesTax    true = el precio YA incluye IVA
     * @return array{unit_price: float, tax_amount: float, tax_source: string}
     */
    public function resolveForUserChoice(float $originalPrice, bool $includesTax): array
    {
        if ($includesTax) {
            return $this->calculateTaxFromIncludedPrice($originalPrice);
        }

        return [
            'unit_price'  => round($originalPrice, 2),
            'tax_amount'  => round($originalPrice * self::TAX_RATE, 2),
            'tax_source'  => 'user_confirmed',
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
        $itemTax      = $item['tax_amount'] ?? null;
        $itemIncludes = $item['price_includes_tax'] ?? null;
        $globalIncludes = $taxInfo['prices_include_tax'] ?? null;
        $taxDetected  = $taxInfo['tax_detected'] ?? false;

        // Guardar siempre el precio original para trazabilidad
        $item['unit_price_original'] = $rawPrice;

        // Caso 1: El proveedor desglosa IVA por producto
        if ($itemTax !== null && $itemTax > 0) {
            return $this->applySupplierPerItemTax($item, $rawPrice, (float) $itemTax, $itemIncludes);
        }

        // Caso 2: Información global disponible
        if ($taxDetected && $globalIncludes !== null) {
            return $this->applyGlobalTaxContext($item, $rawPrice, $globalIncludes);
        }

        // Caso 3: Información por ítem pero sin monto específico
        if ($itemIncludes !== null) {
            return $this->applyGlobalTaxContext($item, $rawPrice, $itemIncludes);
        }

        // Caso 4: No se detectó IVA → dejar pendiente
        $item['tax_amount'] = null;
        $item['tax_source'] = null;

        return $item;
    }

    /**
     * Caso 1: El proveedor da el desglose de IVA por producto.
     * Se priorizan sus montos exactos para evitar variaciones de centavos.
     */
    private function applySupplierPerItemTax(
        array $item,
        float $rawPrice,
        float $supplierTaxAmount,
        ?bool $itemIncludesTax,
    ): array {
        if ($itemIncludesTax === true) {
            // El precio incluye IVA → el precio neto es: precio - IVA del proveedor
            $item['unit_price'] = round($rawPrice - $supplierTaxAmount, 2);
        } else {
            // El precio NO incluye IVA → ya es el neto
            $item['unit_price'] = round($rawPrice, 2);
        }

        $item['tax_amount'] = round($supplierTaxAmount, 2);
        $item['tax_source'] = 'supplier_per_item';

        return $item;
    }

    /**
     * Caso 2: Se sabe si incluye IVA pero no hay desglose por producto.
     * Se calcula el IVA a partir de la tasa fija.
     */
    private function applyGlobalTaxContext(
        array $item,
        float $rawPrice,
        bool $includesTax,
    ): array {
        if ($includesTax) {
            $result = $this->calculateTaxFromIncludedPrice($rawPrice);
            $item['unit_price'] = $result['unit_price'];
            $item['tax_amount'] = $result['tax_amount'];
        } else {
            // Precio ya es sin IVA → calcular IVA
            $item['unit_price'] = round($rawPrice, 2);
            $item['tax_amount'] = round($rawPrice * self::TAX_RATE, 2);
        }

        $item['tax_source'] = 'supplier_global';

        return $item;
    }

    /**
     * Calcula precio neto e IVA a partir de un precio que incluye IVA.
     * Fórmula: neto = bruto / (1 + tasa), IVA = bruto - neto
     *
     * @return array{unit_price: float, tax_amount: float, tax_source: string}
     */
    private function calculateTaxFromIncludedPrice(float $priceWithTax): array
    {
        $netPrice  = round($priceWithTax / (1 + self::TAX_RATE), 2);
        $taxAmount = round($priceWithTax - $netPrice, 2);

        return [
            'unit_price' => $netPrice,
            'tax_amount' => $taxAmount,
            'tax_source' => 'user_confirmed',
        ];
    }
}
