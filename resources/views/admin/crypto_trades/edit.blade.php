@extends('admin.layout')

@section('title', 'Редактирование сделки #' . $trade->id)

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Редактирование сделки #{{ $trade->id }}</h3>
            <a href="{{ route('crypto-trades.show', $trade) }}" class="btn btn-outline-secondary">Назад</a>
        </div>

        @include('admin.crypto_trades.form', [
            'action' => route('crypto-trades.update', $trade),
            'method' => 'PUT',
            'trade' => $trade
        ])
    </div>
@endsection
