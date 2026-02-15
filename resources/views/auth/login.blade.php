@extends('admin.layout')

@section('title', 'Вход')

@section('content')
    <div class="container py-5" style="max-width: 460px;">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h4 class="mb-3">Вход в админку</h4>

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control"
                               value="{{ old('email') }}" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Пароль</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="remember" value="1" id="remember">
                        <label class="form-check-label" for="remember">
                            Запомнить меня
                        </label>
                    </div>

                    <button class="btn btn-primary w-100">Войти</button>
                </form>
            </div>
        </div>
    </div>
@endsection
