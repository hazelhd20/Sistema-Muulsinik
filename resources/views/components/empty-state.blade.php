@props(['icon' => 'inbox', 'title' => '', 'message' => ''])

<div {{ $attributes->class(['text-center py-12']) }}>
    <div class="w-12 h-12 rounded-xl bg-surface-main flex items-center justify-center mx-auto mb-3">
        <i data-lucide="{{ $icon }}" class="w-6 h-6 text-text-muted opacity-40"></i>
    </div>
    @if($title)
        <p class="text-small font-medium text-text-secondary mb-0.5">{{ $title }}</p>
    @endif
    @if($message)
        <p class="text-xs-fluid text-text-muted">{{ $message }}</p>
    @endif
    @if($slot->isNotEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
