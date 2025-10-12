<?php

namespace App\Services;

use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CurrencyService
{
    public const PLUS_USDT_RUB = 0.001; // наценка при продаже USDT -> RUB
    public const PLUS_RUB_USDT = 0.01; // наценка при продаже RUB -> USDT

    public function get(string $currencyFrom, string $currencyTo, float $clientSum = 1.0): array
    {
        $currencyFrom = strtolower($currencyFrom);
        $currencyTo = strtolower($currencyTo);

        if ($currencyFrom === $currencyTo) {
            return [
                'success' => false,
                'message' => 'currency_from equals currency_to',
            ];
        }

        // Формируем запрос к API (пара может быть inverted для рублёвой ветки)
        if ($currencyFrom === 'rub') {
            // Хотим узнать, сколько USDT можно купить за X RUB -> запрашиваем USDT/RUB стакан
            $query = Str::upper($currencyTo) . '/' . Str::upper($currencyFrom);
        } else {
            $query = Str::upper($currencyFrom) . '/' . Str::upper($currencyTo);
        }

        $cacheKey = 'currencies_' . $query . '_' . (int)$clientSum;

        try {
            $response = Cache::get($cacheKey, function () use ($query) {
                $request = Http::baseUrl('https://api.rapira.net')
                    ->timeout(30)
                    ->connectTimeout(30);

                $r = $request->get('/market/exchange-plate-mini?symbol=' . $query);
                $json = $r->json();

                Cache::put('currencies_' . $query . '_' . time(), $json, CarbonInterval::minutes(15));
                return $json;
            });
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        // --- RUB -> USDT (special handling) ---
        if ($currencyFrom === 'rub') {
            // orders that sell USDT (amount in USDT, price in RUB per USDT)
            $asks = $response['ask']['items'] ?? [];

            $rubLeft = $clientSum;
            $usdtBought = 0.0;

            foreach ($asks as $ask) {
                if ($rubLeft <= 0) {
                    break;
                }

                $amountUsdt = (float)$ask['amount'];                // USDT available in this order
                $priceRubPerUsdt = (float)$ask['price'];           // RUB per 1 USDT
                $costRubForFull = $amountUsdt * $priceRubPerUsdt; // RUB needed to buy full amountUsdt

                if ($rubLeft >= $costRubForFull) {
                    // можем купить весь объём ордера
                    $usdtBought += $amountUsdt;
                    $rubLeft -= $costRubForFull;
                } else {
                    // покупаем частично: сколько USDT можно купить на оставшиеся RUB
                    if ($priceRubPerUsdt > 0) {
                        $usdtCanBuy = $rubLeft / $priceRubPerUsdt;
                        $usdtBought += $usdtCanBuy;
                    }
                    $rubLeft = 0;
                    break;
                }
            }

            // fallback на топ-оркд (если стакан пуст или куплено 0) — покупаем по лучшей цене
            if ($usdtBought <= 0 && !empty($asks)) {
                $top = $asks[0];
                $topPrice = (float)$top['price'] ?: 0.0;
                if ($topPrice > 0) {
                    $usdtBought = $clientSum / $topPrice;
                }
            }

            // применяем наценку: при продаже RUB -> USDT мы даём клиенту чуть меньше USDT
            $usdtWithMarkup = $usdtBought - $usdtBought * self::PLUS_RUB_USDT;

            // price = сколько USDT даётся за 1 RUB
            $pricePerOne = $clientSum > 0 ? ($usdtWithMarkup / $clientSum) : 0.0;

            return [
                // даём больше знаков, т.к. это дробные токены
                'price' => number_format($pricePerOne, 6, '.', ' '),
                'total' => number_format($usdtWithMarkup, 6, '.', ' '),
                'currency_from' => $currencyFrom,
                'currency_to' => $currencyTo,
            ];
        }

        // --- BASE -> QUOTE (например USDT -> RUB и прочие) ---
        // orders that buy base (bid) — amount in base (e.g. USDT), price is quote per base (e.g. RUB per USDT)
        $bids = $response['bid']['items'] ?? [];

        $baseLeft = $clientSum; // amount of base currency user gives (e.g. USDT)
        $quoteReceived = 0.0;

        foreach ($bids as $bid) {
            if ($baseLeft <= 0) {
                break;
            }

            $amountBase = (float)$bid['amount'];
            $priceQuotePerBase = (float)$bid['price'];

            $take = min($baseLeft, $amountBase);

            $quoteReceived += $take * $priceQuotePerBase;
            $baseLeft -= $take;
        }

        // fallback: если ничего не получилось, берём топ bid
        if ($quoteReceived <= 0 && !empty($bids)) {
            $top = $bids[0];
            $topPrice = (float)($top['price'] ?? 0.0);
            $quoteReceived = round($topPrice, 2) * $clientSum;
            $averagePrice = $topPrice;
        } else {
            $averagePrice = $clientSum > 0 ? $quoteReceived / $clientSum : 0.0; // quote per 1 base
        }

        // применяем наценку: клиент при продаже base (USDT->RUB) получает чуть меньше (понижаем цену)
        $averagePriceWithMarkup = $averagePrice - $averagePrice * self::PLUS_USDT_RUB;
        $total = round($averagePriceWithMarkup, 2) * $clientSum;

        return [
            'price' => number_format($averagePriceWithMarkup, 2, '.', ' '),
            'total' => number_format($total, 2, '.', ' '),
            'currency_from' => $currencyFrom,
            'currency_to' => $currencyTo,
        ];
    }
}

