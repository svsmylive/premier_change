<?php

namespace App\Services;

use App\Models\CashDeskMovement;
use App\Models\CryptoTrade;
use App\Models\OperationType;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CryptoTradeService
{
    /**
     * Ожидаем payload:
     * - trade поля (как в миграции)
     * - cash_desks: массив строк [{cash_desk_id, amount, rate}, ...]
     */
    public function create(array $payload): CryptoTrade
    {
        return DB::transaction(function () use ($payload) {
            $tradeData = Arr::except($payload, ['cash_desks']);
            $cashDesks = $payload['cash_desks'] ?? [];

            /** @var CryptoTrade $trade */
            $trade = CryptoTrade::query()->create($tradeData);

            $this->syncCashDesks($trade, $cashDesks);
            $this->rebuildMovements($trade);

            return $trade->refresh();
        });
    }

    public function update(CryptoTrade $trade, array $payload): CryptoTrade
    {
        return DB::transaction(function () use ($trade, $payload) {
            $tradeData = Arr::except($payload, ['cash_desks']);
            $cashDesks = $payload['cash_desks'] ?? [];

            $trade->fill($tradeData)->save();

            $this->syncCashDesks($trade, $cashDesks);
            $this->rebuildMovements($trade);

            return $trade->refresh();
        });
    }

    /**
     * Пересобирает движения по сделке.
     * Важно: это "минималка". Логику типов операций можно расширять.
     *
     * Принцип:
     * - pivot cash_desks_trade хранит amount/rate для каждой кассы.
     * - мы создаём движения по этим кассам.
     *
     * Я рекомендую: amount в movements хранить со знаком:
     *  + поступление в кассу
     *  - списание из кассы
     *
     * Но так как у тебя пока в pivot нет "side", ниже я делаю нейтральный вариант:
     * - создаём движения типа 'trade' с amount как есть (как ты передал).
     * Дальше ты решишь: передавать amount уже со знаком, или добавлять side.
     */
    public function rebuildMovements(CryptoTrade $trade): void
    {
        // удалить старые движения по сделке
        CashDeskMovement::query()
            ->where('crypto_trade_id', $trade->id)
            ->delete();

        $op = $this->getOrCreateOperationType('expense', 'Расход');

        // создать новые движения по pivot-кассам
        $rows = $trade->cashDesks()->get();

        foreach ($rows as $cashDesk) {
            CashDeskMovement::query()->create([
                'date' => $trade->date,
                'cash_desk_id' => $cashDesk->id,
                'operation_type_id' => $op->id,
                'crypto_trade_id' => $trade->id,
                'amount' => $cashDesk->pivot->amount,
                'comment' => 'Авто-движение по сделке #' . $trade->id,
            ]);
        }
    }

    /**
     * Синхронизация pivot cash_desks_trade
     * Формат: [{cash_desk_id, amount, rate}, ...]
     */
    private function syncCashDesks(CryptoTrade $trade, array $cashDesks): void
    {
        $sync = [];

        foreach ($cashDesks as $row) {
            if (empty($row['cash_desk_id'])) {
                continue;
            }

            $sync[(int)$row['cash_desk_id']] = [
                'amount' => $row['amount'] ?? 0,
                'rate' => $row['rate'] ?? 0,
                'course' => $row['course'] ?? 0,
            ];
        }

        $trade->cashDesks()->sync($sync);
    }

    private function getOrCreateOperationType(string $code, string $name): OperationType
    {
        return OperationType::query()->firstOrCreate(
            ['code' => $code],
            ['name' => $name]
        );
    }
}
