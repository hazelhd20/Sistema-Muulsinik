@props([
    'currentUrl' => null,
    'removeModel' => 'removeImage',
    'label' => 'Imagen o Logotipo',
    'helper' => 'Formatos admitidos: JPG, PNG, SVG o WebP. Máximo 2MB.',
    'shape' => 'rectangular', // 'rectangular' | 'square'
    'fit' => 'contain', // 'contain' | 'cover'
])

@php
    $modelName = $attributes->wire('model')->value() ?: 'image';
    $inputId = $attributes->get('id', 'image-upload-' . $modelName);
    $containerClasses = $shape === 'square' ? 'w-28 h-28' : 'h-24 w-auto min-w-[160px] max-w-[240px]';
    $fitClasses = $fit === 'cover' ? 'object-cover' : 'object-contain';
@endphp

<div x-data="{
        localPreview: null,
        currentFileKey: null,
        isRemoving: false,
        handleFileSelect(event) {
            const file = event.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const newKey = file.name + '_' + file.size + '_' + file.lastModified;
                if (this.currentFileKey === newKey && this.localPreview) {
                    return;
                }
                this.currentFileKey = newKey;
                this.localPreview = URL.createObjectURL(file);
                this.isRemoving = false;
            }
        },
        clearImage() {
            this.localPreview = null;
            this.currentFileKey = null;
            this.isRemoving = true;
            $wire.set('{{ $removeModel }}', true);
            $wire.set('{{ $modelName }}', null);
            const input = document.getElementById('{{ $inputId }}');
            if (input) input.value = '';
        }
     }"
     wire:ignore.self
     x-init="
        $watch('$wire.{{ $modelName }}', value => {
            if (!value && !localPreview) {
                localPreview = null;
                currentFileKey = null;
            }
        });
        $watch('$wire.{{ $removeModel }}', value => {
            if (!value) {
                isRemoving = false;
                localPreview = null;
                currentFileKey = null;
            } else {
                isRemoving = true;
                localPreview = null;
                currentFileKey = null;
            }
        });
     "
     class="flex flex-col sm:flex-row items-center gap-4 p-4 rounded-lg bg-surface-alt border border-border">

    {{-- Caja del Logotipo / Imagen con GPU Masking para evitar subpíxel fringe --}}
    <div class="relative {{ $containerClasses }} rounded-lg shrink-0 select-none flex items-center justify-center overflow-hidden [mask-image:radial-gradient(circle,white_100%,transparent_100%)] [-webkit-mask-image:radial-gradient(circle,white_100%,transparent_100%)] [transform:translateZ(0)] border border-dashed border-border bg-surface p-2 shadow-sm">
        
        {{-- 1. Previsualización Local instantánea en memoria RAM (Zero-Flicker) --}}
        <template x-if="localPreview">
            <img :src="localPreview" alt="Previsualización local" class="w-full h-full {{ $fitClasses }}">
        </template>

        {{-- 2. Previsualización remota o temporal --}}
        <template x-if="!localPreview && !isRemoving">
            @if ($attributes->wire('model')->value() && !empty($this->{$attributes->wire('model')->value()}))
                <img src="{{ $this->{$attributes->wire('model')->value()}->temporaryUrl() }}" alt="Previsualización" class="w-full h-full {{ $fitClasses }}">
            @elseif ($currentUrl)
                <img src="{{ $currentUrl }}" alt="Imagen actual" class="w-full h-full {{ $fitClasses }}">
            @else
                <div class="flex flex-col items-center justify-center text-text-muted gap-1 text-center">
                    <x-lucide-image class="w-7 h-7 text-text-muted/60 stroke-[1.5]" />
                    <span class="text-[10px] font-medium leading-tight">Sin imagen</span>
                </div>
            @endif
        </template>

        {{-- 3. Estado Eliminado explícitamente por el usuario --}}
        <template x-if="!localPreview && isRemoving">
            <div class="flex flex-col items-center justify-center text-text-muted gap-1 text-center">
                <x-lucide-image class="w-7 h-7 text-text-muted/60 stroke-[1.5]" />
                <span class="text-[10px] font-medium leading-tight">Sin imagen</span>
            </div>
        </template>

        {{-- Indicador de carga central súper suave sin bordes ni halos en modo oscuro --}}
        <div wire:loading.flex wire:target="{{ $modelName }}"
            class="absolute inset-0 z-20 w-full h-full bg-black/75 items-center justify-center transition-all duration-200">
            <x-lucide-loader-2 class="w-6 h-6 text-white animate-spin shrink-0" />
        </div>
    </div>

    {{-- Botones y Leyendas informativas --}}
    <div class="flex-1 text-center sm:text-left space-y-1.5">
        <div>
            <p class="text-small font-semibold text-text-primary">{{ $label }}</p>
            <p class="text-xs text-text-muted">{{ $helper }}</p>
        </div>
        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 pt-1">
            <input type="file"
                {{ $attributes->wire('model') }}
                id="{{ $inputId }}"
                accept="{{ $attributes->get('accept', 'image/jpeg,image/png,image/svg+xml,image/webp') }}"
                @click="$event.target.value = null"
                @change="handleFileSelect($event)"
                class="hidden">

            <label for="{{ $inputId }}"
                wire:loading.class="pointer-events-none opacity-60" wire:target="{{ $modelName }}"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-[4px] border border-border bg-surface text-text-primary hover:bg-surface-hover text-xs font-medium cursor-pointer transition-colors shadow-2xs">
                <x-lucide-upload wire:loading.remove wire:target="{{ $modelName }}" class="w-3.5 h-3.5 text-text-muted shrink-0" />
                <x-lucide-loader-2 wire:loading wire:target="{{ $modelName }}" class="w-3.5 h-3.5 text-primary-600 animate-spin shrink-0" />
                <span wire:loading.remove wire:target="{{ $modelName }}">
                    <template x-if="localPreview || (!isRemoving && @js((bool)$currentUrl))"><span>Cambiar imagen</span></template>
                    <template x-if="!localPreview && (isRemoving || !@js((bool)$currentUrl))"><span>Subir imagen</span></template>
                </span>
                <span wire:loading wire:target="{{ $modelName }}">Subiendo...</span>
            </label>

            <template x-if="localPreview || (!isRemoving && @js((bool)$currentUrl))">
                <button type="button" @click="clearImage()"
                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-[4px] text-xs font-medium text-danger hover:bg-danger/10 transition-colors cursor-pointer">
                    <x-lucide-trash-2 class="w-3.5 h-3.5 shrink-0" />
                    <span>Eliminar</span>
                </button>
            </template>
        </div>
        @error($modelName)
            <p class="text-xs text-danger font-medium mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>
