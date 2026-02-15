<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashDeskRate extends Model
{
    protected $table = 'cash_desk_rates';

    protected $fillable = [
        'cash_desk_id',
        'currency_from_id',
        'sum_from',
        'sum_to',
        'rate',
    ];

    protected $casts = [
        'sum_from' => 'integer',
        'sum_to' => 'integer',
        'rate' => 'decimal:4',
    ];

    public function cashDesk(): BelongsTo
    {
        return $this->belongsTo(CashDesk::class);
    }

    public function currencyFrom(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_from_id');
    }
}
