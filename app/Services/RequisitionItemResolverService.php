<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Measure;
use App\Models\Product;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\Supplier;
use App\Models\Vendor;
use Illuminate\Support\Collection;

/**
 * Servicio para resolver y crear items de requisición
 * Maneja la lógica de normalización, deduplicación y creación de entidades relacionadas
 */
class RequisitionItemResolverService
{
    public function __construct(
        private DataNormalizerService $normalizer
    ) {}

    /**
     * Resolver o crear un proveedor por nombre
     */
    public function resolveSupplier(?string $supplierId, ?string $supplierName): ?int
    {
        if (!empty($supplierId)) {
            return (int) $supplierId;
        }

        if (empty($supplierName)) {
            return null;
        }

        $normalizedName = $this->normalizer->normalizeSupplierName($supplierName);
        $existingSupplier = Supplier::where('normalized_name', $normalizedName)->first();

        if ($existingSupplier) {
            return $existingSupplier->id;
        }

        $newSupplier = Supplier::create(['trade_name' => $supplierName]);
        return $newSupplier->id;
    }

    /**
     * Resolver o crear un vendedor
     */
    public function resolveVendor(?string $vendorName, ?int $supplierId): ?int
    {
        if (empty($vendorName) || empty($supplierId)) {
            return null;
        }

        $vendorMatch = $this->normalizer->findMatchingVendor($vendorName, $supplierId);

        if ($vendorMatch !== null) {
            return $vendorMatch['match']->id;
        }

        $newVendor = Vendor::create([
            'supplier_id' => $supplierId,
            'name' => $vendorName,
        ]);

        return $newVendor->id;
    }

    /**
     * Precargar medidas existentes para lookups O(1)
     *
     * @param array $items Array de items con campo 'unit'
     */
    public function preloadMeasures(array $items): Collection
    {
        $unitKeys = [];

        foreach ($items as $item) {
            if (!empty($item['unit'])) {
                $normalizedUnit = $this->normalizer->normalizeUnit($item['unit']);
                $unitKeys[] = $normalizedUnit;
                $unitKeys[] = mb_strtolower($item['unit']);
            }
        }

        if (empty($unitKeys)) {
            return collect();
        }

        return Measure::whereIn('abbreviation', array_unique($unitKeys))
            ->get()
            ->keyBy(fn($m) => $m->abbreviation);
    }

    /**
     * Precargar productos existentes para lookups O(1)
     *
     * @param array $items Array de items con campo 'name'
     */
    public function preloadProducts(array $items): Collection
    {
        $normalizedNames = [];

        foreach ($items as $index => $item) {
            if (!empty($item['name'])) {
                $normalizedNames[$index] = $this->normalizer->normalizeText($item['name']);
            }
        }

        if (empty($normalizedNames)) {
            return collect();
        }

        return Product::whereIn('normalized_name', array_unique($normalizedNames))
            ->get()
            ->keyBy('normalized_name');
    }

    /**
     * Resolver una medida (buscar o crear)
     */
    public function resolveMeasure(
        ?string $unit,
        Collection $existingMeasures,
        ?string $aiUnitName = null
    ): ?int {
        if (empty($unit)) {
            return null;
        }

        $normalizedUnit = $this->normalizer->normalizeUnit($unit);
        $measure = $existingMeasures->get($normalizedUnit);

        if (!$measure) {
            $measure = Measure::create([
                'name' => $this->normalizer->getUnitName($normalizedUnit, $aiUnitName),
                'abbreviation' => $normalizedUnit,
            ]);
            $existingMeasures->put($normalizedUnit, $measure);
        }

        return $measure->id;
    }

    /**
     * Resolver una categoría (buscar o crear)
     */
    public function resolveCategory(?string $categoryId, ?string $categoryName): ?int
    {
        if (!empty($categoryId)) {
            return (int) $categoryId;
        }

        if (empty($categoryName)) {
            return null;
        }

        $matchedCategory = $this->normalizer->findMatchingCategory($categoryName);

        if ($matchedCategory) {
            return $matchedCategory->id;
        }

        $newCategory = Category::create([
            'name' => mb_convert_case($categoryName, MB_CASE_TITLE, "UTF-8")
        ]);

        return $newCategory->id;
    }

    /**
     * Resolver un producto (buscar o crear)
     */
    public function resolveProduct(
        array $item,
        ?int $measureId,
        Collection $existingProducts,
        ?int $categoryId
    ): ?int {
        if (empty($item['name'])) {
            return $item['product_id'] ?? null;
        }

        $normalizedName = $this->normalizer->normalizeText($item['name']);
        $product = $existingProducts->get($normalizedName);

        if ($product) {
            return $product->id;
        }

        // Crear producto nuevo
        $resolvedCategoryId = $categoryId ?? $this->resolveCategory(
            $item['category_id'] ?? null,
            $item['category_name'] ?? null
        );

        $newProduct = Product::create([
            'canonical_name' => $item['name'],
            'measure_id' => $measureId,
            'category_id' => $resolvedCategoryId,
        ]);

        $existingProducts->put($normalizedName, $newProduct);

        return $newProduct->id;
    }

    /**
     * Procesar todos los items de una requisición
     *
     * @return array Array de datos listos para insertar en RequisitionItem::insert()
     */
    public function processItems(
        array $items,
        int $requisitionId,
        ?int $supplierId
    ): array {
        // Precargar medidas y productos para lookups O(1)
        $existingMeasures = $this->preloadMeasures($items);
        $existingProducts = $this->preloadProducts($items);

        $requisitionItemsData = [];

        foreach ($items as $index => $item) {
            // Resolver medida
            $measureId = $this->resolveMeasure(
                $item['unit'] ?? null,
                $existingMeasures,
                $item['_match']['measure']['unit_name'] ?? null
            );

            // Resolver categoría para el producto
            $categoryId = $this->resolveCategory(
                $item['category_id'] ?? null,
                $item['category_name'] ?? null
            );

            // Resolver producto
            $productId = $this->resolveProduct($item, $measureId, $existingProducts, $categoryId);

            $requisitionItemsData[] = [
                'requisition_id' => $requisitionId,
                'product_id' => $productId,
                'measure_id' => $measureId,
                'quantity' => $item['quantity'] ?? 0,
                'unit_price' => $item['unit_price'] ?? 0,
                'unit_price_original' => $item['unit_price_original'] ?? ($item['unit_price'] ?? 0),
                'tax_amount' => $item['tax_amount'] ?? null,
                'tax_source' => $item['tax_source'] ?? null,
                'line_subtotal' => $item['line_subtotal'] ?? null,
                'line_total' => $item['line_total'] ?? null,
                'supplier_id' => $supplierId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $requisitionItemsData;
    }

    /**
     * Crear requisición con todos sus items
     */
    public function createRequisitionWithItems(
        array $requisitionData,
        array $items,
        ?string $supplierName = null,
        ?string $supplierId = null,
        ?string $vendorName = null
    ): Requisition {
        // Resolver proveedor
        $finalSupplierId = $this->resolveSupplier($supplierId, $supplierName);

        // Resolver vendedor
        $finalVendorId = $this->resolveVendor($vendorName, $finalSupplierId);

        // Crear requisición
        $requisition = Requisition::create([
            'project_id' => $requisitionData['project_id'],
            'vendor_id' => $finalVendorId,
            'annotations' => $requisitionData['annotations'] ?? null,
            'status' => $requisitionData['status'] ?? 'borrador',
            'created_by' => $requisitionData['created_by'],
            'date' => $requisitionData['date'],
        ]);

        // Procesar y guardar items
        if (!empty($items)) {
            $itemsData = $this->processItems($items, $requisition->id, $finalSupplierId);
            RequisitionItem::insert($itemsData);
        }

        return $requisition;
    }
}
