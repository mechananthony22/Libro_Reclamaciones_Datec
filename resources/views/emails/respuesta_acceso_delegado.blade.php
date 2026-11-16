<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respuesta a Solicitud de Acceso Delegado</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 24px; color: #1e293b;">
    <table align="center" width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; background-color: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <!-- Header -->
        <tr>
            <td style="background-color: {{ $accion === 'autorizado' ? '#059669' : '#dc2626' }}; padding: 20px 24px; text-align: left; color: #ffffff;">
                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 800; opacity: 0.9;">
                    Soporte Técnico Datec · Acceso Delegado
                </div>
                <h1 style="margin: 6px 0 0; font-size: 18px; font-weight: 800; line-height: 1.3;">
                    Solicitud {{ ucfirst($accion) }} por {{ $empresa->name }}
                </h1>
            </td>
        </tr>

        <!-- Contenido -->
        <tr>
            <td style="padding: 24px;">
                <p style="font-size: 14px; line-height: 1.6; margin-top: 0; color: #334155;">
                    El cliente <strong>{{ $empresa->name }}</strong> ha respondido a la solicitud de acceso técnico:
                </p>

                <!-- Detalle -->
                <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; border-radius: 8px; margin: 20px 0; border: 1px solid #cbd5e1;">
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 12px; font-weight: 600; color: #475569; width: 35%;">Código Ticket:</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 13px; font-weight: 800; color: #0f172a; font-family: monospace;">
                            {{ $ticket->codigo_ticket }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 12px; font-weight: 600; color: #475569;">Estado Actual:</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 12px; font-weight: 800; color: {{ $accion === 'autorizado' ? '#059669' : '#dc2626' }}; text-transform: uppercase;">
                            {{ $ticket->estado }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 12px; font-weight: 600; color: #475569;">Gestionado por:</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 12px; color: #334155;">
                            {{ $actor->name }} ({{ $actor->email }})
                        </td>
                    </tr>
                    @if($accion === 'autorizado')
                    <tr>
                        <td style="padding: 12px 16px; font-size: 12px; font-weight: 600; color: #475569;">Válido Hasta:</td>
                        <td style="padding: 12px 16px; font-size: 12px; font-weight: bold; color: #059669;">
                            {{ $ticket->valido_hasta?->format('d/m/Y H:i:s') }} ({{ $ticket->horas_vigencia }}h)
                        </td>
                    </tr>
                    @else
                    <tr>
                        <td style="padding: 12px 16px; font-size: 12px; font-weight: 600; color: #475569;">Motivo Rechazo:</td>
                        <td style="padding: 12px 16px; font-size: 12px; color: #b91c1c;">
                            {{ $ticket->motivo_revocacion ?? 'Rechazado por el cliente.' }}
                        </td>
                    </tr>
                    @endif
                </table>

                <div style="text-align: center; margin-top: 24px;">
                    <a href="{{ $urlSoporte }}" style="display: inline-block; background-color: #0f172a; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-size: 13px; font-weight: 700; letter-spacing: 0.02em;">
                        Abrir Panel de Soporte Técnico
                    </a>
                </div>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="background-color: #f8fafc; padding: 16px 24px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8;">
                Plataforma del Libro de Reclamaciones Virtual · Notificación de Auditoría Datec
            </td>
        </tr>
    </table>
</body>
</html>

