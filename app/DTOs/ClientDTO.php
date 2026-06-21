<?php

namespace App\DTOs;

class ClientDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $legal_name = null,
        public readonly ?string $rfc = null,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly bool $active = true,
        public readonly ?int $id = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            legal_name: $data['legal_name'] ?? null,
            rfc: $data['rfc'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            active: $data['active'] ?? true,
            id: $data['id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'legal_name' => $this->legal_name,
            'rfc' => $this->rfc,
            'email' => $this->email,
            'phone' => $this->phone,
            'active' => $this->active,
            'id' => $this->id,
        ];
    }
}
