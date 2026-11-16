<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerta Operativa Datec</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 24px; color: #1e293b;">
    <table align="center" width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; background-color: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <!-- Header -->
        <tr>
            <td style="background-color: {{ $alerta->isCritica() ? '#dc2626' : '#d97706' }}; padding: 20px 24px; text-align: left; color: #ffffff;">
                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 800; opacity: 0.9;">
                    Monitoreo de Infraestructura & Operaciones · Datec
                </div>
                <h1 style="margin: 6px 0 0; font-size: 18px; font-weight: 800; line-height: 1.3;">
                    {{ $alerta->titulo }}
                </h1>
            </td>
        </tr>

        <!-- Contenido -->
        <tr>
            <td style="padding: 24px;">
                <p style="font-size: 14px; line-height: 1.6; margin-top: 0; color: #334155;">
                    El motor de monitoreo de la plataforma <strong>Libro de Reclamaciones Datec</strong> ha registrado una alerta operativa automática que requiere atención técnica:
                </p>

                <!-- Resumen de Métricas -->
                <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f1f5f9; border-radius: 8px; margin: 20px 0; border: 1px solid #cbd5e1;">
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 12px; font-weight: 600; color: #475569; width: 40%;">Severidad:</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 12px; font-weight: 800; color: {{ $alerta->isCritica() ? '#dc2626' : '#d97706' }}; text-transform: uppercase;">
                            {{ $alerta->severidad }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 12px; font-weight: 600; color: #475569;">Tipo de Alerta:</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 12px; font-weight: 700; color: #0f172a;">
                            {{ $alerta->tipo }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 12px; font-weight: 600; color: #475569;">Valor Detectado:</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 13px; font-family: monospace; font-weight: bold; color: #b91c1c;">
                            {{ $alerta->valor_actual }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 12px; font-weight: 600; color: #475569;">Umbral Límite:</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 13px; font-family: monospace; color: #334155;">
                            {{ $alerta->umbral_definido }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 16px; font-size: 12px; font-weight: 600; color: #475569;">Detectado en:</td>
                        <td style="padding: 12px 16px; font-size: 12px; color: #334155;">
                            {{ $fechaHora }}
                        </td>
                    </tr>
                </table>

                <div style="background-color: #fffbeb; border-left: 4px solid #f59e0b; padding: 12px 16px; border-radius: 4px; font-size: 13px; color: #78350f; margin-bottom: 24px; line-height: 1.5;">
                    <strong>Detalle del Diagnóstico:</strong><br>
                    {{ $alerta->descripcion }}
                </div>

                <div style="text-align: center; margin-top: 24px;">
                    <a href="{{ $urlDashboard }}" style="display: inline-block; background-color: #0f172a; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-size: 13px; font-weight: 700; letter-spacing: 0.02em;">
                        Abrir Panel de Control Datec
                    </a>
                </div>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="background-color: #f8fafc; padding: 16px 24px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8;">
                Sistema de Monitoreo Automatizado · Datec Soluciones Tecnológicas SAC<br>
                Este correo fue generado automáticamente en base a las métricas del sistema.
            </td>
        </tr>
    </table>
</body>
</html>

