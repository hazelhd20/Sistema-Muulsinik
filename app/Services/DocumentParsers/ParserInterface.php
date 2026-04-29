<?php

namespace App\Services\DocumentParsers;

/**
 * Contrato común para todos los parsers de documentos.
 * Cada implementación devuelve un array estructurado uniforme.
 */
interface ParserInterface
{
    /**
     * Extrae texto crudo y lo estructura en un array normalizado.
     *
     * @return array{
     *   supplier: ?string,
     *   store: ?string,
     *   tax_info: ?array{
     *     tax_rate: ?float,
     *     prices_include_tax: ?bool,
     *     tax_detected: bool,
     *     subtotal: ?float,
     *     tax_total: ?float,
     *     grand_total: ?float,
     *   },
     *   items: array<int, array{
     *     name: string,
     *     quantity: ?float,
     *     unit: ?string,
     *     unit_price: ?float,
     *     tax_amount: ?float,
     *     price_includes_tax: ?bool,
     *   }>,
     *   raw_text: string,
     * }
     */
    public function parse(string $filePath): array;
}
