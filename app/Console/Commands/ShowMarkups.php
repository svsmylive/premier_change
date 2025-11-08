<?php

namespace App\Console\Commands;

use App\Services\MarkupService;
use Illuminate\Console\Command;

class ShowMarkups extends Command
{
    protected $signature = 'exchange:markups';
    protected $description = 'Show current RUB→USDT / USDT→RUB markups';

    public function handle(MarkupService $markupService): int
    {
        $this->info(
            sprintf(
                "USDT→RUB (sell): %.4f%%\nRUB→USDT (buy): %.4f%%",
                $markupService->getUsdtRub() * 100,
                $markupService->getRubUsdt() * 100
            )
        );
        return self::SUCCESS;
    }
}
