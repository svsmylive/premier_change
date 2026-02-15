<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\OperationType;
use App\Models\CashDeskMovement;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function create(array $data): Expense
    {
        return DB::transaction(function () use ($data) {
            $expense = Expense::query()->create($data);

            // Авто-движение по кассе (минус)
            $op = OperationType::query()->firstOrCreate(
                ['code' => 'expense'],
                ['name' => 'Расход']
            );

            CashDeskMovement::query()->create([
                'date' => $data['date'],
                // у тебя date string, но в movements datetime — лучше передавать datetime строку
                'cash_desk_id' => $data['cash_desk_id'],
                'operation_type_id' => $op->id,
                'crypto_trade_id' => null,
                'amount' => -1 * (float)$data['sum'],
                'comment' => $data['description'] ?? null,
            ]);

            return $expense;
        });
    }
}
