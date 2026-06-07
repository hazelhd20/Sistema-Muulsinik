@props(['disabled' => false])

<input type="checkbox" {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'rounded-md border-gray-300 accent-primary-600 focus:ring focus:ring-primary-200 focus:ring-opacity-50 transition-colors duration-150 ease-in-out w-4 h-4 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed']) !!}>
