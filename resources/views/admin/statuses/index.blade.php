@extends('admin.layout')

@section('content')
    <div class="container py-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Статусы</h3>
            <a href="{{ route('statuses.create') }}" class="btn btn-primary">
                + Добавить
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card mb-3">
            <div class="card-body">
                <form class="row g-2" method="GET" action="{{ route('statuses.index') }}">
                    <div class="col-md-6">
                        <input type="text" name="q" value="{{ request('q') }}"
                               class="form-control" placeholder="Поиск по названию...">
                    </div>

                    <div class="col-md-3">
                        <select name="is_end" class="form-select">
                            <option value="">Все</option>
                            <option value="0" @selected(request('is_end') === '0')>Не финальный</option>
                            <option value="1" @selected(request('is_end') === '1')>Финальный</option>
                        </select>
                    </div>

                    <div class="col-md-3 d-flex gap-2">
                        <button class="btn btn-outline-primary w-100" type="submit">Фильтр</button>
                        <a class="btn btn-outline-secondary w-100" href="{{ route('statuses.index') }}">Сброс</a>
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
                        <th style="width: 140px;">Финальный</th>
                        <th style="width: 220px;" class="text-end">Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($statuses as $status)
                        <tr>
                            <td>{{ $status->id }}</td>
                            <td>{{ $status->name }}</td>
                            <td>
                                @if($status->is_end)
                                    <span class="badge bg-success">Да</span>
                                @else
                                    <span class="badge bg-secondary">Нет</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('statuses.edit', $status) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    Редактировать
                                </a>

                                <button type="button"
                                        class="btn btn-sm btn-outline-danger ms-1 js-delete"
                                        data-action="{{ route('statuses.destroy', $status) }}"
                                        data-name="{{ $status->name }}">
                                    Удалить
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                Ничего не найдено
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-body">
                {{ $statuses->links() }}
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
                        <h5 class="modal-title">Удалить статус</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="alert alert-warning mb-0">
                            Вы уверены, что хотите удалить статус: <b id="deleteName"></b>?
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
            const modalEl = document.getElementById('deleteModal');
            const modal = new bootstrap.Modal(modalEl);

            $('.js-delete').on('click', function () {
                const action = $(this).data('action');
                const name = $(this).data('name');

                $('#deleteForm').attr('action', action);
                $('#deleteName').text(name);

                modal.show();
            });
        });
    </script>
@endpush
