<?php

namespace App\Console\Commands;

use App\Enums\EstadoEmpresa;
use App\Models\Empresa;
use App\Services\AuditoriaService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Comando programado para la depuración y purga definitiva de empresas dadas de baja
 * cuyo plazo de retención legal y custodia probatoria (retencion_hasta) ha expirado.
 */
class PurgarBajasRetencionVencidaCommand extends Command
{
    /**
     * Nombre y firma del comando en consola.
     *
     * @var string
     */
    protected $signature = 'empresa:purgar-bajas-vencidas
                            {--dry-run : Simula la depuración sin eliminar archivos de custodia}';

    /**
     * Descripción del comando en consola.
     *
     * @var string
     */
    protected $description = 'Purga paquetes de custodia de empresas en baja programada cuyo plazo de retención legal ha expirado.';

    /**
     * Ejecuta el comando.
     */
    public function handle(): int
    {
        $esDryRun = (bool) $this->option('dry-run');
        $hoy = Carbon::today('America/Lima');

        $this->info('================================================================================');
        $this->info('   PURGA AUTOMÁTICA DE CUSTODIAS LEGALES CON PLAZO DE RETENCIÓN VENCIDO');
        $this->info('================================================================================');
        $this->line("Fecha de corte legal: {$hoy->toDateString()}");

        $empresasVencidas = Empresa::where('estado', EstadoEmpresa::BajaProgramada)
            ->whereNotNull('retencion_hasta')
            ->where('retencion_hasta', '<=', $hoy->toDateString())
            ->get();

        $total = $empresasVencidas->count();
        $this->line("Empresas candidatas para purga de custodia: {$total}");

        if ($total === 0) {
            $this->info('No hay empresas con plazo de retención vencido. Custodia vigente.');

            return self::SUCCESS;
        }

        $purgadas = 0;

        foreach ($empresasVencidas as $empresa) {
            $disco = Storage::disk('local');
            $directorioCustodia = "custodia/{$empresa->id}";

            if ($esDryRun) {
                $this->line("[DRY-RUN] Se purgaría custodia de: {$empresa->name} (RUC: {$empresa->ruc}) - Venció: {$empresa->retencion_hasta?->format('Y-m-d')}");
                $purgadas++;

                continue;
            }

            // Eliminar archivos físicos de custodia si existen mediante Storage de Laravel
            if ($disco->exists($directorioCustodia)) {
                $disco->deleteDirectory($directorioCustodia);
            }

            // Compatibilidad para limpiar remanentes antiguos fuera de Storage si existieran
            $directorioLegado = storage_path("app/custodia/{$empresa->id}");
            if (is_dir($directorioLegado)) {
                $archivosLegados = glob("{$directorioLegado}/*");
                if ($archivosLegados) {
                    foreach ($archivosLegados as $archivo) {
                        if (is_file($archivo)) {
                            @unlink($archivo);
                        }
                    }
                }
                @rmdir($directorioLegado);
            }

            // Registrar en la bitácora inmutable de auditoría
            app(AuditoriaService::class)->registrar(
                accion: 'retencion_legal_purgada',
                modulo: 'empresas',
                descripcion: "Cumplimiento del plazo de retención legal ({$empresa->retencion_hasta?->format('Y-m-d')}) y purga definitiva de custodia para la empresa {$empresa->name} (RUC: {$empresa->ruc}).",
                opciones: [
                    'empresa_id' => $empresa->id,
                    'datos_despues' => [
                        'retencion_hasta' => $empresa->retencion_hasta?->toDateString(),
                        'purgado_en' => now()->toIso8601String(),
                    ],
                ]
            );

            Log::info("Custodia legal purgada por vencimiento de retención para empresa {$empresa->ruc}");
            $this->line("Purga completada para: {$empresa->name} (RUC: {$empresa->ruc})");
            $purgadas++;
        }

        $this->info("Proceso concluido: {$purgadas} empresa(s) procesada(s).");

        return self::SUCCESS;
    }
}
