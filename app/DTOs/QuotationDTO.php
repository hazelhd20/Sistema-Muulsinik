<?php

namespace App\DTOs;

use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

readonly class QuotationDTO
{
    public function __construct(
        public ?int $project_id,
        public int $uploaded_by,
        public UploadedFile|TemporaryUploadedFile $file,
    ) {}

    public static function fromFile(UploadedFile|TemporaryUploadedFile $file, ?int $projectId, int $uploadedBy): self
    {
        return new self(
            project_id: $projectId,
            uploaded_by: $uploadedBy,
            file: $file,
        );
    }

    public function toArray(): array
    {
        return [
            'project_id' => $this->project_id,
            'uploaded_by' => $this->uploaded_by,
            'file' => $this->file,
        ];
    }
}

