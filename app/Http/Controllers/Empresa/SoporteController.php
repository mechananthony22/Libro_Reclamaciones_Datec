<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\SoporteAutorizacion;
use App\Services\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de gestión de tickets de soporte técnico y autorizaciones de acceso delegado.
 * Permite a la empresa autorizar o revocar de manera temporal y controlada el acceso de Datec a sus datos.
 */
class SoporteController extends Controller
{
    public function __construct(
        protected AuditoriaService $auditoriaService
    ) {}

    /**
     * Muestra el panel de autorizaciones y tickets de soporte técnico de la empresa.
     */
    public function index(): Response
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        $tickets = SoporteAutorizacion::where('empresa_id', $empresa->id)
            ->with(['solicitadoPor:id,name,email', 'autorizadoPor:id,name,email'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (SoporteAutorizacion $item) => [
                'id' => $item->id,
                'codigo_ticket' => $item->codigo_ticket,
                'titulo' => $item->titulo,
                'motivo' => $item->motivo,
                'estado' => $item->estado,
                'is_activo' => $item->isActivo(),
                'horas_vigencia' => $item->horas_vigencia,
                'autorizado_en' => $item->autorizado_en?->format('d/m/Y H:i'),
                'valido_hasta' => $item->valido_hasta?->format('d/m/Y H:i'),
                'revocado_en' => $item->revocado_en?->format('d/m/Y H:i'),
                'motivo_revocacion' => $item->motivo_revocacion,
                'solicitado_por' => $item->solicitadoPor?->name ?? 'Datec Soporte',
                'autorizado_por' => $item->autorizadoPor?->name,
                'created_at' => $item->created_at?->format('d/m/Y H:i'),
            ]);

        $autorizacionVigente = $empresa->autorizacionSoporteActiva();

        return Inertia::render('Empresa/Soporte/Index', [
            'tickets' => $tickets,
            'autorizacionVigente' => $autorizacionVigente ? [
                'codigo_ticket' => $autorizacionVigente->codigo_ticket,
                'titulo' => $autorizacionVigente->titulo,
                'valido_hasta' => $autorizacionVigente->valido_hasta?->format('d/m/Y H:i'),
                'horas_restantes' => max(0, round(now()->diffInMinutes($autorizacionVigente->valido_hasta) / 60, 1)),
            ] : null,
        ]);
    }

    /**
     * Registra un nuevo ticket de soporte técnico por parte de la empresa.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        $datos = $request->validate([
            'titulo' => ['required', 'string', 'max:150'],
            'motivo' => ['required', 'string', 'max:1000'],
            'horas_vigencia' => ['nullable', 'integer', 'min:1', 'max:168'],
            'conceder_acceso_inmediato' => ['nullable', 'boolean'],
        ]);

        $horas = (int) ($datos['horas_vigencia'] ?? 24);
        $conceder = (bool) ($datos['conceder_acceso_inmediato'] ?? false);
        $user = $request->user();

        // Generar código correlativo de ticket
        $conteoHoy = SoporteAutorizacion::whereDate('created_at', now()->toDateString())->count() + 1;
        $codigoTicket = 'TCK-'.now()->format('Ymd').'-'.str_pad((string) $conteoHoy, 4, '0', STR_PAD_LEFT);

        $ticket = SoporteAutorizacion::create([
            'empresa_id' => $empresa->id,
            'codigo_ticket' => $codigoTicket,
            'titulo' => $datos['titulo'],
            'motivo' => $datos['motivo'],
            'solicitado_por_id' => $user?->id,
            'estado' => $conceder ? 'autorizado' : 'pendiente',
            'horas_vigencia' => $horas,
            'autorizado_por_id' => $conceder ? $user?->id : null,
            'autorizado_en' => $conceder ? now() : null,
            'valido_hasta' => $conceder ? now()->addHours($horas) : null,
        ]);

        $this->auditoriaService->registrar(
            'ticket_soporte_creado',
            'soporte',
            "Ticket de soporte técnico {$ticket->codigo_ticket} creado para {$empresa->name} (Acceso delegado: ".($conceder ? "Autorizado por {$horas}h" : 'Pendiente').')',
            [
                'empresa_id' => $empresa->id,
                'datos_despues' => [
                    'codigo_ticket' => $ticket->codigo_ticket,
                    'estado' => $ticket->estado,
                    'horas' => $horas,
                ],
            ]
        );

        $mensaje = $conceder
            ? "Ticket {$ticket->codigo_ticket} registrado y acceso delegado concedido por {$horas} horas."
            : "Ticket {$ticket->codigo_ticket} registrado con éxito.";

        return back()->with('success', $mensaje);
    }

    /**
     * Concede o renueva la autorización temporal de acceso delegado al equipo de soporte.
     */
    public function autorizar(Request $request, int $id): RedirectResponse
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        $ticket = SoporteAutorizacion::where('empresa_id', $empresa->id)->findOrFail($id);

        $horas = (int) $request->input('horas_vigencia', $ticket->horas_vigencia ?: 24);
        if ($horas < 1 || $horas > 168) {
            $horas = 24;
        }

        $ticket->autorizar($request->user(), $horas);

        $this->auditoriaService->registrar(
            'acceso_delegado_autorizado',
            'soporte',
            "Autorización de acceso delegado temporal concedida bajo ticket {$ticket->codigo_ticket} por {$horas} horas.",
            [
                'empresa_id' => $empresa->id,
                'datos_despues' => [
                    'codigo_ticket' => $ticket->codigo_ticket,
                    'valido_hasta' => $ticket->valido_hasta?->toIso8601String(),
                ],
            ]
        );

        return back()->with('success', "Acceso delegado para soporte técnico concedido por {$horas} horas.");
    }

    /**
     * Revoca anticipadamente el acceso delegado de soporte técnico.
     */
    public function revocar(Request $request, int $id): RedirectResponse
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::current();

        $ticket = SoporteAutorizacion::where('empresa_id', $empresa->id)->findOrFail($id);
        $motivo = trim((string) ($request->input('motivo_revocacion') ?: $request->input('motivo', 'Acceso revocado por el administrador')));

        $ticket->revocar($motivo);

        $this->auditoriaService->registrar(
            'acceso_delegado_revocado',
            'soporte',
            "Acceso delegado de soporte técnico revocado para ticket {$ticket->codigo_ticket}. Motivo: {$motivo}",
            [
                'empresa_id' => $empresa->id,
                'datos_despues' => [
                    'codigo_ticket' => $ticket->codigo_ticket,
                    'revocado_en' => now()->toIso8601String(),
                ],
            ]
        );

        return back()->with('success', "Acceso delegado revocado con éxito para el ticket {$ticket->codigo_ticket}.");
    }
}
