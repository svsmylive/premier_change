<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExchangeSettingsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('exchange_settings')->upsert([
            ['key' => 'markup_usdt_rub', 'value' => 0.002, 'updated_at' => now(), 'created_at' => now()],
            // USDT->RUB (выдача)
            ['key' => 'markup_rub_usdt', 'value' => 0.010, 'updated_at' => now(), 'created_at' => now()],
            // RUB->USDT (приём)
        ], ['key'], ['value', 'updated_at']);
    }
}
