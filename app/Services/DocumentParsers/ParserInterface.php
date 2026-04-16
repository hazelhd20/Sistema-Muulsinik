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
     *   items: array<int, array{name: string, quantity: ?float, unit: ?string, unit_price: ?float}>,
     *   raw_text: string,
     * }
     */
    public function parse(string $filePath): array;
}
