<?php

namespace App\DTOs;

readonly class QuickBudgetDTO
{
    /**
     * @param string $title
     * @param string|null $description
     * @param string|null $client
     * @param float $margin_percent
     * @param int $created_by
     * @param QuickBudgetItemDTO[] $items
     */
    public function __construct(
        public string $title,
        public ?string $description,
        public ?string $client,
        public float $margin_percent,
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
            client: $data['client'] ?? null,
            margin_percent: (float) ($data['margin_percent'] ?? $data['marginPercent'] ?? 0),
            created_by: $userId,
            items: $items,
        );
    }
}
