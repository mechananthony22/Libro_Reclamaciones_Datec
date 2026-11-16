<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de Acceso Delegado de Soporte</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 24px; color: #1e293b;">
    <table align="center" width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; background-color: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <!-- Header -->
        <tr>
            <td style="background-color: #2563eb; padding: 20px 24px; text-align: left; color: #ffffff;">
                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 800; opacity: 0.9;">
                    Soporte Técnico Especializado · Datec
                </div>
                <h1 style="margin: 6px 0 0; font-size: 18px; font-weight: 800; line-height: 1.3;">
                    Solicitud de Acceso Delegado Temporal
                </h1>
            </td>
        </tr>

        <!-- Contenido -->
        <tr>
            <td style="padding: 24px;">
                <p style="font-size: 14px; line-height: 1.6; margin-top: 0; color: #334155;">
                    Estimado equipo de <strong>{{ $empresa->name }}</strong>,
                </p>
                <p style="font-size: 14px; line-height: 1.6; color: #334155;">
                    El equipo técnico de Datec ha generado una solicitud de acceso temporal para asistirles en la plataforma:
                </p>

                <!-- Detalle del Ticket -->
                <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; border-radius: 8px; margin: 20px 0; border: 1px solid #cbd5e1;">
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 12px; font-weight: 600; color: #475569; width: 35%;">Código Ticket:</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 13px; font-weight: 800; color: #2563eb; font-family: monospace;">
                            {{ $ticket->codigo_ticket }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 12px; font-weight: 600; color: #475569;">Título:</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 13px; font-weight: 700; color: #0f172a;">
                            {{ $ticket->titulo }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 12px; font-weight: 600; color: #475569;">Solicitado por:</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 12px; color: #334155;">
                            {{ $solicitante?->name ?? 'Ingeniero de Soporte Datec' }} ({{ $solicitante?->email ?? 'soporte@datec.pe' }})
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 16px; font-size: 12px; font-weight: 600; color: #475569;">Vigencia solicitada:</td>
                        <td style="padding: 12px 16px; font-size: 12px; color: #334155;">
                            Hasta {{ $ticket->horas_vigencia }} horas
                        </td>
                    </tr>
                </table>

                <div style="background-color: #eff6ff; border-left: 4px solid #3b82f6; padding: 12px 16px; border-radius: 4px; font-size: 13px; color: #1e40af; margin-bottom: 24px; line-height: 1.5;">
                    <strong>Motivo del Soporte:</strong><br>
                    {{ $ticket->motivo }}
                </div>

                <p style="font-size: 13px; color: #64748b; line-height: 1.5;">
                    Por seguridad y cumplimiento estricto del D.S. N° 011-2011-PCM y la Ley de Protección de Datos Personales, el personal de Datec no podrá acceder a sus expedientes sin la previa autorización explícita de su empresa.
                </p>

                <div style="text-align: center; margin-top: 24px;">
                    <a href="{{ $urlSoporte }}" style="display: inline-block; background-color: #2563eb; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-size: 13px; font-weight: 700; letter-spacing: 0.02em;">
                        Revisar y Autorizar / Rechazar Acceso
                    </a>
                </div>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="background-color: #f8fafc; padding: 16px 24px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8;">
                Plataforma del Libro de Reclamaciones Virtual · Desarrollado por Datec Soluciones Tecnológicas SAC
            </td>
        </tr>
    </table>
</body>
</html>

