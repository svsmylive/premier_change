<?php

namespace App\Services;

use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CurrencyService
{
    public function __construct(
        private readonly MarkupService $markupService
    ) {
    }

    public function get(string $currencyFrom, string $currencyTo, float $clientSum = 1.0): array
    {
        $currencyFrom = strtolower($currencyFrom);
        $currencyTo = strtolower($currencyTo);

        if ($currencyFrom === $currencyTo) {
            return ['success' => false, 'message' => 'currency_from equals currency_to'];
        }

        // Пара для Rapira
        $query = $currencyFrom === 'rub'
            ? Str::upper($currencyTo) . '/' . Str::upper($currencyFrom)
            : Str::upper($currencyFrom) . '/' . Str::upper($currencyTo);

        $cacheKey = 'currencies_' . $query . '_' . (int)$clientSum;

        try {
            $response = Cache::get($cacheKey, function () use ($query) {
                $r = Http::baseUrl('https://api.rapira.net')
                    ->timeout(30)->connectTimeout(30)
                    ->get('/market/exchange-plate-mini?symbol=' . $query);

                $json = $r->json();
                Cache::put('currencies_' . $query . '_' . time(), $json, CarbonInterval::minutes(15));
                return $json;
            });
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        if ($currencyFrom === 'rub') {
            // RUB -> USDT (приём)
            $asks = $response['ask']['items'] ?? [];
            $rubLeft = $clientSum;
            $usdtBought = 0.0;

            foreach ($asks as $ask) {
                if ($rubLeft <= 0) {
                    break;
                }

                $amountUsdt = (float)$ask['amount'];
                $priceRubPerUsdt = (float)$ask['price'];
                $costRubForFull = $amountUsdt * $priceRubPerUsdt;

                if ($rubLeft >= $costRubForFull) {
                    $usdtBought += $amountUsdt;
                    $rubLeft -= $costRubForFull;
                } else {
                    if ($priceRubPerUsdt > 0) {
                        $usdtBought += $rubLeft / $priceRubPerUsdt;
                    }
                    $rubLeft = 0;
                    break;
                }
            }

            if ($usdtBought <= 0 && !empty($asks)) {
                $topPrice = (float)$asks[0]['price'] ?: 0.0;
                if ($topPrice > 0) {
                    $usdtBought = $clientSum / $topPrice;
                }
            }

            // Наценка при приёме RUB -> USDT (даём меньше USDT)
            $markup = $this->markupService->getRubUsdt(); // доля, например 0.02 для 2%
            $usdtWithMarkup = $usdtBought - $usdtBought * $markup;
            $pricePerOne = $clientSum > 0 ? ($usdtWithMarkup / $clientSum) : 0.0;

            return [
                'price' => number_format($pricePerOne, 6, '.', ' '),
                'total' => number_format($usdtWithMarkup, 6, '.', ' '),
                'currency_from' => $currencyFrom,
                'currency_to' => $currencyTo,
            ];
        }

        // Иначе — USDT -> RUB (выдача) или другие BASE->QUOTE
        $bids = $response['bid']['items'] ?? [];
        $baseLeft = $clientSum;
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

        if ($quoteReceived <= 0 && !empty($bids)) {
            $topPrice = (float)($bids[0]['price'] ?? 0.0);
            $quoteReceived = round($topPrice, 2) * $clientSum;
            $averagePrice = $topPrice;
        } else {
            $averagePrice = $clientSum > 0 ? $quoteReceived / $clientSum : 0.0;
        }

        // Наценка на USDT -> RUB (клиент получает меньше RUB)
        $markup = $this->markupService->getUsdtRub();
        $averagePriceWithMarkup = $averagePrice - $averagePrice * $markup;
        $total = round($averagePriceWithMarkup, 2) * $clientSum;

        return [
            'price' => number_format($averagePriceWithMarkup, 2, '.', ' '),
            'total' => number_format($total, 2, '.', ' '),
            'currency_from' => $currencyFrom,
            'currency_to' => $currencyTo,
        ];
    }
}

