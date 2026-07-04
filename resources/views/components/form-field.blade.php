@props(['label' => '', 'error' => null, 'hint' => null, 'required' => false, 'for' => null])

@php
    $inputId = $for ?? $attributes->whereStartsWith('wire:model')->first() ?? $attributes->get('name');
@endphp

<div {{ $attributes->class([$error ? 'has-error' : '']) }}>
    @if($label)
        <label class="label" @if($inputId) for="{{ $inputId }}" @endif>
            {{ $label }}
            @if($required)
                {{-- aria-hidden: el asterisco es visual; el atributo required/aria-required
                     debe estar en el input mismo para tecnologías asistivas --}}
                <span class="text-danger ml-0.5 font-medium" aria-hidden="true">*</span>
            @endif
        </label>
    @endif
    
    {{ $slot }}
    
    @if($hint)
        <p class="mt-1.5 text-xs-fluid text-text-muted">{{ $hint }}</p>
    @endif
    
    @if($error)
        <div x-data="{ show: true }" x-show="show" x-transition.opacity.duration.200ms>
            <p class="mt-1.5 text-xs-fluid text-danger flex items-center gap-1 font-medium">
                <x-lucide-circle-alert class="w-3.5 h-3.5 shrink-0" />
                <span>{{ $error }}</span>
            </p>
        </div>
    @endif
</div>
