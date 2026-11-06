<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Servicio de cálculo de plazos legales y estado del semáforo para expedientes de reclamación.
 * Basado en el plazo general de 15 días hábiles conforme al marco legal peruano de protección al consumidor.
 */
class PlazoReclamacionService
{
    /**
     * Feriados nacionales fijos en Perú (mes-día).
     */
    protected const FERIADOS_FIJOS = [
        '01-01', // Año Nuevo
        '05-01', // Día del Trabajo
        '06-07', // Día de la Bandera
        '06-29', // San Pedro y San Pablo
        '07-23', // Día de la Fuerza Aérea
        '07-28', // Fiestas Patrias
        '07-29', // Fiestas Patrias
        '08-06', // Batalla de Junín
        '08-30', // Santa Rosa de Lima
        '10-08', // Combate de Angamos
        '11-01', // Todos los Santos
        '12-08', // Inmaculada Concepción
        '12-09', // Batalla de Ayacucho
        '12-25', // Navidad
    ];

    /**
     * Calcula la fecha de vencimiento sumando un número determinado de días hábiles.
     */
    public function calcularFechaVencimiento(CarbonInterface $fechaInicio, int $diasHabiles = 15): Carbon
    {
        $fecha = Carbon::parse($fechaInicio)->copy()->startOfDay();
        $diasAgregados = 0;

        while ($diasAgregados < $diasHabiles) {
            $fecha->addDay();
            if ($this->esDiaHabil($fecha)) {
                $diasAgregados++;
            }
        }

        return $fecha;
    }

    /**
     * Verifica si una fecha dada es día hábil (lunes a viernes y no feriado fijo nacional).
     */
    public function esDiaHabil(CarbonInterface $fecha): bool
    {
        if ($fecha->isWeekend()) {
            return false;
        }

        $mesDia = $fecha->format('m-d');
        if (in_array($mesDia, self::FERIADOS_FIJOS, true)) {
            return false;
        }

        return true;
    }

    /**
     * Calcula los días hábiles restantes hasta el vencimiento o transcurridos si ya venció.
     */
    public function calcularDiasHabilesRestantes(CarbonInterface $fechaVencimiento): int
    {
        $hoy = Carbon::now('America/Lima')->startOfDay();
        $vence = Carbon::parse($fechaVencimiento)->startOfDay();

        if ($hoy->greaterThan($vence)) {
            // Ya venció: contamos días hábiles transcurridos en negativo
            $dias = 0;
            $cursor = $vence->copy();
            while ($cursor->lessThan($hoy)) {
                $cursor->addDay();
                if ($this->esDiaHabil($cursor)) {
                    $dias--;
                }
            }

            return $dias;
        }

        // Aún no vence: contamos días hábiles disponibles
        $dias = 0;
        $cursor = $hoy->copy();
        while ($cursor->lessThan($vence)) {
            $cursor->addDay();
            if ($this->esDiaHabil($cursor)) {
                $dias++;
            }
        }

        return $dias;
    }

    /**
     * Determina el color del semáforo y la etiqueta del estado de vencimiento del expediente.
     * Retorna array shape: ['color' => string, 'etiqueta' => string, 'dias_restantes' => int, 'vencido' => bool]
     */
    public function obtenerEstadoSemaforo(CarbonInterface $fechaVencimiento, string $estadoExpediente): array
    {
        // Si ya está atendida o cerrada, el semáforo se considera resuelto
        if (in_array($estadoExpediente, ['atendida', 'cerrada'], true)) {
            return [
                'color' => 'neutral',
                'etiqueta' => 'Finalizado',
                'dias_restantes' => 0,
                'vencido' => false,
            ];
        }

        $diasRestantes = $this->calcularDiasHabilesRestantes($fechaVencimiento);

        if ($diasRestantes < 0) {
            return [
                'color' => 'danger',
                'etiqueta' => 'Vencido hace '.abs($diasRestantes).' día(s) hábil(es)',
                'dias_restantes' => $diasRestantes,
                'vencido' => true,
            ];
        }

        if ($diasRestantes === 0) {
            return [
                'color' => 'danger',
                'etiqueta' => 'Vence hoy',
                'dias_restantes' => 0,
                'vencido' => false,
            ];
        }

        if ($diasRestantes <= 3) {
            return [
                'color' => 'warning',
                'etiqueta' => "Urgente: {$diasRestantes} día(s) hábil(es)",
                'dias_restantes' => $diasRestantes,
                'vencido' => false,
            ];
        }

        if ($diasRestantes <= 7) {
            return [
                'color' => 'amber',
                'etiqueta' => "Atención: {$diasRestantes} día(s) hábil(es)",
                'dias_restantes' => $diasRestantes,
                'vencido' => false,
            ];
        }

        return [
            'color' => 'success',
            'etiqueta' => "En plazo: {$diasRestantes} día(s) hábil(es)",
            'dias_restantes' => $diasRestantes,
            'vencido' => false,
        ];
    }
}
