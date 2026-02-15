<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurrencyExchange extends Model
{
    protected $table = 'currency_exchanges';

    protected $fillable = [
        'name',
        'uri',
    ];

    public function trades(): HasMany
    {
        return $this->hasMany(CryptoTrade::class);
    }
}
