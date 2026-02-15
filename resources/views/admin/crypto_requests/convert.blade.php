@extends('admin.layout')
@section('title', 'Перевести в сделку')

@section('content')
    <div class="container py-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Перевести заявку #{{ $cryptoRequest->id }} в сделку</h3>
                <div class="text-muted">
                    {{ $cryptoRequest->currencyFrom->code ?? '' }} → {{ $cryptoRequest->currencyTo->code ?? '' }},
                    сумма {{ money($cryptoRequest->amount) }} {{ $cryptoRequest->currencyFrom->code ?? '' }}
                </div>
            </div>

            <a href="{{ route('crypto-requests.show', $cryptoRequest) }}" class="btn btn-outline-secondary">Назад</a>
        </div>

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $fromCode = $cryptoRequest->currencyFrom->code ?? '';
            $toCode = $cryptoRequest->currencyTo->code ?? '';

            $amountIncomeRaw = (int)($cryptoRequest->amount ?? 0);

            // показываем кассы только "под обмен":
            // если принимаем USDT -> показываем RUB кассы, иначе -> USDT кассы
            $needCashDeskCurrency = (strtoupper($fromCode) === 'USDT') ? 'RUB' : 'USDT';

            $oldRows = old('cash_desks') ?? [];

            $cashDesksJson = [];
            foreach ($cashDesks as $cd) {
                $code = optional($cd->currency)->code;
                if ($code !== $needCashDeskCurrency) continue;

                $cashDesksJson[] = [
                    'id' => $cd->id,
                    'name' => $cd->name,
                    'currency' => $code,
                    'is_our' => (bool)$cd->is_our,
                ];
            }
        @endphp

        <form method="POST" action="{{ route('crypto-requests.convert.store', $cryptoRequest) }}">
            @csrf

            {{-- принимаем не редактируем, но отправляем чистое число --}}
            <input type="hidden" name="amount_income" id="amount_income_raw" value="{{ $amountIncomeRaw }}">

            {{-- отдаем: отправляем чистое число --}}
            <input type="hidden" name="amount_outcome" id="amount_outcome_raw" value="{{ (int)old('amount_outcome', 0) }}">

            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-body">
                            <div class="row g-3">

                                <div class="col-md-6">
                                    <label class="form-label">Оператор</label>
                                    <select name="operator_id" class="form-select" required>
                                        @foreach($operators as $o)
                                            <option value="{{ $o->id }}" @if(auth()->user()?->id == $o->id) selected @endif>
                                                {{ $o->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6"></div>

                                <div class="col-md-6">
                                    <label class="form-label">Курс биржи</label>
                                    <input type="number" step="0.0001" min="0"
                                           name="course_of_currency_exchange"
                                           id="course_of_currency_exchange"
                                           class="form-control"
                                           placeholder="Заполнить для авто расчета"
                                           value="{{ old('course_of_currency_exchange') }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Биржа</label>
                                    <select name="currency_exchange_id" class="form-select">
                                        <option value="">— не выбрано —</option>
                                        @foreach($exchanges as $ex)
                                            <option value="{{ $ex->id }}" @selected((string)$ex->id === (string)old('currency_exchange_id'))>
                                                {{ $ex->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- курс клиента = курс биржи +/- курс биржи * %ставки --}}
                                <div class="col-md-6">
                                    <label class="form-label">Курс клиента</label>
                                    <input type="number" step="0.01" min="0"
                                           name="course_of_client"
                                           id="course_of_client"
                                           class="form-control"
                                           required readonly
                                           value="{{ old('course_of_client') }}">
                                    <div class="form-text">
                                        Авто: USDT→RUB: биржа - биржа*%<br> RUB→USDT: биржа + биржа*%
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Ставка клиента (%)</label>
                                    <input type="number" step="0.0001" min="0"
                                           name="rate_of_client"
                                           id="rate_of_client"
                                           class="form-control"
                                           required
                                           placeholder="Например 2.5"
                                           value="{{ old('rate_of_client') }}">
                                </div>

                                {{-- Принимаем (disabled) --}}
                                <div class="col-md-6">
                                    <label class="form-label">Принимаем {{ $fromCode }}</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" disabled
                                               id="amount_income_pretty"
                                               value="{{ money($amountIncomeRaw) }}">
                                        <span class="input-group-text">{{ $fromCode }}</span>
                                    </div>
                                </div>

                                {{-- Отдаем (pretty + hidden raw) --}}
                                <div class="col-md-6">
                                    <label class="form-label">Отдаем {{ $toCode }}</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="amount_outcome_pretty" inputmode="numeric"
                                               value="{{ money((int)old('amount_outcome', 0)) }}">
                                        <span class="input-group-text">{{ $toCode }}</span>
                                    </div>
                                    <div class="form-text">Авто после расчёта курса клиента (можно поправить вручную)</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Партнёр</label>
                                    <select name="partner_id" class="form-select">
                                        <option value="">— нет —</option>
                                        @foreach($partners as $p)
                                            <option value="{{ $p->id }}" @selected((string)$p->id === (string)old('partner_id'))>
                                                {{ $p->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Ставка партнёра (%)</label>
                                    <input type="number" step="0.0001" min="0"
                                           name="rate_of_partner"
                                           class="form-control"
                                           value="{{ old('rate_of_partner') }}">
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Комментарий</label>
                                    <textarea name="comment" class="form-control" rows="3">{{ old('comment', $cryptoRequest->comment) }}</textarea>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                {{-- кассы --}}
                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <div class="fw-semibold">Кассы в сделке</div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addCashDesk">+ Добавить</button>
                        </div>
                        <div class="card-body" id="cashDeskRows"></div>
                    </div>

                    <div class="alert alert-info mt-3 mb-0">
                        Для <b>наших</b> касс ставка и курс не учитываются
                    </div>
                </div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button class="btn btn-primary">Создать сделку</button>
                <a href="{{ route('crypto-requests.show', $cryptoRequest) }}" class="btn btn-outline-secondary">Отмена</a>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            $(function () {
                const FROM_CODE = @json($fromCode);
                const cashDesks = @json($cashDesksJson);
                const desksById = {};
                cashDesks.forEach(d => desksById[String(d.id)] = d);

                let rows = @json($oldRows);

                // ---- money helpers (integer) ----
                function digitsOnly(s) { return String(s || '').replace(/[^\d]/g, ''); }
                function formatIntSpaces(raw) {
                    raw = digitsOnly(raw);
                    if (raw === '') return '';
                    return raw.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
                }
                function intVal(raw) {
                    raw = digitsOnly(raw);
                    return raw === '' ? 0 : parseInt(raw, 10);
                }

                function calcCourseOfClient() {
                    const ex = Number($('#course_of_currency_exchange').val());
                    const ratePct = Number($('#rate_of_client').val()); // % (например 2.5)

                    if (!ex || ratePct === null || ratePct === undefined || ratePct === '') {
                        $('#course_of_client').val('');
                        return null;
                    }

                    const k = (ratePct / 100);

                    let course;
                    if (String(FROM_CODE).toUpperCase() === 'USDT') {
                        // USDT -> RUB : ex - ex * %
                        course = ex - (ex * k);
                    } else {
                        // RUB -> USDT : ex + ex * %
                        course = ex + (ex * k);
                    }

                    // до 2 знаков
                    course = Math.round(course * 100) / 100;

                    $('#course_of_client').val(course.toFixed(2));
                    return course;
                }

                function calcOutcome() {
                    const course = Number($('#course_of_client').val());
                    if (!course) return;

                    const income = Number($('#amount_income_raw').val()) || 0;

                    let outcome = 0;

                    if (String(FROM_CODE).toUpperCase() === 'USDT') {
                        // USDT -> RUB
                        outcome = income * course;
                    } else {
                        // RUB -> USDT
                        outcome = income / course;
                    }

                    outcome = Math.round(outcome); // целое

                    $('#amount_outcome_raw').val(outcome);
                    $('#amount_outcome_pretty').val(formatIntSpaces(outcome));
                }

                function fillCashDeskCoursesFromExchange() {
                    const ex = Number($('#course_of_currency_exchange').val());
                    rows = rows.map(r => {
                        r.course = ex ? Number(ex).toFixed(4) : '';
                        return r;
                    });
                    render();
                }

                // ---- outcome pretty sync ----
                function syncOutcomeFromPretty() {
                    const raw = intVal($('#amount_outcome_pretty').val());
                    $('#amount_outcome_raw').val(raw);
                    $('#amount_outcome_pretty').val(formatIntSpaces(raw));
                }

                $('#amount_outcome_pretty').on('input', function () {
                    $('#amount_outcome_raw').val(intVal($(this).val()));
                });
                $('#amount_outcome_pretty').on('blur', syncOutcomeFromPretty);

                // ---- listeners for auto ----
                $('#course_of_currency_exchange, #rate_of_client').on('input', function () {
                    const c = calcCourseOfClient();
                    if (c) {
                        calcOutcome();
                        fillCashDeskCoursesFromExchange();
                    }
                });

                // ---- cash desks UI ----
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
                            return `<option value="${cd.id}" ${selected}>${cd.name}</option>`;
                        }).join('');

                        const selectedDesk = row.cash_desk_id ? desksById[String(row.cash_desk_id)] : null;
                        const isOur = selectedDesk ? !!selectedDesk.is_our : false;

                        const amountPretty = formatIntSpaces(row.amount ?? '');

                        // rate/course:
                        // - если касса наша => не показываем, но отправляем hidden 0
                        // - иначе показываем и валидируем
                        const rateHtml = isOur
                            ? `<input type="hidden" name="cash_desks[${index}][rate]" value="0">`
                            : `
                                <div style="width: 110px;">
                                  <label class="form-label small text-muted mb-1">Ставка</label>
                                  <input type="number" step="0.0001" min="0" class="form-control form-control-sm"
                                         name="cash_desks[${index}][rate]" value="${row.rate ?? ''}" required>
                                </div>
                              `;

                        const courseHtml = isOur
                            ? `<input type="hidden" name="cash_desks[${index}][course]" value="0">`
                            : `
                                <div style="width: 110px;">
                                  <label class="form-label small text-muted mb-1">Курс</label>
                                  <input type="number" step="0.0001" min="0" class="form-control form-control-sm"
                                         name="cash_desks[${index}][course]" value="${row.course ?? ''}" required>
                                </div>
                              `;

                        $wrap.append(`
                            <div class="border rounded p-2 mb-2">
                              <div class="d-flex gap-2 align-items-start">
                                <div style="flex: 1; min-width: 200px;">
                                  <label class="form-label small text-muted mb-1">Касса</label>
                                  <select class="form-select form-select-sm js-cd"
                                          data-index="${index}"
                                          name="cash_desks[${index}][cash_desk_id]" required>
                                    <option value="">— выбрать —</option>
                                    ${options}
                                  </select>
                                  ${isOur ? '<div class="text-muted small mt-1">Наша касса: ставку и курс не учитываем</div>' : ''}
                                </div>

                               <div style="width: 150px;" class="align-self-end">
                                  <label class="form-label small text-muted mb-1">Сумма</label>
                                  <input type="hidden" name="cash_desks[${index}][amount]" value="${intVal(row.amount ?? '')}">
                                  <input type="text" class="form-control form-control-sm js-amt"
                                         data-index="${index}"
                                         value="${amountPretty}" inputmode="numeric" required>
                                </div>

                                ${rateHtml}
                                ${courseHtml}

                                <div>
                                  <button type="button" class="btn btn-sm btn-outline-danger js-remove" data-index="${index}">✕</button>
                                </div>
                              </div>
                            </div>
                        `);
                    });
                }

                $('#addCashDesk').on('click', function () {
                    rows.push({cash_desk_id: '', amount: '', rate: '', course: ''});
                    render();
                });

                $(document).on('click', '.js-remove', function () {
                    rows.splice(Number($(this).data('index')), 1);
                    render();
                });

                // amount pretty -> hidden
                $(document).on('input', '.js-amt', function () {
                    const idx = Number($(this).data('index'));
                    const raw = intVal($(this).val());
                    rows[idx].amount = raw;
                    $(this).prev('input[type="hidden"]').val(raw);
                });

                $(document).on('blur', '.js-amt', function () {
                    const idx = Number($(this).data('index'));
                    $(this).val(formatIntSpaces(rows[idx].amount ?? 0));
                });

                // when cashdesk changes -> rerender to apply "our" rules
                $(document).on('change', '.js-cd', function () {
                    const idx = Number($(this).data('index'));
                    rows[idx].cash_desk_id = $(this).val();

                    const d = desksById[String(rows[idx].cash_desk_id || '')];
                    if (d && d.is_our) {
                        rows[idx].rate = 0;
                        rows[idx].course = 0;
                    }

                    render();
                });

                // init
                $('#amount_outcome_pretty').val(formatIntSpaces(intVal($('#amount_outcome_raw').val())));
                render();
            });
        </script>
    @endpush
@endsection
