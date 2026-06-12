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
])

@php
    $wireModel = $attributes->wire('model')->value();
@endphp

<div
    x-data="{
        isDragging: false,
        fileName: null,
        fileSize: null,
        fileExt: null,
        uploading: false,
        progress: 0,
        showPreview: false,
        previewUrl: null,
        previewType: null,
        get iconData() {
            const ext = (this.fileExt || '').toLowerCase();
            const map = {
                jpg:  { icon: 'image',            color: 'text-info',            bg: 'bg-info-light' },
                jpeg: { icon: 'image',            color: 'text-info',            bg: 'bg-info-light' },
                png:  { icon: 'image',            color: 'text-info',            bg: 'bg-info-light' },
                svg:  { icon: 'image',            color: 'text-info',            bg: 'bg-info-light' },
                pdf:  { icon: 'file-text',        color: 'text-danger',         bg: 'bg-danger-light' },
                xlsx: { icon: 'file-spreadsheet', color: 'text-primary-600',    bg: 'bg-primary-50' },
                xls:  { icon: 'file-spreadsheet', color: 'text-primary-600',    bg: 'bg-primary-50' },
            };
            return map[ext] || { icon: 'file', color: 'text-text-secondary', bg: 'bg-surface-hover' };
        },
        handleFile(e) {
            const file = e.target.files[0];
            if (!file) return;
            this.fileName = file.name;
            this.fileSize = (file.size / 1024).toFixed(1);
            this.fileExt  = file.name.split('.').pop();
            if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
            this.previewUrl = URL.createObjectURL(file);
            this.previewType = file.type;
            this.$nextTick(() => this.renderFileIcon());
        },
        get canPreview() {
            if (!this.previewType) return false;
            return this.previewType.startsWith('image/') || this.previewType === 'application/pdf';
        },
        openPreview() {
            if (this.canPreview) this.showPreview = true;
        },
        closePreview() {
            this.showPreview = false;
        },
        // El renderizado de iconos ahora lo hace Alpine con x-show en la vista
        renderFileIcon() {},
        resetState() {
            this.fileName = null;
            this.fileSize = null;
            this.fileExt = null;
            if (this.$refs.fileInput) this.$refs.fileInput.value = '';
            if (this.previewUrl) { URL.revokeObjectURL(this.previewUrl); this.previewUrl = null; this.previewType = null; }
            this.showPreview = false;
        },
        removeFile() {
            this.resetState();
            @if($wireModel)
                @this.set('{{ $wireModel }}', null);
            @endif
            this.$dispatch('file-removed');
        },
        init() {
            @if($wireModel)
                if (this.$wire) {
                    this.$watch('$wire.{{ $wireModel }}', (value) => {
                        if (!value) this.resetState();
                    });
                }
            @endif
        }
    }"
    {{ $attributes->whereDoesntStartWith('wire:model')->except(['accept','maxSize','hint','variant','formats','icon','title','subtitle','inputId']) }}
>
    {{-- ── Hidden real input ── --}}
    <input
        x-ref="fileInput"
        id="{{ $inputId }}"
        type="file"
        wire:model="{{ $wireModel }}"
        accept="{{ $accept }}"
        class="hidden"
        @change="handleFile($event)"
        x-on:livewire-upload-start="uploading = true; progress = 0"
        x-on:livewire-upload-progress="progress = $event.detail.progress"
        x-on:livewire-upload-finish="uploading = false; $nextTick(() => renderFileIcon())"
        x-on:livewire-upload-error="uploading = false"
    >

    @if($variant === 'dropzone')
        {{-- ═══════ DROPZONE VARIANT ═══════ --}}
        <div
            x-on:dragover.prevent="isDragging = true"
            x-on:dragleave.prevent="isDragging = false"
            x-on:drop.prevent="isDragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))"
            @click="$refs.fileInput.click()"
            class="relative border-2 border-dashed rounded-2xl p-12 text-center transition-all duration-300 cursor-pointer"
            :class="isDragging
                ? 'border-primary-500 bg-primary-50/50 scale-[1.02]'
                : 'border-gray-200 hover:border-primary-300 hover:bg-primary-50/20'"
        >
            <div class="flex flex-col items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-primary-50 flex items-center justify-center">
                    <x-dynamic-component :component="'lucide-' . $icon" class="w-8 h-8 text-primary-600" wire:ignore />
                </div>
                <div>
                    <p class="text-h3 text-text-primary">{{ $title }}</p>
                    <p class="text-body text-text-muted mt-1">{{ $subtitle }}</p>
                </div>

                @if(!empty($formats))
                    <div class="flex items-center gap-3 mt-2">
                        @foreach($formats as $fmt)
                            @php
                                $fmtColors = match(strtoupper($fmt)) {
                                    'PDF'          => 'bg-danger-light text-danger',
                                    'XLSX', 'XLS'  => 'bg-primary-50 text-primary-600',
                                    'JPG', 'PNG', 'JPEG' => 'bg-info-light text-info',
                                    default        => 'bg-surface-hover text-text-secondary',
                                };
                            @endphp
                            <span class="px-3 py-1 rounded-lg {{ $fmtColors }} text-xs font-medium">{{ strtoupper($fmt) }}</span>
                        @endforeach
                    </div>
                @endif

                <p class="text-xs text-text-muted mt-1">Máximo {{ $maxSize }}</p>
            </div>
        </div>

        {{-- File selected card (dropzone) --}}
        <div
            x-show="fileName"
            x-cloak
            x-effect="if (fileExt) $nextTick(() => renderFileIcon())"
            class="mt-4 p-3.5 rounded-xl bg-surface-card border border-border shadow-sm"
        >
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0"
                     :class="iconData.bg">
                    <div :class="iconData.color" class="flex items-center justify-center">
                        <x-lucide-image x-show="iconData.icon === 'image'" class="w-5 h-5" x-cloak />
                        <x-lucide-file-text x-show="iconData.icon === 'file-text'" class="w-5 h-5" x-cloak />
                        <x-lucide-file-spreadsheet x-show="iconData.icon === 'file-spreadsheet'" class="w-5 h-5" x-cloak />
                        <x-lucide-file x-show="iconData.icon === 'file'" class="w-5 h-5" x-cloak />
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
                    <button x-show="canPreview && !uploading" type="button" @click="openPreview()"
                        class="p-1.5 rounded-lg hover:bg-surface-hover text-text-muted hover:text-primary-600 transition" title="Vista previa">
                        <x-lucide-eye class="w-4 h-4" wire:ignore />
                    </button>
                    <button type="button" @click="removeFile()"
                        class="p-1.5 rounded-lg hover:bg-surface-hover text-text-muted hover:text-danger transition">
                        <x-lucide-x class="w-4 h-4" wire:ignore />
                    </button>
                </div>
            </div>
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
