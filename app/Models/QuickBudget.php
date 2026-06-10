<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuickBudget extends Model
{
    protected $fillable = [
        'title', 'description', 'client',
        'subtotal', 'tax_amount', 'total',
        'margin_percent', 'grand_total',
        'status', 'created_by',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'margin_percent' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuickBudgetItem::class);
    }
}
