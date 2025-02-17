<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '^(?!api).*$');

Route::get('/health', function () {
    try {
        // Vérifier la connexion à la base de données
        DB::connection()->getPdo();
        return response('ok', 200)
            ->header('Content-Type', 'text/plain');
    } catch (\Exception $e) {
        return response('error: ' . $e->getMessage(), 500)
            ->header('Content-Type', 'text/plain');
    }
});
