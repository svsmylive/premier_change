@extends('admin.layout')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Редактирование статуса #{{ $status->id }}</h3>
            <a href="{{ route('statuses.index') }}" class="btn btn-outline-secondary">Назад</a>
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

                <form method="POST" action="{{ route('statuses.update', $status) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Название</label>
                        <input type="text" name="name" class="form-control"
                               value="{{ old('name', $status->name) }}" required>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_end" value="1"
                               id="is_end" @checked(old('is_end', $status->is_end))>
                        <label class="form-check-label" for="is_end">
                            Финальный статус
                        </label>
                    </div>

                    <button class="btn btn-primary">Сохранить</button>
                </form>
            </div>
        </div>
    </div>
@endsection
