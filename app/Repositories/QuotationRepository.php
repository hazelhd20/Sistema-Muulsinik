<?php

namespace App\Repositories;

use App\DTOs\QuotationDTO;
use App\Helpers\FileHelpers;
use App\Models\Quotation;
use Exception;
use Illuminate\Support\Facades\DB;

class QuotationRepository
{
    /**
     * Uploads a quotation file, validates Magic Bytes, and creates the Quotation record.
     */
    public function uploadAndCreate(QuotationDTO $dto): Quotation
    {
        // Validate Magic Bytes before saving
        if (!FileHelpers::validateMagicBytes($dto->file->getRealPath())) {
            throw new Exception("El archivo '{$dto->file->getClientOriginalName()}' no tiene un formato válido o está corrupto (Magic Bytes mismatch).");
        }

        $path = $dto->file->store('quotations', config('filesystems.default'));
        $originalName = $dto->file->getClientOriginalName();
        $mimeType = $dto->file->getMimeType();

        return Quotation::create([
            'project_id' => $dto->project_id,
            'file_path' => $path,
            'file_type' => $mimeType,
            'original_filename' => $originalName,
            'status' => 'pending',
            'uploaded_by' => $dto->uploaded_by,
        ]);
    }
}
