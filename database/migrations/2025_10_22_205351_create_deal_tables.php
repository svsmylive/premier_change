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
        Schema::create('deals', function (Blueprint $table) {
            $table->id();

            $table->dateTime('date');
            $table->foreignId('client_id');
            $table->foreignId('cashbox_from_id');
            $table->foreignId('cashbox_to_id');
            $table->foreignId('currency_from_id');
            $table->foreignId('currency_to_id');

            $table->decimal('cryptocurrency_exchange_rate');
            $table->decimal('rate_buy')->nullable();
            $table->decimal('rate_sell')->nullable();
            $table->decimal('amount', 20);
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
