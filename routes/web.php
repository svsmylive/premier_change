<?php

use App\Http\Controllers\CurrenciesController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

Route::get('currencies:calculate', [CurrenciesController::class, 'calculate'])->name('currencies.calculate');

Route::get('/', function () {
    return view('index');
});

Route::get('sitemap.xml', function () {
    return Sitemap::create()
        ->add(Url::create('/'))
        ->toResponse(request());
});

Route::get('rates', [CurrenciesController::class, 'getRates'])->name('rates');
Route::get('token-secure', fn() => response()->json(['token' => csrf_token()]))->middleware('web');

Route::middleware(['auth'])->group(function () {
    Route::post('logout', [UserController::class, 'logout'])->name('logout');
    Route::get('account', [UserController::class, 'account'])->name('account');
});

Route::post('login', [UserController::class, 'login'])->name('login');
Route::post('confirm-email', [UserController::class, 'confirmEmail'])->name('login');
Route::post('register', [UserController::class, 'register'])->name('register');
Route::post('password-reset', [UserController::class, 'passwordReset'])->name('password-reset');
