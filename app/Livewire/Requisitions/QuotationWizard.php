<?php

namespace App\Livewire\Requisitions;

use App\Livewire\Concerns\EnforcesPermissions;
use App\DTOs\QuotationDTO;
use App\Jobs\ProcessQuotationJob;
use App\Models\Category;
use App\Models\Measure;
use App\Models\Product;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\Supplier;
use App\Models\Vendor;
use App\Repositories\QuotationRepository;
use App\Services\DataNormalizerService;
use App\Services\DiscountNormalizerService;
use App\Services\DocumentParsers\DocumentParserFactory;
use App\Services\RequisitionItemResolverService;
use App\Services\TaxNormalizerService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
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
    use EnforcesPermissions;
    use WithFileUploads;

    /* ── Estado del Wizard ───────────────────────────── */
    public int $step = 1;
    public bool $isProcessing = false;

    /* ── Paso 1: Upload ──────────────────────────────── */
    public $files = [];
    public $uploadQueue = []; // Cola temporal para las subidas

    public function updatedUploadQueue()
    {
        $rules = [
            'file' => 'file|max:20480|mimetypes:application/pdf,image/jpeg,image/png,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel|mimes:pdf,jpg,jpeg,png,xlsx,xls',
        ];
        
        $messages = [
            'file.max' => 'El archivo supera el límite de 20 MB.',
            'file.mimetypes' => 'El formato del archivo no está permitido (solo PDF, JPG, PNG, XLSX).',
            'file.mimes' => 'El formato del archivo no está permitido (solo PDF, JPG, PNG, XLSX).',
        ];

        foreach ($this->uploadQueue as $file) {
            $validator = \Illuminate\Support\Facades\Validator::make(['file' => $file], $rules, $messages);
            
            if ($validator->fails()) {
                $this->dispatch('toast', [
                    'icon' => 'error',
                    'message' => 'Error en ' . $file->getClientOriginalName() . ': ' . $validator->errors()->first('file'),
                ]);
            } else {
                $this->files[] = $file;
            }
        }

        // Limpiar la cola para permitir nuevas subidas
        $this->uploadQueue = [];
    }

    #[\Livewire\Attributes\On('file-removed')]
    public function removeFile($index = null)
    {
        if ($index !== null && isset($this->files[$index])) {
            unset($this->files[$index]);
            $this->files = array_values($this->files); // Reindexar
        }
    }

    #[Url(as: 'ids')]
    public array $quotationIds = [];

    #[Url]
    public string $source = '';

    public ?int $activeQuotationId = null;

    public array $completedQuotationIds = [];

    /* ── Paso 2: Processing status ───────────────────── */
    public string $processingStatus = 'pending';

    public ?string $errorMessage = null;

    #[Computed]
    public function processingProgress(): int
    {
        if (empty($this->quotationIds)) {
            return 0;
        }

        $quotations = Quotation::whereIn('id', $this->quotationIds)->get();
        $total = $quotations->count();
        $completed = $quotations->filter(fn($q) => $q->isCompleted() || $q->isFailed())->count();
        
        return $total > 0 ? (int) (($completed / $total) * 100) : 0;
    }

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

    #[Computed]
    public function subtotalSinIva(): float
    {
        return collect($this->items)->sum(function ($item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            return $item['line_subtotal'] ?? round($qty * $price, 2);
        });
    }

    #[Computed]
    public function totalConIva(): float
    {
        return collect($this->items)->sum(function ($item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            $sub = $item['line_subtotal'] ?? round($qty * $price, 2);
            return $item['line_total'] ?? round($sub + ($item['tax_amount'] ?? 0), 2);
        });
    }

    #[Computed]
    public function totalIva(): float
    {
        return $this->totalConIva() - $this->subtotalSinIva();
    }

    #[Computed]
    public function totalDescuento(): float
    {
        return collect($this->items)->sum(function ($item) {
            $original = (float) ($item['unit_price_original'] ?? 0);
            $net = (float) ($item['unit_price'] ?? 0);
            $qty = (float) ($item['quantity'] ?? 0);
            if ($original <= 0 || $net <= 0 || $original <= $net) {
                return 0;
            }
            return round(($original - $net) * $qty, 2);
        });
    }

    #[Computed]
    public function hasAnyDiscount(): bool
    {
        return $this->totalDescuento() > 0;
    }

    #[Computed]
    public function subtotalBruto(): float
    {
        return $this->hasAnyDiscount() ? ($this->subtotalSinIva() + $this->totalDescuento()) : 0;
    }

    /* ── Metadata de resolución (proveedor/vendedor) ──── */
    public array $supplierMatch = [];

    public array $vendorMatch = [];

    public function updated($property, $value): void
    {
        // Solo autoguardar si estamos en el paso 3
        if ($this->step === 3) {
            $this->autoSaveDraft();
        }
    }

    public function autoSaveDraft(): void
    {
        if ($this->activeQuotationId) {
            $quotation = Quotation::find($this->activeQuotationId);
            if ($quotation) {
                $quotation->update([
                    'draft_state' => [
                        'projectId' => $this->projectId,
                        'supplierName' => $this->supplierName,
                        'supplierId' => $this->supplierId,
                        'storeName' => $this->storeName,
                        'vendorName' => $this->vendorName,
                        'annotations' => $this->annotations,
                        'date' => $this->date,
                        'items' => $this->items,
                        'supplierMatch' => $this->supplierMatch,
                        'vendorMatch' => $this->vendorMatch,
                    ],
                ]);
            }
        }
    }

    public function mount(): void
    {
        if (! auth()->user()?->hasPermission('requisiciones.crear') && ! auth()->user()?->hasPermission('*')) {
            abort(403, 'No tienes permiso para subir cotizaciones.');
        }

        $this->date = now()->format('Y-m-d');

        if (! empty($this->quotationIds)) {
            $quotations = Quotation::whereIn('id', $this->quotationIds)->get();
            if ($quotations->isNotEmpty()) {
                $allCompleted = true;
                $anyFailed = false;

                foreach ($quotations as $quotation) {
                    if ($quotation->isFailed()) {
                        $anyFailed = true;
                        $this->errorMessage = "El archivo {$quotation->original_filename} falló: {$quotation->error_message}";
                    }
                    if (!$quotation->isCompleted() && !$quotation->isFailed()) {
                        $allCompleted = false;
                    }
                }

                if ($anyFailed) {
                    $this->processingStatus = 'failed';
                    $this->step = 2;
                } elseif ($allCompleted) {
                    $this->processingStatus = 'completed';
                    
                    // Sincronizar completedQuotationIds con la base de datos (evita resets en hard reload)
                    foreach ($quotations as $quotation) {
                        if ($quotation->requisition_id && !in_array($quotation->id, $this->completedQuotationIds)) {
                            $this->completedQuotationIds[] = $quotation->id;
                        }
                    }

                    // Activar el primer tab incompleto
                    $nextId = null;
                    foreach ($this->quotationIds as $id) {
                        if (!in_array($id, $this->completedQuotationIds)) {
                            $nextId = $id;
                            break;
                        }
                    }
                    if ($nextId) {
                        $this->setActiveTab($nextId);
                        $this->step = 3;
                    } else {
                        // Todos guardados
                        $redirectUrl = $this->source === 'borradores'
                            ? route('requisiciones.index', ['tab' => 'borradores'])
                            : route('requisiciones.index');
                        $this->redirect($redirectUrl, navigate: true);
                    }
                } else {
                    $this->processingStatus = 'processing';
                    $this->step = 2;
                }
            } else {
                $this->quotationIds = [];
            }
        }
    }

    public function setActiveTab($quotationId): void
    {
        if ($this->activeQuotationId && $this->activeQuotationId !== $quotationId) {
            $this->autoSaveDraft();
        }

        $this->activeQuotationId = $quotationId;
        $quotation = Quotation::find($quotationId);
        
        if ($quotation) {
            if (! empty($quotation->draft_state)) {
                $this->loadFromDraftState($quotation->draft_state);
                $this->rawText = $quotation->raw_text ?? '';
            } else {
                $this->loadParsedData($quotation);
            }
        }
    }

    /* ═══════════════════════════════════════════════════
     *  PASO 1 — Upload del archivo
     * ═══════════════════════════════════════════════════ */

    public function updatedFiles(): void
    {
        if (! $this->files || !is_array($this->files)) {
            return;
        }

        $this->validate([
            'files.*' => 'required|file|max:20480|mimetypes:application/pdf,image/jpeg,image/png,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel|mimes:pdf,jpg,jpeg,png,xlsx,xls',
        ], [
            'files.*.max' => 'Ningún archivo debe superar los 20 MB.',
            'files.*.mimes' => 'Formatos permitidos: PDF, JPG, PNG, XLSX.',
        ]);
    }

    public function removeUploadedFile($index): void
    {
        if (is_array($this->files)) {
            unset($this->files[$index]);
            $this->files = array_values($this->files);
        } else {
            $this->files = [];
        }
    }

    public function processUpload(QuotationRepository $repository): void
    {
        $this->validate([
            'files' => 'required|array|min:1',
        ], [
            'files.required' => 'Debes seleccionar al menos un archivo.',
            'files.min' => 'Debes seleccionar al menos un archivo.',
        ]);

        $this->quotationIds = [];
        $factory = app(DocumentParserFactory::class);

        foreach ($this->files as $file) {
            try {
                if (! $file || ! $file->exists()) {
                    continue;
                }

                $dto = QuotationDTO::fromFile($file, $this->projectId ?: null, auth()->id());
                $quotation = $repository->uploadAndCreate($dto);

                $this->quotationIds[] = $quotation->id;
                
                $filePath = $file->getRealPath();
                $mimeType = $file->getMimeType();
                $extension = $file->getClientOriginalExtension();
                $resolution = $factory->resolve($filePath, $mimeType, $extension);

                if ($resolution['async']) {
                    ProcessQuotationJob::dispatch($quotation->id);
                } else {
                    $quotation->update(['status' => 'processing']);
                    try {
                        $result = $resolution['parser']->parse($filePath);
                        $quotation->update([
                            'status' => 'completed',
                            'raw_text' => $result['raw_text'] ?? null,
                            'raw_parsed_data' => $result,
                            'processed_at' => now(),
                        ]);
                    } catch (\Throwable $e) {
                        $quotation->update([
                            'status' => 'failed',
                            'error_message' => $e->getMessage(),
                        ]);
                    }
                }
            } catch (\Exception $e) {
                // Capturar el error de Magic Bytes o de creación
                $this->dispatch('toast', ['icon' => 'error', 'message' => $e->getMessage()]);
                // Si el archivo falla, no seguimos procesando este archivo en particular
                continue;
            }
        }

        // Si se subieron archivos exitosamente, comenzamos a verificarlos
        if (count($this->quotationIds) > 0) {
            $this->isProcessing = true;
            $this->step = 2;
            $this->processingStatus = 'processing';
            $this->checkProcessingStatus();
        } else {
            // Si ninguno pasó, reseteamos
            $this->files = [];
            $this->uploadQueue = [];
        }
    }

    /* ═══════════════════════════════════════════════════
     *  PASO 2 — Polling del estado de procesamiento
     * ═══════════════════════════════════════════════════ */

    public function checkProcessingStatus(): void
    {
        if (empty($this->quotationIds)) {
            return;
        }

        $quotations = Quotation::whereIn('id', $this->quotationIds)->get();
        $allCompleted = true;
        $anyFailed = false;

        foreach ($quotations as $quotation) {
            if ($quotation->isFailed()) {
                $anyFailed = true;
                $this->errorMessage = "El archivo {$quotation->original_filename} falló: {$quotation->error_message}";
            }
            if (!$quotation->isCompleted() && !$quotation->isFailed()) {
                $allCompleted = false;
            }
        }

        if ($anyFailed) {
            $this->processingStatus = 'failed';
        } elseif ($allCompleted) {
            $this->processingStatus = 'completed';
            $nextId = null;
            foreach ($this->quotationIds as $id) {
                if (!in_array($id, $this->completedQuotationIds)) {
                    $nextId = $id;
                    break;
                }
            }
            if ($nextId) {
                $this->setActiveTab($nextId);
                $this->step = 3;
            }
        } else {
            $this->processingStatus = 'processing';
        }
    }

    /**
     * Permite reintentar un procesamiento fallido.
     */
    public function retryProcessing(): void
    {
        if (empty($this->quotationIds)) {
            return;
        }

        $quotations = Quotation::whereIn('id', $this->quotationIds)->where('status', 'failed')->get();
        foreach ($quotations as $quotation) {
            $quotation->update([
                'status' => 'pending',
                'error_message' => null,
            ]);
            ProcessQuotationJob::dispatch($quotation->id);
        }

        $this->processingStatus = 'processing';
        $this->errorMessage = null;
    }

    /**
     * Permite continuar a la captura manual cuando la extracción falla.
     */
    public function continueManually(): void
    {
        if (!empty($this->quotationIds)) {
            Quotation::whereIn('id', $this->quotationIds)->where('status', 'failed')->update(['status' => 'completed']);
            $this->checkProcessingStatus();
        }
    }

    /* ═══════════════════════════════════════════════════
     *  PASO 3 — Formulario editable
     * ═══════════════════════════════════════════════════ */

    /**
     * Carga los datos desde un estado de borrador guardado previamente.
     */
    private function loadFromDraftState(array $state): void
    {
        $this->projectId = $state['projectId'] ?? '';
        $this->supplierName = $state['supplierName'] ?? '';
        $this->supplierId = $state['supplierId'] ?? '';
        $this->storeName = $state['storeName'] ?? '';
        $this->vendorName = $state['vendorName'] ?? '';
        $this->annotations = $state['annotations'] ?? '';
        $this->date = $state['date'] ?? now()->format('Y-m-d');
        $this->items = $state['items'] ?? [];
        $this->supplierMatch = $state['supplierMatch'] ?? [];
        $this->vendorMatch = $state['vendorMatch'] ?? [];
    }

    /**
     * Carga los datos extraídos del Quotation al formulario editable.
     * Incluye normalización fiscal vía TaxNormalizerService
     * y normalización de datos vía DataNormalizerService.
     */
    private function loadParsedData(Quotation $quotation): void
    {
        $data = $quotation->raw_parsed_data ?? [];

        // Normalizar descuentos ANTES de impuestos (pipeline: Precio → Descuento → IVA)
        $discountNormalizer = app(DiscountNormalizerService::class);
        $data = $discountNormalizer->normalize($data);

        // Normalizar datos fiscales después de descuentos
        $taxNormalizer = app(TaxNormalizerService::class);
        $data = $taxNormalizer->normalize($data);

        // Normalizar datos (unidades, texto) vía DataNormalizerService
        $dataNormalizer = app(DataNormalizerService::class);

        $this->supplierName = ! empty($data['supplier']) ? $dataNormalizer->normalizeTitleCase($data['supplier']) : '';
        $this->storeName = ! empty($data['store']) ? $dataNormalizer->normalizeTitleCase($data['store']) : '';
        $this->vendorName = ! empty($data['seller']) ? $dataNormalizer->normalizeTitleCase($data['seller']) : '';
        $this->rawText = $data['raw_text'] ?? '';
        $this->items = [];
        $this->supplierMatch = [];
        $this->vendorMatch = [];

        // Intentar asociar proveedor existente con fuzzy matching
        if ($this->supplierName) {
            $match = $dataNormalizer->findMatchingSupplier($this->supplierName);

            if ($match !== null) {
                $this->supplierId = $match['match']->id;
                $this->supplierName = $match['match']->trade_name;
                $this->supplierMatch = [
                    'status' => $match['source'] === 'exact' ? 'exact' : 'fuzzy',
                    'confidence' => $match['confidence'],
                    'id' => $match['match']->id,
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
                    'status' => $vendorMatchResult['source'] === 'exact' ? 'exact' : 'fuzzy',
                    'confidence' => $vendorMatchResult['confidence'],
                    'id' => $vendorMatchResult['match']->id,
                ];
            } else {
                $this->vendorMatch = ['status' => 'new'];
            }
        }

        // Cargar ítems extraídos con normalización de unidades
        $normalizedItems = $dataNormalizer->normalizeItems($data['items'] ?? []);

        foreach ($normalizedItems as $item) {
            $matchedCategory = ! empty($item['category'])
                ? $dataNormalizer->findMatchingCategory($item['category'])
                : null;

            // Resolver medida con fuzzy matching
            $measureMatch = ! empty($item['unit'])
                ? $dataNormalizer->findMatchingMeasure($item['unit'])
                : null;

            // Resolver producto con fuzzy matching (reemplaza match exacto)
            $productMatch = ! empty($item['name'])
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
                if (
                    $matchedCategory && $existingProduct->category_id
                    && $matchedCategory->id !== $existingProduct->category_id
                ) {
                    $conflictFields['category'] = [
                        'registered' => $existingProduct->category?->name,
                        'registered_id' => $existingProduct->category_id,
                        'suggested' => $matchedCategory->name,
                        'suggested_id' => $matchedCategory->id,
                    ];
                }

                // Conflicto de unidad
                if (! empty($item['unit']) && $existingProduct->measure_id) {
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

                if (! empty($conflictFields)) {
                    $conflict = $conflictFields;
                }
            }

            $suggestedCategoryId = $matchedCategory?->id ?? null;
            $suggestedCategoryName = $item['category'] ?? 'General';
            $suggestedUnit = $item['unit'] ?? 'pza';

            $qty = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            $subtotalVal = $item['line_subtotal'] ?? round($price * $qty, 2);
            $taxVal = $item['tax_amount'] ?? round($subtotalVal * 0.16, 2);
            $totalVal = $item['line_total'] ?? ($subtotalVal + $taxVal);

            $this->items[] = [
                'id' => uniqid(),
                'name' => $item['name'] ?? '',
                'quantity' => $qty,
                'unit' => ($existingProduct && $existingProduct->measure_id)
                    ? ($existingProduct->measure?->abbreviation ?? $suggestedUnit)
                    : $suggestedUnit,
                'category_id' => ($existingProduct)
                    ? $existingProduct->category_id
                    : $suggestedCategoryId,
                'category_name' => ($existingProduct)
                    ? ($existingProduct->category?->name ?? 'General')
                    : $suggestedCategoryName,
                'unit_price' => $price,
                'unit_price_original' => $item['unit_price_original'] ?? $price,
                'tax_amount' => $taxVal,
                'tax_source' => $item['tax_source'] ?? 'calculated',
                'line_subtotal' => $subtotalVal,
                'line_total' => $totalVal,
                'discount_percent' => $item['discount_percent'] ?? null,
                'product_id' => $existingProductId,
                'conflict' => $conflict,
                'product_confirmed' => ($productMatchStatus === 'exact' || $productMatchStatus === 'new'),
                '_match' => [
                    'product' => [
                        'status' => $productMatchStatus,
                        'confidence' => $productMatch['confidence'] ?? null,
                        'catalog_name' => $existingProduct ? $existingProduct->canonical_name : null,
                    ],
                    'category' => [
                        'status' => $matchedCategory ? 'matched' : 'unmatched',
                        'suggested_name' => $item['category'] ?? null,
                    ],
                    'measure' => [
                        'status' => $measureMatch ? 'matched' : 'new',
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
    }

    /* ── Acciones del formulario editable ─────────────── */

    /**
     * Actualiza la categoría y/o unidad del producto maestro con los valores
     * sugeridos por la IA en esta cotización.
     *
     * Solo se actualiza el campo que el usuario confirma; el conflicto se limpia
     * para que la alerta desaparezca de la fila.
     *
     * @param  int  $index  Índice del ítem en $this->items
     * @param  string  $field  'category' | 'unit' | 'both'
     */
    public function resolveProductConflict(int $index, string $field, \App\Actions\Requisitions\ResolveProductConflictAction $action): void
    {
        $item = $this->items[$index] ?? null;
        if (! $item) return;

        $this->items[$index] = $action->execute($item, $field);
    }

    /**
     * Confirma la asociación difusa de un producto y actualiza sus datos en la vista
     * para que coincidan con los oficiales del catálogo.
     */
    public function confirmProductAssociation(int $index): void
    {
        $item = $this->items[$index] ?? null;
        if (! $item || empty($item['product_id'])) {
            return;
        }

        $product = Product::find($item['product_id']);
        if (! $product) {
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

    }

    /**
     * Rechaza la asociación difusa con el producto del catálogo y restablece el ítem
     * para que se cree como producto nuevo con los valores sugeridos por la IA.
     */
    public function rejectProductAssociation(int $index): void
    {
        $item = $this->items[$index] ?? null;
        if (! $item) {
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

    public function updatedProjectId(): void {}

    public function updatedDate(): void {}

    public function updatedItems($value = null, $key = null): void
    {
        // Recalcular dinámicamente line_subtotal, tax_amount y line_total SOLO si el usuario edita
        // explícitamente la cantidad o el precio unitario. Esto preserva los cálculos exactos
        // del proveedor si se edita cualquier otro campo (ej. nombre, unidad, categoría).
        if ($key !== null && (str_ends_with($key, '.quantity') || str_ends_with($key, '.unit_price'))) {
            $parts = explode('.', $key);
            $i = $parts[0];

            if (isset($this->items[$i])) {
                $qty = (float) ($this->items[$i]['quantity'] ?? 0);
                $price = (float) ($this->items[$i]['unit_price'] ?? 0);

                $calculatedSubtotal = round($price * $qty, 2);
                $this->items[$i]['line_subtotal'] = $calculatedSubtotal;
                $this->items[$i]['tax_amount'] = round($calculatedSubtotal * 0.16, 2);
                $this->items[$i]['line_total'] = round($calculatedSubtotal + $this->items[$i]['tax_amount'], 2);
            }
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
                'status' => $match['source'] === 'exact' ? 'exact' : 'fuzzy',
                'confidence' => $match['confidence'],
                'id' => $match['match']->id,
            ];
        } else {
            $this->supplierId = '';
            $this->supplierMatch = ['status' => 'new'];
        }

    }

    public function addItem(): void
    {
        $this->items[] = [
            'id' => uniqid(),
            'name' => '',
            'quantity' => 1,
            'unit' => 'pza',
            'category_id' => null,
            'category_name' => '',
            'unit_price' => 0,
            'unit_price_original' => 0,
            'tax_amount' => 0,
            'tax_source' => 'calculated',
            'line_subtotal' => 0,
            'line_total' => 0,
            'discount_percent' => null,
            'product_id' => null,
        ];

    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
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
        if ($this->activeQuotationId) {
            $quotation = Quotation::find($this->activeQuotationId);
            if ($quotation) {
                $quotation->update([
                    'requisition_id' => $requisition->id,
                    'supplier_id' => $requisition->vendor?->supplier_id,
                ]);
            }
            $this->completedQuotationIds[] = $this->activeQuotationId;
        }

        $nextId = null;
        foreach ($this->quotationIds as $id) {
            if (!in_array($id, $this->completedQuotationIds)) {
                $nextId = $id;
                break;
            }
        }

        if ($nextId) {
            $this->setActiveTab($nextId);
            session()->flash('success', 'Requisición creada. Por favor revisa la siguiente cotización.');
        } else {
            session()->flash('success', '¡Todas las requisiciones han sido creadas con éxito!');
            $redirectUrl = $this->source === 'borradores'
                ? route('requisiciones.index', ['tab' => 'borradores'])
                : route('requisiciones.index');
            $this->redirect($redirectUrl, navigate: true);
        }
    }

    /**
     * Volver al paso 1 (resetear todo).
     */
    public function resetWizard(): void
    {
        $this->step = 1;
        $this->files = [];
        $this->quotationIds = [];
        $this->activeQuotationId = null;
        $this->completedQuotationIds = [];
        $this->processingStatus = 'pending';
        $this->errorMessage = null;
        $this->supplierName = '';
        $this->supplierId = '';
        $this->storeName = '';
        $this->vendorName = '';
        $this->annotations = '';
        $this->items = [];
        $this->supplierMatch = [];
        $this->vendorMatch = [];
        $this->rawText = '';
    }

    /**
     * Carga datos de prueba ficticios pero basados en su base de datos real
     * para verificar la UI de alertas, popovers y confirmaciones.
     */
    public function loadMockDataForTesting(): void
    {
        // 1. Tomar un proyecto activo
        $project = Project::where('status', 'activo')->first();
        if ($project) {
            $this->projectId = $project->id;
        }

        // 2. Tomar categorías y unidades existentes en la base de datos
        $categories = Category::limit(3)->get();
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
            $otherCat = Category::where('id', '!=', $prod1->category_id)->first() ?? $categories->last();
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
                'unit_price' => 135.00,
                'unit_price_original' => 150.00,
                'tax_amount' => 21.60,
                'tax_source' => 'calculated',
                'line_subtotal' => 675.00,
                'line_total' => 783.00,
                'discount_percent' => 10.0,
                'product_id' => $prod1->id,
                'conflict' => ! empty($conflictFields) ? $conflictFields : null,
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
                    ],
                ],
            ];
        }

        // Ítem 2: Simular producto difuso (Fuzzy Match) pendiente de confirmación
        if ($products->count() > 1) {
            $prod2 = $products->skip(1)->first()->load(['category', 'measure']);

            $this->items[] = [
                'name' => $prod2->canonical_name.' Extra', // Nombre con agregado para simular coincidencia difusa
                'quantity' => 10,
                'unit' => $prod2->measure?->abbreviation ?? 'pza',
                'category_id' => $prod2->category_id,
                'category_name' => $prod2->category?->name ?? 'General',
                'unit_price' => 81.23,
                'unit_price_original' => 85.50,
                'tax_amount' => 12.997,
                'tax_source' => 'calculated',
                'line_subtotal' => 812.30,
                'line_total' => 942.27,
                'discount_percent' => 5.0,
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
                        'name' => $prod2->canonical_name.' Extra',
                    ],
                ],
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
                'discount_percent' => null,
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
                    ],
                ],
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
            'discount_percent' => null,
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
                ],
            ],
        ];

        // Cambiar al paso 3 directamente
        $this->step = 3;
    }

    #[Layout('components.layouts.app')]
    #[Title('Subir Cotización')]
    public function render()
    {
        $projects = Project::where('status', 'activo')->orderBy('name')->get();
        $suppliers = Supplier::orderBy('trade_name')->get();
        $measures = Measure::getOptions();
        $categories = Category::orderBy('name')->get();

        $vendors = collect();
        if ($this->supplierId) {
            $vendors = Vendor::where('supplier_id', $this->supplierId)->orderBy('name')->get();
        }

        // El modelo Quotation se carga aquí para evitar queries en la vista
        $quotation = $this->activeQuotationId ? Quotation::find($this->activeQuotationId) : null;

        // Obtener el listado de todas las cotizaciones del wizard en el orden original
        $wizardQuotations = collect();
        if (! empty($this->quotationIds)) {
            $quotations = Quotation::whereIn('id', $this->quotationIds)->get()->keyBy('id');
            foreach ($this->quotationIds as $id) {
                if (isset($quotations[$id])) {
                    $wizardQuotations->put($id, $quotations[$id]);
                }
            }
        }

        return view('livewire.requisitions.quotation-wizard', compact(
            'projects',
            'suppliers',
            'measures',
            'categories',
            'vendors',
            'quotation',
            'wizardQuotations'
        ));
    }
}
