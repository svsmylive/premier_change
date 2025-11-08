<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramSetWebhook extends Command
{
    protected $signature = 'telegram:set-webhook {url?}';
    protected $description = 'Set Telegram webhook to {url}/api/telegram/webhook/{secret}';

    public function handle(): int
    {
        $base = rtrim($this->argument('url') ?: config('app.url'), '/');
        $secret = config('services.telegram.webhook_secret');
        $token = config('services.telegram.bot_token');

        if (!$token || !$secret) {
            $this->error('TELEGRAM_BOT_TOKEN or TELEGRAM_WEBHOOK_SECRET is missing.');
            return self::FAILURE;
        }

        $webhookUrl = "{$base}/api/telegram/webhook/{$secret}";
        $resp = Http::asForm()->post("https://api.telegram.org/bot{$token}/setWebhook", [
            'url' => $webhookUrl,
            // опционально: секретный заголовок, сертификат и т.п.
        ])->json();

        $this->info('Request: ' . $webhookUrl);
        $this->line('Response: ' . json_encode($resp));
        return self::SUCCESS;
    }
}
