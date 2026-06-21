<?php

namespace App\DTOs;

readonly class QuickBudgetDTO
{
    /**
     * @param string $title
     * @param string|null $description
     * @param int|null $client_id
     * @param float $margin_percent
     * @param bool $include_tax
     * @param int $created_by
     * @param QuickBudgetItemDTO[] $items
     */
    public function __construct(
        public string $title,
        public ?string $description,
        public ?int $client_id,
        public float $margin_percent,
        public bool $include_tax,
        public int $created_by,
        public array $items,
    ) {}

    public static function fromArray(array $data, int $userId): self
    {
        $items = array_map(function ($item) {
            return QuickBudgetItemDTO::fromArray($item);
        }, $data['items'] ?? []);

        return new self(
            title: $data['title'] ?? '',
            description: $data['description'] ?? null,
            client_id: isset($data['client_id']) && $data['client_id'] !== '' ? (int) $data['client_id'] : null,
            margin_percent: (float) ($data['margin_percent'] ?? $data['marginPercent'] ?? 0),
            include_tax: (bool) ($data['include_tax'] ?? $data['includeTax'] ?? false),
            created_by: $userId,
            items: $items,
        );
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'client' => $this->client,
            'margin_percent' => $this->margin_percent,
            'include_tax' => $this->include_tax,
            'created_by' => $this->created_by,
            'items' => $this->items,
        ];
    }
}

