<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchCurrenciesRequest;
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

        $response = Http::get('https://garantex.org/api/v2/depth?market=' . $query);

        $asks = $response->json()['asks']; // ордера покупки
        $bids = $response->json()['bids']; // ордера продажи

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
