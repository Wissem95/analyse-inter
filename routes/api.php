<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InterventionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['api'])->group(function () {
    Route::post('upload', [InterventionController::class, 'import']);
    Route::get('stats', [InterventionController::class, 'stats']);
    Route::get('stats/technicien/{technicien}', [InterventionController::class, 'statsTechnicien']);
    Route::post('export/technicien/{technicien}', [InterventionController::class, 'exportPDF']);
    Route::post('/interventions/update-presta-revenue', [InterventionController::class, 'updatePrestaRevenue']);
    Route::post('/interventions/update-revenu-percu', [InterventionController::class, 'updateRevenuPercu']);
});
