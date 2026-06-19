<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'requisition_id', 'supplier_id', 'project_id',
        'file_path', 'file_type', 'original_filename',
        'status', 'raw_text', 'raw_parsed_data', 'draft_state', 'is_orphan',
        'error_message', 'uploaded_by', 'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'raw_parsed_data' => 'array',
        'draft_state' => 'array',
        'is_orphan' => 'boolean',
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

    /* ── Scopes ────────────────────────────────────────── */

    /**
     * Scope para obtener las cotizaciones "borradores/pendientes"
     * que se muestran en el inbox del usuario.
     */
    public function scopePendingInbox($query)
    {
        return $query->whereNull('requisition_id')
            ->where('is_orphan', false)
            ->where(function ($q) {
                $q->whereIn('status', ['pending', 'processing'])
                  ->orWhere(function ($subQ) {
                      $subQ->whereIn('status', ['completed', 'failed'])
                           ->where('created_at', '>=', now()->subDays(7));
                  });
            });
    }
}
