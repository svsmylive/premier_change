@extends('admin.layout')

@section('title', 'Сделка #' . $cryptoTrade->id)

@section('content')
    <div class="container py-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Сделка #{{ $cryptoTrade->id }}</h3>
                <div class="text-muted">
                    {{ $cryptoTrade->date?->format('d.m.Y H:i') }}
                </div>
            </div>

            <div class="d-flex gap-2">
                {{--                <a href="{{ route('crypto-trades.edit', $cryptoTrade) }}" class="btn btn-outline-primary">--}}
                {{--                    Редактировать--}}
                {{--                </a>--}}
                <a href="{{ route('crypto-trades.index') }}" class="btn btn-outline-secondary">
                    Назад
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-body">

                        <div class="mb-2">
                        <span class="badge bg-light text-dark border">
                            {{ $cryptoTrade->currencyFrom->code ?? '' }} →
                            {{ $cryptoTrade->currencyTo->code ?? '' }}
                        </span>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="text-muted small">Клиент</div>
                                <div class="fw-semibold">{{ $cryptoTrade->client->name ?? '—' }}</div>
                                <div class="text-muted small">#{{ $cryptoTrade->client_id }}</div>
                            </div>

                            <div class="col-md-6">
                                <div class="text-muted small">Оператор</div>
                                <div class="fw-semibold">{{ $cryptoTrade->operator->name ?? '—' }}</div>
                            </div>

                            <div class="col-md-6">
                                <div class="text-muted small">Источник</div>
                                <div class="fw-semibold">{{ $cryptoTrade->request->source->name ?? '—' }}</div>
                            </div>

                            <div class="col-md-6">
                                <div class="text-muted small">Партнёр</div>
                                <div class="fw-semibold">{{ $cryptoTrade->partner->name ?? '—' }}</div>
                            </div>

                            <div class="col-md-6">
                                <div class="text-muted small">Приняли</div>
                                <div class="fw-semibold">
                                    {{ number_format((float)$cryptoTrade->amount_income, 0, '.', ' ') }}
                                    {{ $cryptoTrade->currencyFrom->code ?? '' }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="text-muted small">Выдали</div>
                                <div class="fw-semibold">
                                    {{ number_format((float)$cryptoTrade->amount_outcome, 0, '.', ' ') }}
                                    {{ $cryptoTrade->currencyTo->code ?? '' }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="text-muted small">Курс клиента</div>
                                <div class="fw-semibold">{{ $cryptoTrade->course_of_client }}</div>
                            </div>

                            <div class="col-md-6">
                                <div class="text-muted small">Курс биржи {{ $cryptoTrade->currencyExchange->name ?? '' }}</div>
                                <div class="fw-semibold">
                                    {{ $cryptoTrade->course_of_currency_exchange ?? '—' }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="text-muted small">Ставка партнёра</div>
                                <div class="fw-semibold">
                                    {{ $cryptoTrade->rate_of_partner ?? '—' }}
                                </div>
                            </div>
                        </div>

                        @if($cryptoTrade->comment)
                            <hr>
                            <div class="text-muted small">Комментарий</div>
                            <div>{{ $cryptoTrade->comment }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header bg-white">
                        <div class="fw-semibold">Кассы в сделке</div>
                    </div>

                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead class="table-light">
                            <tr>
                                <th>Касса</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Rate</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($cryptoTrade->cashDesks as $cd)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $cd->name }}</div>
                                        <div class="text-muted small">{{ $cd->currency->code ?? '' }}</div>
                                    </td>
                                    <td class="text-end">
                                        {{ number_format((float)$cd->pivot->amount, 0, '.', ' ') }}
                                    </td>
                                    <td class="text-end">
                                        {{ $cd->pivot->rate }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">
                                        Кассы не указаны
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header bg-white">
                        <div class="fw-semibold">Движения по кассам</div>
                    </div>

                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead class="table-light">
                            <tr>
                                <th>Дата</th>
                                <th>Касса</th>
                                <th class="text-end">Сумма</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($cryptoTrade->movements as $m)
                                <tr>
                                    <td class="text-muted small">{{ $m->date?->format('d.m H:i') }}</td>
                                    <td class="small">{{ $m->cashDesk->name ?? '' }}</td>
                                    <td class="text-end">
                                        {{ number_format((float)$m->amount, 0, '.', ' ') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">
                                        Движений нет
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
