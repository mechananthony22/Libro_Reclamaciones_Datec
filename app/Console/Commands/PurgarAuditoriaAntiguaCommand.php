<?php

namespace App\Console\Commands;

use App\Models\AuditoriaLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Comando para el mantenimiento, purga y optimización de almacenamiento
 * de la bitácora de auditoría histórica, respetando el plazo de custodia legal
 * mínimo de veinticuatro (24) meses establecido en el D.S. N° 011-2011-PCM
 * y el Código de Protección y Defensa del Consumidor (Ley N° 29571).
 */
class PurgarAuditoriaAntiguaCommand extends Command
{
    /**
     * Nombre y firma del comando en consola.
     *
     * @var string
     */
    protected $signature = 'auditoria:purgar
                            {--meses=24 : Cantidad mínima de meses de antigüedad a conservar conforme a ley (mínimo 24 meses)}
                            {--dry-run : Simula la depuración mostrando el volumen de registros a purgar sin borrarlos}
                            {--force : Omite la restricción mínima de 24 meses (solo para mantenimiento forzado o pruebas)}';

    /**
     * Descripción del comando en consola.
     *
     * @var string
     */
    protected $description = 'Purga registros de auditoría que superan el plazo legal de retención (24 meses) para optimizar el almacenamiento.';

    /**
     * Ejecuta el comando.
     */
    public function handle(): int
    {
        $meses = (int) $this->option('meses');
        $esDryRun = (bool) $this->option('dry-run');
        $esForzado = (bool) $this->option('force');

        // Validación de salvaguarda legal: no permitir purga de datos menores a 24 meses sin --force
        if ($meses < 24 && ! $esForzado) {
            $this->error('La normativa peruana (Ley N° 29571 / D.S. N° 011-2011-PCM) exige conservar las reclamaciones y su trazabilidad durante al menos 24 meses.');
            $this->warn('Si por razones de prueba o mantenimiento extraordinario requieres purgar antes, añade la opción --force.');

            return self::FAILURE;
        }

        $fechaLimite = Carbon::now()->subMonths($meses);

        $this->info('================================================================================');
        $this->info("      MANTENIMIENTO Y PURGA DE AUDITORÍA HISTÓRICA (RETENCIÓN: {$meses} MESES)");
        $this->info('================================================================================');
        $this->line("Fecha límite de retención: {$fechaLimite->format('Y-m-d H:i:s')} (registros anteriores serán purgados)");

        $query = AuditoriaLog::where('created_at', '<', $fechaLimite);
        $totalCandidatos = $query->count();

        if ($totalCandidatos === 0) {
            $this->info('✓ No existen registros de auditoría anteriores a la fecha límite. Almacenamiento optimizado.');

            return self::SUCCESS;
        }

        if ($esDryRun) {
            $this->warn('[MODO SIMULACIÓN - DRY RUN]');
            $this->line("Se identificaron {$totalCandidatos} registros de auditoría listos para ser purgados.");
            $registroMasAntiguo = AuditoriaLog::where('created_at', '<', $fechaLimite)->orderBy('created_at', 'asc')->first();
            $registroMasReciente = AuditoriaLog::where('created_at', '<', $fechaLimite)->orderBy('created_at', 'desc')->first();

            $this->line("• Registro más antiguo: {$registroMasAntiguo?->created_at}");
            $this->line("• Registro más reciente en lote: {$registroMasReciente?->created_at}");
            $this->info('Ningún registro fue eliminado físicamente.');

            return self::SUCCESS;
        }

        $this->info("Iniciando eliminación de {$totalCandidatos} registros obsoletos en bloques de seguridad...");

        $eliminados = 0;
        // Eliminación por lotes de 500 registros para evitar bloqueos prolongados de tabla en PostgreSQL
        do {
            $loteIds = AuditoriaLog::where('created_at', '<', $fechaLimite)
                ->limit(500)
                ->pluck('id');

            if ($loteIds->isEmpty()) {
                break;
            }

            $cantidad = AuditoriaLog::whereIn('id', $loteIds)->delete();
            $eliminados += $cantidad;
            $this->output->write('.');
        } while (true);

        $this->newLine();
        $this->info("✓ Purga completada exitosamente. Total de registros liberados: {$eliminados}.");

        Log::info("Purga de auditoría completada: {$eliminados} registros eliminados anteriores a {$fechaLimite->toDateString()} (Retención: {$meses} meses).");

        return self::SUCCESS;
    }
}
