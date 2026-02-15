<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashDesk;
use App\Models\Client;
use App\Models\CryptoTrade;
use App\Models\Currency;
use App\Models\CurrencyExchange;
use App\Models\Partner;
use App\Models\Source;
use App\Models\Status;
use App\Services\CryptoTradeService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CryptoTradeController extends Controller
{
    public function index(Request $request)
    {
        $q = CryptoTrade::query()
            ->with([
                'client',
                'source',
                'partner',
                'currencyFrom',
                'currencyTo',
                'operator',
                'currencyExchange',
            ]);

        // фильтры
        if ($request->filled('date_from')) {
            $dateFrom = Carbon::parse($request->get('date_from'), 'Europe/Moscow')
                ->setTimezone('UTC');

            $q->where('date', '>=', $dateFrom);
        }

        if ($request->filled('date_to')) {
            $dateTo = Carbon::parse($request->get('date_to'), 'Europe/Moscow')
                ->endOfMinute()          // чтобы включить всю минуту из datetime-local
                ->setTimezone('UTC');

            $q->where('date', '<=', $dateTo);
        }
        if ($request->filled('status_id')) {
            $q->where('status_id', $request->get('status_id'));
        }
        if ($request->filled('partner_id')) {
            $q->where('partner_id', $request->get('partner_id'));
        }
        if ($request->filled('source_id')) {
            $q->whereHas('request', function ($query) use ($request) {
                $query->where('source_id', $request->get('source_id'));
            });
        }
        if ($request->filled('operator_id')) {
            $q->where('operator_id', $request->get('operator_id'));
        }
        if ($request->filled('client_id')) {
            $q->where('client_id', $request->get('client_id'));
        }

        // поиск по комменту
        if ($search = trim((string)$request->get('q'))) {
            $q->where('comment', 'like', "%{$search}%");
        }

        $trades = $q->orderByDesc('date')->paginate(30)->withQueryString();

        // справочники для фильтров
        $statuses = Status::query()->orderBy('id')->get();
        $partners = Partner::query()->orderBy('name')->get();
        $sources = Source::query()->orderBy('name')->get();
        $operators = User::query()->orderBy('name')->get();
        $clients = Client::query()->orderBy('name')->limit(300)->get(); // для фильтра

        return view(
            'admin.crypto_trades.index',
            compact(
                'trades',
                'statuses',
                'partners',
                'sources',
                'operators',
                'clients'
            )
        );
    }

    public function show(CryptoTrade $cryptoTrade)
    {
        $cryptoTrade->load([
            'client',
            'source',
            'partner',
            'currencyFrom',
            'currencyTo',
            'operator',
            'currencyExchange',
            'cashDesks.currency',
            'movements.operationType',
            'request.source',
        ]);

        return view('admin.crypto_trades.show', compact('cryptoTrade'));
    }

    public function create()
    {
        return view('admin.crypto_trades.create', $this->formData());
    }

    public function store(Request $request, CryptoTradeService $service)
    {
        $data = $this->validateTrade($request);

        $trade = $service->create($data);

        return redirect()
            ->route('crypto-trades.show', $trade)
            ->with('success', 'Сделка создана');
    }

    public function edit(CryptoTrade $cryptoTrade)
    {
        $cryptoTrade->load('cashDesks');

        return view(
            'admin.crypto_trades.edit',
            array_merge(
                $this->formData(),
                ['trade' => $cryptoTrade]
            )
        );
    }

    public function update(Request $request, CryptoTrade $cryptoTrade, CryptoTradeService $service)
    {
        $data = $this->validateTrade($request);

        $trade = $service->update($cryptoTrade, $data);

        return redirect()
            ->route('crypto-trades.show', $trade)
            ->with('success', 'Сделка обновлена');
    }

    public function destroy(CryptoTrade $cryptoTrade)
    {
        // лучше не удалять сделки, но оставим пока так
        $cryptoTrade->delete();

        return redirect()
            ->route('crypto-trades.index')
            ->with('success', 'Сделка удалена');
    }

    private function formData(): array
    {
        return [
            'clients' => Client::query()->orderBy('name')->get(),
            'statuses' => Status::query()->orderBy('id')->get(),
            'sources' => Source::query()->orderBy('name')->get(),
            'partners' => Partner::query()->orderBy('name')->get(),
            'currencies' => Currency::query()->orderBy('code')->get(),
            'operators' => User::query()->orderBy('name')->get(),
            'exchanges' => CurrencyExchange::query()->orderBy('name')->get(),
            'cashDesks' => CashDesk::query()->with('currency')->orderBy('name')->get(),
        ];
    }

    private function validateTrade(Request $request): array
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'client_id' => ['required', 'integer'],
            'currency_from_id' => ['required', 'integer'],
            'currency_to_id' => ['required', 'integer'],
            'operator_id' => ['required', 'integer'],

            'amount_income' => ['required', 'numeric'],
            'course_of_client' => ['required', 'numeric'],
            'course_of_currency_exchange' => ['nullable', 'numeric'],
            'currency_exchange_id' => ['nullable', 'integer'],

            'partner_id' => ['nullable', 'integer'],
            'rate_of_partner' => ['nullable', 'numeric'],

            'amount_outcome' => ['required', 'numeric'],
            'comment' => ['nullable', 'string'],

            // pivot кассы
            'cash_desks' => ['nullable', 'array'],
            'cash_desks.*.cash_desk_id' => ['required', 'integer'],
            'cash_desks.*.amount' => ['required', 'numeric'],
            'cash_desks.*.rate' => ['required', 'numeric'],
        ]);

        // если не передали — пустой массив
        $data['cash_desks'] = $data['cash_desks'] ?? [];

        return $data;
    }
}
