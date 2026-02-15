<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashDesk;
use App\Models\Client;
use App\Models\CryptoRequest;
use App\Models\Currency;
use App\Models\CurrencyExchange;
use App\Models\Partner;
use App\Models\Source;
use App\Models\Status;
use App\Models\User;
use App\Services\CryptoTradeService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CryptoRequestController extends Controller
{
    public function index(Request $request)
    {
        $q = CryptoRequest::query()
            ->with(['client', 'status', 'source', 'currencyFrom', 'currencyTo', 'trade']);

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
        if ($request->filled('source_id')) {
            $q->where('source_id', $request->get('source_id'));
        }
        if ($request->filled('client_id')) {
            $q->where('client_id', $request->get('client_id'));
        }
        if ($request->filled('q')) {
            $search = trim((string)$request->get('q'));
            $q->where('comment', 'like', "%{$search}%");
        }

        $requests = $q->orderByDesc('date')->paginate(30)->withQueryString();

        // справочники для фильтров
        $statuses = Status::query()->orderBy('id')->get();
        $sources = Source::query()->orderBy('name')->get();
        $clients = Client::query()->orderBy('name')->limit(300)->get();

        return view('admin.crypto_requests.index', compact('requests', 'statuses', 'sources', 'clients'));
    }

    public function show(CryptoRequest $cryptoRequest)
    {
        $cryptoRequest->load(['client', 'status', 'source', 'currencyFrom', 'currencyTo', 'trade']);
        $cryptoRequest->date = Carbon::parse($cryptoRequest->date)->timezone('Europe/Moscow')->format('Y-m-d H:i:s');

        return view('admin.crypto_requests.show', compact('cryptoRequest'));
    }

    public function create()
    {
        return view('admin.crypto_requests.create', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);
        $data['status_id'] = Status::where('code', 'new')->first()->id;
        $data['date'] = Carbon::parse($data['date'], 'Europe/Moscow')
            ->setTimezone('UTC')
            ->format('Y-m-d H:i:s');

        $cryptoRequest = CryptoRequest::query()->create($data);

        return redirect()
            ->route('crypto-requests.show', $cryptoRequest)
            ->with('success', 'Заявка создана');
    }

    public function edit(CryptoRequest $cryptoRequest)
    {
        $cryptoRequest->date = Carbon::parse($cryptoRequest->date)->timezone('Europe/Moscow')->format('Y-m-d H:i:s');

        return view(
            'admin.crypto_requests.edit',
            array_merge(
                $this->formData(),
                ['cryptoRequest' => $cryptoRequest]
            )
        );
    }

    public function update(Request $request, CryptoRequest $cryptoRequest)
    {
        $data = $this->validateRequest($request);
        $data['date'] = Carbon::parse($data['date'], 'Europe/Moscow')
            ->setTimezone('UTC')
            ->format('Y-m-d H:i:s');

        $cryptoRequest->fill($data)->save();

        return redirect()
            ->route('crypto-requests.show', $cryptoRequest)
            ->with('success', 'Заявка обновлена');
    }

    public function destroy(CryptoRequest $cryptoRequest)
    {
        // можно запретить удаление, если уже есть сделка
        if ($cryptoRequest->trade) {
            return redirect()
                ->route('crypto-requests.show', $cryptoRequest)
                ->with('error', 'Нельзя удалить заявку: она уже переведена в сделку');
        }

        $cryptoRequest->delete();

        return redirect()
            ->route('crypto-requests.index')
            ->with('success', 'Заявка удалена');
    }

    /**
     * Страница "Перевести в сделку" — форма дозаполнения сделки.
     */
    public function convert(CryptoRequest $cryptoRequest)
    {
        $cryptoRequest->load(['client', 'status', 'source', 'currencyFrom', 'currencyTo', 'trade']);

        if ($cryptoRequest->trade) {
            return redirect()->route('crypto-trades.show', $cryptoRequest->trade);
        }

        // данные для формы сделки
        return view(
            'admin.crypto_requests.convert',
            array_merge(
                $this->tradeFormData(),
                ['cryptoRequest' => $cryptoRequest]
            )
        );
    }

    /**
     * Создание сделки из заявки (через твой CryptoTradeService)
     */
    public function storeConvertedTrade(Request $request, CryptoRequest $cryptoRequest, CryptoTradeService $service)
    {
        if ($cryptoRequest->trade) {
            return redirect()->route('crypto-trades.show', $cryptoRequest->trade);
        }

        $data = $request->validate([
            'operator_id' => ['required', 'integer'],

            // суммы целые
            'amount_income' => ['required', 'integer', 'min:0'],
            'amount_outcome' => ['required', 'integer', 'min:0'],

            'course_of_currency_exchange' => ['required', 'numeric', 'min:0'],
            'currency_exchange_id' => ['nullable', 'integer'],

            // % клиента
            'rate_of_client' => ['required', 'numeric', 'min:0'],
            'course_of_client' => ['required', 'numeric', 'min:0'], // перезапишем ниже

            'partner_id' => ['nullable', 'integer'],
            'rate_of_partner' => ['nullable', 'numeric', 'min:0'],
            'comment' => ['nullable', 'string'],

            'cash_desks' => ['required', 'array', 'min:1'],
            'cash_desks.*.cash_desk_id' => ['required', 'integer'],
            'cash_desks.*.amount' => ['required', 'integer', 'min:0'],
            'cash_desks.*.rate' => ['nullable', 'numeric', 'min:0'],
            'cash_desks.*.course' => ['nullable', 'numeric', 'min:0'],
        ]);

        $cryptoRequest->loadMissing('currencyFrom');

        $fromCode = $cryptoRequest->currencyFrom->code ?? '';
        $ex = (float)$data['course_of_currency_exchange'];
        $rateClientPct = (float)$data['rate_of_client'];
        $k = $rateClientPct / 100;

        // курс клиента: USDT->RUB минус %, RUB->USDT плюс %
        $courseClient = strtoupper($fromCode) === 'USDT'
            ? ($ex - $ex * $k)
            : ($ex + $ex * $k);

        // 2 знака
        $courseClient = round($courseClient, 2);
        $data['course_of_client'] = $courseClient;

        // отдаем
        $income = (int)$data['amount_income'];
        $outcome = strtoupper($fromCode) === 'USDT'
            ? (int)round($income * $courseClient)
            : (int)round($income / $courseClient);

        $data['amount_outcome'] = $outcome;

        // кассы: для наших rate/course = 0, для партнёров course по формуле от биржи и их ставки
        $cashDeskIds = collect($data['cash_desks'])->pluck('cash_desk_id')->unique()->values()->all();
        $desks = CashDesk::query()->whereIn('id', $cashDeskIds)->get()->keyBy('id');

        $data['cash_desks'] = array_map(function ($row) use ($desks, $ex, $fromCode) {
            $desk = $desks->get((int)$row['cash_desk_id']);

            $row['amount'] = (int)$row['amount'];

            if ($desk && $desk->is_our) {
                $row['rate'] = 0;
                $row['course'] = 0;
                return $row;
            }

            $ratePct = (float)($row['rate'] ?? 0);
            $k = $ratePct / 100;

            $course = strtoupper($fromCode) === 'USDT'
                ? ($ex - $ex * $k)
                : ($ex + $ex * $k);

            $row['course'] = round($course, 2);
            $row['rate'] = $ratePct;

            return $row;
        }, $data['cash_desks']);

        $payload = array_merge($data, [
            'date' => $cryptoRequest->date, // UTC
            'client_id' => $cryptoRequest->client_id,
            'source_id' => $cryptoRequest->source_id,
            'currency_from_id' => $cryptoRequest->currency_from_id,
            'currency_to_id' => $cryptoRequest->currency_to_id,
            'crypto_request_id' => $cryptoRequest->id,
        ]);

        $trade = $service->create($payload);

        // если у статусов есть code
        $inTrade = Status::query()->where('code', 'in-trade')->first();
        if ($inTrade) {
            $cryptoRequest->status_id = $inTrade->id;
            $cryptoRequest->save();
        }

        return redirect()
            ->route('crypto-trades.show', $trade)
            ->with('success', 'Заявка переведена в сделку');
    }

    // -------- helpers --------
    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'date' => ['required', 'date'],
            'client_id' => ['required', 'integer'],
            'status_id' => ['integer'],
            'source_id' => ['required', 'integer'],
            'currency_from_id' => ['required', 'integer'],
            'currency_to_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric'],
            'comment' => ['nullable', 'string'],
        ]);
    }

    private function formData(): array
    {
        return [
            'clients' => Client::query()->orderBy('name')->get(),
            'statuses' => Status::query()->orderBy('id')->get(),
            'sources' => Source::query()->orderBy('name')->get(),
            'currencies' => Currency::query()->orderBy('code')->get(),
        ];
    }

    private function tradeFormData(): array
    {
        return [
            'operators' => User::query()->orderBy('name')->get(),
            'partners' => Partner::query()->orderBy('name')->get(),
            'exchanges' => CurrencyExchange::query()->orderBy('name')->get(),
            'cashDesks' => CashDesk::query()->with('currency')->orderBy('name')->get(),
        ];
    }
}
