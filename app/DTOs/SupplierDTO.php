<?php

namespace App\DTOs;

class SupplierDTO
{
    public function __construct(
        public readonly string $trade_name,
        public readonly ?string $legal_name = null,
        public readonly ?string $rfc = null,
        public readonly ?string $category = null,
        public readonly ?string $notes = null,
        public readonly bool $active = true,
        public readonly ?int $id = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            trade_name: $data['trade_name'],
            legal_name: $data['legal_name'] ?? null,
            rfc: $data['rfc'] ?? null,
            category: $data['category'] ?? null,
            notes: $data['notes'] ?? null,
            active: (bool)($data['active'] ?? true),
            id: $data['id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'trade_name' => $this->trade_name,
            'legal_name' => $this->legal_name,
            'rfc' => $this->rfc,
            'category' => $this->category,
            'notes' => $this->notes,
            'active' => $this->active,
            'id' => $this->id,
        ];
    }
}

