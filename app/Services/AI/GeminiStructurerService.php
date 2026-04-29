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
 * Responsabilidad única:
 * - Estructurar texto crudo de cotizaciones en JSON limpio de ítems.
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
                    "price_includes_tax": false,
                    "line_subtotal": 1505.00,
                    "line_total": 1745.80
                }
            ]
        }

        REGLAS CRÍTICAS PARA DISTINGUIR COLUMNAS (MUY IMPORTANTE):
        Muchas cotizaciones mexicanas usan nombres de columnas que pueden confundirse entre sí. Debes distinguir correctamente:
        - "Precio" o "P.U." o "Precio Unitario" o "Costo" → es el PRECIO UNITARIO de UNA unidad. Va en "unit_price".
        - "Importe" o "Subtotal" o "Monto" → es el SUBTOTAL DE LÍNEA (cantidad × precio unitario, SIN IVA). Va en "line_subtotal".
        - "Impuesto" o "IVA" o "I.V.A." → es el MONTO DE IVA de esa línea. Va en "tax_amount".
        - "Importe Neto" o "Total" o "Neto" o "Total con IVA" → es el TOTAL DE LÍNEA (subtotal + IVA). Va en "line_total".

        Para identificar correctamente cuál es cuál:
        1. El PRECIO UNITARIO es siempre el valor más PEQUEÑO por producto. Si multiplicas cantidad × precio unitario deberías obtener el subtotal.
        2. El SUBTOTAL DE LÍNEA (importe) es = cantidad × precio unitario.
        3. El IMPUESTO es un monto menor, normalmente ~16% del subtotal.
        4. El TOTAL DE LÍNEA (importe neto) es = subtotal + impuesto.
        5. Si solo hay 2 columnas numéricas y una es mucho mayor que la otra, la grande probablemente es subtotal/total y la pequeña es precio unitario.

        Reglas generales:
        - En "name": incluye SOLO el nombre real del producto. Elimina códigos internos (ej: "M-20384"), viñetas ("1.", "- "), SKUs, y caracteres basura.
        - En "unit": normaliza a: pza, kg, m, m2, m3, lt, bulto, rollo, pieza, metro, litro, caja, paquete.
        - En "unit_price": pon el precio unitario TAL COMO aparece en la cotización. NO lo modifiques, NO le quites ni agregues IVA. Si no se identifica, intenta calcularlo como subtotal ÷ cantidad. Si tampoco puedes, pon 0.
        - En "tax_amount": si la cotización desglosa el IVA por producto (por línea), pon ese valor exacto. Si NO lo desglosa por producto, pon null.
        - En "price_includes_tax": true si el precio unitario YA tiene IVA incluido, false si el IVA se suma aparte, null si no puedes determinarlo.
        - En "quantity": si no se identifica, pon 1.
        - En "line_subtotal": si aparece el subtotal/importe de la línea (cantidad × precio sin IVA), pon ese valor. Si no, pon null.
        - En "line_total": si aparece el total/importe neto de la línea (con IVA), pon ese valor. Si no, pon null.

        Reglas sobre tax_info:
        - "tax_rate": la tasa de IVA detectada (normalmente 0.16 en México). null si no se detecta.
        - "prices_include_tax": true si los precios de los productos YA incluyen IVA, false si el IVA se suma aparte, null si no se puede determinar.
        - "tax_detected": true si encontraste CUALQUIER referencia a IVA, impuestos, o desglose fiscal en el documento. false si no hay ninguna mención.
        - "subtotal", "tax_total", "grand_total": extráelos si aparecen en la cotización. null si no aparecen.

        Indicadores comunes de IVA: "IVA incluido", "IVA incl.", "más IVA", "+ IVA", "+ 16%", "Subtotal", "Total", "IVA:", "impuesto", "16%", "c/IVA", "s/IVA", "I.V.A.", "I.V.A".

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

            $unitPrice    = isset($item['unit_price']) ? (float) $item['unit_price'] : null;
            $quantity     = isset($item['quantity']) ? (float) $item['quantity'] : null;
            $lineSubtotal = isset($item['line_subtotal']) ? (float) $item['line_subtotal'] : null;
            $lineTotal    = isset($item['line_total']) ? (float) $item['line_total'] : null;
            $taxAmount    = isset($item['tax_amount']) ? (float) $item['tax_amount'] : null;

            // Validación cruzada: detectar si unit_price es realmente un subtotal
            $unitPrice = $this->crossValidateUnitPrice(
                $unitPrice, $quantity, $lineSubtotal, $lineTotal, $taxAmount
            );

            $items[] = [
                'name'               => trim($item['name']),
                'quantity'           => $quantity,
                'unit'               => isset($item['unit']) ? strtolower(trim($item['unit'])) : null,
                'unit_price'         => $unitPrice,
                'tax_amount'         => $taxAmount,
                'price_includes_tax' => $item['price_includes_tax'] ?? null,
                'line_subtotal'      => $lineSubtotal,
                'line_total'         => $lineTotal,
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
     * Validación cruzada del precio unitario extraído por la IA.
     *
     * Detecta y corrige el caso donde la IA confunde el subtotal de línea
     * (importe) con el precio unitario. Esto ocurre cuando la cotización
     * usa "precio" para referirse al subtotal de línea.
     *
     * Heurística: Si unit_price × quantity ≈ line_subtotal, está bien.
     * Si unit_price ≈ line_subtotal y quantity > 1, el unit_price es realmente el subtotal.
     */
    private function crossValidateUnitPrice(
        ?float $unitPrice,
        ?float $quantity,
        ?float $lineSubtotal,
        ?float $lineTotal,
        ?float $taxAmount,
    ): ?float {
        if ($unitPrice === null || $unitPrice <= 0) {
            // Sin precio unitario: intentar inferir desde subtotal/total
            return $this->inferUnitPriceFromLineValues($quantity, $lineSubtotal, $lineTotal, $taxAmount);
        }

        $qty = ($quantity !== null && $quantity > 1) ? $quantity : null;

        // Si no hay quantity > 1 ni subtotal, no podemos validar
        if ($qty === null || $lineSubtotal === null) {
            return $unitPrice;
        }

        $expectedSubtotal = $unitPrice * $qty;
        $tolerance = 0.02; // Tolerancia para errores de redondeo

        // Caso normal: unit_price × qty ≈ subtotal → correcto
        if (abs($expectedSubtotal - $lineSubtotal) / max($lineSubtotal, 1) < $tolerance) {
            return $unitPrice;
        }

        // Caso sospechoso: unit_price ≈ subtotal → la IA confundió las columnas
        if (abs($unitPrice - $lineSubtotal) / max($lineSubtotal, 1) < $tolerance) {
            $corrected = round($lineSubtotal / $qty, 2);
            Log::info('Gemini AI: Corrección automática de unit_price (era subtotal de línea).', [
                'original_unit_price' => $unitPrice,
                'corrected_unit_price' => $corrected,
                'quantity' => $qty,
                'line_subtotal' => $lineSubtotal,
            ]);
            return $corrected;
        }

        return $unitPrice;
    }

    /**
     * Infiere el precio unitario desde los totales de línea cuando
     * no se extrajo directamente.
     */
    private function inferUnitPriceFromLineValues(
        ?float $quantity,
        ?float $lineSubtotal,
        ?float $lineTotal,
        ?float $taxAmount,
    ): ?float {
        $qty = ($quantity !== null && $quantity > 0) ? $quantity : 1.0;

        if ($lineSubtotal !== null && $lineSubtotal > 0) {
            return round($lineSubtotal / $qty, 2);
        }

        if ($lineTotal !== null && $lineTotal > 0) {
            $base = ($taxAmount !== null && $taxAmount > 0)
                ? $lineTotal - $taxAmount
                : $lineTotal;

            return round($base / $qty, 2);
        }

        return null;
    }

}
