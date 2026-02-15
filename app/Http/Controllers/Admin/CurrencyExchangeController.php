<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CurrencyExchange;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CurrencyExchangeController extends Controller
{
    public function index(Request $request)
    {
        $q = CurrencyExchange::query();

        if ($search = trim((string)$request->get('q'))) {
            $q->where(function ($qq) use ($search) {
                $qq->where('name', 'like', "%{$search}%")
                    ->orWhere('uri', 'like', "%{$search}%");
            });
        }

        $exchanges = $q->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.currency_exchanges.index', compact('exchanges'));
    }

    public function create()
    {
        return view('admin.currency_exchanges.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'uri' => ['required', 'string', 'max:255', 'unique:currency_exchanges,uri'],
        ]);

        CurrencyExchange::query()->create($data);

        return redirect()
            ->route('currency-exchanges.index')
            ->with('success', 'Биржа создана');
    }

    public function edit(CurrencyExchange $currency_exchange)
    {
        return view('admin.currency_exchanges.edit', ['exchange' => $currency_exchange]);
    }

    public function update(Request $request, CurrencyExchange $currency_exchange)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'uri' => [
                'required',
                'string',
                'max:255',
                Rule::unique('currency_exchanges', 'uri')->ignore($currency_exchange->id),
            ],
        ]);

        $currency_exchange->fill($data)->save();

        return redirect()
            ->route('currency-exchanges.index')
            ->with('success', 'Биржа обновлена');
    }

    public function destroy(CurrencyExchange $currency_exchange)
    {
        $currency_exchange->delete();

        return redirect()
            ->route('currency-exchanges.index')
            ->with('success', 'Биржа удалена');
    }
}
