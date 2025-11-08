<?php

// app/Http/Controllers/Telegram/WebhookController.php
namespace App\Http\Controllers;

use App\Services\MarkupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WebhookController extends Controller
{
    public function __construct(
        private readonly MarkupService $markupService
    ) {}

    public function handle(Request $request, string $secret)
    {
        if ($secret !== config('services.telegram.webhook_secret')) {
            return response()->json(['ok' => false, 'error' => 'Forbidden'], 403);
        }

        $payload = $request->all();
        $message = $payload['message'] ?? $payload['edited_message'] ?? null;
        if (!$message) return ['ok' => true];

        $chatId = $message['chat']['id'] ?? null;
        $fromId = $message['from']['id'] ?? null;
        $text = trim((string)($message['text'] ?? ''));

        if (!$chatId || !$fromId || $text === '') return ['ok' => true];

        // Проверка доступа
        $allowed = collect(explode(',', (string)config('services.telegram.allowed_user_ids')))
            ->filter()->map(fn($v) => (int)trim($v))->contains((int)$fromId);

        if (!$allowed) {
            $this->send($chatId, "⛔ Доступ запрещён.");
            return ['ok' => true];
        }

        // Команды:
        // 1) buy 2%     => RUB->USDT (приём)
        // 2) sell 1.5%  => USDT->RUB (выдача)
        // 3) get        => показать текущие наценки
        // Дополнительно поддержим /start и help

        $cmd = strtolower($text);

        if (str_starts_with($cmd, '/start') || str_starts_with($cmd, 'help')) {
            $this->send($chatId, $this->helpText());
            return ['ok' => true];
        }

        if ($cmd === 'get' || $cmd === '/get') {
            $this->replyGet($chatId);
            return ['ok' => true];
        }

        if (str_starts_with($cmd, 'buy')) {
            $fraction = $this->extractPercent($cmd);
            if ($fraction === null) {
                $this->send($chatId, "Неверный формат. Пример: `buy 2%`", true);
            } else {
                // Ограничим разумными пределами, например 0..20%
                if ($fraction < 0 || $fraction > 0.2) {
                    $this->send($chatId, "Значение вне диапазона (0–20%).", true);
                } else {
                    $this->markupService->setRubUsdt($fraction);
                    $this->replyGet($chatId, "✅ Наценка для приёма (RUB→USDT) обновлена.");
                }
            }
            return ['ok' => true];
        }

        if (str_starts_with($cmd, 'sell')) {
            $fraction = $this->extractPercent($cmd);
            if ($fraction === null) {
                $this->send($chatId, "Неверный формат. Пример: `sell 1.5%`", true);
            } else {
                if ($fraction < 0 || $fraction > 0.2) {
                    $this->send($chatId, "Значение вне диапазона (0–20%).", true);
                } else {
                    $this->markupService->setUsdtRub($fraction);
                    $this->replyGet($chatId, "✅ Наценка для выдачи (USDT→RUB) обновлена.");
                }
            }
            return ['ok' => true];
        }

        // alias: можно задавать напрямую ключ: value
        if (preg_match('~^(usdt[_\-]?rub|rub[_\-]?usdt)\s+([\d\.,]+)\%?$~i', $cmd, $m)) {
            $key = strtolower(str_replace(['-', '_'], '_', $m[1]));
            $fraction = $this->normalizePercentToFraction($m[2]);

            if ($fraction === null) {
                $this->send($chatId, "Неверное число. Пример: `usdt_rub 0.3%`", true);
            } else {
                if ($fraction < 0 || $fraction > 0.2) {
                    $this->send($chatId, "Значение вне диапазона (0–20%).", true);
                } else {
                    $key === 'usdt_rub'
                        ? $this->markupService->setUsdtRub($fraction)
                        : $this->markupService->setRubUsdt($fraction);

                    $this->replyGet($chatId, "✅ Наценка обновлена для {$key}.");
                }
            }
            return ['ok' => true];
        }

        // Иначе — показать справку
        $this->send($chatId, $this->helpText());
        return ['ok' => true];
    }

    private function extractPercent(string $cmd): ?float
    {
        if (!preg_match('~(-?[\d\.,]+)\s*\%?~', $cmd, $m)) return null;
        return $this->normalizePercentToFraction($m[1]);
    }

    private function normalizePercentToFraction(string $num): ?float
    {
        $num = str_replace(',', '.', trim($num));
        if (!is_numeric($num)) return null;
        return (float)$num / 100.0;
    }

    private function replyGet(int $chatId, string $prefix = null): void
    {
        $sell = $this->markupService->getUsdtRub(); // USDT->RUB
        $buy  = $this->markupService->getRubUsdt(); // RUB->USDT
        $msg = ($prefix ? $prefix."\n" : '') .
            "Текущие наценки:\n".
            "• Выдача (USDT→RUB): ".round($sell*100, 4)." %\n".
            "• Приём  (RUB→USDT): ".round($buy*100, 4)." %";
        $this->send($chatId, $msg);
    }

    private function helpText(): string
    {
        return "Команды:\n".
            "• `get` — показать текущие наценки\n".
            "• `buy 2%` — наценка при приёме (RUB→USDT)\n".
            "• `sell 1.5%` — наценка при выдаче (USDT→RUB)\n".
            "• `usdt_rub 0.3%` или `rub_usdt 2%` — задать напрямую\n".
            "\nДиапазон: 0–20%";
    }

    private function send(int $chatId, string $text, bool $markdown = false): void
    {
        $token = config('services.telegram.bot_token');
        Http::asForm()
            ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => $markdown ? 'Markdown' : null,
                'disable_web_page_preview' => 1,
            ]);
    }
}

