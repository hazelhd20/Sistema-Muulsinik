<?php

namespace App\Services\AI;

use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Log;

/**
 * Wrapper aislado para Google Gemini AI.
 *
 * Principio de Agnosticismo de Dependencias:
 * Si en el futuro se cambia de Gemini a OpenAI u otro proveedor,
 * solo se modifica esta clase, no el resto de la aplicación.
 *
 * Responsabilidades:
 * - Estructurar texto crudo de cotizaciones en JSON limpio de ítems.
 * - Limpiar nombres de productos (quitar códigos, viñetas, etc.).
 * - Seleccionar el mejor match de homologación entre candidatos.
 */
class GeminiStructurerService
{
    /**
     * ¿Está disponible la integración con Gemini?
     * Retorna false si no hay API key configurada.
     */
    public function isAvailable(): bool
    {
        return !empty(config('gemini.api_key'));
    }

    /**
     * Obtiene el modelo de Gemini configurado.
     * Configurable vía GEMINI_MODEL en .env.
     */
    private function getModel(): string
    {
        return config('gemini.model', env('GEMINI_MODEL', 'gemini-flash-latest'));
    }

    /**
     * Estructura texto crudo de una cotización en ítems limpios.
     *
     * Recibe el texto tal cual sale del OCR o PDF parser y devuelve
     * un array estructurado con los productos identificados, incluyendo
     * información fiscal (IVA) cuando está disponible.
     *
     * @return array{
     *   supplier: ?string,
     *   store: ?string,
     *   tax_info: ?array{tax_rate: ?float, prices_include_tax: ?bool, tax_detected: bool,
     *     subtotal: ?float, tax_total: ?float, grand_total: ?float},
     *   items: array<int, array{
     *     name: string, quantity: ?float, unit: ?string,
     *     unit_price: ?float, tax_amount: ?float, price_includes_tax: ?bool
     *   }>
     * }|null
     *         null si la IA no está disponible o falla.
     */
    public function structureRawText(string $rawText): ?array
    {
        if (!$this->isAvailable() || empty(trim($rawText))) {
            return null;
        }

        $prompt = $this->buildExtractionPrompt($rawText);

        try {
            $result = Gemini::generativeModel(model: $this->getModel())
                ->generateContent($prompt);

            $responseText = $result->text();

            return $this->parseJsonResponse($responseText);
        } catch (\Throwable $e) {
            Log::warning('Gemini AI: Error al estructurar texto de cotización.', [
                'error'    => $e->getMessage(),
                'fallback' => 'Se usará el parser de regex como fallback.',
            ]);

            return null;
        }
    }

    /**
     * Limpia una lista de nombres de productos eliminando códigos internos,
     * viñetas, numeración y caracteres irrelevantes.
     *
     * @param  array<int, string> $names
     * @return array<int, string>|null Lista de nombres limpiados, o null si falla.
     */
    public function cleanProductNames(array $names): ?array
    {
        if (!$this->isAvailable() || empty($names)) {
            return null;
        }

        $namesList = implode("\n", array_map(
            fn (int $i, string $n) => ($i + 1) . ". {$n}",
            array_keys($names),
            array_values($names),
        ));

        $prompt = <<<PROMPT
        Eres un experto en materiales de construcción y cotizaciones en México.

        Te daré una lista de nombres de productos tal como aparecen en una cotización de un proveedor.
        Muchos incluyen códigos internos del proveedor, viñetas, números de partida, claves SKU o caracteres basura al inicio.

        Tu tarea es devolver SOLO el nombre limpio y legible de cada producto, conservando:
        - El nombre real del material/producto
        - Especificaciones relevantes (medidas, calibre, marca, modelo, etc.)

        Elimina:
        - Códigos de producto del proveedor (ej: "M-20384", "SKU-123", "3020-A")
        - Numeración de lista (ej: "1.", "2.", "- ", "* ")
        - Caracteres basura o ruido del OCR

        IMPORTANTE: Devuelve SOLO un JSON array de strings, sin texto adicional. Ejemplo:
        ["Cemento Portland Gris CPC 30R 50kg", "Varilla corrugada 3/8 12m"]

        Lista de nombres a limpiar:
        {$namesList}
        PROMPT;

        try {
            $result = Gemini::generativeModel(model: $this->getModel())
                ->generateContent($prompt);

            $responseText = $result->text();
            $cleaned = $this->extractJsonArray($responseText);

            if ($cleaned === null || count($cleaned) !== count($names)) {
                return null;
            }

            return $cleaned;
        } catch (\Throwable $e) {
            Log::warning('Gemini AI: Error al limpiar nombres de productos.', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Selecciona el mejor candidato de homologación usando IA.
     *
     * @param  string $extractedName  Nombre tal como se extrajo de la cotización
     * @param  array<int, array{id: int, canonical_name: string, similarity: int}> $candidates
     * @return int|null  El ID del producto mejor match, o null si la IA no puede decidir.
     */
    public function rankHomologationCandidates(string $extractedName, array $candidates): ?int
    {
        if (!$this->isAvailable() || empty($candidates)) {
            return null;
        }

        $candidatesList = implode("\n", array_map(
            fn (array $c) => "ID:{$c['id']} → \"{$c['canonical_name']}\"",
            $candidates,
        ));

        $prompt = <<<PROMPT
        Eres un experto en materiales de construcción en México.

        Un proveedor cotizó el siguiente producto:
        "{$extractedName}"

        En nuestro catálogo interno tenemos los siguientes candidatos:
        {$candidatesList}

        ¿Cuál de estos candidatos es el MISMO producto que cotizó el proveedor?
        Considera sinónimos, abreviaturas comunes del sector construcción (ej: "cal" = "calibre", "pza" = "pieza", "cem" = "cemento"), marcas, y especificaciones técnicas.

        Si estás seguro del match, responde SOLO con el ID numérico (ej: 42).
        Si ninguno coincide con certeza, responde SOLO con: null

        Respuesta (solo el ID o null):
        PROMPT;

        try {
            $result = Gemini::generativeModel(model: $this->getModel())
                ->generateContent($prompt);

            $responseText = trim($result->text());

            if ($responseText === 'null' || $responseText === 'NULL') {
                return null;
            }

            $id = filter_var($responseText, FILTER_VALIDATE_INT);

            if ($id === false) {
                return null;
            }

            // Verificar que el ID realmente está en los candidatos
            $validIds = array_column($candidates, 'id');
            return in_array($id, $validIds) ? $id : null;
        } catch (\Throwable $e) {
            Log::warning('Gemini AI: Error al rankear candidatos de homologación.', [
                'error'          => $e->getMessage(),
                'extracted_name' => $extractedName,
            ]);

            return null;
        }
    }

    /**
     * Construye el prompt para extracción estructurada de ítems.
     *
     * El prompt instruye a Gemini para:
     * 1. Extraer productos con precios TAL COMO aparecen (sin modificar).
     * 2. Detectar información fiscal (IVA desglosado, incluido, etc.).
     * 3. Indicar por producto si su precio incluye IVA.
     */
    private function buildExtractionPrompt(string $rawText): string
    {
        return <<<PROMPT
        Eres un experto en procesar cotizaciones de materiales de construcción en México.

        A continuación recibirás texto crudo extraído de una cotización (puede venir de OCR, PDF o una hoja de cálculo).
        El texto puede tener errores de OCR, estar desordenado o incluir información irrelevante (encabezados, pies de página, datos fiscales).

        Tu tarea es extraer la información estructurada del documento. Devuelve SOLO un JSON válido con el siguiente formato, sin texto adicional ni markdown:

        {
            "supplier": "Nombre del proveedor o empresa (o null si no se identifica)",
            "store": "Nombre de la sucursal/tienda (o null si no se identifica)",
            "tax_info": {
                "tax_rate": 0.16,
                "prices_include_tax": true,
                "tax_detected": true,
                "subtotal": 5000.00,
                "tax_total": 800.00,
                "grand_total": 5800.00
            },
            "items": [
                {
                    "name": "Nombre limpio del producto (sin códigos del proveedor, sin viñetas)",
                    "quantity": 10.0,
                    "unit": "pza",
                    "unit_price": 150.50,
                    "tax_amount": 24.08,
                    "price_includes_tax": false
                }
            ]
        }

        Reglas importantes:
        - En "name": incluye SOLO el nombre real del producto. Elimina códigos internos (ej: "M-20384"), viñetas ("1.", "- "), SKUs, y caracteres basura.
        - En "unit": normaliza a: pza, kg, m, m2, m3, lt, bulto, rollo, pieza, metro, litro, caja, paquete.
        - En "unit_price": pon el precio unitario TAL COMO aparece en la cotización. NO lo modifiques, NO le quites ni agregues IVA. Si no se identifica, pon 0.
        - En "tax_amount": si la cotización desglosa el IVA por producto (por línea), pon ese valor exacto. Si NO lo desglosa por producto, pon null.
        - En "price_includes_tax": true si el precio unitario YA tiene IVA incluido, false si el IVA se suma aparte, null si no puedes determinarlo.
        - En "quantity": si no se identifica, pon 1.

        Reglas sobre tax_info:
        - "tax_rate": la tasa de IVA detectada (normalmente 0.16 en México). null si no se detecta.
        - "prices_include_tax": true si los precios de los productos YA incluyen IVA, false si el IVA se suma aparte, null si no se puede determinar.
        - "tax_detected": true si encontraste CUALQUIER referencia a IVA, impuestos, o desglose fiscal en el documento. false si no hay ninguna mención.
        - "subtotal", "tax_total", "grand_total": extráelos si aparecen en la cotización. null si no aparecen.

        Indicadores comunes de IVA: "IVA incluido", "IVA incl.", "más IVA", "+ IVA", "+ 16%", "Subtotal", "Total", "IVA:", "impuesto", "16%", "c/IVA", "s/IVA".

        - Ignora líneas que no sean productos (encabezados, pies de página, datos bancarios, etc.).
        - Si el texto es ilegible o no se identifica ningún producto, devuelve items como array vacío.

        Texto crudo de la cotización:
        ---
        {$rawText}
        ---
        PROMPT;
    }

    /**
     * Parsea una respuesta JSON de Gemini, tolerando markdown code fences.
     *
     * @return array{supplier: ?string, store: ?string, tax_info: ?array, items: array}|null
     */
    private function parseJsonResponse(string $responseText): ?array
    {
        // Gemini a veces envuelve la respuesta en ```json ... ```
        $cleaned = preg_replace('/^```(?:json)?\s*/i', '', trim($responseText));
        $cleaned = preg_replace('/\s*```$/i', '', $cleaned);

        $data = json_decode($cleaned, true);

        if (!is_array($data) || !isset($data['items'])) {
            Log::warning('Gemini AI: Respuesta JSON inválida o sin items.', [
                'response' => mb_substr($responseText, 0, 500),
            ]);
            return null;
        }

        // Normalizar la estructura de ítems
        $items = [];
        foreach ($data['items'] as $item) {
            if (empty($item['name'])) {
                continue;
            }
            $items[] = [
                'name'               => trim($item['name']),
                'quantity'           => isset($item['quantity']) ? (float) $item['quantity'] : null,
                'unit'               => isset($item['unit']) ? strtolower(trim($item['unit'])) : null,
                'unit_price'         => isset($item['unit_price']) ? (float) $item['unit_price'] : null,
                'tax_amount'         => isset($item['tax_amount']) ? (float) $item['tax_amount'] : null,
                'price_includes_tax' => $item['price_includes_tax'] ?? null,
            ];
        }

        // Normalizar tax_info
        $taxInfo = $data['tax_info'] ?? null;
        if (is_array($taxInfo)) {
            $taxInfo = [
                'tax_rate'             => $taxInfo['tax_rate'] ?? null,
                'prices_include_tax'   => $taxInfo['prices_include_tax'] ?? null,
                'tax_detected'         => $taxInfo['tax_detected'] ?? false,
                'subtotal'             => isset($taxInfo['subtotal']) ? (float) $taxInfo['subtotal'] : null,
                'tax_total'            => isset($taxInfo['tax_total']) ? (float) $taxInfo['tax_total'] : null,
                'grand_total'          => isset($taxInfo['grand_total']) ? (float) $taxInfo['grand_total'] : null,
            ];
        }

        return [
            'supplier' => $data['supplier'] ?? null,
            'store'    => $data['store'] ?? null,
            'tax_info' => $taxInfo,
            'items'    => $items,
        ];
    }

    /**
     * Extrae un JSON array de la respuesta, tolerando markdown code fences.
     *
     * @return array<int, string>|null
     */
    private function extractJsonArray(string $responseText): ?array
    {
        $cleaned = preg_replace('/^```(?:json)?\s*/i', '', trim($responseText));
        $cleaned = preg_replace('/\s*```$/i', '', $cleaned);

        $data = json_decode($cleaned, true);

        if (!is_array($data)) {
            return null;
        }

        return array_values(array_map('trim', $data));
    }
}
