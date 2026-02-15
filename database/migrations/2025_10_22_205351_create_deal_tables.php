<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //сами сделки
        Schema::create('crypto_trades', function (Blueprint $table) {
            $table->id();

            $table->dateTime('date');
            $table->foreignId('client_id');

            $table->foreignId('crypto_request_id')->nullable();
            // чтобы 1 заявка не была переведена в 2 сделки
            $table->unique('crypto_request_id');

            $table->foreignId('currency_from_id');
            $table->foreignId('currency_to_id');
            $table->foreignId('operator_id');

            $table->decimal('amount_income', 12, 2); //итого сумма клиента, с которой он пришел currency_from_id

            $table->decimal('course_of_client', 12, 4); //курс для клиента (наш курс + курс rate_of_partner)
            $table->decimal('rate_of_client', 12, 4); //ставка для клиента
            $table->decimal('course_of_currency_exchange', 12, 4)->nullable(
            ); // курс биржи (будем давать на выбор rapira / grinex)
            $table->foreignId('currency_exchange_id')->nullable(); // биржа

            $table->foreignId('partner_id')->nullable();
            $table->decimal('rate_of_partner', 6, 4)->nullable(); // ставка партнера (если от него клиент)

            $table->decimal('amount_outcome', 12, 2); // итого сумма клиента, которую он получит currency_to_id

            $table->text('comment')->nullable();
            $table->softDeletes();

            $table->timestamps();
        });

        // какие кассы и в каком объеме и каких ставках задействованы при сделки (ставка вынесена сюда, т.к. под сделку могут быть персональными)
        Schema::create('cash_desks_trade', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crypto_trade_id');
            $table->foreignId('cash_desk_id');

            $table->decimal('amount', 12, 2);
            $table->decimal('rate', 6, 4)->nullable();
            $table->decimal('course', 12, 4)->nullable();

            $table->timestamps();
        });

        // кассы наши и партнеров (в совокупности их может быть 100+)
        Schema::create('cash_desks', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->foreignId('currency_id');
            $table->boolean('is_our');

            $table->timestamps();
        });

        // партнеры
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('comment');

            $table->timestamps();
        });

        // источник
        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('comment');

            $table->timestamps();
        });

        // расходы
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date');
            $table->text('description');
            $table->unsignedInteger('sum');
            $table->foreignId('cash_desk_id');
            $table->foreignId('currency_id');

            $table->timestamps();
        });

        // статусы
        Schema::create('statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_end');

            $table->timestamps();
        });

        // биржи
        Schema::create('currency_exchanges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('uri');

            $table->timestamps();
        });

        // биржи
        Schema::create('operations_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');

            $table->timestamps();
        });

        // клиенты
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('telegram');
            $table->text('comment');

            $table->timestamps();
        });

        // клиенты
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');

            $table->timestamps();
        });

        // движения
        Schema::create('cash_desk_movements', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date');
            $table->foreignId('cash_desk_id');
            $table->foreignId('operation_type_id');
            $table->foreignId('crypto_trade_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('comment')->nullable();

            $table->timestamps();
        });

        // диапазонные наценки для касс (нужно сделать покрывающими, чтобы любая сумма имела наценку)
        Schema::create('cash_desk_rates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cash_desk_id');
            $table->foreignId(
                'currency_from_id'
            ); //денормализация, хотя в кассе есть currency_id, но чтобы было понятней

            $table->unsignedInteger('sum_from');
            $table->unsignedInteger('sum_to');

            $table->decimal('rate', 6, 4);

            $table->timestamps();
        });


        Schema::table('crypto_trades', function (Blueprint $table) {
            $table->index('date');

            $table->index(['client_id', 'date']);
            $table->index(['partner_id', 'date']);
            $table->index(['operator_id', 'date']);

            $table->index(['currency_from_id', 'currency_to_id', 'date']);
        });

        /** ---------------- cash_desks_trade (pivot) ---------------- */

        Schema::table('cash_desks_trade', function (Blueprint $table) {
            $table->unique(['crypto_trade_id', 'cash_desk_id']);
            $table->index('cash_desk_id');
        });

        /** ---------------- cash_desks ---------------- */

        Schema::table('cash_desks', function (Blueprint $table) {
            $table->index('currency_id');
            $table->index('is_our');
        });

        /** ---------------- expenses ---------------- */

        Schema::table('expenses', function (Blueprint $table) {
            $table->index('cash_desk_id');
            $table->index('currency_id');
            $table->index('date');
        });

        /** ---------------- statuses ---------------- */

        Schema::table('statuses', function (Blueprint $table) {
            $table->index('is_end');
        });

        /** ---------------- currency_exchanges ---------------- */

        Schema::table('currency_exchanges', function (Blueprint $table) {
            $table->unique('uri');
        });

        /** ---------------- operations_types ---------------- */

        Schema::table('operations_types', function (Blueprint $table) {
            $table->unique('code');
        });

        /** ---------------- clients ---------------- */

        Schema::table('clients', function (Blueprint $table) {
            $table->index('telegram');
        });

        /** ---------------- currencies ---------------- */

        Schema::table('currencies', function (Blueprint $table) {
            $table->unique('code');
        });

        /** ---------------- cash_desk_movements ---------------- */

        Schema::table('cash_desk_movements', function (Blueprint $table) {
            $table->index(['cash_desk_id', 'date']);
            $table->index(['operation_type_id', 'date']);
            $table->index('crypto_trade_id');
        });

        // Заявки

        Schema::create('crypto_requests', function (Blueprint $table) {
            $table->id();

            $table->dateTime('date');

            $table->foreignId('client_id');
            $table->foreignId('status_id');

            $table->foreignId('source_id'); // откуда заявка пришла

            $table->foreignId('currency_from_id');
            $table->foreignId('currency_to_id');

            $table->decimal('amount', 12, 2); // сумма по currency_from_id (с которой клиент пришел)

            $table->text('comment')->nullable();

            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crypto_trades');
        Schema::dropIfExists('cash_desks_trade');
        Schema::dropIfExists('cash_desks');
        Schema::dropIfExists('cash_desk_rates');
        Schema::dropIfExists('partners');
        Schema::dropIfExists('sources');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('statuses');
        Schema::dropIfExists('currency_exchanges');
        Schema::dropIfExists('operations_types');
        Schema::dropIfExists('cash_desk_movements');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('crypto_requests');
    }
};
