<?php

namespace App\Livewire\Requisitions;

use App\Jobs\ProcessQuotationJob;
use App\Models\Document;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\Supplier;
use App\Services\DocumentParsers\DocumentParserFactory;
use App\Services\HomologationService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * RF-REQ-01 a RF-REQ-07 — Wizard de 3 pasos para subir cotizaciones.
 *
 * Paso 1: Upload del archivo (drag-and-drop).
 * Paso 2: Procesamiento (barra de progreso + polling).
 * Paso 3: Formulario editable con datos extraídos y homologación.
 */
class QuotationWizard extends Component
{
    use WithFileUploads;

    /* ── Estado del Wizard ───────────────────────────── */
    public int $step = 1;

    /* ── Paso 1: Upload ──────────────────────────────── */
    public $file;
    public ?int $quotationId = null;
    public int $fileInputKey = 0;

    /* ── Paso 2: Processing status ───────────────────── */
    public string $processingStatus = 'pending';
    public ?string $errorMessage = null;

    /* ── Paso 3: Formulario editable ─────────────────── */
    public $projectId = '';
    public string $supplierName = '';
    public $supplierId = '';
    public string $storeName = '';
    public string $description = '';
    public string $needDate = '';
    public array $items = [];
    public string $rawText = '';

    /* ── Alertas de campos inompletos ────────────────── */
    public array $warnings = [];

    /* ── Homologación ────────────────────────────────── */
    public array $homologationSuggestions = [];

    public function mount(): void
    {
        $this->projectId = session('active_project_id', '');
        $this->needDate  = now()->addDays(7)->format('Y-m-d');
    }

    /* ═══════════════════════════════════════════════════
     *  PASO 1 — Upload del archivo
     * ═══════════════════════════════════════════════════ */

    public function updatedFile(): void
    {
        $this->validate([
            'file' => 'required|file|max:20480|mimetypes:application/pdf,image/jpeg,image/png,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel',
        ], [
            'file.max'       => 'El archivo no debe superar los 20 MB.',
            'file.mimetypes' => 'Formatos permitidos: PDF, JPG, JPEG, PNG, XLSX, XLS.',
        ]);
    }

    /**
     * Elimina el archivo seleccionado y resetea el estado del input.
     *
     * Se usa un método dedicado en lugar de $set('file', null)
     * porque WithFileUploads requiere limpiar la referencia interna
     * del TemporaryUploadedFile para que el input acepte un nuevo archivo.
     */
    public function removeFile(): void
    {
        $this->file = null;
        $this->resetValidation('file');
        $this->dispatch('file-cleared');
    }

    public function processUpload(): void
    {
        $this->validate([
            'file' => 'required|file|max:20480|mimetypes:application/pdf,image/jpeg,image/png,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel',
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
     */
    private function loadParsedData(Quotation $quotation): void
    {
        $data = $quotation->parsed_data ?? [];

        $this->supplierName = $data['supplier'] ?? '';
        $this->storeName    = $data['store'] ?? '';
        $this->rawText      = $data['raw_text'] ?? '';
        $this->items        = [];
        $this->warnings     = [];

        // Intentar asociar proveedor existente
        if ($this->supplierName) {
            $supplier = Supplier::where('trade_name', 'LIKE', "%{$this->supplierName}%")->first();
            if ($supplier) {
                $this->supplierId = $supplier->id;
            }
        }

        // Cargar ítems extraídos
        $homologationService = app(HomologationService::class);
        foreach ($data['items'] ?? [] as $index => $item) {
            $suggestions = $homologationService->findSuggestions($item['name'] ?? '');

            $this->items[] = [
                'name'       => $item['name'] ?? '',
                'quantity'   => $item['quantity'] ?? 0,
                'unit'       => $item['unit'] ?? 'pza',
                'unit_price' => $item['unit_price'] ?? 0,
                'product_id' => !empty($suggestions) && $suggestions[0]['similarity'] === 100
                    ? $suggestions[0]['id']
                    : null,
                'homologation_status' => !empty($suggestions) && $suggestions[0]['similarity'] === 100
                    ? 'homologated'
                    : 'pending',
            ];

            $this->homologationSuggestions[$index] = $suggestions;
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
            'product_id'          => null,
            'homologation_status' => 'pending',
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        unset($this->homologationSuggestions[$index]);
        $this->homologationSuggestions = array_values($this->homologationSuggestions);
    }

    /**
     * Homologa un ítem seleccionando un producto del catálogo.
     */
    public function homologateItem(int $index, int $productId): void
    {
        if (!isset($this->items[$index])) {
            return;
        }

        $product = Product::find($productId);
        if (!$product) {
            return;
        }

        $this->items[$index]['product_id'] = $productId;
        $this->items[$index]['homologation_status'] = 'homologated';
    }

    /**
     * Quita la homologación de un ítem (lo deja pendiente).
     */
    public function unhomologateItem(int $index): void
    {
        if (!isset($this->items[$index])) {
            return;
        }
        $this->items[$index]['product_id'] = null;
        $this->items[$index]['homologation_status'] = 'pending';
    }

    /* ═══════════════════════════════════════════════════
     *  GUARDAR REQUISICIÓN
     * ═══════════════════════════════════════════════════ */

    public function saveRequisition(): void
    {
        $this->validate([
            'projectId'       => 'required|exists:projects,id',
            'description'     => 'required|min:3|max:500',
            'needDate'        => 'required|date',
            'items'           => 'required|array|min:1',
            'items.*.name'    => 'required|string|min:1',
        ], [
            'projectId.required' => 'Selecciona un proyecto.',
            'description.required' => 'Agrega una descripción para la requisición.',
            'items.required'     => 'Agrega al menos un producto.',
            'items.min'          => 'Agrega al menos un producto.',
        ]);

        // RF-REQ-09: la requisición inicia como borrador
        $requisition = Requisition::create([
            'project_id'  => $this->projectId,
            'description' => $this->description,
            'status'      => 'borrador',
            'created_by'  => auth()->id(),
            'date'        => now(),
            'need_date'   => $this->needDate,
        ]);

        // Guardar ítems con estado de homologación
        foreach ($this->items as $item) {
            RequisitionItem::create([
                'requisition_id'      => $requisition->id,
                'product_id'          => $item['product_id'] ?? null,
                'product_name'        => $item['name'],
                'quantity'            => $item['quantity'] ?? 0,
                'unit'                => $item['unit'] ?? 'pza',
                'unit_price'          => $item['unit_price'] ?? 0,
                'supplier_id'         => $this->supplierId ?: null,
                'homologation_status' => $item['homologation_status'] ?? 'pending',
            ]);
        }

        // Vincular la cotización con la requisición
        if ($this->quotationId) {
            $quotation = Quotation::find($this->quotationId);
            if ($quotation) {
                $quotation->update([
                    'requisition_id' => $requisition->id,
                    'supplier_id'    => $this->supplierId ?: null,
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
        $this->description      = '';
        $this->items            = [];
        $this->warnings         = [];
        $this->rawText          = '';
        $this->homologationSuggestions = [];
    }

    #[Layout('components.layouts.app')]
    #[Title('Subir Cotización')]
    public function render()
    {
        $projects  = \App\Models\Project::where('status', 'activo')->orderBy('name')->get();
        $suppliers = Supplier::orderBy('trade_name')->get();
        $products  = Product::orderBy('canonical_name')->get();

        return view('livewire.requisitions.quotation-wizard', compact(
            'projects', 'suppliers', 'products'
        ));
    }
}
