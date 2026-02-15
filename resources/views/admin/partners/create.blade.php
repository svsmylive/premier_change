@extends('admin.layout')

@section('title', 'Новый партнёр')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Новый партнёр</h3>
            <a href="{{ route('partners.index') }}" class="btn btn-outline-secondary">Назад</a>
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

                <form method="POST" action="{{ route('partners.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Название</label>
                        <input type="text" name="name" class="form-control"
                               value="{{ old('name') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Комментарий</label>
                        <textarea name="comment" class="form-control" rows="4"
                                  placeholder="Например: условия, ставка, контакт...">{{ old('comment') }}</textarea>
                    </div>

                    <button class="btn btn-primary">Сохранить</button>
                </form>
            </div>
        </div>
    </div>
@endsection
