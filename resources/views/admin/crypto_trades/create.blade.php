@extends('admin.layout')

@section('title', 'Новая сделка')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Новая сделка</h3>
            <a href="{{ route('crypto-trades.index') }}" class="btn btn-outline-secondary">Назад</a>
        </div>

        @include('admin.crypto_trades.form', [
            'action' => route('crypto-trades.store'),
            'method' => 'POST',
            'trade' => null
        ])
    </div>
@endsection
