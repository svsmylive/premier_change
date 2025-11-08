<?php

namespace App\Http\Controllers;

use App\Services\CurrencyService;
use App\Services\MarkupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WebhookController extends Controller
{
    public function __construct(
        private readonly CurrencyService $currencyService,
        private readonly MarkupService $markupService,
    ) {
    }

    public function handle(Request $request, string $secret)
    {
        if ($secret !== config('services.telegram.webhook_secret')) {
            return response()->json(['ok' => false, 'error' => 'Forbidden'], 403);
        }

        $payload = $request->all();
        $message = $payload['message'] ?? $payload['edited_message'] ?? null;
        if (!$message) {
            return ['ok' => true];
        }

        $chatId = $message['chat']['id'] ?? null;
        $fromId = $message['from']['id'] ?? null;
        $text = trim((string)($message['text'] ?? ''));

        if (!$chatId || !$fromId || $text === '') {
            return ['ok' => true];
        }

        if (isset($message['date']) && now()->timestamp - $message['date'] > 60) {
            return ['ok' => true]; // игнорируем старые апдейты старше 1 минуты
        }

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
            $this->sendMenu($chatId); // показать кнопки
            return ['ok' => true];
        }

        if ($cmd === 'info' || $cmd === '/info') {
            try {
                // Получаем текущие наценки
                $buyMarkup = round($this->markupService->getRubUsdt() * 100, 2);
                $sellMarkup = round($this->markupService->getUsdtRub() * 100, 2);

                // Курс покупки USDT за рубли (сколько ₽ стоит 1 USDT)
                $buyRate = $this->currencyService->get('rub', 'usdt', 10000);
                $rubFor1Usdt = 0;
                if (!empty($buyRate['total']) && (float)$buyRate['total'] > 0) {
                    $rubFor1Usdt = 10000 / (float)$buyRate['total'];
                }

                // Курс продажи USDT за рубли (сколько ₽ получаешь за 1 USDT)
                $sellRate = $this->currencyService->get('usdt', 'rub', 1);
                $rubFrom1Usdt = (float)$sellRate['price'];

                // Формируем красивое сообщение
                $text = "ℹ️ *Информация по курсам и наценкам*\n\n"
                    . "💹 *Текущие курсы:*\n"
                    . "• Покупка (RUB → USDT):  *" . number_format($rubFor1Usdt, 2, '.', ' ') . " ₽*\n"
                    . "• Продажа (USDT → RUB): *" . number_format($rubFrom1Usdt, 2, '.', ' ') . " ₽*\n\n"
                    . "⚙️ *Текущие наценки:*\n"
                    . "• RUB → USDT (покупка):  *{$buyMarkup}%*\n"
                    . "• USDT → RUB (продажа): *{$sellMarkup}%*";

                $this->send($chatId, $text, true); // Markdown включен
            } catch (\Throwable $e) {
                $this->send($chatId, "❌ Ошибка при получении данных:\n" . $e->getMessage());
            }

            return ['ok' => true];
        }

        if (str_starts_with($cmd, 'buy')) {
            $fraction = round(min(max($this->extractPercent($cmd), 0.0005), 0.2), 4);
            if ($fraction < 0 || $fraction > 0.2) {
                $this->send($chatId, "Значение вне диапазона (0–20%).", true);
            }
            if ($fraction === null) {
                $this->send($chatId, "Неверный формат. Пример: `buy 2%`", true);
            } else {
                $this->markupService->setRubUsdt($fraction);

                $buyMarkup = round($this->markupService->getRubUsdt() * 100, 2);
                $sellMarkup = round($this->markupService->getUsdtRub() * 100, 2);
                $buyRate = $this->currencyService->get('rub', 'usdt', 10000);
                $rubFor1Usdt = !empty($buyRate['total']) && (float)$buyRate['total'] > 0
                    ? 10000 / (float)$buyRate['total']
                    : 0;
                $sellRate = $this->currencyService->get('usdt', 'rub', 1);
                $rubFrom1Usdt = (float)$sellRate['price'];


                $text = "✅ Наценка для приёма (курс обмена) обновлена.\n\n"
                    . "ℹ️ *Информация по курсам и наценкам*\n\n"
                    . "💹 *Текущие курсы:*\n"
                    . "• Покупка (RUB → USDT):  *" . number_format($rubFor1Usdt, 2, '.', ' ') . " ₽*\n"
                    . "• Продажа (USDT → RUB): *" . number_format($rubFrom1Usdt, 2, '.', ' ') . " ₽*\n\n"
                    . "⚙️ *Текущие наценки:*\n"
                    . "• RUB → USDT (покупка):  *{$buyMarkup}%*\n"
                    . "• USDT → RUB (продажа): *{$sellMarkup}%*";

                $this->send($chatId, $text, true);
                $this->sendMenu($chatId);
            }
            return ['ok' => true];
        }

        if (str_starts_with($cmd, 'sell')) {
            $fraction = round(min(max($this->extractPercent($cmd), 0.0005), 0.2), 4);
            if ($fraction === null) {
                $this->send($chatId, "Неверный формат. Пример: `sell 1.5%`", true);
            } else {
                if ($fraction < 0 || $fraction > 0.2) {
                    $this->send($chatId, "Значение вне диапазона (0–20%).", true);
                } else {
                    $this->markupService->setUsdtRub($fraction);

                    $buyMarkup = round($this->markupService->getRubUsdt() * 100, 2);
                    $sellMarkup = round($this->markupService->getUsdtRub() * 100, 2);
                    $buyRate = $this->currencyService->get('rub', 'usdt', 10000);
                    $rubFor1Usdt = !empty($buyRate['total']) && (float)$buyRate['total'] > 0
                        ? 10000 / (float)$buyRate['total']
                        : 0;
                    $sellRate = $this->currencyService->get('usdt', 'rub', 1);
                    $rubFrom1Usdt = (float)$sellRate['price'];

                    $text = "✅ Наценка для выдачи (курс обмена) обновлена.\n\n"
                        . "ℹ️ *Информация по курсам и наценкам*\n\n"
                        . "💹 *Текущие курсы:*\n"
                        . "• Покупка (RUB → USDT):  *" . number_format($rubFor1Usdt, 2, '.', ' ') . " ₽*\n"
                        . "• Продажа (USDT → RUB): *" . number_format($rubFrom1Usdt, 2, '.', ' ') . " ₽*\n\n"
                        . "⚙️ *Текущие наценки:*\n"
                        . "• RUB → USDT (покупка):  *{$buyMarkup}%*\n"
                        . "• USDT → RUB (продажа): *{$sellMarkup}%*";

                    $this->send($chatId, $text, true);
                    $this->sendMenu($chatId);
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
        if (!preg_match('~(-?[\d\.,]+)\s*\%?~', $cmd, $m)) {
            return null;
        }
        return $this->normalizePercentToFraction($m[1]);
    }

    private function normalizePercentToFraction(string $num): ?float
    {
        $num = str_replace(',', '.', trim($num));
        if (!is_numeric($num)) {
            return null;
        }
        return (float)$num / 100.0;
    }

    private function replyGet(int $chatId, string $prefix = null): void
    {
        $sell = $this->markupService->getUsdtRub(); // USDT->RUB
        $buy = $this->markupService->getRubUsdt(); // RUB->USDT
        $msg = ($prefix ? $prefix . "\n" : '') .
            "Текущие наценки:\n" .
            "• Выдача (USDT→RUB): " . round($sell * 100, 4) . " %\n" .
            "• Приём  (RUB→USDT): " . round($buy * 100, 4) . " %";
        $this->send($chatId, $msg);
    }

    private function helpText(): string
    {
        return "Команды:\n" .
            "• `info` — показать текущие настройки\n" .
            "• `buy 2%` — наценка при приёме (RUB→USDT)\n" .
            "• `sell 1.5%` — наценка при выдаче (USDT→RUB)\n" .
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

    private function sendMenu(int $chatId): void
    {
        $buttons = [
            [['text' => 'info']],
        ];

        Http::post("https://api.telegram.org/bot" . config('services.telegram.bot_token') . "/sendMessage", [
            'chat_id' => $chatId,
            'reply_markup' => json_encode([
                'keyboard' => $buttons,
                'resize_keyboard' => true,
            ]),
        ]);
    }
}

