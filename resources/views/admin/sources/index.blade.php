@extends('admin.layout')

@section('title', 'Источники')

@section('content')
    <div class="container py-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Источники</h3>
            <a href="{{ route('sources.create') }}" class="btn btn-primary">
                + Добавить
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card mb-3">
            <div class="card-body">
                <form class="row g-2" method="GET" action="{{ route('sources.index') }}">
                    <div class="col-md-9">
                        <input type="text" name="q" value="{{ request('q') }}"
                               class="form-control" placeholder="Поиск по названию...">
                    </div>

                    <div class="col-md-3 d-flex gap-2">
                        <button class="btn btn-outline-primary w-100" type="submit">Найти</button>
                        <a class="btn btn-outline-secondary w-100" href="{{ route('sources.index') }}">Сброс</a>
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
                        <th style="width: 45%;">Комментарий</th>
                        <th style="width: 220px;" class="text-end">Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($sources as $source)
                        <tr>
                            <td>{{ $source->id }}</td>
                            <td class="fw-semibold">{{ $source->name }}</td>
                            <td class="text-muted">
                                {{ \Illuminate\Support\Str::limit($source->comment, 140) }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('sources.edit', $source) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    Редактировать
                                </a>

                                <button type="button"
                                        class="btn btn-sm btn-outline-danger ms-1 js-delete"
                                        data-action="{{ route('sources.destroy', $source) }}"
                                        data-name="{{ $source->name }}">
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
                {{ $sources->links() }}
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
                        <h5 class="modal-title">Удалить источник</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="alert alert-warning mb-0">
                            Вы уверены, что хотите удалить источник: <b id="deleteName"></b>?
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
