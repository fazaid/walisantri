<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Table('invoices')]
#[Fillable([
    'order_id',
    'nomor_invoice',
    'bukti_transfer_path',
    'bukti_transfer_uploaded_at',
])]
class Invoice extends Model
{
    protected function casts(): array
    {
        return [
            'bukti_transfer_uploaded_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function hasBuktiTransfer(): bool
    {
        return $this->bukti_transfer_path !== null;
    }

    public function getBuktiTransferUrlAttribute(): ?string
    {
        if (! $this->bukti_transfer_path) {
            return null;
        }

        return Storage::disk('local')->url($this->bukti_transfer_path);
    }
}
