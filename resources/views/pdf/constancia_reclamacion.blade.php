<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Hoja de Reclamación N° {{ $reclamacion->numero_hoja }}</title>
    <style>
        @page {
            margin: 14mm 13mm 14mm 13mm;
            size: a4 portrait;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 10.5px;
            line-height: 1.35;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            border-bottom: 2px solid {{ $colorPrimario ?? '#1e40af' }};
            padding-bottom: 8px;
        }
        .header-logo {
            width: 52%;
            vertical-align: middle;
        }
        .header-title {
            width: 48%;
            text-align: right;
            vertical-align: middle;
        }
        .empresa-nombre {
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 2px;
            line-height: 1.2;
        }
        .empresa-ruc {
            font-size: 10px;
            color: #334155;
            font-weight: 700;
        }
        .empresa-direccion {
            font-size: 8.5px;
            color: #64748b;
            line-height: 1.25;
            margin-top: 1px;
        }
        .doc-title {
            font-size: 15px;
            font-weight: 900;
            color: {{ $colorPrimario ?? '#1e40af' }};
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 2px 0;
        }
        .doc-sub {
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .hoja-badge {
            display: inline-block;
            background-color: #0f172a;
            color: #ffffff;
            padding: 4px 10px;
            border-radius: 4px;
            margin-top: 5px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .fecha-hora-badge {
            font-size: 8.5px;
            color: #475569;
            margin-top: 2px;
            font-weight: 600;
        }
        .section-box {
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            margin-bottom: 8px;
            background-color: #ffffff;
            overflow: hidden;
        }
        .section-header {
            background-color: #f8fafc;
            border-bottom: 1px solid #cbd5e1;
            border-left: 4px solid {{ $colorPrimario ?? '#1e40af' }};
            padding: 5px 8px;
            font-size: 9.5px;
            font-weight: 800;
            text-transform: uppercase;
            color: #0f172a;
            letter-spacing: 0.4px;
        }
        .section-content {
            padding: 6px 8px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table td {
            padding: 2.5px 4px;
            vertical-align: top;
            font-size: 10px;
        }
        .data-label {
            width: 25%;
            font-weight: 700;
            color: #475569;
            font-size: 9.5px;
        }
        .data-value {
            width: 75%;
            color: #0f172a;
        }
        .text-block-label {
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #475569;
            margin-bottom: 2px;
            display: block;
        }
        .text-block-hechos {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 3px solid #94a3b8;
            border-radius: 4px;
            padding: 5px 8px;
            font-size: 9.5px;
            color: #1e293b;
            min-height: 38px;
            line-height: 1.4;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .text-block-pedido {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-left: 3px solid #16a34a;
            border-radius: 4px;
            padding: 5px 8px;
            font-size: 9.5px;
            color: #14532d;
            min-height: 30px;
            line-height: 1.4;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .tipo-tag {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 4px;
            font-weight: 900;
            text-transform: uppercase;
            font-size: 9.5px;
            letter-spacing: 0.3px;
        }
        .tipo-reclamo {
            background-color: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }
        .tipo-queja {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }
        .bien-tag {
            display: inline-block;
            background-color: #e2e8f0;
            color: #0f172a;
            padding: 1px 6px;
            border-radius: 3px;
            font-weight: 800;
            font-size: 9px;
        }
        .token-box {
            display: inline-block;
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 10px;
            font-weight: bold;
            color: #0f172a;
        }
        .footer-legal {
            margin-top: 10px;
            border-top: 1px solid #cbd5e1;
            padding-top: 6px;
            font-size: 8px;
            color: #64748b;
            line-height: 1.35;
        }
        .footer-legal strong {
            color: #334155;
        }
        .logo-img {
            max-height: 44px;
            max-width: 170px;
            margin-bottom: 4px;
        }
        .logo-placeholder {
            display: inline-block;
            background-color: {{ $colorPrimario ?? '#1e40af' }};
            color: #ffffff;
            font-weight: 800;
            font-size: 12px;
            padding: 3px 8px;
            border-radius: 4px;
            margin-bottom: 3px;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

    <!-- Encabezado Oficial -->
    <table class="header-table">
        <tr>
            <td class="header-logo">
                @if(!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" class="logo-img" alt="Logo" /><br>
                @else
                    <div class="logo-placeholder">{{ strtoupper(substr($empresa->name, 0, 18)) }}</div><br>
                @endif
                <div class="empresa-nombre">{{ $empresa->razon_social ?: $empresa->name }}</div>
                <div class="empresa-ruc">RUC: {{ $empresa->ruc }}</div>
                <div class="empresa-direccion">{{ $empresa->direccion ?: 'Domicilio fiscal registrado' }}</div>
            </td>
            <td class="header-title">
                <div class="doc-title">Libro de Reclamaciones</div>
                <div class="doc-sub">Conforme al D.S. N° 011-2011-PCM y Ley N° 29571 (INDECOPI)</div>
                <div class="hoja-badge">HOJA N° {{ $reclamacion->numero_hoja }}</div>
                <div class="fecha-hora-badge">Fecha y Hora: {{ $reclamacion->fecha_hora?->format('d/m/Y H:i:s') ?? now()->format('d/m/Y H:i:s') }}</div>
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
                    <td class="data-value"><strong>{{ $consumidor['nombres'] ?? '' }} {{ $consumidor['apellidos'] ?? '' }}</strong></td>
                </tr>
                <tr>
                    <td class="data-label">Documento de Identidad:</td>
                    <td class="data-value"><strong>{{ strtoupper($consumidor['tipo_documento'] ?? 'DNI') }}:</strong> {{ $consumidor['numero_documento'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="data-label">Domicilio:</td>
                    <td class="data-value">{{ $consumidor['direccion'] ?? 'No especificado' }} 
                        @if(!empty($consumidor['distrito'])) - {{ $consumidor['distrito'] }}, {{ $consumidor['provincia'] ?? '' }} ({{ $consumidor['departamento'] ?? '' }}) @endif
                    </td>
                </tr>
                <tr>
                    <td class="data-label">Teléfono / Celular:</td>
                    <td class="data-value">{{ $consumidor['telefono'] ?? '-' }} &nbsp;&nbsp;&bull;&nbsp;&nbsp; <strong>E-mail:</strong> {{ $consumidor['email'] ?? '-' }}</td>
                </tr>
                @if(!empty($consumidor['es_menor_edad']) && !empty($consumidor['apoderado']))
                <tr>
                    <td class="data-label">Padre / Madre / Apoderado:</td>
                    <td class="data-value"><strong>{{ $consumidor['apoderado']['nombre'] ?? '' }}</strong> (Doc: {{ $consumidor['apoderado']['numero_documento'] ?? '' }})</td>
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
                        <span class="bien-tag">{{ strtoupper($reclamacion->tipo_bien->value ?? 'PRODUCTO') }}</span>
                        @if($reclamacion->monto_reclamado)
                            &nbsp;&nbsp;&bull;&nbsp;&nbsp; <strong>Monto Reclamado:</strong> {{ $reclamacion->moneda }} {{ number_format($reclamacion->monto_reclamado, 2) }}
                        @endif
                        @if($reclamacion->numero_comprobante)
                            &nbsp;&nbsp;&bull;&nbsp;&nbsp; <strong>Comprobante:</strong> {{ $reclamacion->numero_comprobante }}
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="data-label">Descripción:</td>
                    <td class="data-value">{{ $reclamacion->descripcion_bien ?: 'No especificada' }}</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- 3. Detalle de la Reclamación y Pedido del Consumidor -->
    <div class="section-box">
        <div class="section-header">
            3. Detalle de la Disconformidad 
            &nbsp;&bull;&nbsp; 
            <span class="tipo-tag {{ $reclamacion->tipo->value === 'queja' ? 'tipo-queja' : 'tipo-reclamo' }}">
                {{ strtoupper($reclamacion->tipo->value ?? 'RECLAMO') }}
            </span>
            &nbsp;&bull;&nbsp; 
            <span style="font-weight: normal; font-size: 8.5px; color: #475569;">
                {{ $reclamacion->tipo->value === 'queja' ? '(Malestar por atención al público)' : '(Disconformidad sobre producto o servicio)' }}
            </span>
        </div>
        <div class="section-content">
            <span class="text-block-label">Detalle / Hechos Expuestos por el Consumidor:</span>
            <div class="text-block-hechos">
                {{ $reclamacion->detalle_problema }}
            </div>

            <div style="margin-top: 6px;">
                <span class="text-block-label">Pedido Concreto del Consumidor:</span>
                <div class="text-block-pedido">
                    {{ $reclamacion->pedido_consumidor }}
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Acciones Adoptadas por el Proveedor y Plazo Legal / Respuesta Emitida -->
    <div class="section-box">
        <div class="section-header">
            4. Acciones Adoptadas por el Proveedor y Dictamen
            @if(isset($respuesta) && $respuesta)
                &nbsp;&bull;&nbsp;
                <span class="tipo-tag" style="background-color: #059669; color: #ffffff;">
                    DICTAMEN: {{ strtoupper($respuesta->decision?->label() ?? 'ATENDIDO') }}
                </span>
            @endif
        </div>
        <div class="section-content">
            @if(isset($respuesta) && $respuesta)
                <!-- Respuesta Formal Emitida por la Empresa -->
                <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 4px; padding: 7px 9px; margin-bottom: 7px;">
                    <div style="font-size: 9.5px; font-weight: 800; color: #166534; margin-bottom: 3px;">
                        RESPUESTA FORMAL MOTIVADA DEL PROVEEDOR (Versión {{ $respuesta->version }})
                    </div>
                    <div style="font-size: 8.5px; color: #14532d; margin-bottom: 5px;">
                        <strong>Fecha de Emisión:</strong> {{ $respuesta->fecha_respuesta?->format('d/m/Y H:i:s') ?? $respuesta->created_at?->format('d/m/Y H:i:s') }}
                        @if($respuesta->autor_nombre)
                            &nbsp;&bull;&nbsp; <strong>Responsable:</strong> {{ $respuesta->autor_nombre }} {{ $respuesta->autor_cargo ? "({$respuesta->autor_cargo})" : '' }}
                        @endif
                    </div>

                    <span class="text-block-label" style="color: #166534;">Propuesta de Solución / Descargo Oficial:</span>
                    <div class="text-block-hechos" style="background-color: #ffffff; border-color: #86efac; color: #14532d;">
                        {{ $respuesta->propuesta_solucion }}
                    </div>

                    @if($respuesta->fundamento_legal)
                        <div style="margin-top: 5px;">
                            <span class="text-block-label" style="color: #166534;">Fundamentación Legal / Técnica:</span>
                            <div class="text-block-pedido" style="background-color: #ffffff; border-color: #86efac; color: #14532d;">
                                {{ $respuesta->fundamento_legal }}
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <table class="data-table">
                <tr>
                    <td class="data-label">Estado del Expediente:</td>
                    <td class="data-value">
                        <strong style="color: {{ in_array($reclamacion->estado->value, ['atendida', 'cerrada']) ? '#059669' : '#d97706' }};">
                            {{ strtoupper($reclamacion->estado->value ?? 'PENDIENTE') }}
                        </strong>
                        &nbsp;&bull;&nbsp; Plazo Legal: <strong>15 días hábiles</strong> (Ley N° 29571 / Ley N° 31435).
                        &nbsp;&bull;&nbsp; Límite: <strong>{{ $reclamacion->vence_en?->format('d/m/Y') ?? now()->addWeekdays(15)->format('d/m/Y') }}</strong>
                    </td>
                </tr>
                <tr>
                    <td class="data-label">Canal de Notificación:</td>
                    <td class="data-value">La respuesta formal motivada es remitida directamente al correo <strong>{{ $consumidor['email'] ?? '-' }}</strong>.</td>
                </tr>
                <tr>
                    <td class="data-label">Código de Seguimiento:</td>
                    <td class="data-value">
                        <span class="token-box">{{ $reclamacion->token_consulta }}</span>
                        &nbsp;<span style="font-size: 8.5px; color: #64748b;">(Consulta de trazabilidad y respuesta en línea)</span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Pie de página institucional y normativo -->
    <div class="footer-legal">
        <strong>RECLAMO:</strong> Disconformidad relacionada a los bienes expendidos o servicios suministrados.&nbsp;&bull;&nbsp;
        <strong>QUEJA:</strong> Disconformidad no relacionada a los productos o servicios; o malestar respecto a la atención al público.<br>
        * La formulación del presente reclamo o queja no excluye el recurso a otros medios de solución de controversias ni es requisito previo para interponer una denuncia ante el INDECOPI.<br>
        * Documento emitido de manera electrónica con plena validez jurídica según la Ley N° 29571 y D.S. N° 011-2011-PCM. Certificado por plataforma <strong>DatecLibro</strong> para <strong>{{ $empresa->razon_social ?: $empresa->name }}</strong>.
    </div>

</body>
</html>
