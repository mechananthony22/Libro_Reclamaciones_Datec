<?php

use App\Http\Controllers\Api\v1\EstablecimientoApiController;
use App\Http\Controllers\Api\v1\ReclamacionApiController;
use App\Http\Middleware\EnsureApiEnabled;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas API REST (Fase de Expansión F2)
|--------------------------------------------------------------------------
|
| Endpoints REST desacoplados protegidos por el middleware EnsureApiEnabled.
| En la versión actual MVP la API pública se mantiene gobernada y deshabilitada.
|
*/
Route::prefix('v1')->middleware([EnsureApiEnabled::class])->group(function () {
    Route::post('{slug}/reclamaciones', [ReclamacionApiController::class, 'store'])
        ->name('api.v1.reclamaciones.store');
    Route::post('{slug}/reclamaciones/consultar', [ReclamacionApiController::class, 'consultar'])
        ->name('api.v1.reclamaciones.consultar');
    Route::get('{slug}/establecimientos', [EstablecimientoApiController::class, 'index'])
        ->name('api.v1.establecimientos.index');
});
