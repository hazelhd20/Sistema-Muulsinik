<?php

namespace App\Livewire\Requisitions;

use App\Jobs\ProcessQuotationJob;
use App\Models\Document;
use App\Models\Measure;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Requisition;
use App\Models\Supplier;
use App\Services\DataNormalizerService;
use App\Services\DocumentParsers\DocumentParserFactory;
use App\Services\RequisitionItemResolverService;
use App\Services\TaxNormalizerService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * RF-REQ-01 a RF-REQ-06 — Wizard de 3 pasos para subir cotizaciones.
 *
 * Paso 1: Upload del archivo (drag-and-drop).
 * Paso 2: Procesamiento (barra de progreso + polling).
 * Paso 3: Formulario editable con datos extraídos y normalización.
 */
class QuotationWizard extends Component
{
    use WithFileUploads;

    /* ── Estado del Wizard ───────────────────────────── */
    public int $step = 1;

    /* ── Paso 1: Upload ──────────────────────────────── */
    public $file;
    public ?int $quotationId = null;

    /* ── Paso 2: Processing status ───────────────────── */
    public string $processingStatus = 'pending';
    public ?string $errorMessage = null;

    /* ── Paso 3: Formulario editable ─────────────────── */
    public $projectId = '';
    public string $supplierName = '';
    public $supplierId = '';
    public string $storeName = '';
    public string $vendorName = '';
    public string $annotations = '';
    public string $date = '';
    public array $items = [];
    public string $rawText = '';

    /* ── Alertas tipificadas ──────────────────────────── */
    public array $alerts = [
        'errors'   => [],
        'warnings' => [],
        'info'     => [],
    ];

    /* ── Metadata de resolución (proveedor/vendedor) ──── */
    public array $supplierMatch = [];
    public array $vendorMatch = [];

    /* ── Contexto fiscal (IVA) ───────────────────────── */

    /**
     * null  = Gemini no pudo determinar si los precios incluyen IVA (toggle visible).
     * true  = Los precios incluyen IVA (confirmado por IA o usuario).
     * false = Los precios NO incluyen IVA.
     */
    public ?bool $quotationIncludesTax = null;

    /**
     * true = La IA detectó información de IVA en la cotización.
     * false = No se encontró ninguna referencia a IVA.
     */
    public bool $taxDetectedByAI = false;

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
    }

    /* ═══════════════════════════════════════════════════
     *  PASO 1 — Upload del archivo
     * ═══════════════════════════════════════════════════ */

    public function updatedFile(): void
    {
        if (!$this->file) {
            return;
        }

        $this->validate([
            'file' => 'required|file|max:20480|mimes:pdf,jpg,jpeg,png,xlsx,xls',
        ], [
            'file.max' => 'El archivo no debe superar los 20 MB.',
            'file.mimes' => 'Formatos permitidos: PDF, JPG, PNG, XLSX.',
        ]);
    }

    public function processUpload(): void
    {
        try {
            $this->validate([
                'file' => 'required|file|max:20480|mimes:pdf,jpg,jpeg,png,xlsx,xls',
            ]);

            if (!$this->file || !$this->file->exists()) {
                throw new \Exception('El archivo temporal ya no existe.');
            }

            // Guardar el archivo en storage
            $path = $this->file->store('quotations', 'local');
            $originalName = $this->file->getClientOriginalName();
            $extension = strtolower($this->file->getClientOriginalExtension());
            $mimeType = $this->file->getMimeType();

            // Crear registro de cotización
            $quotation = Quotation::create([
                'project_id' => $this->projectId ?: null,
                'file_path' => $path,
                'file_type' => $mimeType,
                'original_filename' => $originalName,
                'status' => 'pending',
                'uploaded_by' => auth()->id(),
            ]);
        } catch (\Exception $e) {
            // Capturar errores de Flysystem (archivo no encontrado en temp)
            if (str_contains($e->getMessage(), 'Unable to retrieve') || str_contains($e->getMessage(), 'file_size')) {
                $this->addError('file', 'El archivo temporal ha expirado o ya no es válido. Por favor, selecciónalo de nuevo.');
                $this->file = null;
                return;
            }
            throw $e;
        }

        $this->quotationId = $quotation->id;

        // Determinar si el procesamiento es síncrono o asíncrono
        $factory = app(DocumentParserFactory::class);
        $filePath = Storage::disk('local')->path($path);
        $resolution = $factory->resolve($filePath, $mimeType, $extension);

        if ($resolution['async']) {
            // OCR → despachar Job y pasar al paso de espera
            ProcessQuotationJob::dispatch($quotation->id);
            $this->processingStatus = 'processing';
            $this->step = 2;
        } else {
            // Síncrono → procesar inline
            $quotation->update(['status' => 'processing']);

            try {
                $result = $resolution['parser']->parse($filePath);

                $quotation->update([
                    'status' => 'completed',
                    'raw_text' => $result['raw_text'] ?? null,
                    'raw_parsed_data' => $result,
                    'processed_at' => now(),
                ]);

                $this->loadParsedData($quotation->fresh());
                $this->step = 3;

            } catch (\Throwable $e) {
                $quotation->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
                $this->processingStatus = 'failed';
                $this->errorMessage = $e->getMessage();
                $this->step = 2;
            }
        }
    }

    /* ═══════════════════════════════════════════════════
     *  PASO 2 — Polling del estado de procesamiento
     * ═══════════════════════════════════════════════════ */

    public function checkProcessingStatus(): void
    {
        if (!$this->quotationId) {
            return;
        }

        $quotation = Quotation::find($this->quotationId);
        if (!$quotation) {
            return;
        }

        $this->processingStatus = $quotation->status;

        if ($quotation->isCompleted()) {
            $this->loadParsedData($quotation);
            $this->step = 3;
        } elseif ($quotation->isFailed()) {
            $this->errorMessage = $quotation->error_message ?? 'Error desconocido durante el procesamiento.';
        }
    }

    /**
     * Permite reintentar un procesamiento fallido.
     */
    public function retryProcessing(): void
    {
        if (!$this->quotationId) {
            return;
        }

        $quotation = Quotation::find($this->quotationId);
        if (!$quotation) {
            return;
        }

        $quotation->update([
            'status' => 'pending',
            'error_message' => null,
        ]);

        ProcessQuotationJob::dispatch($quotation->id);
        $this->processingStatus = 'processing';
        $this->errorMessage = null;
    }

    /* ═══════════════════════════════════════════════════
     *  PASO 3 — Formulario editable
     * ═══════════════════════════════════════════════════ */

    /**
     * Carga los datos extraídos del Quotation al formulario editable.
     * Incluye normalización fiscal vía TaxNormalizerService
     * y normalización de datos vía DataNormalizerService.
     */
    private function loadParsedData(Quotation $quotation): void
    {
        $data = $quotation->raw_parsed_data ?? [];

        // Normalizar datos fiscales antes de cargar al formulario
        $taxNormalizer = app(TaxNormalizerService::class);
        $data = $taxNormalizer->normalize($data);

        // Normalizar datos (unidades, texto) vía DataNormalizerService
        $dataNormalizer = app(DataNormalizerService::class);

        $this->supplierName = !empty($data['supplier']) ? $dataNormalizer->normalizeTitleCase($data['supplier']) : '';
        $this->storeName = !empty($data['store']) ? $dataNormalizer->normalizeTitleCase($data['store']) : '';
        $this->vendorName = !empty($data['seller']) ? $dataNormalizer->normalizeTitleCase($data['seller']) : '';
        $this->rawText = $data['raw_text'] ?? '';
        $this->items = [];
        $this->alerts = ['errors' => [], 'warnings' => [], 'info' => []];
        $this->supplierMatch = [];
        $this->vendorMatch = [];

        // Extraer contexto fiscal global
        $taxInfo = $data['tax_info'] ?? [];
        $this->taxDetectedByAI = $taxInfo['tax_detected'] ?? false;

        if (isset($taxInfo['prices_include_tax'])) {
            $this->quotationIncludesTax = $taxInfo['prices_include_tax'];
        } else {
            $hasResolvedTax = collect($data['items'] ?? [])
                ->contains(fn($item) => !empty($item['tax_source']));
            $this->quotationIncludesTax = $hasResolvedTax ? false : null;
        }

        // Intentar asociar proveedor existente con fuzzy matching
        if ($this->supplierName) {
            $match = $dataNormalizer->findMatchingSupplier($this->supplierName);

            if ($match !== null) {
                $this->supplierId = $match['match']->id;
                $this->supplierName = $match['match']->trade_name;
                $this->supplierMatch = [
                    'status'     => $match['source'] === 'exact' ? 'exact' : 'fuzzy',
                    'confidence' => $match['confidence'],
                    'id'         => $match['match']->id,
                ];
            } else {
                $this->supplierMatch = ['status' => 'new'];
            }
        }

        // Intentar asociar vendedor existente con fuzzy matching
        // Busca con scope de proveedor si existe, o globalmente si no
        if ($this->vendorName) {
            $vendorMatchResult = $dataNormalizer->findMatchingVendor(
                $this->vendorName,
                $this->supplierId ? (int) $this->supplierId : null
            );

            if ($vendorMatchResult !== null) {
                $this->vendorName = $vendorMatchResult['match']->name;
                $this->vendorMatch = [
                    'status'     => $vendorMatchResult['source'] === 'exact' ? 'exact' : 'fuzzy',
                    'confidence' => $vendorMatchResult['confidence'],
                    'id'         => $vendorMatchResult['match']->id,
                ];
            } else {
                $this->vendorMatch = ['status' => 'new'];
            }
        }

        // Cargar ítems extraídos con normalización de unidades
        $normalizedItems = $dataNormalizer->normalizeItems($data['items'] ?? []);

        foreach ($normalizedItems as $item) {
            $matchedCategory = !empty($item['category'])
                ? $dataNormalizer->findMatchingCategory($item['category'])
                : null;

            // Resolver medida con fuzzy matching
            $measureMatch = !empty($item['unit'])
                ? $dataNormalizer->findMatchingMeasure($item['unit'])
                : null;

            // Resolver producto con fuzzy matching (reemplaza match exacto)
            $productMatch = !empty($item['name'])
                ? $dataNormalizer->findMatchingProduct($item['name'])
                : null;

            // Detectar si el producto ya existe y verificar conflictos de categoría/unidad
            $conflict = null;
            $existingProductId = null;
            $productMatchStatus = 'new';
            $existingProduct = null;

            if ($productMatch !== null) {
                $existingProduct = $productMatch['match']->load(['category', 'measure']);
                $existingProductId = $existingProduct->id;
                $productMatchStatus = $productMatch['source'] === 'exact' ? 'exact' : 'fuzzy';
                $conflictFields = [];

                // Conflicto de categoría: la IA sugirió una diferente a la registrada
                if ($matchedCategory && $existingProduct->category_id
                    && $matchedCategory->id !== $existingProduct->category_id) {
                    $conflictFields['category'] = [
                        'registered' => $existingProduct->category?->name,
                        'registered_id' => $existingProduct->category_id,
                        'suggested' => $matchedCategory->name,
                        'suggested_id' => $matchedCategory->id,
                    ];
                }

                // Conflicto de unidad
                if (!empty($item['unit']) && $existingProduct->measure_id) {
                    $normalizedSuggestedUnit = $dataNormalizer->normalizeUnit($item['unit']);
                    $registeredUnit = $existingProduct->measure?->abbreviation;
                    if ($registeredUnit && $normalizedSuggestedUnit !== $registeredUnit) {
                        $conflictFields['unit'] = [
                            'registered' => $registeredUnit,
                            'registered_measure_id' => $existingProduct->measure_id,
                            'suggested' => $normalizedSuggestedUnit,
                        ];
                    }
                }

                if (!empty($conflictFields)) {
                    $conflict = $conflictFields;
                }
            }

            $suggestedCategoryId = $matchedCategory?->id ?? null;
            $suggestedCategoryName = $item['category'] ?? 'General';
            $suggestedUnit = $item['unit'] ?? 'pza';

            $this->items[] = [
                'name' => $item['name'] ?? '',
                'quantity' => $item['quantity'] ?? 0,
                'unit' => ($existingProduct && $existingProduct->measure_id)
                    ? ($existingProduct->measure?->abbreviation ?? $suggestedUnit)
                    : $suggestedUnit,
                'category_id' => ($existingProduct)
                    ? $existingProduct->category_id
                    : $suggestedCategoryId,
                'category_name' => ($existingProduct)
                    ? ($existingProduct->category?->name ?? 'General')
                    : $suggestedCategoryName,
                'unit_price' => $item['unit_price'] ?? 0,
                'unit_price_original' => $item['unit_price_original'] ?? $item['unit_price'] ?? 0,
                'tax_amount' => $item['tax_amount'] ?? null,
                'tax_source' => $item['tax_source'] ?? null,
                'line_subtotal' => $item['line_subtotal'] ?? null,
                'line_total' => $item['line_total'] ?? null,
                'product_id' => $existingProductId,
                'conflict' => $conflict,
                'product_confirmed' => ($productMatchStatus === 'exact' || $productMatchStatus === 'new'),
                '_match' => [
                    'product'  => [
                        'status'     => $productMatchStatus,
                        'confidence' => $productMatch['confidence'] ?? null,
                        'catalog_name' => $existingProduct ? $existingProduct->canonical_name : null,
                    ],
                    'category' => [
                        'status'         => $matchedCategory ? 'matched' : 'unmatched',
                        'suggested_name' => $item['category'] ?? null,
                    ],
                    'measure'  => [
                        'status'    => $measureMatch ? 'matched' : 'new',
                        'canonical' => $measureMatch['canonical'] ?? ($item['unit'] ?? null),
                        'unit_name' => $item['unit_name'] ?? null,
                    ],
                    'suggested' => [
                        'category_id' => $suggestedCategoryId,
                        'category_name' => $suggestedCategoryName,
                        'unit' => $suggestedUnit,
                        'name' => $item['name'] ?? '',
                    ],
                ],
            ];
        }

        // RF-REQ-06: Detectar campos incompletos (sistema tipificado)
        $this->detectAlerts();
    }

    /**
     * RF-REQ-06 — Detecta campos incompletos y genera alertas tipificadas.
     *
     * Tres niveles:
     * - errors:   Bloquean o comprometen el guardado (rojo)
     * - warnings: Requieren atención del usuario (ámbar)
     * - info:     Informativos sobre el estado de la detección (azul)
     */
    private function detectAlerts(): void
    {
        $errors = [];
        $warnings = [];
        $info = [];

        // ── Errores (bloquean guardado) ──
        if (empty($this->projectId)) {
            $errors[] = 'Proyecto no asignado — selecciona un proyecto.';
        }

        if (empty($this->items)) {
            $errors[] = 'No se detectaron productos — agrégalos manualmente.';
        }

        foreach ($this->items as $i => $item) {
            $row = $i + 1;
            if (empty($item['name'])) {
                $errors[] = "Producto en fila {$row}: nombre vacío.";
            }
        }

        // ── Warnings (requieren atención) ──
        if (empty($this->supplierName) && empty($this->supplierId)) {
            $warnings[] = 'Proveedor no identificado — asígnalo manualmente.';
        }

        if ($this->quotationIncludesTax === null && !empty($this->items)) {
            $warnings[] = 'No se detectó si los precios incluyen IVA — indica si los precios ya incluyen el impuesto.';
        }

        foreach ($this->items as $i => $item) {
            $row = $i + 1;
            if (empty($item['unit_price']) || $item['unit_price'] <= 0) {
                $warnings[] = "Producto \"{$item['name']}\" (fila {$row}): precio no identificado.";
            }
            if (empty($item['quantity']) || $item['quantity'] <= 0) {
                $warnings[] = "Producto \"{$item['name']}\" (fila {$row}): cantidad no identificada.";
            }
        }

        // ── Info (contexto sobre detecciones) ──
        $existingCount = collect($this->items)->filter(fn($item) => ($item['_match']['product']['status'] ?? '') !== 'new')->count();
        $newCount = count($this->items) - $existingCount;

        if ($existingCount > 0) {
            $info[] = "{$existingCount} producto(s) ya existente(s) en el catálogo.";
        }
        if ($newCount > 0) {
            $info[] = "{$newCount} producto(s) nuevo(s) — se crearán al guardar.";
        }

        if (!empty($this->supplierMatch) && ($this->supplierMatch['status'] ?? '') === 'fuzzy') {
            $conf = ($this->supplierMatch['confidence'] ?? 0) * 100;
            $info[] = "Proveedor detectado por similitud ({$conf}% de confianza).";
        }

        $this->alerts = compact('errors', 'warnings', 'info');
    }

    /* ── Acciones del formulario editable ─────────────── */

    /**
     * Actualiza la categoría y/o unidad del producto maestro con los valores
     * sugeridos por la IA en esta cotización.
     *
     * Solo se actualiza el campo que el usuario confirma; el conflicto se limpia
     * para que la alerta desaparezca de la fila.
     *
     * @param  int    $index  Índice del ítem en $this->items
     * @param  string $field  'category' | 'unit' | 'both'
     */
    public function resolveProductConflict(int $index, string $field): void
    {
        $item = $this->items[$index] ?? null;
        if (!$item || empty($item['product_id']) || empty($item['conflict'])) {
            return;
        }

        $product = Product::find($item['product_id']);
        if (!$product) {
            return;
        }

        $conflict = $item['conflict'];
        $updates = [];

        if (($field === 'category' || $field === 'both') && isset($conflict['category'])) {
            $updates['category_id'] = $conflict['category']['suggested_id'];
            $this->items[$index]['category_id'] = $conflict['category']['suggested_id'];
            $this->items[$index]['category_name'] = $conflict['category']['suggested'];
            unset($this->items[$index]['conflict']['category']);
        }

        if (($field === 'unit' || $field === 'both') && isset($conflict['unit'])) {
            $normalizer = app(DataNormalizerService::class);
            $suggestedUnit = $conflict['unit']['suggested'];
            $measure = Measure::where('abbreviation', $suggestedUnit)->first();
            if (!$measure) {
                // Usar unit_name del hint de la IA si está disponible
                $aiUnitName = $item['_match']['measure']['unit_name'] ?? null;
                $measure = Measure::create([
                    'name' => $normalizer->getUnitName($suggestedUnit, $aiUnitName),
                    'abbreviation' => $suggestedUnit,
                ]);
            }
            $updates['measure_id'] = $measure->id;
            $this->items[$index]['unit'] = $suggestedUnit;
            unset($this->items[$index]['conflict']['unit']);
        }

        if (!empty($updates)) {
            $product->update($updates);
        }

        // Limpiar el conflicto si ya no quedan campos en conflicto
        if (empty($this->items[$index]['conflict'])) {
            $this->items[$index]['conflict'] = null;
        }
    }

    /**
     * Confirma la asociación difusa de un producto y actualiza sus datos en la vista
     * para que coincidan con los oficiales del catálogo.
     */
    public function confirmProductAssociation(int $index): void
    {
        $item = $this->items[$index] ?? null;
        if (!$item || empty($item['product_id'])) {
            return;
        }

        $product = Product::find($item['product_id']);
        if (!$product) {
            return;
        }

        $existingProduct = $product->load(['category', 'measure']);

        // Sincronizar el estado local con los datos oficiales del catálogo
        $this->items[$index]['name'] = $existingProduct->canonical_name;
        $this->items[$index]['category_id'] = $existingProduct->category_id;
        $this->items[$index]['category_name'] = $existingProduct->category?->name ?? 'General';
        $this->items[$index]['unit'] = $existingProduct->measure?->abbreviation ?? 'pza';
        $this->items[$index]['product_confirmed'] = true;

        // El match status pasa a exacto puesto que ya está confirmado por el usuario
        $this->items[$index]['_match']['product']['status'] = 'exact';

        // Al confirmar que es este producto oficial, ya no hay conflictos en la vista respecto al catálogo
        $this->items[$index]['conflict'] = null;

        $this->detectAlerts();
    }

    /**
     * Rechaza la asociación difusa con el producto del catálogo y restablece el ítem
     * para que se cree como producto nuevo con los valores sugeridos por la IA.
     */
    public function rejectProductAssociation(int $index): void
    {
        $item = $this->items[$index] ?? null;
        if (!$item) {
            return;
        }

        // Obtener los datos sugeridos por la IA originalmente desde el respaldo
        $suggested = $item['_match']['suggested'] ?? [];

        $this->items[$index]['product_id'] = null;
        $this->items[$index]['product_confirmed'] = true; // Confirmado que se creará como nuevo
        $this->items[$index]['_match']['product']['status'] = 'new';
        $this->items[$index]['_match']['product']['confidence'] = null;
        $this->items[$index]['conflict'] = null; // No hay conflictos ya que es un producto nuevo

        // Restablecer el nombre de entrada al original extraído y los valores de la IA
        if (isset($suggested['name'])) {
            $this->items[$index]['name'] = $suggested['name'];
        }
        if (isset($suggested['category_id'])) {
            $this->items[$index]['category_id'] = $suggested['category_id'];
        }
        if (isset($suggested['category_name'])) {
            $this->items[$index]['category_name'] = $suggested['category_name'];
        }
        if (isset($suggested['unit'])) {
            $this->items[$index]['unit'] = $suggested['unit'];
        }

        $this->detectAlerts();
    }

    /**
     * Descarta el conflicto sin actualizar el producto maestro.
     * La cotización conserva los valores sugeridos por la IA solo para este ítem.
     */
    public function dismissProductConflict(int $index): void
    {
        if (isset($this->items[$index])) {
            $this->items[$index]['conflict'] = null;
        }
    }

    public function updatedProjectId(): void
    {
        $this->detectAlerts();
    }

    public function updatedDate(): void
    {
        $this->detectAlerts();
    }

    public function updatedItems(): void
    {
        $this->detectAlerts();
    }

    /**
     * Hook para cuando el nombre del proveedor cambia manualmente.
     * Intenta resolver el ID del proveedor para filtrar vendedores.
     */
    public function updatedSupplierName($value): void
    {
        if (empty($value)) {
            $this->supplierId = '';
            $this->supplierMatch = [];
            $this->vendorMatch = [];
            return;
        }

        $normalizer = app(DataNormalizerService::class);
        $match = $normalizer->findMatchingSupplier($value);

        if ($match !== null) {
            $this->supplierId = $match['match']->id;
            $this->supplierMatch = [
                'status'     => $match['source'] === 'exact' ? 'exact' : 'fuzzy',
                'confidence' => $match['confidence'],
                'id'         => $match['match']->id,
            ];
        } else {
            $this->supplierId = '';
            $this->supplierMatch = ['status' => 'new'];
        }

        $this->detectAlerts();
    }

    public function addItem(): void
    {
        $this->items[] = [
            'name' => '',
            'quantity' => 1,
            'unit' => 'pza',
            'category_id' => null,
            'category_name' => '',
            'unit_price' => 0,
            'unit_price_original' => 0,
            'tax_amount' => null,
            'tax_source' => null,
            'line_subtotal' => null,
            'line_total' => null,
            'product_id' => null,
        ];

        $this->detectAlerts();
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->detectAlerts();
    }

    /* ── Acciones fiscales (IVA) ─────────────────────── */

    /**
     * Toggle global: El usuario indica si los precios incluyen IVA.
     * Recalcula todos los ítems que no tengan tax_source resuelto.
     */
    public function setTaxInclusion(bool $includesTax): void
    {
        $this->quotationIncludesTax = $includesTax;
        $normalizer = app(TaxNormalizerService::class);

        foreach ($this->items as $i => $item) {
            // Solo recalcular ítems que no tienen IVA ya resuelto por el proveedor
            $existingSource = $item['tax_source'] ?? null;
            if ($existingSource === 'supplier_per_item') {
                continue;
            }

            $originalPrice = (float) ($item['unit_price_original'] ?? $item['unit_price'] ?? 0);
            if ($originalPrice <= 0) {
                continue;
            }

            $quantity = (float) ($item['quantity'] ?? 1);
            $resolved = $normalizer->resolveForUserChoice($originalPrice, $quantity, $includesTax);

            $this->items[$i]['unit_price'] = $resolved['unit_price'];
            $this->items[$i]['tax_amount'] = $resolved['tax_amount'];
            $this->items[$i]['tax_source'] = $resolved['tax_source'];
            $this->items[$i]['line_subtotal'] = $resolved['line_subtotal'];
            $this->items[$i]['line_total'] = $resolved['line_total'];
        }

        // Limpiar la advertencia de IVA
        $this->detectAlerts();
    }

    /* ═══════════════════════════════════════════════════
     *  GUARDAR REQUISICIÓN
     * ═══════════════════════════════════════════════════ */

    public function saveRequisition(RequisitionItemResolverService $resolver): void
    {
        $this->validate([
            'projectId' => 'required|exists:projects,id',
            'annotations' => 'nullable|max:500',
            'date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|min:1',
        ], [
            'projectId.required' => 'Selecciona un proyecto.',
            'items.required' => 'Agrega al menos un producto.',
            'items.min' => 'Agrega al menos un producto.',
        ]);

        // Crear requisición con todos sus items usando el servicio
        $requisition = $resolver->createRequisitionWithItems(
            [
                'project_id' => $this->projectId,
                'annotations' => $this->annotations,
                'status' => 'borrador',
                'created_by' => auth()->id(),
                'date' => $this->date,
            ],
            $this->items,
            $this->supplierName,
            $this->supplierId,
            $this->vendorName
        );

        // Vincular la cotización con la requisición
        if ($this->quotationId) {
            $quotation = Quotation::find($this->quotationId);
            if ($quotation) {
                $quotation->update([
                    'requisition_id' => $requisition->id,
                    'supplier_id' => $requisition->vendor?->supplier_id,
                ]);
            }
        }

        session()->flash('success', 'Requisición creada exitosamente desde cotización.');
        $this->redirect(route('requisiciones.index'), navigate: true);
    }

    /**
     * Volver al paso 1 (resetear todo).
     */
    public function resetWizard(): void
    {
        $this->step = 1;
        $this->file = null;
        $this->quotationId = null;
        $this->processingStatus = 'pending';
        $this->errorMessage = null;
        $this->supplierName = '';
        $this->supplierId = '';
        $this->storeName = '';
        $this->vendorName = '';
        $this->annotations = '';
        $this->items = [];
        $this->alerts = ['errors' => [], 'warnings' => [], 'info' => []];
        $this->supplierMatch = [];
        $this->vendorMatch = [];
        $this->rawText = '';
        $this->quotationIncludesTax = null;
        $this->taxDetectedByAI = false;
    }

    /**
     * Carga datos de prueba ficticios pero basados en su base de datos real
     * para verificar la UI de alertas, popovers y confirmaciones.
     */
    public function loadMockDataForTesting(): void
    {
        // 1. Tomar un proyecto activo
        $project = \App\Models\Project::where('status', 'activo')->first();
        if ($project) {
            $this->projectId = $project->id;
        }

        // 2. Tomar categorías y unidades existentes en la base de datos
        $categories = \App\Models\Category::limit(3)->get();
        $measures = Measure::limit(2)->get();
        $products = Product::limit(2)->get();

        // Inicializar ítems vacíos
        $this->items = [];
        $this->supplierName = 'CEMEX S.A. DE C.V.';
        $this->supplierId = '';
        $this->vendorName = 'Leticia Dzul';
        $this->date = now()->format('Y-m-d');

        // Normalizar proveedor de prueba
        $this->updatedSupplierName($this->supplierName);

        // Ítem 1: Simular producto existente con conflictos
        if ($products->count() > 0) {
            $prod1 = $products->first()->load(['category', 'measure']);
            
            // Sugerir categoría diferente
            $otherCat = \App\Models\Category::where('id', '!=', $prod1->category_id)->first() ?? $categories->last();
            // Sugerir unidad diferente
            $otherUnit = Measure::where('id', '!=', $prod1->measure_id)->first() ?? $measures->last();

            $conflictFields = [];
            if ($otherCat && $prod1->category_id) {
                $conflictFields['category'] = [
                    'registered' => $prod1->category?->name,
                    'registered_id' => $prod1->category_id,
                    'suggested' => $otherCat->name,
                    'suggested_id' => $otherCat->id,
                ];
            }
            if ($otherUnit && $prod1->measure_id) {
                $conflictFields['unit'] = [
                    'registered' => $prod1->measure?->abbreviation,
                    'registered_measure_id' => $prod1->measure_id,
                    'suggested' => $otherUnit->abbreviation ?? 'bulto',
                ];
            }

            $this->items[] = [
                'name' => $prod1->canonical_name,
                'quantity' => 5,
                'unit' => $prod1->measure?->abbreviation ?? 'pza',
                'category_id' => $prod1->category_id,
                'category_name' => $prod1->category?->name ?? 'General',
                'unit_price' => 150.00,
                'unit_price_original' => 150.00,
                'tax_amount' => 24.00,
                'tax_source' => 'calculated',
                'line_subtotal' => 750.00,
                'line_total' => 870.00,
                'product_id' => $prod1->id,
                'conflict' => !empty($conflictFields) ? $conflictFields : null,
                'product_confirmed' => true,
                '_match' => [
                    'product' => [
                        'status' => 'exact',
                        'confidence' => 1.0,
                        'catalog_name' => $prod1->canonical_name,
                    ],
                    'category' => [
                        'status' => 'matched',
                        'suggested_name' => $otherCat?->name ?? 'General',
                    ],
                    'measure' => [
                        'status' => 'matched',
                        'canonical' => $prod1->measure?->abbreviation ?? 'pza',
                        'unit_name' => $prod1->measure?->name ?? 'Pieza',
                    ],
                    'suggested' => [
                        'category_id' => $otherCat?->id,
                        'category_name' => $otherCat?->name ?? 'General',
                        'unit' => $otherUnit?->abbreviation ?? 'bulto',
                        'name' => $prod1->canonical_name,
                    ]
                ]
            ];
        }

        // Ítem 2: Simular producto difuso (Fuzzy Match) pendiente de confirmación
        if ($products->count() > 1) {
            $prod2 = $products->skip(1)->first()->load(['category', 'measure']);
            
            $this->items[] = [
                'name' => $prod2->canonical_name . ' Extra', // Nombre con agregado para simular coincidencia difusa
                'quantity' => 10,
                'unit' => $prod2->measure?->abbreviation ?? 'pza',
                'category_id' => $prod2->category_id,
                'category_name' => $prod2->category?->name ?? 'General',
                'unit_price' => 85.50,
                'unit_price_original' => 85.50,
                'tax_amount' => 13.68,
                'tax_source' => 'calculated',
                'line_subtotal' => 855.00,
                'line_total' => 991.68,
                'product_id' => $prod2->id,
                'conflict' => null,
                'product_confirmed' => false, // Desconfirmado para probar el badge
                '_match' => [
                    'product' => [
                        'status' => 'fuzzy',
                        'confidence' => 0.84,
                        'catalog_name' => $prod2->canonical_name,
                    ],
                    'category' => [
                        'status' => 'matched',
                        'suggested_name' => $prod2->category?->name ?? 'General',
                    ],
                    'measure' => [
                        'status' => 'matched',
                        'canonical' => $prod2->measure?->abbreviation ?? 'pza',
                        'unit_name' => $prod2->measure?->name ?? 'Pieza',
                    ],
                    'suggested' => [
                        'category_id' => $prod2->category_id,
                        'category_name' => $prod2->category?->name ?? 'General',
                        'unit' => $prod2->measure?->abbreviation ?? 'pza',
                        'name' => $prod2->canonical_name . ' Extra',
                    ]
                ]
            ];
        } else {
            // Fallback si solo hay 1 o 0 productos en catálogo
            $this->items[] = [
                'name' => 'Tubo Galvanizado 1" (Fuzzy Demo)',
                'quantity' => 12,
                'unit' => 'pza',
                'category_id' => $categories->first()?->id ?? null,
                'category_name' => $categories->first()?->name ?? 'Ferretería',
                'unit_price' => 310.00,
                'unit_price_original' => 310.00,
                'tax_amount' => 49.60,
                'tax_source' => 'calculated',
                'line_subtotal' => 3720.00,
                'line_total' => 4315.20,
                'product_id' => 999, // ID simulado
                'conflict' => null,
                'product_confirmed' => false,
                '_match' => [
                    'product' => [
                        'status' => 'fuzzy',
                        'confidence' => 0.79,
                        'catalog_name' => 'Tubo Galvanizado C-40 1"',
                    ],
                    'category' => [
                        'status' => 'matched',
                        'suggested_name' => 'Ferretería',
                    ],
                    'measure' => [
                        'status' => 'matched',
                        'canonical' => 'pza',
                        'unit_name' => 'Pieza',
                    ],
                    'suggested' => [
                        'category_id' => $categories->first()?->id ?? null,
                        'category_name' => $categories->first()?->name ?? 'Ferretería',
                        'unit' => 'pza',
                        'name' => 'Tubo Galvanizado 1" (Fuzzy Demo)',
                    ]
                ]
            ];
        }

        // Ítem 3: Producto nuevo
        $this->items[] = [
            'name' => 'Clavos de Acero 2" Nuevos',
            'quantity' => 20,
            'unit' => 'kg',
            'category_id' => $categories->last()?->id ?? null,
            'category_name' => $categories->last()?->name ?? 'Construcción',
            'unit_price' => 45.00,
            'unit_price_original' => 45.00,
            'tax_amount' => 7.20,
            'tax_source' => 'calculated',
            'line_subtotal' => 900.00,
            'line_total' => 1044.00,
            'product_id' => null,
            'conflict' => null,
            'product_confirmed' => true,
            '_match' => [
                'product' => [
                    'status' => 'new',
                    'confidence' => null,
                    'catalog_name' => null,
                ],
                'category' => [
                    'status' => 'unmatched',
                    'suggested_name' => 'Construcción',
                ],
                'measure' => [
                    'status' => 'new',
                    'canonical' => 'kg',
                    'unit_name' => 'Kilogramo',
                ],
                'suggested' => [
                    'category_id' => $categories->last()?->id ?? null,
                    'category_name' => $categories->last()?->name ?? 'Construcción',
                    'unit' => 'kg',
                    'name' => 'Clavos de Acero 2" Nuevos',
                ]
            ]
        ];

        // Cambiar al paso 3 directamente
        $this->step = 3;
        $this->quotationIncludesTax = true;
        $this->taxDetectedByAI = true;

        $this->detectAlerts();
    }

    #[Layout('components.layouts.app')]
    #[Title('Subir Cotización')]
    public function render()
    {
        $projects = \App\Models\Project::where('status', 'activo')->orderBy('name')->get();
        $suppliers = Supplier::orderBy('trade_name')->get();
        $measures = Measure::orderBy('name')->get();
        $categories = \App\Models\Category::orderBy('name')->get();

        $vendors = [];
        if ($this->supplierId) {
            $vendors = \App\Models\Vendor::where('supplier_id', $this->supplierId)->orderBy('name')->get();
        }

        return view('livewire.requisitions.quotation-wizard', compact(
            'projects',
            'suppliers',
            'measures',
            'categories',
            'vendors'
        ));
    }
}
