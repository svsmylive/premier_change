<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Status extends Model
{
    protected $table = 'statuses';

    protected $fillable = [
        'name',
        'is_end',
        'code',
    ];

    protected $casts = [
        'is_end' => 'boolean',
    ];

    public function trades(): HasMany
    {
        return $this->hasMany(CryptoTrade::class);
    }
}
