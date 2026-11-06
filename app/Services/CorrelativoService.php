<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\Establecimiento;
use Illuminate\Support\Facades\DB;

/**
 * Servicio especializado en la generación atómica y correlativa de números de hoja
 * del Libro de Reclamaciones, garantizando la inmutabilidad y la prevención estricta
 * de duplicidades ante solicitudes concurrentes según las directrices de Indecopi.
 */
class CorrelativoService
{
    /**
     * Longitud de relleno con ceros a la izquierda para el número correlativo.
     */
    public const LONGITUD_CORRELATIVO = 8;

    /**
     * Genera el siguiente número correlativo atómico para la empresa y ámbito correspondiente.
     * Utiliza bloqueo pesimista a nivel de fila (lockForUpdate) dentro de una transacción de base de datos
     * para asegurar que dos peticiones simultáneas nunca obtengan el mismo número.
     */
    public function generarProximoNumero(Empresa $empresa, ?int $establecimientoId = null, ?int $anio = null): string
    {
        $anio = $anio ?: (int) now()->year;

        // Determinar el código de ámbito y prefijo en caso de estar asociado a un establecimiento físico específico
        $codigoAmbito = 'GENERAL';
        $prefijoEstablecimiento = null;

        if ($establecimientoId) {
            $establecimiento = Establecimiento::where('empresa_id', $empresa->id)
                ->where('id', $establecimientoId)
                ->first();

            if ($establecimiento && ! empty($establecimiento->codigo)) {
                $prefijoEstablecimiento = preg_replace('/[^A-Za-z0-9_-]/', '', $establecimiento->codigo);
                $codigoAmbito = 'EST_'.$prefijoEstablecimiento;
            } elseif ($establecimiento) {
                $codigoAmbito = 'EST_'.$establecimiento->id;
            }
        }

        // Ejecutar la asignación y actualización dentro del contexto transaccional con bloqueo pesimista
        return DB::transaction(function () use ($empresa, $codigoAmbito, $prefijoEstablecimiento, $anio) {
            // Garantizar la existencia previa del registro de secuencia para el año y ámbito
            DB::table('secuencias_reclamaciones')->insertOrIgnore([
                'empresa_id' => $empresa->id,
                'codigo_ambito' => $codigoAmbito,
                'anio' => $anio,
                'ultimo_numero' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Bloquear la fila de secuencia para concurrencia atómica
            $secuencia = DB::table('secuencias_reclamaciones')
                ->where('empresa_id', $empresa->id)
                ->where('codigo_ambito', $codigoAmbito)
                ->where('anio', $anio)
                ->lockForUpdate()
                ->first();

            $siguienteNumero = ((int) $secuencia->ultimo_numero) + 1;

            // Actualizar el correlativo persistido
            DB::table('secuencias_reclamaciones')
                ->where('id', $secuencia->id)
                ->update([
                    'ultimo_numero' => $siguienteNumero,
                    'updated_at' => now(),
                ]);

            // Formatear el correlativo oficial con padding de 8 dígitos y sufijo de año (ej. 00000001-2026)
            $numeroPadded = str_pad((string) $siguienteNumero, self::LONGITUD_CORRELATIVO, '0', STR_PAD_LEFT);

            if ($prefijoEstablecimiento) {
                return "{$prefijoEstablecimiento}-{$numeroPadded}-{$anio}";
            }

            return "{$numeroPadded}-{$anio}";
        });
    }
}
