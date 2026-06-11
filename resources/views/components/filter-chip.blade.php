@props(['label', 'value'])

<div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md border border-border bg-surface-card text-xs-fluid font-medium text-text-secondary shadow-sm transition-colors hover:border-border-strong">
    <span class="text-text-muted">{{ $label }}:</span>
    <span class="text-text-primary">{{ $value }}</span>
    <button
        {{ $attributes }}
        type="button"
        class="ml-1 text-text-muted hover:text-danger focus:outline-none focus-visible:ring-1 focus-visible:ring-danger rounded transition-colors"
        title="Quitar filtro de {{ $label }}"
        aria-label="Quitar filtro de {{ $label }}: {{ $value }}">
        <x-lucide-x class="w-3.5 h-3.5" aria-hidden="true" />
    </button>
</div>
