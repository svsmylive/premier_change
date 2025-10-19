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
        Schema::create('analytic_rates', function (Blueprint $table) {
            $table->id();

            $table->string('source');
            $table->string('crypto_exchanger');
            $table->string('currency_from');
            $table->string('currency_to');
            $table->decimal('crypto_exchanger_course', 8, 4);
            $table->decimal('crypto_exchange_course', 8, 4);
            $table->decimal('plus', 8, 4);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytic_rates');
    }
};
