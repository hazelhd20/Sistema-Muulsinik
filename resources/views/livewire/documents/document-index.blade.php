<div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Gestión Documental</h1>
            <p class="text-sm text-text-muted">Repositorio centralizado de documentos por proyecto</p>
        </div>
        <button wire:click="openUploadModal" class="btn-primary">
            <i data-lucide="upload" class="w-4 h-4"></i>
            Subir Documento
        </button>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>{{ session('success') }}
        </div>
    @endif

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <div class="relative flex-1">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"></i>
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar documento..." class="input pl-10">
        </div>
        <x-custom-select 
            wire:model.live="projectFilter" 
            :options="$projects->pluck('name', 'id')->toArray()" 
            placeholder="Todos los proyectos" 
            class="w-auto min-w-[180px]"
        />
        <x-custom-select 
            wire:model.live="categoryFilter" 
            :options="$categoryLabels" 
            placeholder="Todas las categorías" 
            class="w-auto min-w-[160px]"
        />
    </div>

    {{-- Documents grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($documents as $doc)
            @php
                $ext = pathinfo($doc->file_path, PATHINFO_EXTENSION);
                $iconColors = [
                    'pdf' => ['bg-red-100', 'text-red-600'],
                    'doc' => ['bg-blue-100', 'text-blue-600'],
                    'docx' => ['bg-blue-100', 'text-blue-600'],
                    'xls' => ['bg-green-100', 'text-green-600'],
                    'xlsx' => ['bg-green-100', 'text-green-600'],
                    'jpg' => ['bg-amber-100', 'text-amber-600'],
                    'jpeg' => ['bg-amber-100', 'text-amber-600'],
                    'png' => ['bg-amber-100', 'text-amber-600'],
                ];
                $colors = $iconColors[$ext] ?? ['bg-gray-100', 'text-gray-600'];
                $catColors = [
                    'contratos' => 'badge-primary',
                    'planos' => 'badge-success',
                    'permisos' => 'badge-warning',
                    'cotizaciones' => 'badge-danger',
                    'otros' => 'bg-gray-100 text-gray-700',
                ];
            @endphp
            <div class="card hover:shadow-md transition-shadow">
                <div class="flex items-start gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl {{ $colors[0] }} flex items-center justify-center shrink-0">
                        <i data-lucide="file-text" class="w-5 h-5 {{ $colors[1] }}"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-semibold text-text-primary truncate">{{ $doc->name }}</h3>
                        <p class="text-xs text-text-muted">{{ $doc->project->name ?? '—' }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 mb-3">
                    <span class="badge {{ $catColors[$doc->category] ?? '' }} text-[10px]">{{ $categoryLabels[$doc->category] ?? $doc->category }}</span>
                    <span class="text-xs text-text-muted">v{{ $doc->version }}</span>
                    <span class="text-xs text-text-muted uppercase font-mono">.{{ $ext }}</span>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-gray-100 text-xs text-text-muted">
                    <span>{{ $doc->uploader->name ?? '—' }} · {{ $doc->created_at->format('d/m/Y') }}</span>
                    <div class="flex items-center gap-1">
                        <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="p-1.5 rounded-lg hover:bg-surface-hover text-text-muted hover:text-primary-600 transition">
                            <i data-lucide="download" class="w-4 h-4"></i>
                        </a>
                        <button wire:click="deleteDocument({{ $doc->id }})" wire:confirm="¿Eliminar este documento?" class="p-1.5 rounded-lg hover:bg-red-50 text-text-muted hover:text-danger transition">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full card text-center py-12">
                <i data-lucide="folder-open" class="w-12 h-12 mx-auto mb-3 text-text-muted opacity-40"></i>
                <h3 class="text-lg font-semibold text-text-primary mb-1">No hay documentos</h3>
                <p class="text-sm text-text-muted mb-4">Sube tu primer documento al repositorio</p>
                <button wire:click="openUploadModal" class="btn-primary">
                    <i data-lucide="upload" class="w-4 h-4"></i>Subir Documento
                </button>
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $documents->links() }}</div>

    {{-- Upload Modal --}}
    @if($showUploadModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('showUploadModal', false)"></div>
            <div class="relative bg-surface-card rounded-2xl shadow-xl w-full max-w-lg">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-text-primary">Subir Documento</h2>
                    <button wire:click="$set('showUploadModal', false)" class="p-1 rounded-lg hover:bg-surface-hover">
                        <i data-lucide="x" class="w-5 h-5 text-text-muted"></i>
                    </button>
                </div>
                <form wire:submit="uploadDocument" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1.5">Nombre del documento *</label>
                        <input wire:model="docName" type="text" class="input" placeholder="Ej. Contrato principal - Fase 1">
                        @error('docName') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1.5">Proyecto *</label>
                            <x-custom-select 
                                wire:model="docProjectId" 
                                :options="$projects->pluck('name', 'id')->toArray()" 
                                placeholder="Seleccionar..." 
                            />
                            @error('docProjectId') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1.5">Categoría *</label>
                            <x-custom-select 
                                wire:model="docCategory" 
                                :options="$categoryLabels" 
                                placeholder="" 
                            />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1.5">Archivo *</label>
                        <input wire:model="docFile" type="file" class="input text-sm" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.dwg,.dxf">
                        <p class="mt-1 text-xs text-text-muted">PDF, DOC, XLS, JPG, PNG, DWG. Máximo 50 MB. Si sube un documento con nombre duplicado, se incrementará la versión automáticamente.</p>
                        @error('docFile') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror

                        <div wire:loading wire:target="docFile" class="mt-2 flex items-center gap-2 text-xs text-primary-600">
                            <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/>
                                <path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor" class="opacity-75"/>
                            </svg>
                            Subiendo archivo...
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="$set('showUploadModal', false)" class="btn-secondary">Cancelar</button>
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.class="opacity-0" wire:target="uploadDocument" class="transition-opacity">Subir Documento</span>
                            <span wire:loading wire:target="uploadDocument" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
                                <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
