<?php

namespace App\Services;

use App\Models\ExchangeSetting;
use Illuminate\Support\Facades\Cache;

class MarkupService
{
    // Фоллбеки на случай пустой БД/ошибок
    private const DEFAULT_USDT_RUB = 0.015; // 0.2%
    private const DEFAULT_RUB_USDT = 0.010; // 1.0%

    public function getUsdtRub(): float
    {
        return (float)(ExchangeSetting::query()
            ->where('key', 'markup_usdt_rub')
            ->value('value') ?? self::DEFAULT_USDT_RUB);
    }

    public function getRubUsdt(): float
    {
        return (float)(ExchangeSetting::query()
            ->where('key', 'markup_rub_usdt')
            ->value('value') ?? self::DEFAULT_RUB_USDT);
    }

    public function setUsdtRub(float $fraction): void
    {
        ExchangeSetting::updateOrCreate(['key' => 'markup_usdt_rub'], ['value' => $fraction]);
        Cache::forget('markup_usdt_rub');
    }

    public function setRubUsdt(float $fraction): void
    {
        ExchangeSetting::updateOrCreate(['key' => 'markup_rub_usdt'], ['value' => $fraction]);
        Cache::forget('markup_rub_usdt');
    }
}
