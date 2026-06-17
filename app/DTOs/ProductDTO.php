<?php

namespace App\DTOs;

class ProductDTO
{
    public function __construct(
        public readonly string $canonical_name,
        public readonly ?int $measure_id = null,
        public readonly ?string $description = null,
        public readonly ?int $category_id = null,
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
            id: $data['id'] ?? null,
        );
    }
}
