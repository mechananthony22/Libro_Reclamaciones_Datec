<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Alerta de Vencimiento de Reclamación</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .header {
            background-color: {{ $esVencido ? '#dc2626' : '#d97706' }};
            color: #ffffff;
            padding: 24px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
        }
        .header p {
            margin: 6px 0 0 0;
            font-size: 13px;
            opacity: 0.95;
        }
        .content {
            padding: 24px;
            font-size: 14px;
            line-height: 1.6;
        }
        .alert-box {
            background-color: {{ $esVencido ? '#fef2f2' : '#fffbeb' }};
            border: 1px solid {{ $esVencido ? '#fecaca' : '#fde68a' }};
            border-radius: 12px;
            padding: 16px;
            margin: 16px 0;
            color: {{ $esVencido ? '#991b1b' : '#92400e' }};
        }
        .card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            margin: 16px 0;
            font-size: 13px;
        }
        .btn {
            display: inline-block;
            background-color: #2563eb;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 13px;
            text-align: center;
        }
        .footer {
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 16px 24px;
            font-size: 11px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $esVencido ? '⚠️ EXPEDIENTE VENCIDO' : '⏳ ALERTA DE PLAZO LEGAL' }}</h1>
            <p>{{ $empresa->name }} • Libro de Reclamaciones</p>
        </div>

        <div class="content">
            <p>Estimado(a) equipo de atención / responsable,</p>

            <div class="alert-box">
                <div style="font-weight: 800; font-size: 14px; margin-bottom: 4px;">
                    {{ $semaforo['etiqueta'] ?? 'Plazo próximo a vencer' }}
                </div>
                <div style="font-size: 12px;">
                    Conforme al D.S. N° 011-2011-PCM y la Ley N° 29571, el proveedor debe emitir y notificar respuesta formal en un plazo perentorio de 15 días hábiles.
                </div>
            </div>

            <div class="card">
                <div><strong>Hoja de Reclamación:</strong> {{ $reclamacion->numero_hoja }}</div>
                <div><strong>Tipo:</strong> {{ $reclamacion->tipo->label() }}</div>
                <div><strong>Fecha de Presentación:</strong> {{ $reclamacion->fecha_hora?->format('d/m/Y H:i') }}</div>
                <div><strong>Fecha Límite Legal:</strong> {{ $reclamacion->vence_en?->format('d/m/Y') }}</div>
                <div><strong>Consumidor:</strong> {{ $reclamacion->datos_consumidor['nombres'] ?? '' }} {{ $reclamacion->datos_consumidor['apellidos'] ?? '' }}</div>
                <div><strong>Estado Actual:</strong> {{ $reclamacion->estado->label() }}</div>
            </div>

            <p style="font-size: 13px; color: #475569;">
                Por favor ingrese al sistema a la brevedad para evaluar el expediente y emitir la respuesta formal correspondiente.
            </p>

            <div style="text-align: center; margin: 24px 0;">
                <a href="{{ $urlExpediente }}" class="btn" target="_blank">
                    Abrir Ficha de Expediente
                </a>
            </div>
        </div>

        <div class="footer">
            DatecLibro • Plataforma Automatizada de Alertas y Vencimientos Legales INDECOPI
        </div>
    </div>
</body>
</html>

