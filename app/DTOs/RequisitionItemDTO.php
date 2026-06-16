<?php

namespace App\DTOs;

readonly class RequisitionItemDTO
{
    public function __construct(
        public int $productId,
        public int $measureId,
        public float $quantity,
        public ?float $unitPrice = null,
        public ?float $taxAmount = null,
        public ?float $lineTotal = null,
        public ?string $taxSource = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            productId: $data['product_id'] ?? $data['id'] ?? 0,
            measureId: $data['measure_id'] ?? 0,
            quantity: (float) ($data['quantity'] ?? 1),
            unitPrice: isset($data['unit_price']) ? (float) $data['unit_price'] : null,
            taxAmount: isset($data['tax_amount']) ? (float) $data['tax_amount'] : null,
            lineTotal: isset($data['line_total']) ? (float) $data['line_total'] : null,
            taxSource: $data['tax_source'] ?? null
        );
    }
    
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'measure_id' => $this->measureId,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'tax_amount' => $this->taxAmount,
            'line_total' => $this->lineTotal,
            'tax_source' => $this->taxSource,
        ];
    }
}
