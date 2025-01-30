<?php

use App\Http\Controllers\CurrenciesController;
use Illuminate\Support\Facades\Route;

Route::get('/currencies:calculate', [CurrenciesController::class, 'calculate'])->name('currencies.calculate');

Route::get('/', function () {
    return view('welcome');
});
