@props(['label' => '', 'error' => null, 'hint' => null, 'required' => false])

<div {{ $attributes->class([$error ? 'has-error' : '']) }}>
    @if($label)
        <label class="label">
            {{ $label }}
            @if($required)
                {{-- aria-hidden: el asterisco es visual; el atributo required/aria-required
                     debe estar en el input mismo para tecnologías asistivas --}}
                <span class="text-danger ml-0.5 font-medium" aria-hidden="true">*</span>
            @endif
        </label>
    @endif
    {{ $slot }}
    @if($hint && !$error)
        <p class="mt-1 text-xs-fluid text-text-muted">{{ $hint }}</p>
    @endif
    @if($error)
        <p class="mt-1 text-xs-fluid text-danger flex items-center gap-1">
            <i data-lucide="circle-alert" class="w-3 h-3 shrink-0"></i>
            {{ $error }}
        </p>
    @endif
</div>
