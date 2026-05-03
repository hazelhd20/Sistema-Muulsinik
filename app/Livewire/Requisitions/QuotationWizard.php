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
    public string $annotations = '';
    public string $date = '';
    public array $items = [];
    public string $rawText = '';

    /* ── Alertas de campos incompletos ────────────────── */
    public array $warnings = [];

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
            'file.max'   => 'El archivo no debe superar los 20 MB.',
            'file.mimes' => 'Formatos permitidos: PDF, JPG, PNG, XLSX.',
        ]);
    }

    public function processUpload(): void
    {
        $this->validate([
            'file' => 'required|file|max:20480|mimes:pdf,jpg,jpeg,png,xlsx,xls',
        ]);

        // Guardar el archivo en storage
        $path = $this->file->store('quotations', 'local');
        $originalName = $this->file->getClientOriginalName();
        $extension    = strtolower($this->file->getClientOriginalExtension());
        $mimeType     = $this->file->getMimeType();

        // Crear registro de cotización
        $quotation = Quotation::create([
            'project_id'        => $this->projectId ?: null,
            'file_path'         => $path,
            'file_type'         => $mimeType,
            'original_filename' => $originalName,
            'status'            => 'pending',
            'uploaded_by'       => auth()->id(),
        ]);

        $this->quotationId = $quotation->id;

        // Determinar si el procesamiento es síncrono o asíncrono
        $factory    = app(DocumentParserFactory::class);
        $filePath   = Storage::disk('local')->path($path);
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
                    'status'       => 'completed',
                    'raw_text'     => $result['raw_text'] ?? '',
                    'parsed_data'  => $result,
                    'processed_at' => now(),
                ]);

                $this->loadParsedData($quotation->fresh());
                $this->step = 3;

            } catch (\Throwable $e) {
                $quotation->update([
                    'status'        => 'failed',
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
            'status'        => 'pending',
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
        $data = $quotation->parsed_data ?? [];

        // Normalizar datos fiscales antes de cargar al formulario
        $taxNormalizer = app(TaxNormalizerService::class);
        $data = $taxNormalizer->normalize($data);

        // Normalizar datos (unidades, texto) vía DataNormalizerService
        $dataNormalizer = app(DataNormalizerService::class);

        $this->supplierName = $data['supplier'] ?? '';
        $this->storeName    = $data['store'] ?? '';
        $this->rawText      = $data['raw_text'] ?? '';
        $this->items        = [];
        $this->warnings     = [];

        // Extraer contexto fiscal global
        $taxInfo = $data['tax_info'] ?? [];
        $this->taxDetectedByAI = $taxInfo['tax_detected'] ?? false;

        if (isset($taxInfo['prices_include_tax'])) {
            $this->quotationIncludesTax = $taxInfo['prices_include_tax'];
        } else {
            // Si algún ítem ya tiene tax_source resuelto, no necesitamos toggle
            $hasResolvedTax = collect($data['items'] ?? [])
                ->contains(fn ($item) => !empty($item['tax_source']));
            $this->quotationIncludesTax = $hasResolvedTax ? false : null;
        }

        // Intentar asociar proveedor existente con fuzzy matching
        if ($this->supplierName) {
            $supplierMatch = $dataNormalizer->findMatchingSupplier($this->supplierName);

            if ($supplierMatch !== null) {
                $this->supplierId = $supplierMatch['supplier']->id;
                // Si es fuzzy match, mantener el nombre detectado como referencia
                if ($supplierMatch['source'] === 'exact_trade_name') {
                    $this->supplierName = '';
                }
            }
        }

        // Cargar ítems extraídos con normalización de unidades
        $normalizedItems = $dataNormalizer->normalizeItems($data['items'] ?? []);

        foreach ($normalizedItems as $item) {
            $this->items[] = [
                'name'                => $item['name'] ?? '',
                'quantity'            => $item['quantity'] ?? 0,
                'unit'                => $item['unit'] ?? 'pza',
                'unit_price'          => $item['unit_price'] ?? 0,
                'unit_price_original' => $item['unit_price_original'] ?? $item['unit_price'] ?? 0,
                'tax_amount'          => $item['tax_amount'] ?? null,
                'tax_source'          => $item['tax_source'] ?? null,
                'line_subtotal'       => $item['line_subtotal'] ?? null,
                'line_total'          => $item['line_total'] ?? null,
                'product_id'          => null,
            ];
        }

        // RF-REQ-06: Detectar campos incompletos
        $this->detectWarnings();
    }

    /**
     * RF-REQ-06 — Detecta campos vacíos y genera alertas visibles.
     */
    private function detectWarnings(): void
    {
        $this->warnings = [];

        if (empty($this->supplierName) && empty($this->supplierId)) {
            $this->warnings[] = 'Proveedor no identificado — asígnalo manualmente.';
        }

        if (empty($this->projectId)) {
            $this->warnings[] = 'Proyecto no asignado — selecciona un proyecto.';
        }

        if (empty($this->items)) {
            $this->warnings[] = 'No se detectaron productos — agrégalos manualmente.';
        }

        // Alerta de IVA no detectado
        if ($this->quotationIncludesTax === null && !empty($this->items)) {
            $this->warnings[] = 'No se detectó si los precios incluyen IVA — indica si los precios ya incluyen el impuesto.';
        }

        foreach ($this->items as $i => $item) {
            $row = $i + 1;
            if (empty($item['name'])) {
                $this->warnings[] = "Producto en fila {$row}: nombre vacío.";
            }
            if (empty($item['unit_price']) || $item['unit_price'] <= 0) {
                $this->warnings[] = "Producto \"{$item['name']}\" (fila {$row}): precio no identificado.";
            }
            if (empty($item['quantity']) || $item['quantity'] <= 0) {
                $this->warnings[] = "Producto \"{$item['name']}\" (fila {$row}): cantidad no identificada.";
            }
        }
    }

    /* ── Acciones del formulario editable ─────────────── */

    public function addItem(): void
    {
        $this->items[] = [
            'name'                => '',
            'quantity'            => 1,
            'unit'                => 'pza',
            'unit_price'          => 0,
            'unit_price_original' => 0,
            'tax_amount'          => null,
            'tax_source'          => null,
            'line_subtotal'       => null,
            'line_total'          => null,
            'product_id'          => null,
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

            $this->items[$i]['unit_price']    = $resolved['unit_price'];
            $this->items[$i]['tax_amount']    = $resolved['tax_amount'];
            $this->items[$i]['tax_source']    = $resolved['tax_source'];
            $this->items[$i]['line_subtotal'] = $resolved['line_subtotal'];
            $this->items[$i]['line_total']    = $resolved['line_total'];
        }

        // Limpiar la advertencia de IVA
        $this->detectWarnings();
    }

    /* ═══════════════════════════════════════════════════
     *  GUARDAR REQUISICIÓN
     * ═══════════════════════════════════════════════════ */

    public function saveRequisition(): void
    {
        $this->validate([
            'projectId'       => 'required|exists:projects,id',
            'annotations'     => 'nullable|max:500',
            'date'            => 'required|date',
            'items'           => 'required|array|min:1',
            'items.*.name'    => 'required|string|min:1',
        ], [
            'projectId.required' => 'Selecciona un proyecto.',
            'items.required'     => 'Agrega al menos un producto.',
            'items.min'          => 'Agrega al menos un producto.',
        ]);

        // Auto-save Supplier if doesn't exist
        $finalSupplierId = $this->supplierId;
        if (empty($finalSupplierId) && !empty($this->supplierName)) {
            $normalizer = app(DataNormalizerService::class);
            $normalizedName = $normalizer->normalizeSupplierName($this->supplierName);
            
            // Try to find it by normalized name
            $existingSupplier = Supplier::whereRaw('LOWER(trade_name) = ?', [$normalizedName])->first();
            if ($existingSupplier) {
                $finalSupplierId = $existingSupplier->id;
            } else {
                $newSupplier = Supplier::create([
                    'trade_name' => $this->supplierName,
                ]);
                $finalSupplierId = $newSupplier->id;
            }
        }

        // RF-REQ-09: la requisición inicia como borrador
        $requisition = Requisition::create([
            'project_id'  => $this->projectId,
            'annotations' => $this->annotations,
            'status'      => 'borrador',
            'created_by'  => auth()->id(),
            'date'        => $this->date,
        ]);

        // Guardar ítems con datos fiscales y auto-guardado
        foreach ($this->items as $item) {
            // Auto-save Product
            $productId = $item['product_id'] ?? null;
            if (empty($productId) && !empty($item['name'])) {
                $normalizedProductName = app(DataNormalizerService::class)->normalizeText($item['name']);
                $existingProduct = Product::whereRaw('LOWER(canonical_name) = ?', [$normalizedProductName])->first();
                
                if ($existingProduct) {
                    $productId = $existingProduct->id;
                } else {
                    $newProduct = Product::create([
                        'canonical_name' => $item['name'],
                        'unit'           => $item['unit'] ?? 'pza',
                    ]);
                    $productId = $newProduct->id;
                }
            }

            // Auto-save Measure
            if (!empty($item['unit'])) {
                $normalizedUnit = app(DataNormalizerService::class)->normalizeUnit($item['unit']);
                $existingMeasure = Measure::whereRaw('LOWER(name) = ?', [mb_strtolower($item['unit'])])
                                          ->orWhere('abbreviation', $normalizedUnit)
                                          ->first();
                if (!$existingMeasure) {
                    Measure::create([
                        'name'         => ucfirst($item['unit']),
                        'abbreviation' => $normalizedUnit,
                    ]);
                }
            }

            RequisitionItem::create([
                'requisition_id'      => $requisition->id,
                'product_id'          => $productId,
                'product_name'        => $item['name'],
                'quantity'            => $item['quantity'] ?? 0,
                'unit'                => $item['unit'] ?? 'pza',
                'unit_price'          => $item['unit_price'] ?? 0,
                'unit_price_original' => $item['unit_price_original'] ?? $item['unit_price'] ?? 0,
                'tax_amount'          => $item['tax_amount'] ?? null,
                'tax_source'          => $item['tax_source'] ?? null,
                'line_subtotal'       => $item['line_subtotal'] ?? null,
                'line_total'          => $item['line_total'] ?? null,
                'supplier_id'         => $finalSupplierId ?: null,
            ]);
        }

        // Vincular la cotización con la requisición
        if ($this->quotationId) {
            $quotation = Quotation::find($this->quotationId);
            if ($quotation) {
                $quotation->update([
                    'requisition_id' => $requisition->id,
                    'supplier_id'    => $finalSupplierId ?: null,
                ]);

                // RF-DOC-02: vincular archivo al repositorio documental automáticamente
                Document::create([
                    'project_id'     => $this->projectId,
                    'requisition_id' => $requisition->id,
                    'name'           => $quotation->original_filename ?? 'Cotización',
                    'category'       => 'cotizaciones',
                    'file_path'      => $quotation->file_path,
                    'version'        => 1,
                    'uploaded_by'    => auth()->id(),
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
        $this->step             = 1;
        $this->file             = null;
        $this->quotationId      = null;
        $this->processingStatus = 'pending';
        $this->errorMessage     = null;
        $this->supplierName     = '';
        $this->supplierId       = '';
        $this->storeName        = '';
        $this->annotations      = '';
        $this->items            = [];
        $this->warnings         = [];
        $this->rawText          = '';
        $this->quotationIncludesTax   = null;
        $this->taxDetectedByAI        = false;
    }

    #[Layout('components.layouts.app')]
    #[Title('Subir Cotización')]
    public function render()
    {
        $projects  = \App\Models\Project::where('status', 'activo')->orderBy('name')->get();
        $suppliers = Supplier::orderBy('trade_name')->get();
        $measures  = Measure::orderBy('name')->get();

        return view('livewire.requisitions.quotation-wizard', compact(
            'projects', 'suppliers', 'measures'
        ));
    }
}
