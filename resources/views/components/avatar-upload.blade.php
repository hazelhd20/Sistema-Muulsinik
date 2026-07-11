@props([
    'name' => '',
    'currentUrl' => null,
    'removeModel' => 'removePhoto',
])

@php
    $modelName = $attributes->wire('model')->value() ?: 'photo';
    $inputId = $attributes->get('id', 'avatar-upload-' . $modelName);
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
        clearPhoto() {
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

    {{-- Círculo del Avatar (80x80px sin bordes) --}}
    <div class="relative w-20 h-20 rounded-full shrink-0 select-none flex items-center justify-center overflow-hidden shadow-md">
        
        {{-- 1. Previsualización Local instantánea en memoria RAM (Zero-Flicker) --}}
        <template x-if="localPreview">
            <img :src="localPreview" alt="Previsualización local" class="w-20 h-20 rounded-full object-cover">
        </template>

        {{-- 2. Previsualización remota o temporal (cuando no hay localPreview activo ni se ha marcado eliminar) --}}
        <template x-if="!localPreview && !isRemoving">
            @if ($attributes->wire('model')->value() && !empty($this->{$attributes->wire('model')->value()}))
                <img src="{{ $this->{$attributes->wire('model')->value()}->temporaryUrl() }}" alt="Previsualización" class="w-20 h-20 rounded-full object-cover">
            @elseif ($currentUrl)
                <img src="{{ $currentUrl }}" alt="Avatar actual" class="w-20 h-20 rounded-full object-cover">
            @else
                <div class="w-20 h-20 rounded-full bg-primary-600 dark:bg-primary-500 text-white flex items-center justify-center font-bold">
                    <span class="text-2xl leading-none inline-flex items-center justify-center">
                        {{ strtoupper(substr($name ?: 'U', 0, 1)) }}
                    </span>
                </div>
            @endif
        </template>

        {{-- 3. Estado Eliminado explícitamente por el usuario --}}
        <template x-if="!localPreview && isRemoving">
            <div class="w-20 h-20 rounded-full bg-primary-600 dark:bg-primary-500 text-white flex items-center justify-center font-bold">
                <span class="text-2xl leading-none inline-flex items-center justify-center">
                    {{ strtoupper(substr($name ?: 'U', 0, 1)) }}
                </span>
            </div>
        </template>

        {{-- Indicador de carga central súper suave sin bordes ni halos en modo oscuro --}}
        <div wire:loading.flex wire:target="{{ $modelName }}"
            class="absolute -inset-1 z-20 bg-black/75 items-center justify-center transition-all duration-200">
            <x-lucide-loader-2 class="w-6 h-6 text-white animate-spin shrink-0" />
        </div>
    </div>

    {{-- Botones y Leyendas informativas --}}
    <div class="flex-1 text-center sm:text-left space-y-1.5">
        <div>
            <p class="text-small font-semibold text-text-primary">Fotografía de perfil</p>
            <p class="text-xs text-text-muted">Formatos admitidos: JPG, PNG, WebP. Máximo 2MB.</p>
        </div>
        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 pt-1">
            <input type="file"
                {{ $attributes->wire('model') }}
                id="{{ $inputId }}"
                accept="image/jpeg,image/png,image/webp"
                @click="$event.target.value = null"
                @change="handleFileSelect($event)"
                class="hidden">

            <label for="{{ $inputId }}"
                wire:loading.class="pointer-events-none opacity-60" wire:target="{{ $modelName }}"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-[4px] border border-border bg-surface text-text-primary hover:bg-surface-hover text-xs font-medium cursor-pointer transition-colors shadow-2xs">
                <x-lucide-camera wire:loading.remove wire:target="{{ $modelName }}" class="w-3.5 h-3.5 text-text-muted shrink-0" />
                <x-lucide-loader-2 wire:loading wire:target="{{ $modelName }}" class="w-3.5 h-3.5 text-primary-600 animate-spin shrink-0" />
                <span wire:loading.remove wire:target="{{ $modelName }}">
                    <template x-if="localPreview || (!isRemoving && @js((bool)$currentUrl))"><span>Cambiar foto</span></template>
                    <template x-if="!localPreview && (isRemoving || !@js((bool)$currentUrl))"><span>Subir foto</span></template>
                </span>
                <span wire:loading wire:target="{{ $modelName }}">Subiendo...</span>
            </label>

            <template x-if="localPreview || (!isRemoving && @js((bool)$currentUrl))">
                <button type="button" @click="clearPhoto()"
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
