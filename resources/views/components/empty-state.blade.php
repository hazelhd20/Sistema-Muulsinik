@props(['icon' => 'inbox', 'title' => '', 'message' => ''])

<div {{ $attributes->class(['flex flex-col items-center justify-center py-12 text-center']) }}>
    <div class="w-12 h-12 rounded-xl bg-surface-main flex items-center justify-center mb-3">
        <x-dynamic-component :component="'lucide-' . $icon" class="w-6 h-6 text-text-muted opacity-50" />
    </div>
    @if($title)
        <p class="text-small font-semibold text-text-primary mb-1">{{ $title }}</p>
    @endif
    @if(isset($description))
        <div class="text-xs text-text-secondary max-w-sm">{{ $description }}</div>
    @elseif($message)
        <p class="text-xs text-text-secondary max-w-sm">{{ $message }}</p>
    @endif
    @if(isset($actions))
        <div class="mt-6">{{ $actions }}</div>
    @elseif($slot->isNotEmpty())
        <div class="mt-6">{{ $slot }}</div>
    @endif
</div>
