<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DirectorySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // 1) Статусы (и для заявок, и для сделок — пока общие)
            $statuses = [
                ['name' => 'Новая', 'code' => 'new', 'is_end' => 0],
                ['name' => 'Переведена в сделку', 'code' => 'in-trade', 'is_end' => 1],

            ];

            // 2) Источники
            $sources = [
                ['name' => 'Exnode', 'comment' => ''],
                ['name' => 'Kein', 'comment' => ''],
                ['name' => 'Crypto navigator', 'comment' => ''],
                ['name' => 'Прямые клиенты', 'comment' => ''],
            ];

            // 3) Партнеры
            $partners = [
                ['name' => 'РиС', 'comment' => 'Стас и Рома'],
                ['name' => 'Ян', 'comment' => 'FoxChange'],
            ];

            // 4) Биржи
            $exchanges = [
                ['name' => 'Rapira', 'uri' => 'https://api.rapira.net/market/exchange-plate-mini?symbol=USDT_RUB'],
                ['name' => 'Grinex', 'uri' => 'grinex'],
            ];

// 5) Валюты
            $currencies = [
                ['name' => 'RUB', 'code' => 'RUB'],
                ['name' => 'USDT', 'code' => 'USDT'],
            ];

            foreach ($statuses as $row) {
                DB::table('statuses')->updateOrInsert(
                    ['name' => $row['name']],
                    [
                        'is_end' => (int)$row['is_end'],
                        'code' => $row['code'],
                        'updated_at' => now(),
                        'created_at' => now()
                    ]
                );
            }

            foreach ($sources as $row) {
                DB::table('sources')->updateOrInsert(
                    ['name' => $row['name']],
                    ['comment' => $row['comment'] ?? '', 'updated_at' => now(), 'created_at' => now()]
                );
            }

            foreach ($partners as $row) {
                DB::table('partners')->updateOrInsert(
                    ['name' => $row['name']],
                    ['comment' => $row['comment'] ?? '', 'updated_at' => now(), 'created_at' => now()]
                );
            }

            foreach ($exchanges as $row) {
                DB::table('currency_exchanges')->updateOrInsert(
                    ['uri' => $row['uri']],
                    ['name' => $row['name'], 'updated_at' => now(), 'created_at' => now()]
                );
            }

            foreach ($currencies as $row) {
                DB::table('currencies')->updateOrInsert(
                    ['name' => $row['name']],
                    ['code' => $row['code'], 'updated_at' => now(), 'created_at' => now()],
                );
            }
        });
    }
}
