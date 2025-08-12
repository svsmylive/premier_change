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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_confirmed')->default(false);
            $table->string('login')->unique();
            $table->string('email_code')->nullable();
            $table->integer('ref_percent')->default(25);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('ref_user_id')->nullable();
            $table->foreignId('status_id');
            $table->string('valute_from');
            $table->string('valute_to');
            $table->integer('sum_from');
            $table->integer('sum_to');
            $table->integer('course_from');
            $table->integer('course_to');
            $table->string('city');
            $table->timestamps();
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('requisite');
            $table->integer('amount');
            $table->string('status_id');
            $table->timestamps();
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
