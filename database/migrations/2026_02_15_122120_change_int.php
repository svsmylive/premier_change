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
        Schema::table('crypto_trades', function (Blueprint $table) {
            $table->unsignedBigInteger('amount_income')->change();
            $table->unsignedBigInteger('amount_outcome')->change();
        });

        Schema::table('cash_desks_trade', function (Blueprint $table) {
            $table->unsignedBigInteger('amount')->change();
        });

        Schema::table('crypto_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('amount')->change();
        });

        Schema::table('cash_desk_movements', function (Blueprint $table) {
            $table->unsignedBigInteger('amount')->change();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->unsignedBigInteger('sum')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
