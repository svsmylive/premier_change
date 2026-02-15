@extends('admin.layout')

@section('title', 'Заявки')

@section('content')
    <div class="container py-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Заявки</h3>
            <a href="{{ route('crypto-requests.create') }}" class="btn btn-primary">+ Добавить</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card mb-3">
            <div class="card-body">
                <form class="row g-2" method="GET" action="{{ route('crypto-requests.index') }}">

                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Дата от</label>
                        <input type="datetime-local" name="date_from"
                               value="{{ request('date_from') ?? now()->subDay()->startOfDay()->format('Y-m-d\TH:i') }}"
                               class="form-control">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Дата до</label>
                        <input type="datetime-local" name="date_to"
                               value="{{ request('date_to') ?? now()->endOfDay()->format('Y-m-d\TH:i') }}"
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

                    <div class="col-md-3">
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

                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Поиск по комментарию</label>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="...">
                    </div>

                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button class="btn btn-outline-primary w-100" type="submit">Фильтр</button>
                        <a class="btn btn-outline-secondary w-100" href="{{ route('crypto-requests.index') }}">Сброс</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th style="width:80px;">ID</th>
                        <th style="width:170px;">Дата</th>
                        <th>Клиент</th>
                        <th style="width:160px;">Направление</th>
                        <th style="width:170px;">Сумма</th>
                        <th style="width:160px;">Статус</th>
                        <th style="width:140px;">Сделка</th>
                        <th style="width:220px;" class="text-end">Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($requests as $r)
                        <tr>
                            <td>{{ $r->id }}</td>
                            <td class="text-muted">{{ $r->date?->timezone('Europe/Moscow')->format('d.m.Y H:i') }}</td>
                            <td>
                                <div class="fw-semibold">{{ $r->client->name ?? '—' }}</div>
                                <div class="text-muted small">ID: {{ $r->client_id }}</div>
                            </td>
                            <td>
                            <span class="badge bg-light text-dark border">
                                {{ $r->currencyFrom->code ?? '' }} → {{ $r->currencyTo->code ?? '' }}
                            </span>
                                @if($r->currencyFrom->code == "USDT")
                                    <div class="text-muted small">Выдача</div>
                                @else
                                    <div class="text-muted small">Прием</div>
                                @endif
                            </td>
                            <td class="fw-semibold">
                                {{ number_format((float)$r->amount, 0, '.', ' ') }}
                                {{ $r->currencyFrom->code ?? '' }}
                            </td>
                            <td>
                                <span
                                    class="badge @if($r->status?->is_end) bg-success @else bg-secondary @endif">{{ $r->status->name ?? '—' }}</span>
                            </td>
                            <td>
                                @if($r->trade)
                                    <a href="{{ route('crypto-trades.show', $r->trade) }}"
                                       class="badge bg-success text-decoration-none">
                                        #{{ $r->trade->id }}
                                    </a>
                                @else
                                    <span class="badge bg-light text-dark border">нет</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('crypto-requests.show', $r) }}"
                                       class="btn btn-sm btn-outline-dark"
                                       title="Просмотр">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <a href="{{ route('crypto-requests.edit', $r) }}"
                                       class="btn btn-sm btn-outline-primary"
                                       title="Редактировать">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger js-delete"
                                            title="Удалить"
                                            data-action="{{ route('crypto-requests.destroy', $r) }}"
                                            data-name="Заявка #{{ $r->id }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Заявок нет</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-body">
                {{ $requests->links() }}
            </div>
        </div>
    </div>

    {{-- delete modal --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" id="deleteForm">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title">Удалить заявку</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
