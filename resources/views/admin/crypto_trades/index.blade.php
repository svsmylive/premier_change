@extends('admin.layout')

@section('title', 'Сделки')

@section('content')
    <div class="container py-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Сделки</h3>
            <p class="alert alert-warning">
                Сделка создается только из заявки
            </p>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card mb-3">
            <div class="card-body">
                <form class="row g-2" method="GET" action="{{ route('crypto-trades.index') }}">

                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Дата от</label>
                        <input type="datetime-local" name="date_from" value="{{ request('date_from') }}"
                               class="form-control">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Дата до</label>
                        <input type="datetime-local" name="date_to" value="{{ request('date_to') }}"
                               class="form-control">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Статус</label>
                        <select name="status_id" class="form-select">
                            <option value="">Все</option>
                            @foreach($statuses as $s)
                                <option value="{{ $s->id }}" @selected((string)$s->id === (string)request('status_id'))>
                                    {{ $s->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Источник</label>
                        <select name="source_id" class="form-select">
                            <option value="">Все</option>
                            @foreach($sources as $s)
                                <option value="{{ $s->id }}" @selected((string)$s->id === (string)request('source_id'))>
                                    {{ $s->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Партнёр</label>
                        <select name="partner_id" class="form-select">
                            <option value="">Все</option>
                            @foreach($partners as $p)
                                <option
                                    value="{{ $p->id }}" @selected((string)$p->id === (string)request('partner_id'))>
                                    {{ $p->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Оператор</label>
                        <select name="operator_id" class="form-select">
                            <option value="">Все</option>
                            @foreach($operators as $o)
                                <option
                                    value="{{ $o->id }}" @selected((string)$o->id === (string)request('operator_id'))>
                                    {{ $o->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">Клиент</label>
                        <select name="client_id" class="form-select">
                            <option value="">Все</option>
                            @foreach($clients as $c)
                                <option value="{{ $c->id }}" @selected((string)$c->id === (string)request('client_id'))>
                                    ID: {{ $c->id }} — {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label small text-muted mb-1">Поиск по комментарию</label>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                               placeholder="Например: номер заявки, уточнение, адрес кошелька...">
                    </div>

                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button class="btn btn-outline-primary w-100" type="submit">Фильтр</button>
                        <a class="btn btn-outline-secondary w-100" href="{{ route('crypto-trades.index') }}">Сброс</a>
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
                        <th style="width: 170px;">Дата</th>
                        <th>Клиент</th>
                        <th style="width: 160px;">Направление</th>
                        <th style="width: 190px;">Сумма</th>
                        <th style="width: 220px;" class="text-end">Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($trades as $t)
                        <tr>
                            <td>{{ $t->id }}</td>
                            <td class="text-muted">{{ $t->date?->timezone('Europe/Moscow')->format('d.m.Y H:i') }}</td>
                            <td>
                                <div class="fw-semibold">{{ $t->client->name ?? '—' }}</div>
                                <div class="text-muted small">ID: {{ $t->client_id }}</div>
                            </td>

                            <td>
                            <span class="badge bg-light text-dark border">
                                {{ $t->currencyFrom->code ?? '' }}
                                →
                                {{ $t->currencyTo->code ?? '' }}
                            </span>
                                @if($t->currencyFrom->code == "USDT")
                                    <div class="text-muted small">Выдача</div>
                                @else
                                    <div class="text-muted small">Прием</div>
                                @endif
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{ money($t->amount_income) }}
                                    {{ $t->currencyFrom->code ?? '' }}
                                </div>
                                <div class="text-muted small">
                                    →
                                    {{ number_format((float)$t->amount_outcome, 0, '.', ' ') }}
                                    {{ $t->currencyTo->code ?? '' }}
                                </div>
                            </td>

                            <td class="text-end">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('crypto-trades.show', $t) }}"
                                       class="btn btn-sm btn-outline-dark"
                                       title="Просмотр">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    {{--                                    <a href="{{ route('crypto-trades.edit', $t) }}"--}}
                                    {{--                                       class="btn btn-sm btn-outline-primary"--}}
                                    {{--                                       title="Редактировать">--}}
                                    {{--                                        <i class="bi bi-pencil"></i>--}}
                                    {{--                                    </a>--}}

                                    {{--                                    <button type="button"--}}
                                    {{--                                            class="btn btn-sm btn-outline-danger js-delete"--}}
                                    {{--                                            title="Удалить"--}}
                                    {{--                                            data-action="{{ route('crypto-trades.destroy', $t) }}"--}}
                                    {{--                                            data-name="Сделка #{{ $t->id }}">--}}
                                    {{--                                        <i class="bi bi-trash"></i>--}}
                                    {{--                                    </button>--}}
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Сделок нет
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-body">
                {{ $trades->links() }}
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
                        <h5 class="modal-title">Удалить сделку</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="alert alert-warning mb-0">
                            Вы уверены, что хотите удалить: <b id="deleteName"></b>?
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
