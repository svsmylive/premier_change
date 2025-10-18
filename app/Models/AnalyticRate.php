<?php

/**
 * Class AnalyticRate
 *
 * Represents the analytic rates for different cryptocurrency exchanges.
 * This model extends the base Laravel Authenticatable class.
 *
 * @package App\Models
 *
 * @property string $source                    Ресурс (Bestchange)
 * @property string $crypto_exchanger          Криптообменник
 * @property string $currency_from             Валюта приема
 * @property string $currency_to               Валюта выдачи
 * @property float $crypto_exchanger_course    Курс обменника
 * @property float $crypto_exchange_course     Курс биржы (рапира)
 * @property float $plus                       Наценка обменника
 */

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class AnalyticRate extends Authenticatable
{
    protected $table = 'analytic_rates';

    protected $fillable = [
        'source',
        'crypto_exchanger',
        'currency_from',
        'currency_to',
        'crypto_exchanger_course',
        'crypto_exchange_course',
        'plus',
    ];
}
