<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PharmacyStockBatch extends Model
{
    protected $fillable = [
        'medicine_id',
        'batch_number',
        'quantity_received',
        'quantity_available',
        'expiry_date',
        'received_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_received' => 'integer',
            'quantity_available' => 'integer',
            'expiry_date' => 'date',
        ];
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(PharmacyStockMovement::class, 'batch_id');
    }
}
