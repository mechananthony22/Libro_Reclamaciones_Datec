<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Reclamo Recibido</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f1f5f9;
            color: #334155;
            margin: 0;
            padding: 24px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
            border: 1px solid #e2e8f0;
        }
        .header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 28px 32px;
            color: #ffffff;
        }
        .badge-alert {
            display: inline-block;
            background: #ef4444;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 4px 10px;
            border-radius: 20px;
            margin-bottom: 12px;
        }
        .title {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
        }
        .subtitle {
            margin: 6px 0 0;
            font-size: 13px;
            color: #94a3b8;
        }
        .content {
            padding: 28px 32px;
        }
        .alert-box {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 14px 16px;
            border-radius: 6px;
            margin-bottom: 24px;
        }
        .alert-box p {
            margin: 0;
            font-size: 13px;
            line-height: 1.5;
            color: #991b1b;
        }
        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .info-grid td {
            padding: 10px 12px;
            font-size: 13px;
            border-bottom: 1px solid #f1f5f9;
        }
        .info-grid td.label {
            font-weight: 600;
            color: #64748b;
            width: 38%;
            background-color: #f8fafc;
        }
        .info-grid td.value {
            color: #0f172a;
            font-weight: 500;
        }
        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            margin: 20px 0 10px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .box-text {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px;
            font-size: 13px;
            line-height: 1.6;
            color: #334155;
            white-space: pre-wrap;
            margin-bottom: 18px;
        }
        .btn-container {
            text-align: center;
            margin: 32px 0 20px;
        }
        .btn {
            display: inline-block;
            background-color: #2563eb;
            color: #ffffff !important;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            padding: 12px 28px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
        }
        .footer {
            background-color: #f8fafc;
            padding: 20px 32px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 12px;
            color: #64748b;
        }
        .footer p {
            margin: 4px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="badge-alert">⚠️ Notificación para la Empresa</span>
            <h1 class="title">Nuevo Expediente Registrado</h1>
            <p class="subtitle">{{ $empresa->name ?? 'Empresa' }} • RUC: {{ $empresa->ruc ?? '-' }}</p>
        </div>

        <div class="content">
            <div class="alert-box">
                <p><strong>Plazo Legal de Atención:</strong> Conforme a la Ley N° 29571 (modificada por Ley N° 31435), la empresa cuenta con un plazo improrrogable de <strong>15 días hábiles</strong> para atender y notificar la respuesta formal al consumidor.</p>
            </div>

            <table class="info-grid">
                <tr>
                    <td class="label">N° de Hoja:</td>
                    <td class="value" style="font-size: 15px; font-weight: 700; color: #2563eb;">{{ $reclamacion->numero_hoja }}</td>
                </tr>
                <tr>
                    <td class="label">Tipo de Registro:</td>
                    <td class="value">
                        <strong style="color: {{ $reclamacion->tipo->value === 'reclamo' ? '#dc2626' : '#d97706' }};">
                            {{ strtoupper($reclamacion->tipo->label()) }}
                        </strong>
                    </td>
                </tr>
                <tr>
                    <td class="label">Fecha y Hora:</td>
                    <td class="value">{{ ($reclamacion->fecha_hora ?? $reclamacion->created_at)?->timezone('America/Lima')->format('d/m/Y H:i:s') }}</td>
                </tr>
                <tr>
                    <td class="label">Fecha Límite Legal:</td>
                    <td class="value" style="color: #dc2626; font-weight: 700;">
                        {{ $reclamacion->vence_en?->format('d/m/Y') ?? ($reclamacion->fecha_limite_legal?->format('d/m/Y') ?? '15 días hábiles') }}
                    </td>
                </tr>
                <tr>
                    <td class="label">Consumidor:</td>
                    <td class="value">
                        <strong>{{ trim(($consumidor['nombres'] ?? '') . ' ' . ($consumidor['apellidos'] ?? '')) ?: ($consumidor['nombre'] ?? '-') }}</strong>
                    </td>
                </tr>
                <tr>
                    <td class="label">Documento:</td>
                    <td class="value">{{ strtoupper($consumidor['tipo_documento'] ?? 'DOC') }}: {{ $consumidor['numero_documento'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Correo Consumidor:</td>
                    <td class="value">{{ $consumidor['email'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Teléfono:</td>
                    <td class="value">{{ $consumidor['telefono'] ?? '-' }}</td>
                </tr>
                @if($reclamacion->establecimiento)
                <tr>
                    <td class="label">Establecimiento:</td>
                    <td class="value">{{ $reclamacion->establecimiento->nombre }} ({{ $reclamacion->establecimiento->codigo ?? $reclamacion->establecimiento->codigo_establecimiento }})</td>
                </tr>
                @endif
                @if($reclamacion->monto_reclamado)
                <tr>
                    <td class="label">Monto Reclamado:</td>
                    <td class="value">{{ $reclamacion->moneda ?? 'PEN' }} {{ number_format((float) $reclamacion->monto_reclamado, 2) }}</td>
                </tr>
                @endif
            </table>

            <div class="section-title">Detalle / Hechos Expuestos</div>
            <div class="box-text">{{ $reclamacion->detalle_problema ?? ($reclamacion->detalle ?? '-') }}</div>

            @if($reclamacion->pedido_consumidor || $reclamacion->pedido)
            <div class="section-title">Pedido Concreto del Consumidor</div>
            <div class="box-text">{{ $reclamacion->pedido_consumidor ?? $reclamacion->pedido }}</div>
            @endif

            <div class="btn-container">
                <a href="{{ $urlExpediente }}" class="btn">Gestionar y Responder Expediente</a>
            </div>
            
            <p style="font-size: 12px; color: #64748b; text-align: center; margin-top: 15px;">
                Se adjunta en este correo la copia oficial de la <strong>Hoja de Reclamación en formato PDF</strong>.
            </p>
        </div>

        <div class="footer">
            <p>Este es un correo automático del <strong>Sistema de Libro de Reclamaciones - DatecLibro</strong>.</p>
            <p>Enviado a los correos registrados para recepción de alertas de la empresa.</p>
        </div>
    </div>
</body>
</html>

