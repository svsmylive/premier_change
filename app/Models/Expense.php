<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $table = 'expenses';

    protected $fillable = [
        'date',
        'description',
        'sum',
        'cash_desk_id',
        'currency_id',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function cashDesk(): BelongsTo
    {
        return $this->belongsTo(CashDesk::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
