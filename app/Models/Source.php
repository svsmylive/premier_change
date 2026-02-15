<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    protected $table = 'sources';

    protected $fillable = [
        'name',
        'comment',
    ];

    public function trades(): HasMany
    {
        return $this->hasMany(CryptoTrade::class);
    }
}
