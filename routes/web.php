<?php

use App\Http\Controllers\CurrenciesController;
use Illuminate\Support\Facades\Route;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

Route::get('/currencies:calculate', [CurrenciesController::class, 'calculate'])->name('currencies.calculate');

Route::get('/', function () {
    return view('index');
});

Route::get('/sitemap.xml', function () {
    return Sitemap::create()
        ->add(Url::create('/'))
        ->toResponse(request());
});


Route::get('/rates', [CurrenciesController::class, 'getRates'])->name('rates');
