<?php

namespace App\Console\Commands;

use App\Services\PlataformaSaludService;
use Illuminate\Console\Command;

/**
 * Comando programado para supervisar la salud de la plataforma, el uso de recursos,
 * tareas pendientes y fallos de mensajería, alertando a Datec ante desvíos de umbral.
 */
class VerificarSaludPlataformaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plataforma:verificar-salud';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evalúa los umbrales de disponibilidad, recursos y tareas pendientes emitiendo alertas operativas a Datec';

    /**
     * Execute the console command.
     */
    public function handle(PlataformaSaludService $saludService): int
    {
        $this->info('Evaluando disponibilidad, colas, fallos de mensajería y recursos...');

        $diagnostico = $saludService->evaluarUmbralesYAlertar();

        $estadoGlobal = strtoupper($diagnostico['estado_global']);
        if ($estadoGlobal === 'CRITICO') {
            $this->error("ESTADO GLOBAL DE PLATAFORMA: {$estadoGlobal}");
        } elseif ($estadoGlobal === 'ADVERTENCIA') {
            $this->warn("ESTADO GLOBAL DE PLATAFORMA: {$estadoGlobal}");
        } else {
            $this->info("ESTADO GLOBAL DE PLATAFORMA: {$estadoGlobal}");
        }

        $this->table(
            ['Métrica Operativa', 'Valor Actual', 'Umbral Advertencia', 'Umbral Crítico'],
            [
                ['Tareas en Cola Pendientes', $diagnostico['metricas']['jobs_pendientes'], $diagnostico['umbrales']['jobs_pendientes']['warn'], $diagnostico['umbrales']['jobs_pendientes']['crit']],
                ['Trabajos Fallidos', $diagnostico['metricas']['jobs_fallidos'], $diagnostico['umbrales']['jobs_fallidos']['warn'], $diagnostico['umbrales']['jobs_fallidos']['crit']],
                ['Envíos Fallidos de Correo', $diagnostico['metricas']['envios_fallidos'], $diagnostico['umbrales']['envios_fallidos']['warn'], $diagnostico['umbrales']['envios_fallidos']['crit']],
                ['Empresas en Contingencia', $diagnostico['metricas']['contingencias_activas'], $diagnostico['umbrales']['contingencias_activas']['warn'], $diagnostico['umbrales']['contingencias_activas']['crit']],
            ]
        );

        $alertas = $diagnostico['alertas_activas'];
        if (count($alertas) > 0) {
            $this->warn('Alertas Activas Vigentes:');
            foreach ($alertas as $a) {
                $this->line(" - [{$a['severidad']}] {$a['titulo']}: {$a['descripcion']} (Valor: {$a['valor_actual']})");
            }
        } else {
            $this->info('Todos los indicadores se encuentran dentro de los rangos operativos normales.');
        }

        return self::SUCCESS;
    }
}
