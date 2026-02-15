<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashDeskMovement;
use App\Services\CashDeskMovementService;
use Illuminate\Http\Request;

class CashDeskMovementController extends Controller
{
    public function index(Request $request)
    {
        $q = CashDeskMovement::query()->with(['cashDesk.currency', 'operationType', 'trade']);

        $q->when($request->get('cash_desk_id'), fn($qq) => $qq->where('cash_desk_id', $request->get('cash_desk_id')));
        $q->when($request->get('operation_type_id'), fn($qq) => $qq->where('operation_type_id', $request->get('operation_type_id')));
        $q->when($request->get('date_from'), fn($qq) => $qq->where('date', '>=', $request->get('date_from')));
        $q->when($request->get('date_to'), fn($qq) => $qq->where('date', '<=', $request->get('date_to')));

        return response()->json($q->orderByDesc('date')->paginate(100)->withQueryString());
    }

    public function store(Request $request, CashDeskMovementService $service)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'cash_desk_id' => ['required', 'integer'],
            'operation_type_id' => ['required', 'integer'],
            'crypto_trade_id' => ['nullable', 'integer'],
            'amount' => ['required', 'numeric'],
            'comment' => ['nullable', 'string', 'max:255'],
        ]);

        $movement = $service->createManual($data);

        return response()->json($movement->load(['cashDesk', 'operationType']), 201);
    }

    public function destroy(CashDeskMovement $cashDeskMovement, CashDeskMovementService $service)
    {
        $service->delete($cashDeskMovement);

        return response()->noContent();
    }
}
