<?php

$dir = __DIR__ . '/app/Services/Normalizers';

// SupplierNormalizerService
$file = $dir . '/SupplierNormalizerService.php';
$content = file_get_contents($file);
$content = str_replace('$this->normalizeText', '$this->text->normalizeText', $content);
$content = str_replace('$this->extractKeyTokens', '$this->fuzzy->extractKeyTokens', $content);
$content = str_replace('$this->bestFuzzyMatch', '$this->fuzzy->bestFuzzyMatch', $content);
file_put_contents($file, $content);

// ProductNormalizerService
$file = $dir . '/ProductNormalizerService.php';
$content = file_get_contents($file);
$content = str_replace('$this->normalizeText', '$this->text->normalizeText', $content);
$content = str_replace('$this->extractKeyTokens', '$this->fuzzy->extractKeyTokens', $content);
$content = str_replace('$this->bestFuzzyMatch', '$this->fuzzy->bestFuzzyMatch', $content);
$content = str_replace('$this->normalizeUnit', '$this->unit->normalizeUnit', $content);
$content = str_replace('$this->normalizeTitleCase', '$this->text->normalizeTitleCase', $content);
file_put_contents($file, $content);

// UnitNormalizerService
$file = $dir . '/UnitNormalizerService.php';
$content = file_get_contents($file);
// findMatchingMeasure doesn't use anything outside except maybe $this->normalizeUnit and $this->getUnitName which are in the same class.
// BUT calculateSimilarity is used in FuzzyMatcherService, check if UnitNormalizer uses fuzzy: No, it just uses direct DB matches.
file_put_contents($file, $content);

// FuzzyMatcherService
$file = $dir . '/FuzzyMatcherService.php';
$content = file_get_contents($file);
// Fuzzy doesn't call other normalizers.
file_put_contents($file, $content);

echo "Fixed dependencies in normalizers.\n";
