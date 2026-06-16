<?php

namespace App\DTOs;

readonly class ProjectDTO
{
    public function __construct(
        public string $name,
        public ?string $description,
        public ?string $client,
        public float $budget,
        public ?string $start_date,
        public ?string $end_date,
        public string $status = 'activo',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? '',
            description: $data['description'] ?? null,
            client: $data['client'] ?? null,
            budget: (float) ($data['budget'] ?? 0),
            start_date: !empty($data['startDate']) ? $data['startDate'] : (!empty($data['start_date']) ? $data['start_date'] : null),
            end_date: !empty($data['endDate']) ? $data['endDate'] : (!empty($data['end_date']) ? $data['end_date'] : null),
            status: $data['status'] ?? 'activo',
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'client' => $this->client,
            'budget' => $this->budget,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status,
        ];
    }
}
