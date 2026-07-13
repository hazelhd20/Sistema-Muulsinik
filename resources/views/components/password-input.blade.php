@props([
    'id' => null,
    'placeholder' => '••••••••',
    'autocomplete' => 'current-password',
])

<div x-data="{ show: false }" class="relative w-full">
    <input
        :type="show ? 'text' : 'password'"
        @if($id || $attributes->whereStartsWith('wire:model')->first() || $attributes->whereStartsWith('x-model')->first())
            id="{{ $id ?? $attributes->whereStartsWith('wire:model')->first() ?? $attributes->whereStartsWith('x-model')->first() }}"
        @endif
        aria-label="{{ $attributes->get('aria-label') ?? 'Contraseña' }}"
        placeholder="{{ $placeholder }}"
        autocomplete="{{ $autocomplete }}"
        {{ $attributes->merge(['class' => 'input pr-10']) }}
    >
    <button
        type="button"
        @click="show = !show"
        tabindex="-1"
        :title="show ? 'Ocultar contraseña' : 'Mostrar contraseña'"
        :aria-label="show ? 'Ocultar contraseña' : 'Mostrar contraseña'"
        class="absolute inset-y-0 right-0 flex items-center pr-3 text-text-muted hover:text-text-primary transition-colors duration-150 cursor-pointer focus:outline-none"
    >
        {{-- Eye Off Icon (cuando la contraseña está visible) --}}
        <x-lucide-eye-off x-show="show" x-cloak class="w-4 h-4 shrink-0" aria-hidden="true" />
        {{-- Eye Icon (cuando la contraseña está oculta) --}}
        <x-lucide-eye x-show="!show" x-cloak class="w-4 h-4 shrink-0" aria-hidden="true" />
    </button>
</div>
