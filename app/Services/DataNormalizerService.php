<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Measure;
use App\Models\Product;
use App\Services\Normalizers\FuzzyMatcherService;
use App\Services\Normalizers\ProductNormalizerService;
use App\Services\Normalizers\SupplierNormalizerService;
use App\Services\Normalizers\TextNormalizerService;
use App\Services\Normalizers\UnitNormalizerService;

/**
 * Facade/Aggregator for Data Normalization.
 *
 * La lógica pesada ha sido extraída a 5 servicios especializados (SRP):
 * - TextNormalizerService
 * - FuzzyMatcherService
 * - UnitNormalizerService
 * - SupplierNormalizerService
 * - ProductNormalizerService
 */
class DataNormalizerService
{
    public function __construct(
        private TextNormalizerService $textNormalizer,
        private FuzzyMatcherService $fuzzyMatcher,
        private UnitNormalizerService $unitNormalizer,
        private SupplierNormalizerService $supplierNormalizer,
        private ProductNormalizerService $productNormalizer
    ) {}

    public function normalizeUnit(string $rawUnit): string
    {
        return $this->unitNormalizer->normalizeUnit($rawUnit);
    }

    public function getUnitName(string $canonicalUnit, ?string $aiHint = null): string
    {
        return $this->unitNormalizer->getUnitName($canonicalUnit, $aiHint);
    }

    public function normalizeText(string $text): string
    {
        return $this->textNormalizer->normalizeText($text);
    }

    public function normalizeSupplierName(string $rawName): string
    {
        return $this->supplierNormalizer->normalizeSupplierName($rawName);
    }

    public function normalizeVendorName(string $rawName): string
    {
        return $this->supplierNormalizer->normalizeVendorName($rawName);
    }

    public function findMatchingSupplier(string $rawName): ?array
    {
        return $this->supplierNormalizer->findMatchingSupplier($rawName);
    }

    public function findMatchingCategory(string $rawName): ?Category
    {
        return $this->productNormalizer->findMatchingCategory($rawName);
    }

    public function findMatchingVendor(string $rawName, ?int $supplierId = null): ?array
    {
        return $this->supplierNormalizer->findMatchingVendor($rawName, $supplierId);
    }

    public function findMatchingMeasure(string $rawUnit): ?array
    {
        return $this->unitNormalizer->findMatchingMeasure($rawUnit);
    }

    public function findMatchingProduct(string $rawName): ?array
    {
        return $this->productNormalizer->findMatchingProduct($rawName);
    }

    public function normalizeProductName(string $rawName): string
    {
        return $this->productNormalizer->normalizeProductName($rawName);
    }

    public function normalizeItems(array $items): array
    {
        return $this->productNormalizer->normalizeItems($items);
    }

    public function normalizeTitleCase(string $text): string
    {
        return $this->textNormalizer->normalizeTitleCase($text);
    }
}
