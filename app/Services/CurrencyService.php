<?php

namespace App\Services;

use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CurrencyService
{
    public const PLUS = 0.005; //наша наценка

    public function get(string $currencyFrom, string $currencyTo, float $clientSum = 1.0): array
    {
        $query = Str::upper($currencyFrom) . '/' . Str::upper($currencyTo);

        $cacheKey = 'currencies_' . $query . (int)$clientSum;

        try {
            $response = Cache::get('', function () use ($query, $cacheKey) {
                $request = Http::baseUrl('https://api.rapira.net');
                $request->timeout(30);
                $request->connectTimeout(30);

                $response = $request->get('/market/exchange-plate-mini?symbol=' . $query);

                $json = $response->json();

                Cache::put($cacheKey, $json, CarbonInterval::minutes(15));

                return $json;
            });
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        $asks = $response['ask']; // ордера покупки
        $bids = $response['bid']; // ордера продажи

        $averagePrice = 0.0;
        $remainder = 0.0;
        $averageData = [];

        foreach ($bids['items'] as $bid) {
            $volumeCurrency = (float)$bid['amount'];
            $bidPrice = (float)$bid['price'];

            if ($clientSum <= $volumeCurrency && $remainder == 0) {
                $averagePrice = $bidPrice;

                break;
            } else {
                if ($remainder == 0) {
                    $remainder = $clientSum - $volumeCurrency;

                    $averageData[] = [
                        'price' => $bidPrice,
                        'need' => $volumeCurrency,
                    ];

                    continue;
                }

                if ($remainder <= $volumeCurrency) {
                    $averageData[] = [
                        'price' => $bidPrice,
                        'need' => $remainder,
                    ];

                    break;
                }

                $averageData[] = [
                    'price' => $bidPrice,
                    'need' => $volumeCurrency,
                ];

                $remainder -= $volumeCurrency;
            }
        }

        $total = 0.0;

        if (!empty($averageData)) {
            foreach ($averageData as $data) {
                $total = $total + $data['price'] * $data['need'];
            }

            $averagePrice = $total / $clientSum;
            $averagePrice = $averagePrice - $averagePrice * self::PLUS; //наша наценка

            $total = round($averagePrice, 2) * $clientSum;
        }

        if ($total == 0) {
            $averagePrice = $averagePrice - $averagePrice * self::PLUS; //наша наценка

            $total = round($averagePrice, 2) * $clientSum;
        }

        return [
            'price' => number_format($averagePrice, 2, '.', ' '),
            'total' => number_format($total, 2, '.', ' '),
            'currency_from' => $currencyFrom,
            'currency_to' => $currencyTo,
        ];
    }
}
