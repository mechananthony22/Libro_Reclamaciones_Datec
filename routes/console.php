<?php

use App\Console\Commands\PurgarAuditoriaAntiguaCommand;
use App\Console\Commands\VerificarSaludPlataformaCommand;
use App\Console\Commands\VerificarVencimientosReclamacionesCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Tareas Programadas del Sistema
|--------------------------------------------------------------------------
|
| Verificación diaria de plazos legales de atención (15 días hábiles según Ley N° 29571)
| ejecutada en horario laboral matutino con la zona horaria legal de Lima, Perú.
|
*/
Schedule::command(VerificarVencimientosReclamacionesCommand::class)
    ->dailyAt('08:00')
    ->timezone('America/Lima')
    ->withoutOverlapping();

// Supervisión periódica de salud, tareas en cola y fallos de mensajería (alertas operativas a Datec)
Schedule::command(VerificarSaludPlataformaCommand::class)
    ->everyFifteenMinutes()
    ->withoutOverlapping();

// Mantenimiento y depuración mensual de registros de auditoría que superan el plazo legal (24 meses)
Schedule::command(PurgarAuditoriaAntiguaCommand::class)
    ->monthlyOn(1, '02:00')
    ->timezone('America/Lima')
    ->withoutOverlapping();
