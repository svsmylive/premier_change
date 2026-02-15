<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashDesk extends Model
{
    protected $table = 'cash_desks';

    protected $fillable = [
        'name',
        'currency_id',
        'is_our',
    ];

    protected $casts = [
        'is_our' => 'boolean',
    ];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function trades(): BelongsToMany
    {
        return $this->belongsToMany(CryptoTrade::class, 'cash_desks_trade', 'cash_desk_id', 'crypto_trade_id')
            ->withPivot(['amount', 'rate'])
            ->withTimestamps();
    }

    public function movements(): HasMany
    {
        return $this->hasMany(CashDeskMovement::class);
    }

    public function rates(): HasMany
    {
        return $this->hasMany(CashDeskRate::class);
    }
}
