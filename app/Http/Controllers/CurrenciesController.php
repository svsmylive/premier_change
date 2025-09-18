<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchCurrenciesRequest;
use App\Services\CurrencyService;
use SimpleXMLElement;

class CurrenciesController
{
    public function __construct(private readonly CurrencyService $currencyService)
    {
    }

    //Гарантекс
    /*public function calculate(SearchCurrenciesRequest $request): array
    {
        $data = $request->validationData();
        $currencyFrom = $data['currency_from'];
        $currencyTo = $data['currency_to'];
        $clientSum = (float)$data['sum'];

        $query = $currencyFrom . $currencyTo;

        $cacheKey = 'currencies_' . $query . (int)$clientSum;

        try {
            $response = Cache::get($cacheKey, function () use ($query, $cacheKey) {
                $request = Http::baseUrl('https://garantex.org/');
                $request->timeout(30);
                $request->connectTimeout(30);

                $response = $request->get('api/v2/depth?market=' . $query);

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

        $asks = $response['asks']; // ордера покупки
        $bids = $response['bids']; // ордера продажи

        $averagePrice = 0.0;
        $remainder = 0.0;
        $averageData = [];

        foreach ($bids as $bid) {
            $volumeCurrency = (float)$bid['volume'];
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
            $averagePrice = $averagePrice - $averagePrice * 0.003; //наша наценка

            $total = round($averagePrice, 2) * $clientSum;
        }

        if ($total == 0) {
            $averagePrice = $averagePrice - $averagePrice * 0.003; //наша наценка

                $total = round($averagePrice, 2) * $clientSum;
        }

        return [
            'price' => number_format($averagePrice, 2, '.', ' '),
            'total' => number_format($total, 2, '.', ' '),
            'currency_from' => $currencyFrom,
            'currency_to' => $currencyTo,
        ];
    }*/

    //Abcex
    /*public function calculate(SearchCurrenciesRequest $request): array
    {
        $data = $request->validated();
        $currencyFrom = $data['currency_from'];
        $currencyTo = $data['currency_to'];
        $clientSum = (float)$data['sum'];

        $query = $currencyFrom . $currencyTo;

        $cacheKey = 'currencies_' . $query . (int)$clientSum;

        try {
            $response = Cache::get($cacheKey, function () use ($query, $cacheKey) {
                $token = 'eyJhbGciOiJSUzI1NiIsInR5cCIgOiAiSldUIiwia2lkIiA6ICJrenNJYUxtY0R2RVBlRGRYVHVQOXVYN050UzZDSXZ5WXR5VXhwemRrdEZJIn0.eyJleHAiOjE3NzU2Mzg3NDksImlhdCI6MTc0NDEwMjc0OSwianRpIjoiZThhOGVhMDgtNmM5My00NGZkLThjMGUtNDY4NDNiMzExY2ZlIiwiaXNzIjoiaHR0cHM6Ly9hdXRoLmFiY2V4LmlvL3JlYWxtcy9hYmNleCIsImF1ZCI6ImFjY291bnQiLCJzdWIiOiI0MzgwMmM0YS02NDU3LTRlNzEtOWQ5MS1jMDVmMDY5OWNjZDYiLCJ0eXAiOiJCZWFyZXIiLCJhenAiOiJhcHAtYXBpLWtleWNsb2FrLWdhdGV3YXkiLCJzZXNzaW9uX3N0YXRlIjoiYjA3YzFiYjMtMDdmMS00NWE0LWIzMjctNTNjYjkwYmRjOWFhIiwiYWNyIjoiMSIsImFsbG93ZWQtb3JpZ2lucyI6WyIvKiJdLCJyZWFsbV9hY2Nlc3MiOnsicm9sZXMiOlsib2ZmbGluZV9hY2Nlc3MiLCJ1bWFfYXV0aG9yaXphdGlvbiIsImRlZmF1bHQtcm9sZXMtYWJjZXguaW8iXX0sInJlc291cmNlX2FjY2VzcyI6eyJhY2NvdW50Ijp7InJvbGVzIjpbIm1hbmFnZS1hY2NvdW50IiwibWFuYWdlLWFjY291bnQtbGlua3MiLCJ2aWV3LXByb2ZpbGUiXX19LCJzY29wZSI6IiIsInNpZCI6ImIwN2MxYmIzLTA3ZjEtNDVhNC1iMzI3LTUzY2I5MGJkYzlhYSIsImlkIjoiYjMwNWEzZmYtZGUxMi00YjVkLTgyMTQtMTEzMTNlZTQwNDU0In0.LIedMRr5OWs3Vw8WuV0Bl3oUxIycuGAXsr1vMERkqZNVt6cOqH5Vh4jNMnF7LzzHLHV6p3RnPETTD_H3ua3ZXobbNo02iWNRbtQ8ky0cfKSZilB1mz-dVo-yPcPmceFvUb5ekGnqdnZPKDIWhGTP4TEDVTD-HV8o4dnTxkCaqqsOckR9WbgyC-MCIEMB1wcqGoDAK-CZvYbWcWLZe-H4FmdVE9hzQ0aCA-i_AFSukuKkgBFmSAVF88uODMM2OJhCY_UgDTvE6J02ck-TRSTOjSQIasp1JfQITHfEw3JWIofXUPbI6ddesW756bpWcORGDoKcgk5bYZ2r-LfXLFIlVA'; // сократил для читаемости
                $query = 'USDTRUB'; // замените на нужный marketId

                $ch = curl_init();

                curl_setopt(
                    $ch,
                    CURLOPT_URL,
                    "https://gateway.abcex.io/api/v1/markets/price?marketId=" . urlencode(Str::upper($query))
                );
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer $token",
                    "Accept: application/json"
                ]);

                $response = curl_exec($ch);

                $json = json_decode($response);

                Cache::put($cacheKey, $json, CarbonInterval::minutes(15));

                return $json;
            });
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        if (!isset($response->askPrice)) {
            return ['error' => $response];
        }

        $askPrice = $response->askPrice;

        $averagePrice = $askPrice - $askPrice * 0.003; //наша наценка

        $total = round($averagePrice, 2) * $clientSum;

        return [
            'price' => number_format($averagePrice, 2, '.', ' '),
            'total' => number_format($total, 2, '.', ' '),
            'currency_from' => $currencyFrom,
            'currency_to' => $currencyTo,
        ];
    }*/

    //Rapira
    public function calculate(SearchCurrenciesRequest $request): array
    {
        $data = $request->validationData();
        $currencyFrom = $data['currency_from'];
        $currencyTo = $data['currency_to'];
        $clientSum = (float)$data['sum'];

        return $this->currencyService->get($currencyFrom, $currencyTo, $clientSum);
    }

    public function getRates()
    {
        $currencyFrom = 'usdt';
        $currencyTo = 'rub';

        $usdtToRub = $this->currencyService->get($currencyFrom, $currencyTo);
        $rubToUsdt = $this->currencyService->get($currencyTo, $currencyFrom);

        if (!isset($usdtToRub['price'])) {
            return response()->json(['success' => false, 'message' => 'error']);
        }

        $rates = [
            [
                'from' => 'USDTTRC20',
                'to' => 'CASHRUB',
                'in' => '1',
                'out' => $usdtToRub['price'],
                'amount' => '30000000.00',
                'minamount' => '500 USDT',
                'maxamount' => '300000 USDT',
                'city' => 'krasn',
            ],
            [
                'from' => 'CASHRUB',
                'to' => 'USDTTRC20',
                'in' => '1',
                'out' => $rubToUsdt['price'],
                'amount' => '300000.00',
                'minamount' => '50000 CASHRUB',
                'maxamount' => '30000000 CASHRUB',
                'city' => 'krasn',
            ],
        ];

        $xml = new SimpleXMLElement('<rates/>');

        foreach ($rates as $rate) {
            $item = $xml->addChild('item');
            foreach ($rate as $key => $value) {
                $item->addChild($key, htmlspecialchars($value));
            }
        }

        return response($xml->asXML(), 200)
            ->header('Content-Type', 'application/xml');
    }
}
