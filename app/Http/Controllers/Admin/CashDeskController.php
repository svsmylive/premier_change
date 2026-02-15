<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashDesk;
use App\Models\CashDeskMovement;
use App\Models\Currency;
use Illuminate\Http\Request;

class CashDeskController extends Controller
{
    public function index(Request $request)
    {
        $q = CashDesk::query()
            ->with('currency')
            ->addSelect([
                'balance' => CashDeskMovement::query()
                    ->from('cash_desk_movements as m')
                    ->join('operations_types as ot', 'ot.id', '=', 'm.operation_type_id')
                    ->selectRaw("
                COALESCE(SUM(
                    CASE
                        WHEN ot.code = 'expense' THEN -m.amount
                        ELSE m.amount
                    END
                ), 0)
            ")
                    ->whereColumn('m.cash_desk_id', 'cash_desks.id')
            ]);


        if ($search = trim((string)$request->get('q'))) {
            $q->where('name', 'like', "%{$search}%");
        }

        if (($isOur = $request->get('is_our')) !== null && $isOur !== '') {
            $q->where('is_our', (bool)$isOur);
        }

        if ($currencyId = $request->get('currency_id')) {
            $q->where('currency_id', (int)$currencyId);
        }

        // сортировка
        $sort = $request->get('sort', 'name');
        $dir = $request->get('dir', 'asc') === 'desc' ? 'desc' : 'asc';

        if (in_array($sort, ['name', 'balance'], true)) {
            $q->orderBy($sort, $dir);
        } else {
            $q->orderBy('name');
        }

        $cashDesks = $q->paginate(20)->withQueryString();

        $currencies = Currency::query()->orderBy('code')->get();

        return view('admin.cash_desks.index', compact('cashDesks', 'currencies'));
    }

    public function create()
    {
        $currencies = Currency::query()->orderBy('code')->get();
        return view('admin.cash_desks.create', compact('currencies'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'currency_id' => ['required', 'integer'],
            'is_our' => ['nullable'], // checkbox
        ]);

        $data['is_our'] = (bool)($data['is_our'] ?? false);

        CashDesk::query()->create($data);

        return redirect()
            ->route('cash-desks.index')
            ->with('success', 'Касса создана');
    }

    public function edit(CashDesk $cashDesk)
    {
        $currencies = Currency::query()->orderBy('code')->get();
        return view('admin.cash_desks.edit', compact('cashDesk', 'currencies'));
    }

    public function update(Request $request, CashDesk $cashDesk)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'currency_id' => ['required', 'integer'],
            'is_our' => ['nullable'],
        ]);

        $data['is_our'] = (bool)($data['is_our'] ?? false);

        $cashDesk->fill($data)->save();

        return redirect()
            ->route('cash-desks.index')
            ->with('success', 'Касса обновлена');
    }

    public function destroy(CashDesk $cashDesk)
    {
        // позже можно запретить удаление, если есть movements или сделки
        $cashDesk->delete();

        return redirect()
            ->route('cash-desks.index')
            ->with('success', 'Касса удалена');
    }
}
