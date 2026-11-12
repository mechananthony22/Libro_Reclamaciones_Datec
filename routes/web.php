<?php

use App\Http\Controllers\Admin\AuditoriaController as AdminAuditoriaController;
use App\Http\Controllers\Admin\ContingenciaController as AdminContingenciaController;
use App\Http\Controllers\Admin\EmpresaController;
use App\Http\Controllers\Admin\InvitacionController;
use App\Http\Controllers\Admin\MonitoreoController;
use App\Http\Controllers\Admin\SalidaClienteController;
use App\Http\Controllers\Admin\SoporteController as AdminSoporteController;
use App\Http\Controllers\Auth\AceptarInvitacionController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RecuperarPasswordController;
use App\Http\Controllers\Empresa\AuditoriaController as EmpresaAuditoriaController;
use App\Http\Controllers\Empresa\CambiarEmpresaController;
use App\Http\Controllers\Empresa\ContingenciaController as EmpresaContingenciaController;
use App\Http\Controllers\Empresa\DashboardController;
use App\Http\Controllers\Empresa\EstablecimientoController;
use App\Http\Controllers\Empresa\ExpedienteController;
use App\Http\Controllers\Empresa\IdentidadLegalController;
use App\Http\Controllers\Empresa\IntegracionController;
use App\Http\Controllers\Empresa\NotificacionConfigController;
use App\Http\Controllers\Empresa\PersonalizacionController;
use App\Http\Controllers\Empresa\SoporteController as EmpresaSoporteController;
use App\Http\Controllers\Empresa\UsuarioController;
use App\Http\Controllers\Publico\ConsultaEstadoController;
use App\Http\Controllers\Publico\LibroReclamacionesController;
use App\Models\Empresa;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// Autenticación de Usuarios (Superadmin Datec y Administradores de Empresa)
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);

    // Recuperación y restablecimiento de contraseña mediante código por correo
    Route::post('password/enviar-codigo', [RecuperarPasswordController::class, 'enviarCodigo'])
        ->name('password.enviar-codigo');
    Route::post('password/restablecer', [RecuperarPasswordController::class, 'restablecer'])
        ->name('password.restablecer');
});

Route::post('logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Módulo Superadministrador Datec (Requiere privilegios de Superadmin)
Route::prefix('admin')->name('admin.')->middleware(['superadmin'])->group(function () {
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
    Route::post('monitoreo/verificar-salud', [MonitoreoController::class, 'verificarSalud'])
        ->name('monitoreo.verificar-salud');
    Route::post('monitoreo/alertas/{alerta}/resolver', [MonitoreoController::class, 'resolverAlerta'])
        ->name('monitoreo.alertas.resolver');

    // Historial Global de Auditoría
    Route::get('auditoria', [AdminAuditoriaController::class, 'index'])
        ->name('auditoria.index');

    // Gestión y Solicitud de Soporte Técnico y Acceso Delegado
    Route::get('soporte', [AdminSoporteController::class, 'index'])
        ->name('soporte.index');
    Route::post('soporte/solicitar', [AdminSoporteController::class, 'solicitar'])
        ->name('soporte.solicitar');
    Route::patch('soporte/{id}/finalizar', [AdminSoporteController::class, 'finalizar'])
        ->name('soporte.finalizar');

    // Supervisión Centralizada de Contingencias y Ensayos
    Route::get('contingencia', [AdminContingenciaController::class, 'index'])
        ->name('contingencia.index');
    Route::post('contingencia/{empresa}/activar', [AdminContingenciaController::class, 'activar'])
        ->name('contingencia.activar');
    Route::post('contingencia/{empresa}/desactivar', [AdminContingenciaController::class, 'desactivar'])
        ->name('contingencia.desactivar');

    // Desactivación, Salida Formal y Custodia Legal de Clientes
    Route::post('empresas/{empresa}/baja', [SalidaClienteController::class, 'procesarBaja'])
        ->name('empresas.baja');
    Route::post('empresas/{empresa}/custodia/generar', [SalidaClienteController::class, 'generarPaquete'])
        ->name('empresas.custodia.generar');
    Route::get('empresas/{empresa}/custodia/descargar', [SalidaClienteController::class, 'descargarPaquete'])
        ->name('empresas.custodia.descargar');
});

// Aceptación de invitaciones para administradores de empresa
Route::get('invitacion/{token}', [AceptarInvitacionController::class, 'show'])
    ->name('invitacion.show');
Route::post('invitacion/{token}', [AceptarInvitacionController::class, 'store'])
    ->name('invitacion.store');

// Rutas administrativas de la empresa (protegidas por tenant.access)
Route::middleware(['tenant.access'])->group(function () {
    // Dashboard Ejecutivo e Indicadores de Gestión
    Route::get('dashboard', [DashboardController::class, 'index'])
        ->name('empresa.dashboard');

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

    // Historial de Auditoría del Tenant (Aislamiento estricto por empresa)
    Route::get('configuracion/auditoria', [EmpresaAuditoriaController::class, 'index'])
        ->name('empresa.auditoria.index');
    Route::get('configuracion/auditoria/exportar', [EmpresaAuditoriaController::class, 'exportar'])
        ->name('empresa.auditoria.exportar');
    Route::get('empresa/auditoria', [EmpresaAuditoriaController::class, 'index'])
        ->name('empresa.auditoria.alias');
    Route::get('empresa/auditoria/exportar', [EmpresaAuditoriaController::class, 'exportar'])
        ->name('empresa.auditoria.alias.exportar');

    // Configuración de Destinatarios y Alertas de Notificación
    Route::get('configuracion/notificaciones', [NotificacionConfigController::class, 'edit'])
        ->name('empresa.notificaciones.edit');
    Route::put('configuracion/notificaciones', [NotificacionConfigController::class, 'update'])
        ->name('empresa.notificaciones.update');

    // Gestión y Control de Acceso Delegado para Soporte Técnico Datec
    Route::get('configuracion/soporte', [EmpresaSoporteController::class, 'index'])
        ->name('empresa.soporte.index');
    Route::post('configuracion/soporte', [EmpresaSoporteController::class, 'store'])
        ->name('empresa.soporte.store');
    Route::patch('configuracion/soporte/{id}/autorizar', [EmpresaSoporteController::class, 'autorizar'])
        ->name('empresa.soporte.autorizar');
    Route::patch('configuracion/soporte/{id}/finalizar', [EmpresaSoporteController::class, 'finalizar'])
        ->name('empresa.soporte.finalizar');
    Route::patch('configuracion/soporte/{id}/revocar', [EmpresaSoporteController::class, 'revocar'])
        ->name('empresa.soporte.revocar');

    // Plan de Contingencia y Ensayos Preventivos (DR Rehearsal)
    Route::get('configuracion/contingencia', [EmpresaContingenciaController::class, 'index'])
        ->name('empresa.contingencia.index');
    Route::post('configuracion/contingencia/activar', [EmpresaContingenciaController::class, 'activar'])
        ->name('empresa.contingencia.activar');
    Route::post('configuracion/contingencia/desactivar', [EmpresaContingenciaController::class, 'desactivar'])
        ->name('empresa.contingencia.desactivar');
    Route::post('configuracion/contingencia/ensayo', [EmpresaContingenciaController::class, 'ejecutarEnsayo'])
        ->name('empresa.contingencia.ensayo');

    // Enlaces de Integración y Reconfiguración de Slug
    Route::get('configuracion/integracion', [IntegracionController::class, 'index'])
        ->name('empresa.integracion.index');
    Route::post('configuracion/integracion/slug', [IntegracionController::class, 'cambiarSlug'])
        ->name('empresa.integracion.slug');
    Route::post('configuracion/integracion/slug/verificar', [IntegracionController::class, 'verificarDisponibilidad'])
        ->name('empresa.integracion.slug.verificar');

    // Selector de Empresa Multitenant
    Route::post('cambiar-empresa', [CambiarEmpresaController::class, 'switch'])
        ->name('empresa.switch');

    // Bandeja y Gestión de Expedientes (Reclamaciones y Quejas)
    Route::get('expedientes', [ExpedienteController::class, 'index'])
        ->name('empresa.expedientes.index');
    Route::get('expedientes/exportar', [ExpedienteController::class, 'exportar'])
        ->name('empresa.expedientes.exportar');
    Route::get('expedientes/{id}', [ExpedienteController::class, 'show'])
        ->name('empresa.expedientes.show');
    Route::get('expedientes/{id}/constancia-pdf', [ExpedienteController::class, 'descargarPdf'])
        ->name('empresa.expedientes.pdf');
    Route::get('expedientes/{id}/adjuntos/{adjuntoId}', [ExpedienteController::class, 'descargarAdjunto'])
        ->name('empresa.expedientes.adjunto.descargar');
    Route::patch('expedientes/{id}/asignar', [ExpedienteController::class, 'asignarResponsable'])
        ->name('empresa.expedientes.asignar');
    Route::patch('expedientes/{id}/estado', [ExpedienteController::class, 'cambiarEstado'])
        ->name('empresa.expedientes.estado');
    Route::post('expedientes/{id}/respuestas', [ExpedienteController::class, 'registrarRespuesta'])
        ->name('empresa.expedientes.respuestas.store');
    Route::post('expedientes/{id}/respuestas/{respuestaId}/reenviar', [ExpedienteController::class, 'reenviarNotificacionRespuesta'])
        ->name('empresa.expedientes.respuestas.reenviar');
    Route::delete('expedientes/{id}', [ExpedienteController::class, 'destroy'])
        ->name('empresa.expedientes.destroy');
});

// Resolución de tenant para el libro público
Route::prefix('l/{slug}')->middleware(['tenant.slug'])->group(function () {
    Route::get('/', [LibroReclamacionesController::class, 'index'])
        ->name('libro.publico');
    Route::post('registrar', [LibroReclamacionesController::class, 'registrar'])
        ->middleware(['throttle:registro-reclamacion'])
        ->name('libro.registrar');
    Route::get('establecimientos', [EstablecimientoController::class, 'activosPublico'])
        ->name('libro.establecimientos');

    // Confirmación de recepción y constancia con enlace de descarga
    Route::get('confirmacion/{token}', [LibroReclamacionesController::class, 'confirmacion'])
        ->name('libro.confirmacion');
    Route::get('constancia/{token}/pdf', [LibroReclamacionesController::class, 'descargarPdf'])
        ->name('libro.constancia.pdf');

    // Consulta de estado de reclamación sin exposición pública de datos
    Route::get('consultar', [ConsultaEstadoController::class, 'show'])
        ->name('libro.consulta');
    Route::post('consultar', [ConsultaEstadoController::class, 'consultar'])
        ->middleware(['throttle:registro-reclamacion'])
        ->name('libro.consulta.post');
    Route::get('consulta-segura/{token}', [ConsultaEstadoController::class, 'detalleFirmado'])
        ->name('libro.consulta.firmada');
});
