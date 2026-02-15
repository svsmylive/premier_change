@extends('admin.layout')

@section('title', 'Редактирование кассы')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Редактирование кассы #{{ $cashDesk->id }}</h3>
            <a href="{{ route('cash-desks.index') }}" class="btn btn-outline-secondary">Назад</a>
        </div>

        <div class="card">
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('cash-desks.update', $cashDesk) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Название</label>
                        <input type="text" name="name" class="form-control"
                               value="{{ old('name', $cashDesk->name) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Валюта</label>
                        <select name="currency_id" class="form-select" required>
                            @foreach($currencies as $cur)
                                <option value="{{ $cur->id }}" @selected((string)$cur->id === (string)old('currency_id', $cashDesk->currency_id))>
                                    {{ $cur->code }} — {{ $cur->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_our" value="1"
                               id="is_our" @checked(old('is_our', $cashDesk->is_our))>
                        <label class="form-check-label" for="is_our">
                            Наша касса
                        </label>
                    </div>

                    <button class="btn btn-primary">Сохранить</button>
                </form>
            </div>
        </div>
    </div>
@endsection
