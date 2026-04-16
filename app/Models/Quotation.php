<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quotation extends Model
{
    protected $fillable = [
        'requisition_id', 'supplier_id', 'project_id',
        'file_path', 'file_type', 'original_filename',
        'status', 'raw_text', 'parsed_data',
        'error_message', 'uploaded_by', 'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'parsed_data'  => 'array',
    ];

    /* ── Relaciones ────────────────────────────────────── */

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisition::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /* ── Helpers ───────────────────────────────────────── */

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }
}
