@props(['icon' => 'inbox', 'title' => '', 'message' => ''])

<div {{ $attributes->class(['flex flex-col items-center justify-center p-8 md:p-12 text-center rounded-2xl border-2 border-dashed border-border bg-surface-main/30']) }}>
    <div class="w-14 h-14 rounded-2xl bg-surface-card border border-border shadow-sm flex items-center justify-center mb-4">
        <x-dynamic-component :component="'lucide-' . $icon" class="w-7 h-7 text-text-muted" />
    </div>
    @if($title)
        <h3 class="text-h3 font-semibold text-text-primary mb-1">{{ $title }}</h3>
    @endif
    @if($message)
        <p class="text-body text-text-muted max-w-sm">{{ $message }}</p>
    @endif
    @if(isset($actions))
        <div class="mt-6">{{ $actions }}</div>
    @elseif($slot->isNotEmpty())
        <div class="mt-6">{{ $slot }}</div>
    @endif
</div>
