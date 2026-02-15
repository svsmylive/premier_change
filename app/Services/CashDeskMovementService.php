<?php

namespace App\Services;

use App\Models\CashDeskMovement;
use Illuminate\Support\Facades\DB;

class CashDeskMovementService
{
    public function createManual(array $data): CashDeskMovement
    {
        return DB::transaction(function () use ($data) {
            // manual movement: crypto_trade_id обычно null
            return CashDeskMovement::query()->create($data);
        });
    }

    public function delete(CashDeskMovement $movement): void
    {
        DB::transaction(function () use ($movement) {
            // тут можно добавить запрет удаления, если привязан к финальному статусу сделки и т.п.
            $movement->delete();
        });
    }
}
