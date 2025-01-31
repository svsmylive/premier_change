<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchCurrenciesRequest;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CurrenciesController
{
    public function calculate(SearchCurrenciesRequest $request): array
    {
        $data = $request->validationData();
        $currencyFrom = $data['currency_from'];
        $currencyTo = $data['currency_to'];
        $clientSum = (float)$data['sum'];

        $query = $currencyFrom . $currencyTo;

        try {
            $response = Cache::get('currencies', function () use ($query) {
                $request = Http::baseUrl('https://garantex.org/');
                $request->timeout(30);
                $request->connectTimeout(30);

                $response = $request->get('api/v2/depth?market=' . $query);

                $json = $response->json();

                Cache::put('currencies', $json, CarbonInterval::hours(24));

                return $json;
            });
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        $asks = $response['asks']; // ордера покупки
        $bids = $response['bids']; // ордера продажи

        $averagePrice = 0.0;
        $remainder = 0.0;
        $averageData = [];

        foreach ($bids as $bid) {
            $volumeCurrency = (float)$bid['volume'];
            $bidPrice = (float)$bid['price'];

            if ($clientSum <= $volumeCurrency) {
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
            $averagePrice = $averagePrice - $averagePrice * 0.03; //наша наценка

            $total = round($averagePrice, 2) * $clientSum;
        }

        if ($total == 0) {
            $averagePrice = $averagePrice - $averagePrice * 0.03; //наша наценка

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
