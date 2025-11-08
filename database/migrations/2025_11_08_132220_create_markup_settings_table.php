<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exchange_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            // Храним долю (0.02 = 2%), с запасом точности
            $table->decimal('value', 9, 6)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_settings');
    }
};
