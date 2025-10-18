<?php

namespace App\Console\Commands;

use App\Models\AnalyticRate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ParseExchangeRates extends Command
{
    protected $signature = 'parse:exchanges';
    protected $description = 'Парсер курсов USDT/RUB с BestChange и Rapira';

    private const TOP_LIMIT = 5; // 👈 сколько строк брать по каждому направлению

    /** Список возможных User-Agent’ов (рандомно подставляем при каждом запросе) */
    private array $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:45.0) Gecko/20100101 Firefox/45.0',
        'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.102 YaBrowser/20.9.3.136 Yowser/2.5 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.105 YaBrowser/21.3.3.230 Yowser/2.5 Safari/537.36',
        'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:47.0) Gecko/20100101 Firefox/62.0',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.88 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 11.1; rv:84.0) Gecko/20100101 Firefox/84.0',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36',
        'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.81 Safari/537.36 Maxthon/5.3.8.2000',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.116 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.88 YaBrowser/20.12.2.105 Yowser/2.5 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.141 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.90 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.131 YaBrowser/21.8.1.468 Yowser/2.5 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.121 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.212 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.61 Safari/537.36',
    ];

    public function handle()
    {
        $this->info('🚀 Начинаем парсинг обменников...');

        // --- Получаем курс биржи ---
        $this->info('📡 Получаем курс биржи Rapira...');
        $rapira = Http::get('https://api.rapira.net/market/exchange-plate-mini', [
            'symbol' => 'USDT/RUB',
        ])->json();

        $ask = $rapira['ask']['highestPrice'] ?? 0; // RUB→USDT
        $bid = $rapira['bid']['highestPrice'] ?? 0; // USDT→RUB
        $this->info("Биржа: ask={$ask}, bid={$bid}");

        $sources = [
            [
                'url' => 'https://www.bestchange.ru/tether-trc20-to-cash-ruble-in-krasn.html',
                'direction' => 'USDT→RUB',
                'from' => 'USDT',
                'to' => 'RUB',
            ],
            [
                'url' => 'https://www.bestchange.ru/cash-ruble-to-tether-trc20-in-krasn.html',
                'direction' => 'RUB→USDT',
                'from' => 'RUB',
                'to' => 'USDT',
            ],
        ];

        $results = [];

        foreach ($sources as $source) {
            // --- имитируем «живое» поведение ---
            sleep(random_int(1, 3));

            $headers = $this->getRandomHeaders();
            $this->info("🔍 Парсим {$source['direction']} (User-Agent: {$headers['User-Agent']})");

            $client = new Client([
                'timeout' => 30,
                'verify' => false,
                'headers' => $headers,
            ]);

            try {
                $html = $client->get($source['url'])->getBody()->getContents();
                $crawler = new Crawler($html);

                $count = 0;

                $crawler->filter('table#content_table tbody tr')->each(
                    function (Crawler $row) use (&$results, $source, $ask, $bid, &$count) {
                        if ($count >= self::TOP_LIMIT) {
                            return;
                        }

                        $cells = $row->filter('td');
                        if ($cells->count() < 4) {
                            return;
                        }

                        $exchange = trim($cells->eq(1)->filter('.ca')->text(''));

                        if ($source['direction'] === 'RUB→USDT') {
                            $rateText = $cells->eq(2)->filter('.fs')->text('');
                        } else {
                            $rateText = $cells->eq(3)->text('');
                        }

                        preg_match('/[\d.,]+/', $rateText, $matches);
                        $rate = isset($matches[0]) ? (float)str_replace(',', '.', $matches[0]) : 0.0;
                        if ($rate <= 0) {
                            return;
                        }

                        $market = $source['direction'] === 'RUB→USDT' ? $ask : $bid;
                        $markup = $this->calculateMarkup($source['direction'], $rate, $ask, $bid);

                        $results[] = [
                            'resource' => 'BestChange',
                            'exchange' => $exchange,
                            'direction' => $source['direction'],
                            'rate' => $rate,
                            'market' => $market,
                            'markup' => $markup,
                        ];

                        AnalyticRate::create([
                            'source' => 'BestChange',
                            'crypto_exchanger' => $exchange,
                            'currency_from' => $source['from'],
                            'currency_to' => $source['to'],
                            'crypto_exchanger_course' => $rate,
                            'crypto_exchange_course' => $market,
                            'plus' => $markup,
                        ]);

                        $count++;
                    }
                );
            } catch (\Throwable $e) {
                $this->error("Ошибка при парсинге {$source['direction']}: " . $e->getMessage());
            }
        }

        $this->exportToExcel($results);
        $this->info('✅ Файл создан: public/rates/exchange_rates.xlsx');
    }

    private function calculateMarkup(string $direction, float $rate, float $ask, float $bid): float
    {
        if ($ask == 0 || $bid == 0) {
            return 0;
        }

        return $direction === 'RUB→USDT'
            ? (($rate - $ask) / $ask) * 100
            : (($bid - $rate) / $bid) * 100;
    }

    private function exportToExcel(array $data): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray(
            ['Ресурс', 'Обменник', 'Направление', 'Курс обмена', 'Биржевой курс (Rapira)', 'Наценка (%)'],
            null,
            'A1'
        );

        $row = 2;
        foreach ($data as $item) {
            $sheet->setCellValue("A{$row}", $item['resource']);
            $sheet->setCellValue("B{$row}", $item['exchange']);
            $sheet->setCellValue("C{$row}", $item['direction']);
            $sheet->setCellValue("D{$row}", round($item['rate'], 4));
            $sheet->setCellValue("E{$row}", round($item['market'], 4));
            $sheet->setCellValue("F{$row}", round($item['markup'], 2));
            $row++;
        }

        $writer = new Xlsx($spreadsheet);

        $now = now()->tz('Europe/Moscow');
        $year = $now->format('Y');
        $month = $now->format('m');
        $day = $now->format('d');

        $relativeDir = "rates/{$year}/{$month}/{$day}";
        $dir = storage_path("app/{$relativeDir}");

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $postfix = now()->tz('Europe/Moscow')->format('d.m.Y_H:i');
        $fileName = "exchange_rates_{$postfix}.xlsx";
        $filePath = "{$dir}/{$fileName}";

        $writer->save($filePath);

        $this->info("✅ Файл сохранён: storage/app/{$relativeDir}/{$fileName}");
    }

    private function getRandomHeaders(): array
    {
        $ua = $this->userAgents[array_rand($this->userAgents)];
        $acceptLanguages = ['ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7', 'ru,en-US;q=0.8,en;q=0.5', 'ru-RU,ru;q=0.9'];
        $encodings = ['gzip, deflate, br', 'gzip, deflate'];
        $referers = ['https://google.com', 'https://yandex.ru', 'https://bing.com', 'https://duckduckgo.com'];

        return [
            'User-Agent' => $ua,
            'Accept-Language' => $acceptLanguages[array_rand($acceptLanguages)],
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            'Referer' => $referers[array_rand($referers)],
            'Accept-Encoding' => $encodings[array_rand($encodings)],
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache',
            'DNT' => random_int(0, 1), // Do Not Track
            'Connection' => 'keep-alive',
        ];
    }
}
