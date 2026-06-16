<?php

namespace App\DTOs;

readonly class QuickBudgetItemDTO
{
    public function __construct(
        public ?int $product_id,
        public string $concept,
        public ?int $measure_id,
        public float $quantity,
        public float $unit_price,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            product_id: empty($data['product_id']) ? null : (int) $data['product_id'],
            concept: $data['concept'] ?? '',
            measure_id: empty($data['measure_id']) ? null : (int) $data['measure_id'],
            quantity: (float) ($data['quantity'] ?? 0),
            unit_price: (float) ($data['unit_price'] ?? 0),
        );
    }
}
