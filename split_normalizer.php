<?php

$source = __DIR__ . '/app/Services/DataNormalizerService.php';
$content = file_get_contents($source);

// Helper to extract a method or constant
function extractBlock($content, $name, $isConst = false) {
    if ($isConst) {
        preg_match('/private const ' . $name . ' = \[(.*?)\];/s', $content, $matches);
        return $matches[0] ?? '';
    }
    
    // For methods, match public/private function name( until the matching closing brace
    $pattern = '/(?:public|private) function ' . $name . '\s*\(.*?\)\s*(?::\s*[?a-zA-Z\\\]+)?\s*\{/s';
    if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
        $start = $matches[0][1];
        $braceCount = 0;
        $i = $start;
        $inString = false;
        $stringChar = '';
        while ($i < strlen($content)) {
            $char = $content[$i];
            
            if ($inString) {
                if ($char === $stringChar && $content[$i-1] !== '\\') {
                    $inString = false;
                }
            } else {
                if ($char === "'" || $char === '"') {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($char === '{') {
                    $braceCount++;
                } elseif ($char === '}') {
                    $braceCount--;
                    if ($braceCount === 0) {
                        return substr($content, $start, $i - $start + 1);
                    }
                }
            }
            $i++;
        }
    }
    return '';
}

@mkdir(__DIR__ . '/app/Services/Normalizers', 0755, true);

// 1. TextNormalizerService
$textMethods = [
    extractBlock($content, 'normalizeText'),
    extractBlock($content, 'normalizeTitleCase')
];
$textContent = "<?php\nnamespace App\Services\Normalizers;\n\nclass TextNormalizerService\n{\n" . implode("\n\n", $textMethods) . "\n}\n";
file_put_contents(__DIR__ . '/app/Services/Normalizers/TextNormalizerService.php', $textContent);

// 2. FuzzyMatcherService
$fuzzyMethods = [
    extractBlock($content, 'extractKeyTokens'),
    extractBlock($content, 'bestFuzzyMatch'),
    extractBlock($content, 'calculateSimilarity')
];
$fuzzyContent = "<?php\nnamespace App\Services\Normalizers;\n\nclass FuzzyMatcherService\n{\n" . implode("\n\n", $fuzzyMethods) . "\n}\n";
file_put_contents(__DIR__ . '/app/Services/Normalizers/FuzzyMatcherService.php', $fuzzyContent);

// 3. UnitNormalizerService
$unitMethods = [
    extractBlock($content, 'UNIT_MAP', true),
    extractBlock($content, 'UNIT_NAMES', true),
    extractBlock($content, 'normalizeUnit'),
    extractBlock($content, 'getUnitName'),
    extractBlock($content, 'findMatchingMeasure')
];
$unitContent = "<?php\nnamespace App\Services\Normalizers;\n\nuse App\Models\Measure;\n\nclass UnitNormalizerService\n{\n" . implode("\n\n", $unitMethods) . "\n}\n";
file_put_contents(__DIR__ . '/app/Services/Normalizers/UnitNormalizerService.php', $unitContent);

// 4. SupplierNormalizerService
$supplierMethods = [
    extractBlock($content, 'LEGAL_SUFFIXES', true),
    extractBlock($content, 'BUSINESS_PREFIXES', true),
    extractBlock($content, 'normalizeSupplierName'),
    extractBlock($content, 'normalizeVendorName'),
    extractBlock($content, 'findMatchingSupplier'),
    extractBlock($content, 'findMatchingVendor')
];
$supplierContent = "<?php\nnamespace App\Services\Normalizers;\n\nuse App\Models\Supplier;\nuse App\Models\Vendor;\n\nclass SupplierNormalizerService\n{\n    public function __construct(private TextNormalizerService \$text, private FuzzyMatcherService \$fuzzy) {}\n\n" . implode("\n\n", $supplierMethods) . "\n}\n";
file_put_contents(__DIR__ . '/app/Services/Normalizers/SupplierNormalizerService.php', $supplierContent);

// 5. ProductNormalizerService
$productMethods = [
    extractBlock($content, 'normalizeProductName'),
    extractBlock($content, 'stripProductCodes'),
    extractBlock($content, 'findMatchingProduct'),
    extractBlock($content, 'findMatchingCategory'),
    extractBlock($content, 'normalizeItems')
];
$productContent = "<?php\nnamespace App\Services\Normalizers;\n\nuse App\Models\Product;\nuse App\Models\Category;\n\nclass ProductNormalizerService\n{\n    public function __construct(private TextNormalizerService \$text, private FuzzyMatcherService \$fuzzy, private UnitNormalizerService \$unit) {}\n\n" . implode("\n\n", $productMethods) . "\n}\n";
file_put_contents(__DIR__ . '/app/Services/Normalizers/ProductNormalizerService.php', $productContent);

echo "Extraction complete.\n";
