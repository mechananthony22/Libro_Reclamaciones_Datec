<?php

namespace App\Console\Commands;

use App\Enums\EstadoReclamacion;
use App\Enums\RolEmpresa;
use App\Mail\AlertaVencimientoMailable;
use App\Models\Reclamacion;
use App\Models\ReclamacionEvento;
use App\Services\PlazoReclamacionService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Tarea programada para verificar el cumplimiento del plazo legal de 15 días hábiles
 * en expedientes de reclamación y enviar notificaciones preventivas y de vencimiento.
 */
class VerificarVencimientosReclamacionesCommand extends Command
{
    /**
     * Firma del comando en la consola de Artisan.
     *
     * @var string
     */
    protected $signature = 'reclamaciones:verificar-vencimientos {--dias=3 : Días hábiles de anticipación para alertas preventivas}';

    /**
     * Descripción de la tarea en la consola.
     *
     * @var string
     */
    protected $description = 'Verifica plazos de reclamaciones pendientes o en trámite y notifica alertas preventivas o de vencimiento.';

    /**
     * Ejecuta el comando de verificación y notificación de plazos.
     */
    public function handle(PlazoReclamacionService $plazoService): int
    {
        $diasAnticipacion = (int) $this->option('dias');
        $this->info("Iniciando verificación de plazos (umbral preventivo: {$diasAnticipacion} días hábiles)...");

        // Obtenemos todos los expedientes que aún no tienen respuesta formal definitiva
        $reclamaciones = Reclamacion::query()
            ->whereIn('estado', [
                EstadoReclamacion::Pendiente->value,
                EstadoReclamacion::EnTramite->value,
            ])
            ->whereNotNull('vence_en')
            ->with(['empresa.usuarios', 'responsable', 'establecimiento'])
            ->get();

        $hoyLima = Carbon::today('America/Lima');
        $inicioDiaLimaUtc = $hoyLima->copy()->utc();
        $notificacionesEnviadas = 0;
        $expedientesProcesados = 0;

        foreach ($reclamaciones as $rec) {
            $expedientesProcesados++;
            $semaforo = $plazoService->obtenerEstadoSemaforo($rec->vence_en, $rec->estado->value);
            $diasRestantes = $semaforo['dias_restantes'];
            $esVencido = $semaforo['vencido'] ?? false;

            // Configuración de alertas personalizada de la empresa (RF-039)
            $diasUmbralEmpresa = $rec->empresa?->dias_anticipacion_alerta ?? $diasAnticipacion;
            $notificarPreventivo = (bool) ($rec->empresa?->notificar_vencimiento_preventivo ?? true);

            // Si aún no vence y la empresa desactivó alertas preventivas, omitir
            if (! $esVencido && ! $notificarPreventivo) {
                continue;
            }

            // Determinar si corresponde alertar (vencido normativo o dentro del umbral preventivo)
            $debeAlertar = $esVencido || ($diasRestantes <= $diasUmbralEmpresa && $diasRestantes >= 0);

            if (! $debeAlertar) {
                continue;
            }

            // Evitar enviar alertas duplicadas el mismo día para no saturar los buzones
            $yaAlertadoHoy = ReclamacionEvento::query()
                ->where('reclamacion_id', $rec->id)
                ->where('accion', 'alerta_vencimiento_enviada')
                ->where('created_at', '>=', $inicioDiaLimaUtc)
                ->exists();

            if ($yaAlertadoHoy) {
                continue;
            }

            // Recopilar destinatarios: responsable asignado, usuarios de gestión y destinatarios adicionales configurados
            $destinatarios = [];

            if ($rec->responsable?->email) {
                $destinatarios[] = $rec->responsable->email;
            }

            if ($rec->empresa) {
                $adminEmails = $rec->empresa->usuarios
                    ->filter(function ($usuario) {
                        $rol = strtolower((string) ($usuario->pivot->rol ?? ''));
                        $activo = (bool) ($usuario->pivot->activo ?? true);

                        return $activo && $rol === RolEmpresa::AdminEmpresa->value;
                    })
                    ->pluck('email')
                    ->all();

                $destinatarios = array_merge($destinatarios, $adminEmails);

                // Destinatarios adicionales de la empresa (RF-039)
                $destinatarios = array_merge($destinatarios, $rec->empresa->destinatariosNotificaciones());
            }

            $destinatarios = array_values(array_unique(array_filter($destinatarios)));

            if (empty($destinatarios)) {
                $this->warn("Expediente {$rec->numero_hoja}: sin destinatarios válidos configurados.");

                continue;
            }

            // Enviar notificación por correo
            try {
                Mail::to($destinatarios)->send(new AlertaVencimientoMailable($rec, $semaforo));

                // Registrar evento inmutable de auditoría
                $descripcion = $esVencido
                    ? 'Alerta de plazo legal vencido notificada por correo a: '.implode(', ', $destinatarios)
                    : "Alerta preventiva ({$diasRestantes} días hábiles restantes) notificada por correo a: ".implode(', ', $destinatarios);

                ReclamacionEvento::create([
                    'empresa_id' => $rec->empresa_id,
                    'reclamacion_id' => $rec->id,
                    'actor_tipo' => 'sistema',
                    'actor_id' => null,
                    'actor_nombre' => 'Sistema / Programador de Plazos',
                    'accion' => 'alerta_vencimiento_enviada',
                    'descripcion' => $descripcion,
                    'datos_despues' => [
                        'dias_restantes' => $diasRestantes,
                        'vencido' => $esVencido,
                        'etiqueta' => $semaforo['etiqueta'],
                        'destinatarios' => $destinatarios,
                        'vence_en' => $rec->vence_en->format('Y-m-d'),
                    ],
                    'created_at' => now(),
                ]);

                $notificacionesEnviadas++;
                $this->line("Alerta enviada para expediente {$rec->numero_hoja} a ".implode(', ', $destinatarios));
            } catch (\Throwable $e) {
                $this->error("Error enviando alerta para {$rec->numero_hoja}: {$e->getMessage()}");
            }
        }

        $this->info("Verificación finalizada. Procesados: {$expedientesProcesados}. Notificaciones enviadas: {$notificacionesEnviadas}.");

        return self::SUCCESS;
    }
}
