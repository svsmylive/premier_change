<?php

use App\Http\Controllers\Admin\CashDeskController;
use App\Http\Controllers\Admin\CashDeskMovementController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\CryptoRequestController;
use App\Http\Controllers\Admin\CryptoTradeController;
use App\Http\Controllers\Admin\CurrencyExchangeController;
use App\Http\Controllers\Admin\PartnerController;
use App\Http\Controllers\Admin\SourceController;
use App\Http\Controllers\Admin\StatusController;
use App\Http\Controllers\CurrenciesController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;

Route::middleware('throttle:60,1')->group(function () {
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
});

Route::post('/telegram/webhook/{secret}', [WebhookController::class, 'handle'])
    ->name('telegram.webhook');


Route::prefix('admin')->middleware(['auth'])->group(function () {
    Route::resource('crypto-trades', CryptoTradeController::class);
    Route::resource('cash-desks', CashDeskController::class);

    Route::resource('cash-desk-movements', CashDeskMovementController::class)
        ->only(['index', 'create', 'store', 'destroy']);

    // справочники
    Route::resource('statuses', StatusController::class)->except(['show']);
    Route::resource('partners', PartnerController::class)->except(['show']);
    Route::resource('sources', SourceController::class)->except(['show']);
    Route::resource('currency-exchanges', CurrencyExchangeController::class)->except(['show']);

    // аналитика
    Route::get('reports/pnl', [ReportsController::class, 'pnl'])->name('reports.pnl');

    Route::resource('crypto-requests', CryptoRequestController::class); // show нам нужен

    Route::get('crypto-requests/{cryptoRequest}/convert', [CryptoRequestController::class, 'convert'])
        ->name('crypto-requests.convert');

    Route::post('crypto-requests/{cryptoRequest}/convert', [CryptoRequestController::class, 'storeConvertedTrade'])
        ->name('crypto-requests.convert.store');

    Route::post('clients/quick-store', [ClientController::class, 'quickStore'])
        ->name('clients.quick-store');
});

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');
