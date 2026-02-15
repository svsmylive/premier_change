@extends('admin.layout')

@section('title', 'Кассы')

@section('content')
    <div class="container py-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Кассы</h3>
            <a href="{{ route('cash-desks.create') }}" class="btn btn-primary">
                + Добавить
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card mb-3">
            <div class="card-body">
                <form class="row g-2" method="GET" action="{{ route('cash-desks.index') }}">
                    <div class="col-md-4">
                        <input type="text" name="q" value="{{ request('q') }}"
                               class="form-control" placeholder="Поиск по названию...">
                    </div>

                    <div class="col-md-3">
                        <select name="currency_id" class="form-select">
                            <option value="">Все валюты</option>
                            @foreach($currencies as $cur)
                                <option
                                    value="{{ $cur->id }}" @selected((string)$cur->id === (string)request('currency_id'))>
                                    {{ $cur->code }} — {{ $cur->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <select name="is_our" class="form-select">
                            <option value="">Все</option>
                            <option value="1" @selected(request('is_our') === '1')>Наши</option>
                            <option value="0" @selected(request('is_our') === '0')>Партнёров</option>
                        </select>
                    </div>

                    <div class="col-md-3 d-flex gap-2">
                        <button class="btn btn-outline-primary w-100" type="submit">Фильтр</button>
                        <a class="btn btn-outline-secondary w-100" href="{{ route('cash-desks.index') }}">Сброс</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th>Название</th>
                        <th style="width: 160px;">Валюта</th>
                        <th style="width: 160px;">Тип</th>
                        <th style="width: 180px;" class="text-end">
                            <a class="text-decoration-none text-dark"
                               href="{{ request()->fullUrlWithQuery([
                                'sort' => 'balance',
                                'dir' => request('sort') === 'balance' && request('dir') === 'asc' ? 'desc' : 'asc'
                           ]) }}">
                                Остаток
                                @if(request('sort') === 'balance')
                                    <span class="text-muted">{{ request('dir') === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>
                        <th style="width: 220px;" class="text-end">Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($cashDesks as $cashDesk)
                        <tr>
                            <td>{{ $cashDesk->id }}</td>
                            <td class="fw-semibold">{{ $cashDesk->name }}</td>

                            <td>
                            <span class="badge bg-light text-dark border">
                                {{ $cashDesk->currency->code ?? '—' }}
                            </span>
                            </td>

                            <td>
                                @if($cashDesk->is_our)
                                    <span class="badge bg-primary">Наша</span>
                                @else
                                    <span class="badge bg-secondary">Партнёра</span>
                                @endif
                            </td>

                            <td class="text-end">
                            <span class="fw-semibold">
                                {{ number_format((int)($cashDesk->balance ?? 0), 0, '.', ' ') }}
                            </span>
                                <span class="text-muted">
                                {{ $cashDesk->currency->code ?? '' }}
                            </span>
                            </td>

                            <td class="text-end">
                                <a href="{{ route('cash-desks.edit', $cashDesk) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    Редактировать
                                </a>

                                <button type="button"
                                        class="btn btn-sm btn-outline-danger ms-1 js-delete"
                                        data-action="{{ route('cash-desks.destroy', $cashDesk) }}"
                                        data-name="{{ $cashDesk->name }}">
                                    Удалить
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                Ничего не найдено
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-body">
                {{ $cashDesks->links() }}
            </div>
        </div>
    </div>

    {{-- Delete modal --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" id="deleteForm">
                    @csrf
                    @method('DELETE')

                    <div class="modal-header">
                        <h5 class="modal-title">Удалить кассу</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="alert alert-warning mb-0">
                            Вы уверены, что хотите удалить кассу: <b id="deleteName"></b>?
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-danger">Удалить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function () {
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));

            $('.js-delete').on('click', function () {
                $('#deleteForm').attr('action', $(this).data('action'));
                $('#deleteName').text($(this).data('name'));
                modal.show();
            });
        });
    </script>
@endpush
