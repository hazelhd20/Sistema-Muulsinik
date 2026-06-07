@props(['label', 'value'])

<div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md border border-border bg-surface-card text-xs-fluid font-medium text-text-secondary shadow-sm transition-colors hover:border-border-strong">
    <span class="text-text-muted">{{ $label }}:</span>
    <span class="text-text-primary">{{ $value }}</span>
    <button {{ $attributes }} type="button" class="ml-1 text-text-muted hover:text-danger focus:outline-none transition-colors" title="Quitar filtro">
        <i data-lucide="x" class="w-3.5 h-3.5"></i>
    </button>
</div>
