<?php

namespace App\DTOs;

class UserDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $role_id,
        public readonly bool $active = true,
        public readonly ?string $password = null,
        public readonly ?int $id = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            role_id: (int)$data['role_id'],
            active: (bool)($data['active'] ?? true),
            password: $data['password'] ?? null,
            id: $data['id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'role_id' => $this->role_id,
            'active' => $this->active,
            'password' => $this->password,
            'id' => $this->id,
        ];
    }
}

