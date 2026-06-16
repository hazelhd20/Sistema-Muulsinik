<?php

namespace App\DTOs;

use Carbon\Carbon;

readonly class RequisitionDTO
{
    public function __construct(
        public ?int $projectId,
        public ?int $vendorId,
        public ?string $annotations,
        public string $status,
        public int $createdBy,
        public ?Carbon $date,
        /** @var RequisitionItemDTO[] */
        public array $items = []
    ) {}

    public static function fromArray(array $data): self
    {
        $items = array_map(
            fn(array $item) => RequisitionItemDTO::fromArray($item),
            $data['items'] ?? []
        );

        return new self(
            projectId: $data['project_id'] ?? null,
            vendorId: $data['vendor_id'] ?? null,
            annotations: $data['annotations'] ?? null,
            status: $data['status'] ?? 'borrador',
            createdBy: $data['created_by'] ?? auth()->id(),
            date: isset($data['date']) ? Carbon::parse($data['date']) : now(),
            items: $items
        );
    }
    
    public function toArray(): array
    {
        return [
            'project_id' => $this->projectId,
            'vendor_id' => $this->vendorId,
            'annotations' => $this->annotations,
            'status' => $this->status,
            'created_by' => $this->createdBy,
            'date' => $this->date?->format('Y-m-d'),
        ];
    }
}
