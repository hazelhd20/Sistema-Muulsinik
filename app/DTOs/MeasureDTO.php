<?php

namespace App\DTOs;

class MeasureDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $abbreviation = null,
        public readonly ?int $id = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            abbreviation: $data['abbreviation'] ?? null,
            id: $data['id'] ?? null,
        );
    }
}
