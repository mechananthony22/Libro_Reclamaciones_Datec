<?php

use App\Http\Controllers\Admin\EmpresaController;
use App\Http\Controllers\Admin\InvitacionController;
use App\Http\Controllers\Admin\MonitoreoController;
use App\Http\Controllers\Auth\AceptarInvitacionController;
use App\Http\Controllers\Empresa\CambiarEmpresaController;
use App\Http\Controllers\Empresa\EstablecimientoController;
use App\Http\Controllers\Empresa\IdentidadLegalController;
use App\Http\Controllers\Empresa\PersonalizacionController;
use App\Http\Controllers\Empresa\UsuarioController;
use App\Http\Controllers\Publico\LibroReclamacionesController;
use App\Models\Empresa;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// Módulo Superadministrador Datec 
Route::prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/empresas');

    Route::resource('empresas', EmpresaController::class)
        ->except(['show', 'destroy']);

    Route::patch('empresas/{empresa}/estado', [EmpresaController::class, 'cambiarEstado'])
        ->name('empresas.estado');

    Route::post('empresas/{empresa}/invitaciones', [InvitacionController::class, 'store'])
        ->name('empresas.invitaciones.store');

    Route::post('empresas/{empresa}/invitaciones/{invitacion}/reenviar', [InvitacionController::class, 'resend'])
        ->name('empresas.invitaciones.resend');

    // Monitoreo Operativo Global 
    Route::get('monitoreo', [MonitoreoController::class, 'index'])
        ->name('monitoreo.index');
    Route::post('monitoreo/colas/reintentar-todos', [MonitoreoController::class, 'reintentarTodos'])
        ->name('monitoreo.colas.reintentar-todos');
    Route::post('monitoreo/colas/{id}/reintentar', [MonitoreoController::class, 'reintentarJob'])
        ->name('monitoreo.colas.reintentar');
    Route::delete('monitoreo/colas/{id}', [MonitoreoController::class, 'eliminarJob'])
        ->name('monitoreo.colas.eliminar');
});

// Aceptación de invitaciones para administradores de empresa 
Route::get('invitacion/{token}', [AceptarInvitacionController::class, 'show'])
    ->name('invitacion.show');
Route::post('invitacion/{token}', [AceptarInvitacionController::class, 'store'])
    ->name('invitacion.store');

// Configuración de Identidad Legal de la Empresa
Route::get('configuracion/empresa', [IdentidadLegalController::class, 'edit'])
    ->name('empresa.identidad.edit');
Route::put('configuracion/empresa', [IdentidadLegalController::class, 'update'])
    ->name('empresa.identidad.update');

// Personalización Visual y Logotipo 
Route::get('configuracion/personalizacion', [PersonalizacionController::class, 'edit'])
    ->name('empresa.personalizacion.edit');
Route::post('configuracion/personalizacion', [PersonalizacionController::class, 'update'])
    ->name('empresa.personalizacion.update');
Route::delete('configuracion/personalizacion/logo', [PersonalizacionController::class, 'destroyLogo'])
    ->name('empresa.personalizacion.logo.destroy');

// Gestión de Establecimientos / Sedes 
Route::get('configuracion/establecimientos', [EstablecimientoController::class, 'index'])
    ->name('empresa.establecimientos.index');
Route::post('configuracion/establecimientos', [EstablecimientoController::class, 'store'])
    ->name('empresa.establecimientos.store');
Route::put('configuracion/establecimientos/{establecimiento}', [EstablecimientoController::class, 'update'])
    ->name('empresa.establecimientos.update');
Route::patch('configuracion/establecimientos/{establecimiento}/toggle', [EstablecimientoController::class, 'toggleActivo'])
    ->name('empresa.establecimientos.toggle');
Route::delete('configuracion/establecimientos/{establecimiento}', [EstablecimientoController::class, 'destroy'])
    ->name('empresa.establecimientos.destroy');

// Gestión de Usuarios y Roles de la Empresa 
Route::get('configuracion/usuarios', [UsuarioController::class, 'index'])
    ->name('empresa.usuarios.index');
Route::post('configuracion/usuarios/invitar', [UsuarioController::class, 'invitar'])
    ->name('empresa.usuarios.invitar');
Route::put('configuracion/usuarios/{usuario}/rol', [UsuarioController::class, 'cambiarRol'])
    ->name('empresa.usuarios.rol');
Route::patch('configuracion/usuarios/{usuario}/toggle', [UsuarioController::class, 'toggleActivo'])
    ->name('empresa.usuarios.toggle');
Route::delete('configuracion/usuarios/{usuario}', [UsuarioController::class, 'desvincular'])
    ->name('empresa.usuarios.desvincular');
Route::post('configuracion/usuarios/invitaciones/{invitacion}/reenviar', [UsuarioController::class, 'reenviarInvitacion'])
    ->name('empresa.usuarios.invitaciones.reenviar');
Route::delete('configuracion/usuarios/invitaciones/{invitacion}', [UsuarioController::class, 'cancelarInvitacion'])
    ->name('empresa.usuarios.invitaciones.cancelar');

// Selector de Empresa Multitenant 
Route::post('cambiar-empresa', [CambiarEmpresaController::class, 'switch'])
    ->name('empresa.switch');

// Resolución de tenant para el libro público
Route::prefix('l/{slug}')->middleware(['tenant.slug'])->group(function () {
    Route::get('/', [LibroReclamacionesController::class, 'index'])
        ->name('libro.publico');
    Route::post('registrar', [LibroReclamacionesController::class, 'registrar'])
        ->name('libro.registrar');
    Route::get('establecimientos', [EstablecimientoController::class, 'activosPublico'])
        ->name('libro.establecimientos');
});
