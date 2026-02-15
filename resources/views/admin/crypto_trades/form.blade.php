@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $action }}">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-body">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Дата</label>
                            <input type="datetime-local" name="date" class="form-control" required
                                   value="{{ old('date', $trade?->date?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Статус</label>
                            <select name="status_id" class="form-select" required>
                                @foreach($statuses as $s)
                                    <option value="{{ $s->id }}"
                                        @selected((string)$s->id === (string)old('status_id', $trade?->status_id))>
                                        {{ $s->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Клиент</label>
                            <select name="client_id" class="form-select" required>
                                <option value="">— выбрать —</option>
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}"
                                        @selected((string)$c->id === (string)old('client_id', $trade?->client_id))>
                                        #{{ $c->id }} — {{ $c->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Источник</label>
                            <select name="source_id" class="form-select" required>
                                @foreach($sources as $s)
                                    <option value="{{ $s->id }}"
                                        @selected((string)$s->id === (string)old('source_id', $trade?->source_id))>
                                        {{ $s->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Оператор</label>
                            <select name="operator_id" class="form-select" required>
                                @foreach($operators as $o)
                                    <option value="{{ $o->id }}"
                                        @selected((string)$o->id === (string)old('operator_id', $trade?->operator_id))>
                                        {{ $o->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Валюта FROM</label>
                            <select name="currency_from_id" class="form-select" required>
                                @foreach($currencies as $cur)
                                    <option value="{{ $cur->id }}"
                                        @selected((string)$cur->id === (string)old('currency_from_id', $trade?->currency_from_id))>
                                        {{ $cur->code }} — {{ $cur->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Валюта TO</label>
                            <select name="currency_to_id" class="form-select" required>
                                @foreach($currencies as $cur)
                                    <option value="{{ $cur->id }}"
                                        @selected((string)$cur->id === (string)old('currency_to_id', $trade?->currency_to_id))>
                                        {{ $cur->code }} — {{ $cur->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Сумма вход</label>
                            <input type="number" min="0" step="1" name="amount_income" class="form-control" required
                                   value="{{ old('amount_income', $trade?->amount_income) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Сумма выход</label>
                            <input type="number" min="0" step="1" name="amount_outcome" class="form-control" required
                                   value="{{ old('amount_outcome', $trade?->amount_outcome) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Курс клиента</label>
                            <input type="number" step="0.0001" name="course_of_client" class="form-control" required
                                   value="{{ old('course_of_client', $trade?->course_of_client) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Курс биржи</label>
                            <input type="number" step="0.0001" name="course_of_currency_exchange" class="form-control"
                                   value="{{ old('course_of_currency_exchange', $trade?->course_of_currency_exchange) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Биржа</label>
                            <select name="currency_exchange_id" class="form-select">
                                <option value="">— не выбрано —</option>
                                @foreach($exchanges as $ex)
                                    <option value="{{ $ex->id }}"
                                        @selected((string)$ex->id === (string)old('currency_exchange_id', $trade?->currency_exchange_id))>
                                        {{ $ex->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Партнёр</label>
                            <select name="partner_id" class="form-select">
                                <option value="">— нет —</option>
                                @foreach($partners as $p)
                                    <option value="{{ $p->id }}"
                                        @selected((string)$p->id === (string)old('partner_id', $trade?->partner_id))>
                                        {{ $p->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Ставка партнёра</label>
                            <input type="number" step="0.0001" name="rate_of_partner" class="form-control"
                                   value="{{ old('rate_of_partner', $trade?->rate_of_partner) }}">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Комментарий</label>
                            <textarea name="comment" class="form-control"
                                      rows="3">{{ old('comment', $trade?->comment) }}</textarea>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold">Кассы в сделке</div>
                        <div class="text-muted small">amount + rate</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addCashDesk">
                        + Добавить
                    </button>
                </div>

                <div class="card-body" id="cashDeskRows">
                    {{-- rows will be inserted here --}}
                </div>
            </div>

            <div class="alert alert-info mt-3">
                <b>Важно:</b> amount лучше хранить со знаком.<br>
                + = приход в кассу, − = списание из кассы.
            </div>
        </div>
    </div>

    <div class="mt-3 d-flex gap-2">
        <button class="btn btn-primary">Сохранить</button>
        <a href="{{ route('crypto-trades.index') }}" class="btn btn-outline-secondary">Отмена</a>
    </div>
</form>

@php
    // rows для JS (old() имеет приоритет)
    $oldRows = old('cash_desks');

    if ($oldRows === null && $trade) {
        $oldRows = $trade->cashDesks->map(function ($cd) {
            return [
                'cash_desk_id' => $cd->id,
                'amount' => $cd->pivot->amount,
                'rate' => $cd->pivot->rate,
            ];
        })->values()->toArray();
    }

    $oldRows = $oldRows ?? [];

    // cash desks для JS (без стрелочных функций, чтобы blade не ломался)
    $cashDesksJson = [];
    foreach ($cashDesks as $cd) {
        $cashDesksJson[] = [
            'id' => $cd->id,
            'name' => $cd->name,
            'currency' => optional($cd->currency)->code,
        ];
    }
@endphp

@push('scripts')
    <script>
        $(function () {
            const cashDesks = @json($cashDesksJson);

            let rows = @json($oldRows);

            function render() {
                const $wrap = $('#cashDeskRows');
                $wrap.html('');

                if (!rows.length) {
                    $wrap.html('<div class="text-muted text-center py-3">Кассы не добавлены</div>');
                    return;
                }

                rows.forEach((row, index) => {
                    const options = cashDesks.map(cd => {
                        const selected = String(cd.id) === String(row.cash_desk_id) ? 'selected' : '';
                        return `<option value="${cd.id}" ${selected}>${cd.name} (${cd.currency || ''})</option>`;
                    }).join('');

                    const html = `
                <div class="border rounded p-2 mb-2">
                    <div class="d-flex gap-2">
                        <div style="flex: 1;">
                            <label class="form-label small text-muted mb-1">Касса</label>
                            <select class="form-select form-select-sm" name="cash_desks[${index}][cash_desk_id]" required>
                                <option value="">— выбрать —</option>
                                ${options}
                            </select>
                        </div>

                        <div style="width: 140px;">
                            <label class="form-label small text-muted mb-1">Amount</label>
                            <input type="number" min="0" step="1"
                                   class="form-control form-control-sm"
                                   name="cash_desks[${index}][amount]"
                                   value="${row.amount ?? ''}" required>
                        </div>

                        <div style="width: 120px;">
                            <label class="form-label small text-muted mb-1">Rate</label>
                            <input type="number" step="0.0001"
                                   class="form-control form-control-sm"
                                   name="cash_desks[${index}][rate]"
                                   value="${row.rate ?? ''}" required>
                        </div>

                        <div class="d-flex align-items-end">
                            <button type="button" class="btn btn-sm btn-outline-danger js-remove" data-index="${index}">
                                ✕
                            </button>
                        </div>
                    </div>
                </div>
            `;

                    $wrap.append(html);
                });
            }

            $('#addCashDesk').on('click', function () {
                rows.push({cash_desk_id: '', amount: '', rate: ''});
                render();
            });

            $(document).on('click', '.js-remove', function () {
                const index = Number($(this).data('index'));
                rows.splice(index, 1);
                render();
            });

            render();
        });
    </script>
@endpush

