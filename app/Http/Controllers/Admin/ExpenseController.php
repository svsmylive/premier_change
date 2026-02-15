<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $q = Expense::query()->with(['cashDesk.currency', 'currency']);

        $q->when($request->get('cash_desk_id'), fn($qq) => $qq->where('cash_desk_id', $request->get('cash_desk_id')));
        $q->when($request->get('currency_id'), fn($qq) => $qq->where('currency_id', $request->get('currency_id')));
        $q->when($request->get('date_from'), fn($qq) => $qq->where('date', '>=', $request->get('date_from')));
        $q->when($request->get('date_to'), fn($qq) => $qq->where('date', '<=', $request->get('date_to')));

        return response()->json($q->orderByDesc('date')->paginate(50)->withQueryString());
    }

    public function store(Request $request, ExpenseService $service)
    {
        $data = $request->validate([
            'date' => ['required', 'string'], // пока string как в миграции
            'description' => ['required', 'string'],
            'sum' => ['required', 'numeric'],
            'cash_desk_id' => ['required', 'integer'],
            'currency_id' => ['required', 'integer'],
        ]);

        $expense = $service->create($data);

        return response()->json($expense, 201);
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return response()->noContent();
    }
}
