<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\SoporteAutorizacion;
use App\Services\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de gestión central de soporte técnico para el Superadministrador Datec.
 * Permite monitorear tickets y solicitar acceso temporal delegado a las empresas clientes.
 */
class SoporteController extends Controller
{
    public function __construct(
        protected AuditoriaService $auditoriaService
    ) {}

    /**
     * Muestra la bandeja de tickets de soporte técnico del ecosistema multiempresa.
     */
    public function index(Request $request): Response
    {
        $query = SoporteAutorizacion::query()
            ->with(['empresa:id,name,slug,ruc', 'solicitadoPor:id,name,email', 'autorizadoPor:id,name,email', 'finalizadoPor:id,name,email'])
            ->orderBy('created_at', 'desc');

        if ($empresaId = $request->input('empresa_id')) {
            $query->where('empresa_id', $empresaId);
        }

        if ($estado = $request->input('estado')) {
            $query->where('estado', $estado);
        }

        $tickets = $query->paginate(20)->withQueryString()->through(fn (SoporteAutorizacion $item) => [
            'id' => $item->id,
            'empresa_id' => $item->empresa_id,
            'empresa_nombre' => $item->empresa?->name,
            'empresa_slug' => $item->empresa?->slug,
            'codigo_ticket' => $item->codigo_ticket,
            'titulo' => $item->titulo,
            'motivo' => $item->motivo,
            'estado' => $item->estado,
            'is_activo' => $item->isActivo(),
            'horas_vigencia' => $item->horas_vigencia,
            'autorizado_en' => $item->autorizado_en?->format('d/m/Y H:i'),
            'valido_hasta' => $item->valido_hasta?->format('d/m/Y H:i'),
            'finalizado_en' => $item->finalizado_en?->format('d/m/Y H:i'),
            'resolucion_detalle' => $item->resolucion_detalle,
            'finalizado_por' => $item->finalizadoPor?->name,
            'solicitado_por' => $item->solicitadoPor?->name ?? 'Datec Soporte',
            'autorizado_por' => $item->autorizadoPor?->name,
            'created_at' => $item->created_at?->format('d/m/Y H:i'),
        ]);

        $empresas = Empresa::orderBy('name')->get(['id', 'name', 'slug']);

        return Inertia::render('Admin/Soporte/Index', [
            'tickets' => $tickets,
            'empresas' => $empresas,
            'filtros' => [
                'empresa_id' => $request->input('empresa_id', ''),
                'estado' => $request->input('estado', ''),
            ],
        ]);
    }

    /**
     * Solicita acceso delegado a una empresa cliente para soporte técnico.
     */
    public function solicitar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'empresa_id' => ['required', 'exists:tenants,id'],
            'titulo' => ['required', 'string', 'max:150'],
            'motivo' => ['required', 'string', 'max:1000'],
            'horas_vigencia' => ['nullable', 'integer', 'min:1', 'max:168'],
        ]);

        $empresa = Empresa::findOrFail($datos['empresa_id']);
        $user = $request->user();

        $conteoHoy = SoporteAutorizacion::whereDate('created_at', now()->toDateString())->count() + 1;
        $codigoTicket = 'TCK-'.now()->format('Ymd').'-'.str_pad((string) $conteoHoy, 4, '0', STR_PAD_LEFT);

        $ticket = SoporteAutorizacion::create([
            'empresa_id' => $empresa->id,
            'codigo_ticket' => $codigoTicket,
            'titulo' => $datos['titulo'],
            'motivo' => $datos['motivo'],
            'solicitado_por_id' => $user?->id,
            'estado' => 'pendiente',
            'horas_vigencia' => (int) ($datos['horas_vigencia'] ?? 24),
        ]);

        $this->auditoriaService->registrar(
            'solicitud_acceso_delegado_creada',
            'soporte',
            "Superadmin {$user?->name} solicitó acceso delegado de soporte a {$empresa->name} (Ticket {$ticket->codigo_ticket})",
            [
                'empresa_id' => $empresa->id,
                'datos_despues' => [
                    'codigo_ticket' => $ticket->codigo_ticket,
                    'motivo' => $datos['motivo'],
                ],
            ]
        );

        return back()->with('success', "Solicitud de acceso delegado {$ticket->codigo_ticket} generada para {$empresa->name}. Requiere autorización del cliente.");
    }

    /**
     * Permite al Superadministrador marcar como resuelto / finalizado un ticket de soporte.
     */
    public function finalizar(Request $request, int $id): RedirectResponse
    {
        $ticket = SoporteAutorizacion::findOrFail($id);
        $user = $request->user();
        $resolucion = trim((string) ($request->input('resolucion') ?: 'Atención técnica completada y verificada por Datec Soporte.'));

        $ticket->finalizar($user, $resolucion);

        $this->auditoriaService->registrar(
            'ticket_soporte_finalizado_superadmin',
            'soporte',
            "Superadmin {$user?->name} finalizó el ticket de soporte {$ticket->codigo_ticket} de la empresa {$ticket->empresa?->name}. Resolución: {$resolucion}",
            [
                'empresa_id' => $ticket->empresa_id,
                'datos_despues' => [
                    'codigo_ticket' => $ticket->codigo_ticket,
                    'estado' => $ticket->estado,
                    'finalizado_en' => now()->toIso8601String(),
                ],
            ]
        );

        return back()->with('success', "Ticket {$ticket->codigo_ticket} marcado como Finalizado exitosamente.");
    }
}
