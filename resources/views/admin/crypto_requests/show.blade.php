@extends('admin.layout')
@section('title', 'Заявка #' . $cryptoRequest->id)

@section('content')
    <div class="container py-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Заявка #{{ $cryptoRequest->id }}</h3>
                <div class="text-muted">{{ $cryptoRequest->date?->format('d.m.Y H:i') }}</div>
            </div>

            <div class="d-flex gap-2">
                @if($cryptoRequest->trade)
                    <a href="{{ route('crypto-trades.show', $cryptoRequest->trade) }}" class="btn btn-success">
                        Открыть сделку #{{ $cryptoRequest->trade->id }}
                    </a>
                @else
                    <a href="{{ route('crypto-requests.convert', $cryptoRequest) }}" class="btn btn-primary">
                        Перевести в сделку
                    </a>
                @endif

                <a href="{{ route('crypto-requests.edit', $cryptoRequest) }}" class="btn btn-outline-primary">Редактировать</a>
                <a href="{{ route('crypto-requests.index') }}" class="btn btn-outline-secondary">Назад</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small">Клиент</div>
                        <div class="fw-semibold">{{ $cryptoRequest->client->name ?? '—' }}</div>
                    </div>

                    <div class="col-md-3">
                        <div class="text-muted small">Статус</div>
                        <div class="fw-semibold">{{ $cryptoRequest->status->name ?? '—' }}</div>
                    </div>

                    <div class="col-md-3">
                        <div class="text-muted small">Источник</div>
                        <div class="fw-semibold">{{ $cryptoRequest->source->name ?? '—' }}</div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">Направление</div>
                        <div class="fw-semibold">
                            {{ $cryptoRequest->currencyFrom->code ?? '' }}
                            → {{ $cryptoRequest->currencyTo->code ?? '' }}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">Сумма</div>
                        <div class="fw-semibold">
                            {{ number_format((float)$cryptoRequest->amount, 0, '.', ' ') }}
                            {{ $cryptoRequest->currencyFrom->code ?? '' }}
                        </div>
                    </div>

                    @if($cryptoRequest->comment)
                        <div class="col-12">
                            <hr class="my-1">
                            <div class="text-muted small">Комментарий</div>
                            <div>{{ $cryptoRequest->comment }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
