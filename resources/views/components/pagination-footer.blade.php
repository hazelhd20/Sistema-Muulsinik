@props(['paginator'])

@if ($paginator->hasPages() || $paginator->total() > 0)
    <div class="px-4 py-3 border-t border-border sm:px-6 rounded-b-lg">
        {{ $paginator->links('vendor.pagination.tailwind') }}
    </div>
@endif
