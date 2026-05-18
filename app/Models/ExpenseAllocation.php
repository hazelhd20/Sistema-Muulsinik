<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseAllocation extends Model
{
    protected $fillable = [
        'expense_id',
        'project_id',
        'amount',
        'percentage',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'percentage' => 'decimal:2',
    ];

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
