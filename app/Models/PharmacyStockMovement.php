<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyStockMovement extends Model
{
    protected $fillable = [
        'medicine_id',
        'batch_id',
        'invoice_id',
        'invoice_item_id',
        'user_id',
        'movement_type',
        'quantity',
        'notes',
        'reversed_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'reversed_at' => 'datetime',
        ];
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PharmacyStockBatch::class, 'batch_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
