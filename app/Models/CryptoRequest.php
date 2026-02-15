<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class CryptoRequest extends Model
{
    use SoftDeletes;

    protected $table = 'crypto_requests';

    protected $fillable = [
        'date',
        'client_id',
        'status_id',
        'source_id',
        'currency_from_id',
        'currency_to_id',
        'amount',
        'comment',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function currencyFrom(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_from_id');
    }

    public function currencyTo(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_to_id');
    }

    /**
     * Если заявка уже переведена в сделку — тут будет связанная сделка
     */
    public function trade(): HasOne
    {
        return $this->hasOne(CryptoTrade::class, 'crypto_request_id');
    }

    public function getIsConvertedAttribute(): bool
    {
        return $this->trade()->exists();
    }
}
