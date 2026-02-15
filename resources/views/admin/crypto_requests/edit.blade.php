@extends('admin.layout')
@section('title', 'Редактирование заявки #' . $cryptoRequest->id)

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Редактирование заявки #{{ $cryptoRequest->id }}</h3>
            <a href="{{ route('crypto-requests.show', $cryptoRequest) }}" class="btn btn-outline-secondary">Назад</a>
        </div>

        @include('admin.crypto_requests.form', [
          'action' => route('crypto-requests.update', $cryptoRequest),
          'method' => 'PUT',
          'cryptoRequest' => $cryptoRequest
        ])
    </div>
@endsection
