@extends('admin.layout')
@section('title', 'Новая заявка')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Новая заявка</h3>
            <a href="{{ route('crypto-requests.index') }}" class="btn btn-outline-secondary">Назад</a>
        </div>

        @include('admin.crypto_requests.form', [
          'action' => route('crypto-requests.store'),
          'method' => 'POST',
          'cryptoRequest' => null
        ])
    </div>
@endsection
