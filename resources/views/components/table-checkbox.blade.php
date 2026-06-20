@props(['disabled' => false, 'label' => 'Seleccionar fila'])

<input type="checkbox" aria-label="{{ $label }}" {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'rounded-[4px] border-border text-primary-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/30 focus-visible:border-primary-500 transition-all duration-200 ease-in-out w-4 h-4 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed']) !!}>
