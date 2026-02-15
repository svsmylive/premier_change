@extends('admin.layout')

@section('title', 'Новая касса')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Новая касса</h3>
            <a href="{{ route('cash-desks.index') }}" class="btn btn-outline-secondary">Назад</a>
        </div>

        <div class="card">
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('cash-desks.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Название</label>
                        <input type="text" name="name" class="form-control"
                               value="{{ old('name') }}" required
                               placeholder="Например: Наличка RUB (офис), USDT TRC20 (кошелёк 1)">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Валюта</label>
                        <select name="currency_id" class="form-select" required>
                            <option value="">— выбрать —</option>
                            @foreach($currencies as $cur)
                                <option
                                    value="{{ $cur->id }}" @selected((string)$cur->id === (string)old('currency_id'))>
                                    {{ $cur->code }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_our" value="1"
                               id="is_our" @checked(old('is_our'))>
                        <label class="form-check-label" for="is_our">
                            Наша касса (если не отмечено — касса партнёра)
                        </label>
                    </div>

                    <button class="btn btn-primary">Сохранить</button>
                </form>
            </div>
        </div>
    </div>
@endsection
