<?php

namespace App\Livewire\Requisitions;

use App\Jobs\ProcessQuotationJob;
use App\Models\Document;
use App\Models\Quotation;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\Measure;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\DataNormalizerService;
use App\Services\DocumentParsers\DocumentParserFactory;
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
        if ($this->vendorName && $this->supplierId) {
            $vendorMatchResult = $dataNormalizer->findMatchingVendor(
                $this->vendorName,
                (int) $this->supplierId
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
        } elseif ($this->vendorName) {
            $this->vendorMatch = ['status' => 'new'];
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

            $this->items[] = [
                'name' => $item['name'] ?? '',
                'quantity' => $item['quantity'] ?? 0,
                'unit' => $item['unit'] ?? 'pza',
                'category_id' => $matchedCategory?->id ?? null,
                'category_name' => $item['category'] ?? 'General',
                'unit_price' => $item['unit_price'] ?? 0,
                'unit_price_original' => $item['unit_price_original'] ?? $item['unit_price'] ?? 0,
                'tax_amount' => $item['tax_amount'] ?? null,
                'tax_source' => $item['tax_source'] ?? null,
                'line_subtotal' => $item['line_subtotal'] ?? null,
                'line_total' => $item['line_total'] ?? null,
                'product_id' => $existingProductId,
                'conflict' => $conflict,
                '_match' => [
                    'product'  => [
                        'status'     => $productMatchStatus,
                        'confidence' => $productMatch['confidence'] ?? null,
                    ],
                    'category' => [
                        'status'         => $matchedCategory ? 'matched' : 'unmatched',
                        'suggested_name' => $item['category'] ?? null,
                    ],
                    'measure'  => [
                        'status'    => $measureMatch ? 'matched' : 'new',
                        'canonical' => $measureMatch['canonical'] ?? ($item['unit'] ?? null),
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
            unset($this->items[$index]['conflict']['category']);
        }

        if (($field === 'unit' || $field === 'both') && isset($conflict['unit'])) {
            $normalizer = app(DataNormalizerService::class);
            $suggestedUnit = $conflict['unit']['suggested'];
            $measure = Measure::where('abbreviation', $suggestedUnit)->first();
            if (!$measure) {
                $measure = Measure::create([
                    'name' => $normalizer->getUnitName($suggestedUnit),
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
     * Descarta el conflicto sin actualizar el producto maestro.
     * La cotización conserva los valores sugeridos por la IA solo para este ítem.
     */
    public function dismissProductConflict(int $index): void
    {
        if (isset($this->items[$index])) {
            $this->items[$index]['conflict'] = null;
        }
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
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
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

    public function saveRequisition(): void
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

        // Auto-save Supplier if doesn't exist
        $finalSupplierId = $this->supplierId;
        if (empty($finalSupplierId) && !empty($this->supplierName)) {
            $normalizer = app(DataNormalizerService::class);
            $normalizedName = $normalizer->normalizeSupplierName($this->supplierName);

            // Try to find it by normalized name
            $existingSupplier = Supplier::where('normalized_name', $normalizedName)->first();
            if ($existingSupplier) {
                $finalSupplierId = $existingSupplier->id;
            } else {
                $newSupplier = Supplier::create([
                    'trade_name' => $this->supplierName,
                ]);
                $finalSupplierId = $newSupplier->id;
            }
        }

        // Auto-save Vendor (person) if name is provided
        $finalVendorId = null;
        if (!empty($this->vendorName) && $finalSupplierId) {
            $normalizer = app(DataNormalizerService::class);
            $vendorMatch = $normalizer->findMatchingVendor($this->vendorName, (int) $finalSupplierId);

            if ($vendorMatch !== null) {
                $finalVendorId = $vendorMatch['match']->id;
            } else {
                $newVendor = \App\Models\Vendor::create([
                    'supplier_id' => $finalSupplierId,
                    'name' => $this->vendorName,
                ]);
                $finalVendorId = $newVendor->id;
            }
        }

        // RF-REQ-09: la requisición inicia como borrador
        $requisition = Requisition::create([
            'project_id' => $this->projectId,
            'vendor_id' => $finalVendorId,
            'annotations' => $this->annotations,
            'status' => 'borrador',
            'created_by' => auth()->id(),
            'date' => $this->date,
        ]);

        // ═══════════════════════════════════════════════════════
        // OPTIMIZACIÓN: Precarga batch de medidas y productos
        // para evitar N+1 queries en el loop de items
        // ═══════════════════════════════════════════════════════

        $normalizer = app(DataNormalizerService::class);

        // 1. Precargar TODAS las medidas relevantes en una sola query
        $unitKeys = [];
        foreach ($this->items as $item) {
            if (!empty($item['unit'])) {
                $normalizedUnit = $normalizer->normalizeUnit($item['unit']);
                $unitKeys[] = $normalizedUnit;
                $unitKeys[] = mb_strtolower($item['unit']);
            }
        }

        $existingMeasures = collect();
        if (!empty($unitKeys)) {
            $existingMeasures = Measure::whereIn('abbreviation', array_unique($unitKeys))
                ->get()
                ->keyBy(fn($m) => $m->abbreviation);  // indexar por abreviatura
        }

        // 2. Precargar TODOS los productos candidatos en una sola query
        $normalizedProductNames = [];

        foreach ($this->items as $index => $item) {
            if (!empty($item['name'])) {
                $normalizedName = $normalizer->normalizeText($item['name']);
                $normalizedProductNames[$index] = $normalizedName;
            }
        }

        $existingProducts = collect();
        if (!empty($normalizedProductNames)) {
            $existingProducts = Product::whereIn('normalized_name', array_unique($normalizedProductNames))
                ->get()
                ->keyBy('normalized_name');  // indexar por nombre normalizado
        }

        // 3. Procesar items con lookups O(1) desde colecciones precargadas
        $requisitionItemsData = [];

        foreach ($this->items as $index => $item) {
            // --- Resolver Medida ---
            $measureId = null;
            if (!empty($item['unit'])) {
                $normalizedUnit = $normalizer->normalizeUnit($item['unit']);
                $measure = $existingMeasures->get($normalizedUnit);

                if (!$measure) {
                    // Crear medida nueva
                    $measure = Measure::create([
                        'name' => $normalizer->getUnitName($normalizedUnit),
                        'abbreviation' => $normalizedUnit,
                    ]);
                    $existingMeasures->put($normalizedUnit, $measure);
                }
                $measureId = $measure->id;
            }

            // --- Resolver Producto (O(1) desde cache) ---
            $productId = $item['product_id'] ?? null;
            if (empty($productId) && !empty($item['name'])) {
                $normalizedName = $normalizedProductNames[$index] ?? $normalizer->normalizeText($item['name']);
                $product = $existingProducts->get($normalizedName);

                if ($product) {
                    $productId = $product->id;
                } else {
                    // Resolver categoría (prioridad: category_id manual > búsqueda por nombre IA)
                    $categoryId = $item['category_id'] ?? null;

                    // Si no seleccionó del catálogo, buscar/crear por el nombre detectado por IA
                    if (empty($categoryId) && !empty($item['category_name'])) {
                        $matchedCategory = $normalizer->findMatchingCategory($item['category_name']);
                        if ($matchedCategory) {
                            $categoryId = $matchedCategory->id;
                        } else {
                            $newCategory = \App\Models\Category::create([
                                'name' => mb_convert_case($item['category_name'], MB_CASE_TITLE, "UTF-8")
                            ]);
                            $categoryId = $newCategory->id;
                        }
                    }

                    // Crear producto nuevo
                    $newProduct = Product::create([
                        'canonical_name' => $item['name'],
                        'measure_id' => $measureId,
                        'category_id' => $categoryId,
                    ]);
                    $productId = $newProduct->id;

                    // Agregar a cache para futuros items con mismo nombre
                    $existingProducts->put($normalizedName, $newProduct);
                }
            }

            // Preparar datos del requisition item
            $requisitionItemsData[] = [
                'requisition_id' => $requisition->id,
                'product_id' => $productId,
                'measure_id' => $measureId,
                'quantity' => $item['quantity'] ?? 0,
                'unit_price' => $item['unit_price'] ?? 0,
                'unit_price_original' => $item['unit_price_original'] ?? $item['unit_price'] ?? 0,
                'tax_amount' => $item['tax_amount'] ?? null,
                'tax_source' => $item['tax_source'] ?? null,
                'line_subtotal' => $item['line_subtotal'] ?? null,
                'line_total' => $item['line_total'] ?? null,
                'supplier_id' => $finalSupplierId ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // 4. Insertar todos los requisition items en una sola query (si son muchos)
        if (count($requisitionItemsData) > 0) {
            RequisitionItem::insert($requisitionItemsData);
        }

        // Vincular la cotización con la requisición
        if ($this->quotationId) {
            $quotation = Quotation::find($this->quotationId);
            if ($quotation) {
                $quotation->update([
                    'requisition_id' => $requisition->id,
                    'supplier_id' => $finalSupplierId ?: null,
                ]);

                // RF-DOC-02: vincular archivo al repositorio documental automáticamente
                Document::create([
                    'project_id' => $this->projectId,
                    'requisition_id' => $requisition->id,
                    'name' => $quotation->original_filename ?? 'Cotización',
                    'category' => 'cotizaciones',
                    'file_path' => $quotation->file_path,
                    'version' => 1,
                    'uploaded_by' => auth()->id(),
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
