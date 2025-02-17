<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '^(?!api).*$');

Route::get('/health', function () {
    return 'ok';
});
