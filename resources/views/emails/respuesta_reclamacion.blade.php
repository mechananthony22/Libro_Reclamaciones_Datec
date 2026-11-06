<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Respuesta Formal a su Reclamación</title>
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
            background-color: {{ $empresa->color_primario ?: '#2563EB' }};
            color: #ffffff;
            padding: 28px 24px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .header p {
            margin: 6px 0 0 0;
            font-size: 13px;
            opacity: 0.9;
        }
        .content {
            padding: 24px;
            font-size: 14px;
            line-height: 1.6;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            background-color: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }
        .card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            margin: 16px 0;
        }
        .card-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 8px;
        }
        .solution-box {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            padding: 16px;
            margin: 16px 0;
            color: #166534;
        }
        .btn {
            display: inline-block;
            background-color: {{ $empresa->color_primario ?: '#2563EB' }};
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 13px;
            text-align: center;
            margin-top: 10px;
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
            <h1>{{ $empresa->razon_social }}</h1>
            <p>Atención y Respuesta Formal de Reclamación</p>
        </div>

        <div class="content">
            <p>Estimado(a) <strong>{{ $consumidor['nombres'] ?? '' }} {{ $consumidor['apellidos'] ?? '' }}</strong>,</p>

            <p>
                Nos dirigimos a usted en atención a su <strong>{{ $reclamacion->tipo->label() }}</strong> registrado en nuestro Libro de Reclamaciones Virtual bajo la <strong>Hoja N° {{ $reclamacion->numero_hoja }}</strong> el día {{ $reclamacion->fecha_hora?->format('d/m/Y') }}.
            </p>

            <div class="card">
                <div class="card-title">Dictamen y Decisión Formal</div>
                <div style="margin-bottom: 6px;">
                    <span class="badge">{{ $respuesta->decision->label() }}</span>
                </div>
                <div style="font-size: 12px; color: #475569;">
                    <strong>Emitido por:</strong> {{ $respuesta->autor_nombre }} {{ $respuesta->autor_cargo ? '('.$respuesta->autor_cargo.')' : '' }}<br>
                    <strong>Fecha de emisión:</strong> {{ $respuesta->fecha_respuesta?->format('d/m/Y H:i') }}
                </div>
            </div>

            <div class="solution-box">
                <div style="font-weight: 700; font-size: 12px; margin-bottom: 6px; text-transform: uppercase;">
                    Propuesta de Solución y Respuesta Motivada:
                </div>
                <div style="white-space: pre-wrap; font-size: 13px; line-height: 1.6;">{{ $respuesta->propuesta_solucion }}</div>
            </div>

            @if($respuesta->fundamento_legal)
                <div class="card">
                    <div class="card-title">Fundamento Técnico / Legal</div>
                    <div style="font-size: 12px; color: #334155; white-space: pre-wrap;">{{ $respuesta->fundamento_legal }}</div>
                </div>
            @endif

            <p style="font-size: 12px; color: #64748b;">
                Usted puede consultar en todo momento el estado actualizado de su expediente y descargar los documentos asociados a través de nuestro portal de consulta segura:
            </p>

            <div style="text-align: center; margin: 20px 0;">
                <a href="{{ $urlConsultaSegura }}" class="btn" target="_blank">
                    Ver Expediente en Línea
                </a>
            </div>

            <p style="font-size: 11px; color: #94a3b8;">
                Conforme a lo dispuesto por la Ley N° 29571 (Código de Protección y Defensa del Consumidor) y directivas de INDECOPI, esta comunicación constituye la respuesta formal a su reclamación.
            </p>
        </div>

        <div class="footer">
            <p style="margin: 0;">
                {{ $empresa->razon_social }} • RUC {{ $empresa->ruc }}<br>
                {{ $empresa->direccion }}
            </p>
            <p style="margin: 6px 0 0 0;">
                Plataforma tecnológica oficial gestionada a través de DatecLibro.
            </p>
        </div>
    </div>
</body>
</html>

