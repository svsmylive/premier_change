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
    // дефолты валют при создании
    $rub = $currencies->firstWhere('code', 'RUB');
    $usdt = $currencies->firstWhere('code', 'USDT');

    $defaultFromId = $rub?->id ?? $currencies->first()?->id;
    $defaultToId = $usdt?->id
        ?? ($currencies->skip(1)->first()?->id ?? $currencies->first()?->id);

    $fromId = (int) old('currency_from_id', $cryptoRequest?->currency_from_id ?? $defaultFromId);
    $toId   = (int) old('currency_to_id', $cryptoRequest?->currency_to_id ?? $defaultToId);

    // словарь id -> code для JS
    $currenciesMap = [];
    foreach ($currencies as $c) {
      $currenciesMap[(int)$c->id] = $c->code;
    }
@endphp

<form method="POST" action="{{ $action }}">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    {{-- hidden валюты (они реально сохраняются) --}}
    <input type="hidden" name="currency_from_id" id="currency_from_id" value="{{ $fromId }}">
    <input type="hidden" name="currency_to_id" id="currency_to_id" value="{{ $toId }}">

    <div class="card">
        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-4">
                    <label class="form-label">Дата</label>
                    <input type="datetime-local" name="date" class="form-control" required
                           value="{{ old('date', $cryptoRequest?->date ?? now()->timezone('Europe/Moscow')->format('Y-m-d\TH:i')) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Статус</label>
                    <select disabled name="status_id" class="form-select" required>
                        @foreach($statuses as $s)
                            <option
                                value="{{ $s->id }}" @selected((string)$s->id === (string)old('status_id', $cryptoRequest?->status_id))>
                                {{ $s->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Источник</label>
                    <select name="source_id" class="form-select" required>
                        <option value="">Выбрать</option>
                        @foreach($sources as $s)
                            <option
                                value="{{ $s->id }}" @selected((string)$s->id === (string)old('source_id', $cryptoRequest?->source_id))>
                                {{ $s->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-12">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label mb-0">Клиент</label>

                        <button type="button"
                                class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#createClientModal">
                            + Новый клиент
                        </button>
                    </div>

                    <select name="client_id" id="client_id" class="form-select" required>
                        <option value="">— выбрать —</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}"
                                @selected((string)$c->id === (string)old('client_id', $cryptoRequest?->client_id))>
                                #{{ $c->id }} — {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </div>


                {{-- Валюты (только отображение) + swap --}}
                <div class="col-md-5">
                    <label class="form-label">Принимаем</label>
                    <input type="text" class="form-control" id="currency_from_code" disabled>
                </div>

                <div class="col-md-2 d-flex align-items-end justify-content-center">
                    <button type="button" class="btn btn-outline-secondary w-100" id="swapCurrencies"
                            title="Поменять местами">
                        ↔
                    </button>
                </div>

                <div class="col-md-5">
                    <label class="form-label">Отдаем</label>
                    <input type="text" class="form-control" id="currency_to_code" disabled>
                </div>

                {{-- Сумма с кодом FROM --}}
                <div class="col-md-6">
                    <label class="form-label">Сумма Клиента</label>

                    <input type="hidden" name="amount" id="amount_raw"
                           value="{{ (int)old('amount', $cryptoRequest?->amount ?? 0) }}">

                    <div class="input-group">
                        <input type="text" id="amount_pretty" class="form-control"
                               inputmode="numeric"
                               value="{{ money(old('amount', $cryptoRequest?->amount ?? 0)) }}">
                        <span class="input-group-text" id="amountCurrency">—</span>
                    </div>
                </div>

                <div class="col-md-12">
                    <label class="form-label">Комментарий</label>
                    <textarea name="comment" class="form-control"
                              rows="3">{{ old('comment', $cryptoRequest?->comment) }}</textarea>
                </div>

            </div>
        </div>
    </div>

    <div class="mt-3 d-flex gap-2">
        <button class="btn btn-primary">Сохранить</button>
        <a href="{{ route('crypto-requests.index') }}" class="btn btn-outline-secondary">Отмена</a>
    </div>
</form>

<div class="modal fade" id="createClientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="createClientForm">
                <div class="modal-header">
                    <h5 class="modal-title">Новый клиент</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-danger d-none" id="clientCreateError"></div>

                    <div class="mb-3">
                        <label class="form-label">Имя</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Telegram</label>
                        <input type="text" name="telegram" class="form-control" placeholder="@username или ссылка">
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Комментарий</label>
                        <textarea name="comment" class="form-control" rows="3"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary" id="clientCreateBtn">Создать</button>
                </div>
            </form>
        </div>
    </div>
</div>


@push('scripts')
    <script>
        $(function () {
            const currenciesMap = @json($currenciesMap);

            function getFromId() {
                return String($('#currency_from_id').val() || '');
            }

            function getToId() {
                return String($('#currency_to_id').val() || '');
            }

            function codeById(id) {
                return currenciesMap[id] || '—';
            }

            function refreshUI() {
                const fromId = getFromId();
                const toId = getToId();

                const fromCode = codeById(fromId);
                const toCode = codeById(toId);

                $('#currency_from_code').val(fromCode);
                $('#currency_to_code').val(toCode);

                $('#amountCurrency').text(fromCode);
            }

            $('#swapCurrencies').on('click', function () {
                const fromId = getFromId();
                const toId = getToId();

                $('#currency_from_id').val(toId);
                $('#currency_to_id').val(fromId);

                refreshUI();
            });

            // init
            refreshUI();
        });

        function moneyPretty(val) {
            if (val === null || val === undefined || val === '') return '0';
            let n = Number(val);
            if (Number.isNaN(n)) return '0';

            // 2 знака, потом убрать хвосты
            let s = n.toFixed(2);
            s = s.replace(/\.00$/, '');     // .00
            s = s.replace(/(\.\d)0$/, '$1'); // .10 -> .1

            // разделители тысяч пробелами
            const parts = s.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
            return parts.join('.');
        }

        function refreshAmountPretty() {
            const fromCode = $('#amountCurrency').text();
            $('#amountPretty').text(moneyPretty($('#amount').val()));
            $('#amountPrettyCur').text(fromCode ? ' ' + fromCode : '');
        }

        const clientModal = new bootstrap.Modal(document.getElementById('createClientModal'));

        $('#createClientForm').on('submit', function (e) {
            e.preventDefault();

            $('#clientCreateError').addClass('d-none').text('');
            $('#clientCreateBtn').prop('disabled', true);

            $.ajax({
                url: "{{ route('clients.quick-store') }}",
                method: "POST",
                data: $(this).serialize(),
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                success: function (resp) {
                    const opt = new Option(`#${resp.id} — ${resp.name}`, resp.id, true, true);
                    $('#client_id').append(opt).val(resp.id).trigger('change');
                    $('#createClientForm')[0].reset();
                    clientModal.hide();
                },
                error: function (xhr) {
                    let msg = 'Ошибка создания клиента';
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    }
                    $('#clientCreateError').removeClass('d-none').html(msg);
                },
                complete: function () {
                    $('#clientCreateBtn').prop('disabled', false);
                }
            });
        });

        function digitsOnly(s) {
            return String(s || '').replace(/[^\d]/g, '');
        }

        function formatIntWithSpaces(n) {
            n = digitsOnly(n);
            if (n === '') return '';
            return n.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        }

        function syncAmount() {
            const raw = digitsOnly($('#amount_pretty').val());
            $('#amount_raw').val(raw === '' ? 0 : parseInt(raw, 10));
            $('#amount_pretty').val(formatIntWithSpaces(raw));
        }

        $('#amount_pretty').on('input', function () {
            // не форматируем на каждом вводе, только чистим символы
            const raw = digitsOnly($(this).val());
            $('#amount_raw').val(raw === '' ? 0 : parseInt(raw, 10));
        });

        $('#amount_pretty').on('blur', function () {
            syncAmount();
        });

        // на загрузке
        syncAmount();

    </script>
@endpush
