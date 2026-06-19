<?php

namespace App\DTOs;

class VendorDTO
{
    public function __construct(
        public readonly int $supplier_id,
        public readonly string $name,
        public readonly ?string $phone = null,
        public readonly ?string $email = null,
        public readonly ?int $id = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            supplier_id: (int)$data['supplier_id'],
            name: $data['name'],
            phone: $data['phone'] ?? null,
            email: $data['email'] ?? null,
            id: $data['id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'supplier_id' => $this->supplier_id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'id' => $this->id,
        ];
    }
}

