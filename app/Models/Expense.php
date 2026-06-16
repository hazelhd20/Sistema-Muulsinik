<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    protected static function booted()
    {
        static::saved(function ($expense) {
            $expense->project?->recalculateTotalExpensesCache();
        });

        static::deleted(function ($expense) {
            $expense->project?->recalculateTotalExpensesCache();
        });
    }
    protected $fillable = [
        'concept', 'amount', 'date', 'category',
        'project_id', 'is_distributed', 'user_id', 'receipt_file',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'is_distributed' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function allocations()
    {
        return $this->hasMany(ExpenseAllocation::class);
    }
}
