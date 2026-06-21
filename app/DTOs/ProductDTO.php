<?php

namespace App\DTOs;

class ProductDTO
{
    public function __construct(
        public readonly string $canonical_name,
        public readonly ?int $measure_id = null,
        public readonly ?string $description = null,
        public readonly ?int $category_id = null,
        public readonly string $item_type = 'material',
        public readonly ?int $id = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            canonical_name: $data['canonical_name'] ?? $data['name'],
            measure_id: $data['measure_id'] ?? null,
            description: $data['description'] ?? null,
            category_id: $data['category_id'] ?? null,
            item_type: $data['item_type'] ?? 'material',
            id: $data['id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'canonical_name' => $this->canonical_name,
            'measure_id' => $this->measure_id,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'item_type' => $this->item_type,
            'id' => $this->id,
        ];
    }
}

