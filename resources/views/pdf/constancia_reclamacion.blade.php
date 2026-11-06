<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Hoja de Reclamación N° {{ $reclamacion->numero_hoja }}</title>
    <style>
        @page {
            margin: 20mm 15mm 20mm 15mm;
            size: a4 portrait;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .header-logo {
            width: 40%;
            vertical-align: middle;
        }
        .header-title {
            width: 60%;
            text-align: right;
            vertical-align: middle;
        }
        .header-title h1 {
            font-size: 15px;
            color: #0f172a;
            margin: 0 0 3px 0;
            text-transform: uppercase;
        }
        .header-title p {
            font-size: 9px;
            color: #64748b;
            margin: 0;
        }
        .hoja-badge {
            display: inline-block;
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 4px 8px;
            border-radius: 4px;
            margin-top: 5px;
            font-family: monospace;
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
        }
        .section-box {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            margin-bottom: 10px;
            overflow: hidden;
        }
        .section-header {
            background-color: #f8fafc;
            border-bottom: 1px solid #cbd5e1;
            padding: 5px 8px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #334155;
            letter-spacing: 0.5px;
        }
        .section-content {
            padding: 8px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table td {
            padding: 3px 4px;
            vertical-align: top;
            font-size: 10.5px;
        }
        .data-label {
            width: 25%;
            font-weight: bold;
            color: #475569;
        }
        .data-value {
            width: 75%;
            color: #0f172a;
        }
        .text-block {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 3px;
            padding: 6px 8px;
            font-size: 10px;
            color: #1e293b;
            min-height: 38px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .footer-legal {
            margin-top: 15px;
            border-top: 1px solid #cbd5e1;
            padding-top: 8px;
            font-size: 8.5px;
            color: #64748b;
            line-height: 1.3;
        }
        .tipo-tag {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }
        .tipo-reclamo {
            background-color: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        .tipo-queja {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
    </style>
</head>
<body>

    <!-- Encabezado Oficial -->
    <table class="header-table">
        <tr>
            <td class="header-logo">
                <strong style="font-size: 14px; color: #0f172a;">{{ $empresa->razon_social }}</strong><br>
                <span style="font-size: 10px; color: #64748b;">RUC: {{ $empresa->ruc }}</span><br>
                <span style="font-size: 9px; color: #64748b;">{{ $empresa->direccion }}</span>
            </td>
            <td class="header-title">
                <h1>Libro de Reclamaciones</h1>
                <p>Conforme al D.S. N° 011-2011-PCM y Ley N° 29571</p>
                <div class="hoja-badge">HOJA N° {{ $reclamacion->numero_hoja }}</div>
            </td>
        </tr>
    </table>

    <!-- 1. Identificación del Consumidor Reclamante -->
    <div class="section-box">
        <div class="section-header">1. Identificación del Consumidor Reclamante</div>
        <div class="section-content">
            <table class="data-table">
                <tr>
                    <td class="data-label">Nombre / Razón Social:</td>
                    <td class="data-value"><strong>{{ $consumidor['nombres'] }} {{ $consumidor['apellidos'] ?? '' }}</strong></td>
                </tr>
                <tr>
                    <td class="data-label">Documento de Identidad:</td>
                    <td class="data-value">{{ strtoupper($consumidor['tipo_documento'] ?? 'DNI') }}: {{ $consumidor['numero_documento'] ?? '' }}</td>
                </tr>
                <tr>
                    <td class="data-label">Domicilio:</td>
                    <td class="data-value">{{ $consumidor['direccion'] ?? 'No especificado' }} 
                        @if(!empty($consumidor['distrito'])) - {{ $consumidor['distrito'] }}, {{ $consumidor['provincia'] ?? '' }} ({{ $consumidor['departamento'] ?? '' }}) @endif
                    </td>
                </tr>
                <tr>
                    <td class="data-label">Teléfono / Celular:</td>
                    <td class="data-value">{{ $consumidor['telefono'] ?? '-' }} &nbsp;&nbsp;|&nbsp;&nbsp; <strong>E-mail:</strong> {{ $consumidor['email'] ?? '-' }}</td>
                </tr>
                @if(!empty($consumidor['es_menor_edad']) && !empty($consumidor['apoderado']))
                <tr>
                    <td class="data-label">Padre / Madre / Apoderado:</td>
                    <td class="data-value">{{ $consumidor['apoderado']['nombre'] ?? '' }} (Doc: {{ $consumidor['apoderado']['numero_documento'] ?? '' }})</td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    <!-- 2. Identificación del Bien Contratado -->
    <div class="section-box">
        <div class="section-header">2. Identificación del Bien Contratado</div>
        <div class="section-content">
            <table class="data-table">
                <tr>
                    <td class="data-label">Tipo de Bien:</td>
                    <td class="data-value">
                        <strong>{{ strtoupper($reclamacion->tipo_bien->value ?? 'PRODUCTO') }}</strong>
                        @if($reclamacion->monto_reclamado)
                            &nbsp;&nbsp;|&nbsp;&nbsp; <strong>Monto Reclamado:</strong> {{ $reclamacion->moneda }} {{ number_format($reclamacion->monto_reclamado, 2) }}
                        @endif
                        @if($reclamacion->numero_comprobante)
                            &nbsp;&nbsp;|&nbsp;&nbsp; <strong>Comprobante:</strong> {{ $reclamacion->numero_comprobante }}
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="data-label">Descripción:</td>
                    <td class="data-value">{{ $reclamacion->descripcion_bien }}</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- 3. Detalle de la Reclamación y Pedido del Consumidor -->
    <div class="section-box">
        <div class="section-header">
            3. Detalle de la Disconformidad 
            &nbsp;•&nbsp; 
            <span class="tipo-tag {{ $reclamacion->tipo->value === 'queja' ? 'tipo-queja' : 'tipo-reclamo' }}">
                {{ strtoupper($reclamacion->tipo->value ?? 'RECLAMO') }}
            </span>
            &nbsp;•&nbsp; Fecha y Hora: {{ $reclamacion->fecha_hora?->format('d/m/Y H:i:s') ?? now()->format('d/m/Y H:i:s') }}
        </div>
        <div class="section-content">
            <strong style="font-size: 10px; color: #475569;">Detalle / Hechos Expuestos:</strong>
            <div class="text-block" style="margin-top: 3px; margin-bottom: 8px;">
                {{ $reclamacion->detalle_problema }}
            </div>

            <strong style="font-size: 10px; color: #475569;">Pedido Concreto del Consumidor:</strong>
            <div class="text-block" style="margin-top: 3px;">
                {{ $reclamacion->pedido_consumidor }}
            </div>
        </div>
    </div>

    <!-- 4. Acciones Adoptadas por el Proveedor (Información Legal) -->
    <div class="section-box">
        <div class="section-header">4. Observaciones y Plazo Legal de Atención</div>
        <div class="section-content">
            <table class="data-table">
                <tr>
                    <td class="data-label">Plazo Máximo de Respuesta:</td>
                    <td class="data-value">
                        <strong>15 días hábiles</strong> improrrogables conforme a la Ley N° 29571 (Modificada por Ley N° 31435).
                        Fecha estimada límite: <strong>{{ $reclamacion->vence_en?->format('d/m/Y') ?? now()->addWeekdays(15)->format('d/m/Y') }}</strong>
                    </td>
                </tr>
                <tr>
                    <td class="data-label">Canal de Notificación:</td>
                    <td class="data-value">La respuesta formal será enviada al correo electrónico <strong>{{ $consumidor['email'] ?? '' }}</strong>.</td>
                </tr>
                <tr>
                    <td class="data-label">Código de Seguimiento:</td>
                    <td class="data-value"><span style="font-family: monospace; font-size: 11px;">{{ $reclamacion->token_consulta }}</span></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Pie de página institucional y normativo -->
    <div class="footer-legal">
        <strong>RECLAMO:</strong> Disconformidad relacionada a los bienes expendidos o servicios suministrados.<br>
        <strong>QUEJA:</strong> Disconformidad no relacionada a los productos o servicios; o malestar respecto a la atención al público.<br>
        * La formulación del reclamo no excluye el recurso a otros medios de solución de controversias ni es requisito previo para interponer una denuncia ante INDECOPI.<br>
        * Documento emitido de manera electrónica con validez legal según la Ley N° 29571 y D.S. N° 011-2011-PCM. Plataforma DatecLibro.
    </div>

</body>
</html>

