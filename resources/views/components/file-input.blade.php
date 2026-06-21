@props([
    'accept'      => '.jpg,.jpeg,.png,.pdf',
    'maxSize'     => '20 MB',
    'hint'        => '',
    'variant'     => 'compact',
    'formats'     => [],
    'icon'        => 'upload-cloud',
    'title'       => 'Arrastra tu archivo aquí',
    'subtitle'    => 'o haz clic para seleccionar',
    'inputId'     => 'file-input-' . uniqid(),
    'multiple'    => false,
])

@php
    $wireModel = $attributes->wire('model')->value();
@endphp

<div
    x-data="{
        isDragging: false,
        files: [],
        uploading: false,
        progress: 0,
        showPreview: false,
        previewUrl: null,
        previewType: null,
        
        get fileName() { return this.files.length ? this.files[0].name : null; },
        get fileSize() { return this.files.length ? this.files[0].size : null; },
        get fileExt() { return this.files.length ? this.files[0].ext : null; },
        get iconData() { return this.getIconData(this.fileExt); },
        
        getIconData(ext) {
            const e = (ext || '').toLowerCase();
            const map = {
                jpg:  { icon: 'image',            color: 'text-info',            bg: 'bg-info-light' },
                jpeg: { icon: 'image',            color: 'text-info',            bg: 'bg-info-light' },
                png:  { icon: 'image',            color: 'text-info',            bg: 'bg-info-light' },
                svg:  { icon: 'image',            color: 'text-info',            bg: 'bg-info-light' },
                pdf:  { icon: 'file-text',        color: 'text-danger',         bg: 'bg-danger-light' },
                xlsx: { icon: 'file-spreadsheet', color: 'text-success',        bg: 'bg-success-light' },
                xls:  { icon: 'file-spreadsheet', color: 'text-success',        bg: 'bg-success-light' },
            };
            return map[e] || { icon: 'file', color: 'text-text-secondary', bg: 'bg-surface-hover' };
        },
        
        handleFile(e) {
            const selectedFiles = Array.from(e.target.files);
            if (!selectedFiles.length) return;
            
            const isMultiple = {{ $multiple ? 'true' : 'false' }};
            const newFiles = selectedFiles.map(file => ({
                name: file.name,
                size: (file.size / 1024).toFixed(1),
                ext: file.name.split('.').pop(),
                type: file.type,
                url: URL.createObjectURL(file),
                originalFile: file
            }));

            if (isMultiple) {
                // Filtrar archivos que ya estén en la lista (mismo nombre y tamaño)
                const existingNames = this.files.map(f => f.name + '_' + f.size);
                const uniqueNewFiles = newFiles.filter(f => !existingNames.includes(f.name + '_' + f.size))
                                               .map(f => ({ ...f, isUploading: true }));
                
                this.files.push(...uniqueNewFiles);
            } else {
                this.files.forEach(f => { if(f.url) URL.revokeObjectURL(f.url); });
                this.files = [ { ...newFiles[0], isUploading: true } ];
            }
        },
        
        canPreview(fileObj) {
            if (!fileObj.type) return false;
            return fileObj.type.startsWith('image/') || fileObj.type === 'application/pdf';
        },
        
        openPreview(fileObj) {
            if (this.canPreview(fileObj)) {
                this.previewUrl = fileObj.url;
                this.previewType = fileObj.type;
                this.showPreview = true;
            }
        },
        
        closePreview() {
            this.showPreview = false;
        },
        
        resetState() {
            this.files.forEach(f => { if(f.url) URL.revokeObjectURL(f.url); });
            this.files = [];
            if (this.$refs.fileInput) this.$refs.fileInput.value = '';
            this.showPreview = false;
            this.previewUrl = null;
            this.previewType = null;
        },
        
        removeFile(index = 0) {
            const file = this.files[index];
            if (!file) return;
            this.files.splice(index, 1);
            
            @if($wireModel)
                if (this.$wire) {
                    if ({{ $multiple ? 'true' : 'false' }}) {
                        this.$wire.dispatch('file-removed', { index: index, name: file.name });
                    } else {
                        @this.set('{{ $wireModel }}', null);
                        this.$wire.dispatch('file-removed');
                    }
                }
            @else
                this.$dispatch('file-removed', { index: index, name: file.name });
            @endif
            
            if (this.files.length === 0) {
                this.resetState();
            }
        },
        
        init() {
            @if($wireModel)
                if (this.$wire) {
                    this.$watch('$wire.{{ $wireModel }}', (value) => {
                        if (!{{ $multiple ? 'true' : 'false' }}) {
                            if (!value || (Array.isArray(value) && value.length === 0)) {
                                this.resetState();
                            }
                        }
                    });
                }
            @endif
        }
    }"
    {{ $attributes->whereDoesntStartWith('wire:model')->except(['accept','maxSize','hint','variant','formats','icon','title','subtitle','inputId', 'multiple']) }}
>
    {{-- ── Hidden real input ── --}}
    <input
        x-ref="fileInput"
        id="{{ $inputId }}"
        type="file"
        {{ $multiple ? 'multiple' : '' }}
        wire:model="{{ $wireModel }}"
        accept="{{ $accept }}"
        class="hidden"
        @change="handleFile($event)"
        x-on:livewire-upload-start="uploading = true; progress = 0"
        x-on:livewire-upload-progress="progress = $event.detail.progress"
        x-on:livewire-upload-finish="uploading = false; files.forEach(f => f.isUploading = false);"
        x-on:livewire-upload-error="uploading = false; files.forEach(f => f.isUploading = false);"
    >

    @if($variant === 'dropzone')
        {{-- ═══════ DROPZONE VARIANT ═══════ --}}
        <div
            x-on:dragover.prevent="if(!uploading) isDragging = true"
            x-on:dragleave.prevent="isDragging = false"
            x-on:drop.prevent="if(uploading) return; isDragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))"
            @click="if(!uploading) $refs.fileInput.click()"
            class="relative border-2 border-dashed rounded-2xl p-10 text-center transition-all duration-300 cursor-pointer"
            :class="isDragging
                ? 'border-primary-500 bg-primary-50/30 scale-[1.02] shadow-sm'
                : (uploading ? 'border-border bg-surface-main/50 cursor-not-allowed opacity-70' : 'border-border-strong hover:border-primary-400 hover:bg-surface-main/30')"
        >
            <div class="flex flex-col items-center gap-4">
                <div class="w-14 h-14 rounded-full bg-primary-50 flex items-center justify-center transition-transform duration-300" :class="isDragging ? '-translate-y-1' : ''">
                    <x-dynamic-component :component="'lucide-' . $icon" class="w-6 h-6 text-primary-600" stroke-width="1.5" wire:ignore />
                </div>
                <div>
                    <p class="text-body font-medium text-text-primary tracking-tight">{{ $title }}</p>
                    <p class="text-small text-text-muted mt-1 font-light">{{ $subtitle }}</p>
                </div>

                @if(!empty($formats))
                    <div class="flex items-center gap-2 mt-2">
                        @foreach($formats as $fmt)
                            @php
                                $fmtColors = match(strtoupper($fmt)) {
                                    'PDF'          => 'bg-danger-light text-danger',
                                    'XLSX', 'XLS'  => 'bg-success-light text-success',
                                    'JPG', 'PNG', 'JPEG' => 'bg-info-light text-info',
                                    default        => 'bg-surface-main text-text-muted',
                                };
                                $fmtIcon = match(strtoupper($fmt)) {
                                    'PDF'          => 'file-text',
                                    'XLSX', 'XLS'  => 'file-spreadsheet',
                                    'JPG', 'PNG', 'JPEG' => 'image',
                                    default        => 'file',
                                };
                            @endphp
                            <span class="px-2.5 py-1 rounded-md {{ $fmtColors }} text-[10px] font-medium tracking-wide flex items-center gap-1">
                                <x-dynamic-component :component="'lucide-' . $fmtIcon" class="w-3 h-3" stroke-width="2" wire:ignore />
                                {{ strtoupper($fmt) }}
                            </span>
                        @endforeach
                    </div>
                @endif

                <p class="text-[11px] text-text-muted mt-2 font-medium tracking-wide uppercase">Máximo {{ $maxSize }}</p>
            </div>
        </div>

        {{-- File selected cards (dropzone) --}}
        <div class="flex flex-col gap-3 mt-4" wire:ignore>
            <template x-for="(file, index) in files" :key="file.name + '_' + file.size">
                <div class="py-3 px-4 rounded-xl bg-surface-main transition-all animate-fade-in-up">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0"
                             :class="getIconData(file.ext).bg">
                            <div :class="getIconData(file.ext).color" class="flex items-center justify-center">
                                <x-lucide-image x-show="getIconData(file.ext).icon === 'image'" class="w-6 h-6" x-cloak />
                                <x-lucide-file-text x-show="getIconData(file.ext).icon === 'file-text'" class="w-6 h-6" x-cloak />
                                <x-lucide-file-spreadsheet x-show="getIconData(file.ext).icon === 'file-spreadsheet'" class="w-6 h-6" x-cloak />
                                <x-lucide-file x-show="getIconData(file.ext).icon === 'file'" class="w-6 h-6" x-cloak />
                            </div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-small font-semibold text-text-primary truncate tracking-tight" x-text="file.name"></p>
                            <p x-show="!file.isUploading" class="text-xs text-text-muted mt-0.5 font-medium"><span x-text="file.size"></span> KB</p>
                            <div x-show="file.isUploading" class="flex items-center gap-3 mt-1.5">
                                <div class="flex-1 h-1.5 bg-primary-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-primary-600 rounded-full transition-all duration-300" :style="'width:' + progress + '%'"></div>
                                </div>
                                <span class="text-[11px] text-primary-600 font-bold tabular-nums shrink-0" x-text="progress + '%'"></span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0 pl-2">
                            <button x-show="canPreview(file) && !file.isUploading" type="button" @click.stop="openPreview(file)"
                                class="btn-icon-primary" title="Vista previa">
                                <x-lucide-eye class="w-4 h-4" wire:ignore />
                            </button>
                            <button x-show="!file.isUploading" type="button" @click.stop="removeFile(index)"
                                class="btn-icon-danger" title="Eliminar archivo">
                                <x-lucide-trash-2 class="w-4 h-4" wire:ignore />
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

    @elseif($variant === 'compact-inline')
        {{-- ═══════ COMPACT-INLINE VARIANT (thumbnail preview beside picker) ═══════ --}}
        <div class="flex items-center gap-3">
            {{-- Inline thumbnail --}}
            <div x-show="fileName && previewUrl && previewType && previewType.startsWith('image/')" x-cloak class="relative shrink-0">
                <img :src="previewUrl" class="h-16 w-auto max-w-[10rem] object-contain border border-border rounded-lg p-1 bg-surface-card" />
                <button type="button" @click="removeFile()"
                    class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-surface-card border border-border shadow-sm flex items-center justify-center text-danger hover:bg-surface-hover transition">
                    <x-lucide-x class="w-3.5 h-3.5" wire:ignore />
                </button>
            </div>

            {{-- Non-image file selected --}}
            <div x-show="fileName && !(previewType && previewType.startsWith('image/'))" x-cloak class="relative shrink-0">
                <div class="h-16 w-16 rounded-lg flex items-center justify-center border border-border bg-surface-card"
                     :class="iconData.bg">
                    <div :class="iconData.color" class="flex items-center justify-center">
                        <x-lucide-image x-show="iconData.icon === 'image'" class="w-6 h-6" x-cloak />
                        <x-lucide-file-text x-show="iconData.icon === 'file-text'" class="w-6 h-6" x-cloak />
                        <x-lucide-file-spreadsheet x-show="iconData.icon === 'file-spreadsheet'" class="w-6 h-6" x-cloak />
                        <x-lucide-file x-show="iconData.icon === 'file'" class="w-6 h-6" x-cloak />
                    </div>
                </div>
                <button type="button" @click="removeFile()"
                    class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-surface-card border border-border shadow-sm flex items-center justify-center text-danger hover:bg-surface-hover transition">
                    <x-lucide-x class="w-3.5 h-3.5" wire:ignore />
                </button>
            </div>

            {{-- File picker / upload info --}}
            <div class="flex-1 min-w-0">
                {{-- Picker area --}}
                <div
                    x-on:dragover.prevent="isDragging = true"
                    x-on:dragleave.prevent="isDragging = false"
                    x-on:drop.prevent="isDragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))"
                    @click="$refs.fileInput.click()"
                    class="border-2 border-dashed rounded-xl px-3 py-2.5 transition-all duration-200 cursor-pointer"
                    :class="isDragging
                        ? 'border-primary-500 bg-primary-50/50'
                        : (fileName
                            ? 'border-border bg-surface-card hover:border-primary-300 shadow-sm'
                            : 'border-gray-200 hover:border-primary-300 hover:bg-primary-50/20')"
                >
                    <div class="flex items-center gap-2">
                        <x-lucide-upload class="w-4 h-4 text-primary-600 shrink-0" wire:ignore />
                        <div class="min-w-0">
                            <p class="text-body text-text-primary" x-text="fileName ? 'Cambiar archivo' : 'Seleccionar archivo'"></p>
                            <p x-show="!uploading" class="text-xs text-text-muted">
                                {{ $hint ?: strtoupper(str_replace('.', '', str_replace(',', ', ', $accept))) . '. Máximo ' . $maxSize . '.' }}
                            </p>
                            <div x-show="uploading" x-cloak class="flex items-center gap-2 mt-0.5">
                                <div class="flex-1 h-1.5 bg-primary-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-primary-600 rounded-full transition-all duration-300" :style="'width:' + progress + '%'"></div>
                                </div>
                                <span class="text-xs text-primary-600 font-medium tabular-nums shrink-0" x-text="progress + '%'"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    @else
        {{-- ═══════ COMPACT VARIANT (default) ═══════ --}}
        <div
            x-on:dragover.prevent="isDragging = true"
            x-on:dragleave.prevent="isDragging = false"
            x-on:drop.prevent="isDragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))"
            @click="$refs.fileInput.click()"
            class="group relative border-2 border-dashed rounded-xl px-4 py-3 transition-all duration-200 cursor-pointer"
            :class="isDragging
                ? 'border-primary-500 bg-primary-50/50'
                : (fileName
                    ? 'border-border bg-surface-card shadow-sm'
                    : 'border-gray-200 hover:border-primary-300 hover:bg-primary-50/20')"
        >
            {{-- Empty state --}}
            <div x-show="!fileName" class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-primary-50 flex items-center justify-center shrink-0">
                    <x-lucide-upload class="w-4 h-4 text-primary-600" wire:ignore />
                </div>
                <div class="min-w-0">
                    <p class="text-body text-text-primary">Seleccionar archivo</p>
                    <p class="text-xs text-text-muted">
                        {{ $hint ?: strtoupper(str_replace('.', '', str_replace(',', ', ', $accept))) . '. Máximo ' . $maxSize . '.' }}
                    </p>
                </div>
            </div>

            {{-- File selected --}}
            <div x-show="fileName" x-cloak class="flex items-center gap-3" @click.stop
                 x-effect="if (fileExt) $nextTick(() => renderFileIcon())">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
                     :class="iconData.bg">
                    <div :class="iconData.color" class="flex items-center justify-center">
                        <x-lucide-image x-show="iconData.icon === 'image'" class="w-4 h-4" x-cloak />
                        <x-lucide-file-text x-show="iconData.icon === 'file-text'" class="w-4 h-4" x-cloak />
                        <x-lucide-file-spreadsheet x-show="iconData.icon === 'file-spreadsheet'" class="w-4 h-4" x-cloak />
                        <x-lucide-file x-show="iconData.icon === 'file'" class="w-4 h-4" x-cloak />
                    </div>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-body font-medium text-text-primary truncate" x-text="fileName"></p>
                    <p x-show="!uploading" class="text-xs text-text-muted"><span x-text="fileSize"></span> KB</p>
                    <div x-show="uploading" class="flex items-center gap-2 mt-1">
                        <div class="flex-1 h-1.5 bg-primary-100 rounded-full overflow-hidden">
                            <div class="h-full bg-primary-600 rounded-full transition-all duration-300" :style="'width:' + progress + '%'"></div>
                        </div>
                        <span class="text-xs text-primary-600 font-medium tabular-nums shrink-0" x-text="progress + '%'"></span>
                    </div>
                </div>
                <div class="flex items-center gap-1 shrink-0">
                    <button x-show="canPreview && !uploading" type="button" @click.stop="openPreview()"
                        class="p-1.5 rounded-lg hover:bg-surface-hover text-text-muted hover:text-primary-600 transition" title="Vista previa">
                        <x-lucide-eye class="w-4 h-4" wire:ignore />
                    </button>
                    <button type="button" @click.stop="removeFile()"
                        class="p-1.5 rounded-lg hover:bg-surface-hover text-text-muted hover:text-danger transition">
                        <x-lucide-x class="w-4 h-4" wire:ignore />
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Preview modal (shared by dropzone + compact) --}}
    <template x-teleport="body">
        <div x-show="showPreview" x-cloak
            class="fixed inset-0 z-[300] flex items-center justify-center p-4"
            @keydown.escape.window="closePreview()">
            <div class="absolute inset-0 bg-black/60" @click="closePreview()"></div>
            <div class="relative max-w-4xl w-full max-h-[90vh] flex flex-col bg-surface-card rounded-xl border border-border shadow-xl overflow-hidden animate-scale-in">
                <div class="flex items-center justify-between px-4 py-3 border-b border-border">
                    <p class="text-body font-medium text-text-primary truncate" x-text="fileName"></p>
                    <button type="button" @click="closePreview()"
                        class="p-1.5 rounded-lg hover:bg-surface-hover text-text-muted transition">
                        <x-lucide-x class="w-4 h-4" wire:ignore />
                    </button>
                </div>
                <div class="flex-1 overflow-auto flex items-center justify-center p-4 bg-surface-main">
                    <template x-if="previewType && previewType.startsWith('image/')">
                        <img :src="previewUrl" class="max-w-full max-h-[75vh] object-contain rounded-lg" />
                    </template>
                    <template x-if="previewType === 'application/pdf'">
                        <iframe :src="previewUrl" class="w-full h-[75vh] rounded-lg border-0"></iframe>
                    </template>
                </div>
            </div>
        </div>
    </template>

    {{-- Error slot --}}
    @error($wireModel)
        <div class="mt-2 p-3 rounded-xl bg-surface-hover border border-danger/20 text-danger text-body flex items-center gap-2">
            <x-lucide-alert-circle class="w-4 h-4 shrink-0" wire:ignore />
            {{ $message }}
        </div>
    @enderror
</div>
