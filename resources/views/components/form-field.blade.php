@props(['label' => '', 'error' => null, 'hint' => null, 'required' => false])

<div {{ $attributes->class([$error ? 'has-error' : '']) }}>
    @if($label)
        <label class="label">{{ $label }}@if($required) *@endif</label>
    @endif
    {{ $slot }}
    @if($hint && !$error)
        <p class="mt-1 text-xs-fluid text-text-muted">{{ $hint }}</p>
    @endif
    @if($error)
        <p class="mt-1 text-xs-fluid text-danger">{{ $error }}</p>
    @endif
</div>
