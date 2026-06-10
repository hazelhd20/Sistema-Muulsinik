<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequisitionActivity extends Model
{
    protected $fillable = [
        'requisition_id',
        'user_id',
        'action',
        'description',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function requisition()
    {
        return $this->belongsTo(Requisition::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
