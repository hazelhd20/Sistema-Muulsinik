<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Requisition extends Model
{
    protected $fillable = [
        'project_id', 'number', 'annotations', 'status',
        'created_by', 'approved_by', 'rejection_comment',
        'date', 'need_date',
    ];

    protected static function booted()
    {
        static::created(function ($requisition) {
            if (empty($requisition->number)) {
                $projectPrefix = 'PRJ';
                if ($requisition->project) {
                    $projectPrefix = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $requisition->project->name), 0, 3));
                }
                $requisition->number = sprintf('%s%d-REQ%04d', $projectPrefix, $requisition->project_id, $requisition->id);
                $requisition->saveQuietly();
            }
        });
    }

    protected $casts = [
        'date' => 'date',
        'need_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RequisitionItem::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    /** Total estimado de la requisición. */
    public function getTotalAttribute(): float
    {
        return (float) $this->items->sum(fn ($item) => $item->quantity * $item->unit_price);
    }
}
