<?php
App\Models\RequisitionItem::with(['product.measure', 'measure'])->chunkById(100, function ($items) {
    foreach ($items as $item) {
        $item->snapshot_product_name = $item->product?->canonical_name ?? '—';
        $item->snapshot_measure = $item->product?->measure?->abbreviation ?? $item->measure?->abbreviation ?? 'pza';
        $item->saveQuietly();
    }
});
echo "Done\n";
