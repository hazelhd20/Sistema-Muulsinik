<?php

namespace App\DTOs;

use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

readonly class ExpenseDTO
{
    public function __construct(
        public string $concept,
        public float $amount,
        public string $date,
        public string $category,
        public ?int $project_id,
        public bool $is_distributed,
        public int $user_id,
        public UploadedFile|TemporaryUploadedFile|null $receipt_file,
    ) {}

    public static function fromArray(array $data, int $userId): self
    {
        return new self(
            concept: $data['concept'] ?? '',
            amount: (float) ($data['amount'] ?? 0),
            date: $data['date'] ?? now()->format('Y-m-d'),
            category: $data['category'] ?? '',
            project_id: empty($data['projectId']) ? null : (int) $data['projectId'],
            is_distributed: (bool) ($data['isDistributed'] ?? false),
            user_id: $userId,
            receipt_file: $data['receiptFile'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'concept' => $this->concept,
            'amount' => $this->amount,
            'date' => $this->date,
            'category' => $this->category,
            'project_id' => $this->project_id,
            'is_distributed' => $this->is_distributed,
            'user_id' => $this->user_id,
        ];
    }
}
