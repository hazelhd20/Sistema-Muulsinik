<?php

namespace App\DTOs;

readonly class QuickBudgetItemDTO
{
    public function __construct(
        public ?int $product_id,
        public string $concept,
        public string $item_type,
        public ?int $measure_id,
        public float $quantity,
        public float $unit_price,
        public float $unit_cost,
        public float $margin_percent,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            product_id: empty($data['product_id']) ? null : (int) $data['product_id'],
            concept: $data['concept'] ?? '',
            item_type: $data['item_type'] ?? 'material',
            measure_id: empty($data['measure_id']) ? null : (int) $data['measure_id'],
            quantity: (float) ($data['quantity'] ?? 0),
            unit_price: (float) ($data['unit_price'] ?? 0),
            unit_cost: (float) ($data['unit_cost'] ?? 0),
            margin_percent: (float) ($data['margin_percent'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->product_id,
            'concept' => $this->concept,
            'item_type' => $this->item_type,
            'measure_id' => $this->measure_id,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'unit_cost' => $this->unit_cost,
            'margin_percent' => $this->margin_percent,
        ];
    }
}

