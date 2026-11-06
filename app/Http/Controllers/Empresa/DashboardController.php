<?php

namespace App\Http\Controllers\Empresa;

use App\Enums\EstadoReclamacion;
use App\Enums\TipoBien;
use App\Enums\TipoReclamacion;
use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Establecimiento;
use App\Models\Reclamacion;
use App\Services\PlazoReclamacionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador del Dashboard analítico e indicadores de gestión de la empresa.
 * Provee métricas en tiempo real sobre estados, vencimientos conforme a plazos de ley,
 * antigüedad de casos, distribución tipológica y tiempos promedio de respuesta.
 */
class DashboardController extends Controller
{
    public function __construct(
        protected PlazoReclamacionService $plazoService
    ) {}

    /**
     * Muestra el panel principal de indicadores analíticos para el tenant actual.
     */
    public function index(Request $request): Response
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        $periodo = (string) $request->input('periodo', '30_dias');
        $sedeId = $request->input('establecimiento_id');

        // Determinar rango de fechas según el periodo seleccionado
        $ahora = Carbon::now('America/Lima');
        $fechaDesde = null;
        $fechaHasta = null;

        switch ($periodo) {
            case 'este_mes':
                $fechaDesde = $ahora->copy()->startOfMonth();
                $fechaHasta = $ahora->copy()->endOfMonth();
                break;
            case '3_meses':
                $fechaDesde = $ahora->copy()->subDays(90)->startOfDay();
                $fechaHasta = $ahora->copy()->endOfDay();
                break;
            case 'este_anio':
                $fechaDesde = $ahora->copy()->startOfYear();
                $fechaHasta = $ahora->copy()->endOfYear();
                break;
            case 'todos':
                $fechaDesde = null;
                $fechaHasta = null;
                break;
            case 'personalizado':
                if ($request->filled('fecha_desde')) {
                    $fechaDesde = Carbon::parse($request->input('fecha_desde'), 'America/Lima')->startOfDay();
                }
                if ($request->filled('fecha_hasta')) {
                    $fechaHasta = Carbon::parse($request->input('fecha_hasta'), 'America/Lima')->endOfDay();
                }
                break;
            case '30_dias':
            default:
                $periodo = '30_dias';
                $fechaDesde = $ahora->copy()->subDays(30)->startOfDay();
                $fechaHasta = $ahora->copy()->endOfDay();
                break;
        }

        // Construir consulta base delimitada estrictamente por empresa y filtros
        $baseQuery = Reclamacion::where('empresa_id', $empresa->id);

        if ($fechaDesde) {
            $baseQuery->where('fecha_hora', '>=', $fechaDesde);
        }
        if ($fechaHasta) {
            $baseQuery->where('fecha_hora', '<=', $fechaHasta);
        }
        if ($sedeId) {
            $baseQuery->where('establecimiento_id', $sedeId);
        }

        // Métricas de estado (KPIs)
        $totalExpedientes = (clone $baseQuery)->count();
        $pendientes = (clone $baseQuery)->where('estado', EstadoReclamacion::Pendiente->value)->count();
        $enTramite = (clone $baseQuery)->where('estado', EstadoReclamacion::EnTramite->value)->count();
        $atendidas = (clone $baseQuery)->where('estado', EstadoReclamacion::Atendida->value)->count();
        $cerradas = (clone $baseQuery)->where('estado', EstadoReclamacion::Cerrada->value)->count();

        $resueltas = $atendidas + $cerradas;
        $tasaResolucion = $totalExpedientes > 0
            ? round(($resueltas / $totalExpedientes) * 100, 1)
            : 0;

        // Distribución por Tipo de Caso
        $reclamos = (clone $baseQuery)->where('tipo', TipoReclamacion::Reclamo->value)->count();
        $quejas = (clone $baseQuery)->where('tipo', TipoReclamacion::Queja->value)->count();

        // Distribución por Tipo de Bien
        $productos = (clone $baseQuery)->where('tipo_bien', TipoBien::Producto->value)->count();
        $servicios = (clone $baseQuery)->where('tipo_bien', TipoBien::Servicio->value)->count();

        // Cómputo del semáforo legal para expedientes abiertos (Pendientes y En trámite)
        $expedientesAbiertos = (clone $baseQuery)
            ->whereIn('estado', [EstadoReclamacion::Pendiente->value, EstadoReclamacion::EnTramite->value])
            ->whereNotNull('vence_en')
            ->get(['id', 'numero_hoja', 'fecha_hora', 'vence_en', 'estado', 'datos_consumidor', 'establecimiento_id']);

        $semaforoConteos = [
            'en_plazo' => 0,       // > 3 días hábiles
            'proximo' => 0,        // 1 a 3 días hábiles
            'vence_hoy' => 0,      // 0 días hábiles
            'vencidos' => 0,       // < 0 días hábiles
        ];

        // Antigüedad de expedientes abiertos (días calendario transcurridos desde fecha_hora)
        $antiguedadConteos = [
            'de_0_a_5' => 0,
            'de_6_a_10' => 0,
            'de_11_a_15' => 0,
            'mas_de_15' => 0,
        ];

        $prioritarios = [];

        foreach ($expedientesAbiertos as $rec) {
            $semaforo = $this->plazoService->obtenerEstadoSemaforo($rec->vence_en, $rec->estado->value);
            $dias = $semaforo['dias_restantes'];
            $vencido = $semaforo['vencido'] ?? false;

            if ($vencido) {
                $semaforoConteos['vencidos']++;
            } elseif ($dias === 0) {
                $semaforoConteos['vence_hoy']++;
            } elseif ($dias <= 3) {
                $semaforoConteos['proximo']++;
            } else {
                $semaforoConteos['en_plazo']++;
            }

            // Antigüedad desde ingreso
            $diasAntiguedad = (int) $rec->fecha_hora->diffInDays($ahora);
            if ($diasAntiguedad <= 5) {
                $antiguedadConteos['de_0_a_5']++;
            } elseif ($diasAntiguedad <= 10) {
                $antiguedadConteos['de_6_a_10']++;
            } elseif ($diasAntiguedad <= 15) {
                $antiguedadConteos['de_11_a_15']++;
            } else {
                $antiguedadConteos['mas_de_15']++;
            }

            // Candidato a prioritario (menor cantidad de días restantes o vencido)
            $consumidor = $rec->datos_consumidor ?? [];
            $prioritarios[] = [
                'id' => $rec->id,
                'numero_hoja' => $rec->numero_hoja,
                'fecha_hora' => $rec->fecha_hora?->format('d/m/Y') ?? '',
                'estado' => $rec->estado->value,
                'consumidor_nombre' => trim(($consumidor['nombres'] ?? '').' '.($consumidor['apellidos'] ?? '')),
                'vence_en' => $rec->vence_en?->format('d/m/Y'),
                'dias_restantes' => $dias,
                'vencido' => $vencido,
                'semaforo' => $semaforo,
            ];
        }

        // Ordenar prioritarios: los vencidos primero (más negativos) y luego los más cercanos a vencer
        usort($prioritarios, fn ($a, $b) => $a['dias_restantes'] <=> $b['dias_restantes']);
        $prioritarios = array_slice($prioritarios, 0, 5);

        // Tiempo promedio de respuesta formal en días
        $reclamacionesAtendidas = (clone $baseQuery)
            ->whereIn('estado', [EstadoReclamacion::Atendida->value, EstadoReclamacion::Cerrada->value])
            ->with(['respuestas' => fn ($q) => $q->orderBy('created_at', 'asc')])
            ->get();

        $totalDiasRespuesta = 0;
        $atendidasConRespuesta = 0;
        $atendidasEnPlazo = 0;

        foreach ($reclamacionesAtendidas as $rec) {
            $primeraRespuesta = $rec->respuestas->first();
            if ($primeraRespuesta) {
                $atendidasConRespuesta++;
                $dias = max(0, (int) $rec->fecha_hora->diffInDays($primeraRespuesta->created_at));
                $totalDiasRespuesta += $dias;

                // Verificar si se respondió dentro de la fecha límite
                if ($rec->vence_en && $primeraRespuesta->created_at->lte($rec->vence_en->endOfDay())) {
                    $atendidasEnPlazo++;
                }
            }
        }

        $tiempoPromedioDias = $atendidasConRespuesta > 0
            ? round($totalDiasRespuesta / $atendidasConRespuesta, 1)
            : null;

        $tasaCumplimientoLegal = $atendidasConRespuesta > 0
            ? round(($atendidasEnPlazo / $atendidasConRespuesta) * 100, 1)
            : ($resueltas > 0 ? 100.0 : null);

        // Distribución por Sedes / Establecimientos
        $sedes = Establecimiento::where('empresa_id', $empresa->id)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'codigo', 'nombre']);

        $desgloseSedes = [];
        foreach ($sedes as $sede) {
            $conteo = (clone $baseQuery)->where('establecimiento_id', $sede->id)->count();
            $desgloseSedes[] = [
                'id' => $sede->id,
                'codigo' => $sede->codigo,
                'nombre' => $sede->nombre,
                'total' => $conteo,
            ];
        }

        return Inertia::render('Empresa/Dashboard/Index', [
            'filtros' => [
                'periodo' => $periodo,
                'fecha_desde' => $fechaDesde?->format('Y-m-d') ?? '',
                'fecha_hasta' => $fechaHasta?->format('Y-m-d') ?? '',
                'establecimiento_id' => $sedeId ? (int) $sedeId : null,
            ],
            'sedes' => $sedes,
            'kpis' => [
                'total' => $totalExpedientes,
                'pendientes' => $pendientes,
                'en_tramite' => $enTramite,
                'atendidas' => $atendidas,
                'cerradas' => $cerradas,
                'tasa_resolucion' => $tasaResolucion,
                'tiempo_promedio_dias' => $tiempoPromedioDias,
                'tasa_cumplimiento_legal' => $tasaCumplimientoLegal,
            ],
            'semaforo' => $semaforoConteos,
            'antiguedad' => $antiguedadConteos,
            'distribucion_tipo' => [
                'reclamos' => $reclamos,
                'quejas' => $quejas,
            ],
            'distribucion_bien' => [
                'productos' => $productos,
                'servicios' => $servicios,
            ],
            'desglose_sedes' => $desgloseSedes,
            'prioritarios' => $prioritarios,
        ]);
    }
}
