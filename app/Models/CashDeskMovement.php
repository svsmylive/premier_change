<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashDeskMovement extends Model
{
    protected $table = 'cash_desk_movements';

    protected $fillable = [
        'date',
        'cash_desk_id',
        'operation_type_id',
        'crypto_trade_id',
        'amount',
        'comment',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function cashDesk(): BelongsTo
    {
        return $this->belongsTo(CashDesk::class);
    }

    public function operationType(): BelongsTo
    {
        return $this->belongsTo(OperationType::class, 'operation_type_id');
    }

    public function trade(): BelongsTo
    {
        return $this->belongsTo(CryptoTrade::class, 'crypto_trade_id');
    }

    public function scopeBetweenDates(Builder $q, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $q->where('date', '>=', $from);
        }
        if ($to) {
            $q->where('date', '<=', $to);
        }
        return $q;
    }
}
