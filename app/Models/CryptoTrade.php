<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CryptoTrade extends Model
{
    use SoftDeletes;

    protected $table = 'crypto_trades';

    protected $fillable = [
        'date',
        'crypto_request_id',
        'client_id',
        'currency_from_id',
        'currency_to_id',
        'operator_id',
        'amount_income',
        'course_of_client',
        'rate_of_client',
        'course_of_currency_exchange',
        'currency_exchange_id',
        'partner_id',
        'rate_of_partner',
        'amount_outcome',
        'comment',
    ];

    protected $casts = [
        'date' => 'datetime',
        'course_of_client' => 'decimal:4',
        'course_of_currency_exchange' => 'decimal:4',
        'rate_of_partner' => 'decimal:4',
    ];

    // ---- Relations ----

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
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

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function currencyExchange(): BelongsTo
    {
        return $this->belongsTo(CurrencyExchange::class, 'currency_exchange_id');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Кассы, задействованные в сделке (pivot: amount, rate)
     */
    public function cashDesks(): BelongsToMany
    {
        return $this->belongsToMany(CashDesk::class, 'cash_desks_trade', 'crypto_trade_id', 'cash_desk_id')
            ->withPivot(['amount', 'rate', 'course'])
            ->withTimestamps();
    }

    public function movements(): HasMany
    {
        return $this->hasMany(CashDeskMovement::class, 'crypto_trade_id');
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(CryptoRequest::class, 'crypto_request_id');
    }

    // ---- Scopes ----

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
