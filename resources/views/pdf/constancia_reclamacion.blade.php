<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Hoja de Reclamación N° {{ $reclamacion->numero_hoja }}</title>
    <style>
        @page {
            margin: 11mm 12mm 11mm 12mm;
            size: a4 portrait;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 9.8px;
            line-height: 1.38;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        /* Barra superior roja estilo PDF Oficial */
        .top-red-bar {
            height: 5px;
            background-color: #dc2626;
            border-radius: 3px;
            margin-bottom: 10px;
        }

        /* Cabecera flexible de 2 columnas */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .header-left {
            width: 56%;
            vertical-align: middle;
            padding-right: 12px;
        }
        .header-right {
            width: 44%;
            vertical-align: middle;
        }
        .logo-img {
            max-height: 42px;
            max-width: 165px;
            margin-bottom: 4px;
        }
        .logo-badge {
            display: inline-block;
            background-color: #dc2626;
            color: #ffffff;
            font-weight: 900;
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 5px;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .empresa-nombre {
            font-size: 13.5px;
            font-weight: 900;
            color: #0f172a;
            line-height: 1.2;
            margin-bottom: 3px;
        }
        .empresa-ruc {
            display: inline-block;
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            font-weight: 800;
            font-size: 8.5px;
            padding: 1.5px 7px;
            border-radius: 4px;
            margin-bottom: 3px;
        }
        .empresa-dir {
            font-size: 8.5px;
            color: #64748b;
            line-height: 1.3;
        }

        /* Recuadro Oficial Rojo de Hoja de Reclamación */
        .official-stamp {
            background-color: #fef2f2;
            border: 1.5px solid #dc2626;
            border-radius: 8px;
            padding: 8px 12px;
            text-align: center;
        }
        .stamp-norma {
            font-size: 7.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #991b1b;
            margin-bottom: 2px;
        }
        .stamp-title {
            font-size: 12.5px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #b91c1c;
            margin-bottom: 5px;
        }
        .stamp-number {
            display: inline-block;
            background-color: #dc2626;
            color: #ffffff;
            font-family: 'Courier New', Courier, monospace;
            font-size: 13px;
            font-weight: 900;
            padding: 3px 12px;
            border-radius: 5px;
            letter-spacing: 0.8px;
        }
        .stamp-date {
            font-size: 8px;
            color: #7f1d1d;
            margin-top: 4px;
            font-weight: 600;
        }

        /* Franja de Resumen Flexible */
        .summary-banner {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff1f2;
            border: 1px solid #fecdd3;
            border-left: 4px solid #dc2626;
            border-radius: 6px;
            margin-bottom: 10px;
        }
        .summary-banner td {
            width: 25%;
            padding: 6px 9px;
            vertical-align: middle;
            border-right: 1px dashed #fda4af;
        }
        .summary-banner td:last-child {
            border-right: none;
        }
        .sum-label {
            font-size: 7px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #9f1239;
            display: block;
            margin-bottom: 2px;
        }
        .sum-val {
            font-size: 9px;
            font-weight: 800;
            color: #1e293b;
        }

        /* Secciones Flexibles y Abiertas */
        .section-block {
            margin-bottom: 9px;
        }
        .section-head-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1.5px solid #dc2626;
            padding-bottom: 3px;
            margin-bottom: 5px;
        }
        .section-head-table td {
            padding: 0 0 3px 0;
            vertical-align: middle;
        }
        .sec-pill {
            display: inline-block;
            background-color: #dc2626;
            color: #ffffff;
            font-size: 8px;
            font-weight: 900;
            padding: 1.5px 6px;
            border-radius: 4px;
            margin-right: 5px;
        }
        .sec-title {
            font-size: 9.5px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #991b1b;
        }

        /* Tabla de Datos Flexible (sin encajonamiento rígido) */
        .flex-table {
            width: 100%;
            border-collapse: collapse;
        }
        .flex-table td {
            padding: 4px 6px;
            border-bottom: 1px dashed #e2e8f0;
            vertical-align: top;
        }
        .flex-table tr:last-child td {
            border-bottom: none;
        }
        .f-label {
            font-size: 7.5px;
            font-weight: 800;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.3px;
            display: block;
            margin-bottom: 1px;
        }
        .f-value {
            font-size: 9.5px;
            color: #0f172a;
            font-weight: 600;
            word-wrap: break-word;
        }

        /* Etiquetas / Badges */
        .pill {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 4px;
            font-weight: 900;
            text-transform: uppercase;
            font-size: 8px;
            letter-spacing: 0.3px;
        }
        .pill-reclamo {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }
        .pill-queja {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }
        .pill-bien {
            background-color: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .pill-ok {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        .pill-wait {
            background-color: #ffedd5;
            color: #c2410c;
            border: 1px solid #fdba74;
        }

        /* Cajas Narrativas Flexibles (se adaptan al texto) */
        .narrative-wrap {
            margin-top: 3px;
            margin-bottom: 5px;
        }
        .narrative-title {
            font-size: 8px;
            font-weight: 800;
            text-transform: uppercase;
            color: #991b1b;
            letter-spacing: 0.3px;
            margin-bottom: 2px;
            display: block;
        }
        .narrative-content {
            background-color: #fafafa;
            border: 1px solid #e2e8f0;
            border-left: 3.5px solid #dc2626;
            border-radius: 5px;
            padding: 6px 9px;
            font-size: 9.2px;
            color: #1e293b;
            line-height: 1.42;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .narrative-content-pedido {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-left: 3.5px solid #b91c1c;
            border-radius: 5px;
            padding: 6px 9px;
            font-size: 9.2px;
            color: #7f1d1d;
            line-height: 1.42;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        /* Respuesta del proveedor */
        .provider-box {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-left: 4px solid #16a34a;
            border-radius: 6px;
            padding: 7px 10px;
            margin-bottom: 6px;
        }
        .provider-title {
            font-size: 8.5px;
            font-weight: 900;
            color: #14532d;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .provider-meta {
            font-size: 8px;
            color: #166534;
            margin-bottom: 4px;
        }
        .provider-text {
            background-color: #ffffff;
            border: 1px solid #dcfce7;
            border-radius: 4px;
            padding: 5px 8px;
            font-size: 9px;
            color: #14532d;
            line-height: 1.38;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .token-code {
            display: inline-block;
            background-color: #b91c1c;
            color: #ffffff;
            font-family: 'Courier New', Courier, monospace;
            font-size: 9px;
            font-weight: 900;
            padding: 2px 7px;
            border-radius: 4px;
            letter-spacing: 0.4px;
        }

        /* Pie de página legal */
        .footer-legal {
            margin-top: 8px;
            border-top: 1.5px solid #fecaca;
            background-color: #fef2f2;
            border-radius: 5px;
            padding: 6px 9px;
            font-size: 7.6px;
            color: #64748b;
            line-height: 1.35;
        }
        .footer-legal strong {
            color: #991b1b;
        }
    </style>
</head>
<body>

    <div class="top-red-bar"></div>

    <!-- Cabecera Principal -->
    <table class="header-table">
        <tr>
            <td class="header-left">
                @if(!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" class="logo-img" alt="Logo" /><br>
                @else
                    <div class="logo-badge">{{ strtoupper(substr($empresa->name, 0, 20)) }}</div><br>
                @endif
                <div class="empresa-nombre">{{ $empresa->razon_social ?: $empresa->name }}</div>
                <div class="empresa-ruc">RUC: {{ $empresa->ruc }}</div>
                <div class="empresa-dir">{{ $empresa->direccion ?: 'Domicilio fiscal registrado en plataforma' }}</div>
            </td>
            <td class="header-right">
                <div class="official-stamp">
                    <div class="stamp-norma">Ley N° 29571 &bull; D.S. N° 011-2011-PCM (INDECOPI)</div>
                    <div class="stamp-title">Libro de Reclamaciones</div>
                    <div class="stamp-number">HOJA N° {{ $reclamacion->numero_hoja }}</div>
                    <div class="stamp-date">
                        Fecha y Hora: {{ $reclamacion->fecha_hora?->format('d/m/Y H:i:s') ?? now()->format('d/m/Y H:i:s') }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Franja de Resumen Flexible -->
    <table class="summary-banner">
        <tr>
            <td>
                <span class="sum-label">Tipo de Registro</span>
                <span class="pill {{ $reclamacion->tipo->value === 'queja' ? 'pill-queja' : 'pill-reclamo' }}">
                    {{ strtoupper($reclamacion->tipo->value ?? 'RECLAMO') }}
                </span>
            </td>
            <td>
                <span class="sum-label">Estado Actual</span>
                <span class="pill {{ in_array($reclamacion->estado->value, ['atendida', 'cerrada']) ? 'pill-ok' : 'pill-wait' }}">
                    {{ strtoupper(str_replace('_', ' ', $reclamacion->estado->value ?? 'PENDIENTE')) }}
                </span>
            </td>
            <td>
                <span class="sum-label">Plazo Legal Máximo</span>
                <span class="sum-val">{{ $reclamacion->vence_en?->format('d/m/Y') ?? '15 días hábiles' }}</span>
                <span style="font-size: 7.5px; color: #64748b;">(15 días hábiles)</span>
            </td>
            <td>
                <span class="sum-label">Código de Seguimiento</span>
                <span class="token-code">{{ $reclamacion->token_consulta }}</span>
            </td>
        </tr>
    </table>

    <!-- 1. Identificación del Consumidor Reclamante -->
    <div class="section-block">
        <table class="section-head-table">
            <tr>
                <td>
                    <span class="sec-pill">01</span>
                    <span class="sec-title">Identificación del Consumidor Reclamante</span>
                </td>
                <td style="text-align: right; font-size: 7.5px; color: #64748b;">
                    Datos consignados por el consumidor
                </td>
            </tr>
        </table>

        <table class="flex-table">
            <tr>
                <td style="width: 60%;">
                    <span class="f-label">Nombre Completo / Razón Social</span>
                    <span class="f-value">{{ $consumidor['nombres'] ?? '' }} {{ $consumidor['apellidos'] ?? '' }}</span>
                </td>
                <td style="width: 40%;">
                    <span class="f-label">Documento de Identidad</span>
                    <span class="f-value">{{ strtoupper($consumidor['tipo_documento'] ?? 'DNI') }}: {{ $consumidor['numero_documento'] ?? '-' }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="f-label">Correo Electrónico</span>
                    <span class="f-value">{{ $consumidor['email'] ?? '-' }}</span>
                </td>
                <td>
                    <span class="f-label">Teléfono / Celular</span>
                    <span class="f-value">{{ $consumidor['telefono'] ?? '-' }}</span>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <span class="f-label">Domicilio Declarado</span>
                    <span class="f-value" style="font-weight: 500;">
                        {{ $consumidor['direccion'] ?? 'No especificado' }}
                        @if(!empty($consumidor['distrito']))
                            &mdash; {{ $consumidor['distrito'] }}, {{ $consumidor['provincia'] ?? '' }} ({{ $consumidor['departamento'] ?? '' }})
                        @endif
                    </span>
                </td>
            </tr>
            @if(!empty($consumidor['es_menor_edad']) && !empty($consumidor['apoderado']))
            <tr>
                <td colspan="2" style="background-color: #fffbeb; border-radius: 4px;">
                    <span class="f-label" style="color: #92400e;">Padre, Madre o Apoderado Legal (Menor de Edad)</span>
                    <span class="f-value">{{ $consumidor['apoderado']['nombre'] ?? '' }} &mdash; Doc: {{ $consumidor['apoderado']['numero_documento'] ?? '' }}</span>
                </td>
            </tr>
            @endif
        </table>
    </div>

    <!-- 2. Identificación del Bien Contratado -->
    <div class="section-block">
        <table class="section-head-table">
            <tr>
                <td>
                    <span class="sec-pill">02</span>
                    <span class="sec-title">Identificación del Bien Contratado</span>
                </td>
                <td style="text-align: right;">
                    <span class="pill pill-bien">{{ strtoupper($reclamacion->tipo_bien->value ?? 'PRODUCTO') }}</span>
                </td>
            </tr>
        </table>

        <table class="flex-table">
            <tr>
                <td style="width: 34%;">
                    <span class="f-label">Tipo de Bien</span>
                    <span class="f-value">{{ strtoupper($reclamacion->tipo_bien->value ?? 'PRODUCTO') }}</span>
                </td>
                <td style="width: 33%;">
                    <span class="f-label">Monto Reclamado</span>
                    <span class="f-value" style="color: #b91c1c;">
                        @if($reclamacion->monto_reclamado)
                            {{ $reclamacion->moneda }} {{ number_format($reclamacion->monto_reclamado, 2) }}
                        @else
                            No especificado
                        @endif
                    </span>
                </td>
                <td style="width: 33%;">
                    <span class="f-label">N° Comprobante / Referencia</span>
                    <span class="f-value">{{ $reclamacion->numero_comprobante ?: 'Sin comprobante' }}</span>
                </td>
            </tr>
            <tr>
                <td colspan="3">
                    <span class="f-label">Descripción del Producto o Servicio</span>
                    <span class="f-value" style="font-weight: 500;">{{ $reclamacion->descripcion_bien ?: 'No especificada' }}</span>
                </td>
            </tr>
        </table>
    </div>

    <!-- 3. Detalle de la Disconformidad y Pedido del Consumidor -->
    <div class="section-block">
        <table class="section-head-table">
            <tr>
                <td>
                    <span class="sec-pill">03</span>
                    <span class="sec-title">Detalle de la Disconformidad y Pedido del Consumidor</span>
                </td>
                <td style="text-align: right;">
                    <span class="pill {{ $reclamacion->tipo->value === 'queja' ? 'pill-queja' : 'pill-reclamo' }}">
                        {{ strtoupper($reclamacion->tipo->value ?? 'RECLAMO') }}
                    </span>
                    <span style="font-size: 7.5px; color: #64748b; margin-left: 3px;">
                        {{ $reclamacion->tipo->value === 'queja' ? '(Malestar en la atención)' : '(Disconformidad con el producto/servicio)' }}
                    </span>
                </td>
            </tr>
        </table>

        <div class="narrative-wrap">
            <span class="narrative-title">Detalle / Hechos Expuestos por el Consumidor:</span>
            <div class="narrative-content">{{ $reclamacion->detalle_problema }}</div>
        </div>

        <div class="narrative-wrap">
            <span class="narrative-title">Pedido Concreto Solicitado por el Consumidor:</span>
            <div class="narrative-content-pedido">{{ $reclamacion->pedido_consumidor }}</div>
        </div>
    </div>

    <!-- 4. Acciones Adoptadas por el Proveedor y Trazabilidad -->
    <div class="section-block">
        <table class="section-head-table">
            <tr>
                <td>
                    <span class="sec-pill">04</span>
                    <span class="sec-title">Acciones Adoptadas por el Proveedor y Dictamen</span>
                </td>
                <td style="text-align: right;">
                    @if(isset($respuesta) && $respuesta)
                        <span class="pill pill-ok">
                            DICTAMEN: {{ strtoupper($respuesta->decision?->label() ?? 'ATENDIDO') }}
                        </span>
                    @else
                        <span style="font-size: 7.5px; color: #991b1b; font-weight: 700;">Plazo legal: 15 días hábiles</span>
                    @endif
                </td>
            </tr>
        </table>

        @if(isset($respuesta) && $respuesta)
            <div class="provider-box">
                <div class="provider-title">
                    Respuesta Formal Motivada del Proveedor (Versión {{ $respuesta->version }})
                </div>
                <div class="provider-meta">
                    <strong>Fecha de Emisión:</strong> {{ $respuesta->fecha_respuesta?->format('d/m/Y H:i:s') ?? $respuesta->created_at?->format('d/m/Y H:i:s') }}
                    @if($respuesta->autor_nombre)
                        &nbsp;&bull;&nbsp; <strong>Responsable:</strong> {{ $respuesta->autor_nombre }} {{ $respuesta->autor_cargo ? "({$respuesta->autor_cargo})" : '' }}
                    @endif
                </div>

                <span class="narrative-title" style="color: #14532d;">Propuesta de Solución / Descargo Oficial:</span>
                <div class="provider-text">{{ $respuesta->propuesta_solucion }}</div>

                @if($respuesta->fundamento_legal)
                    <div style="margin-top: 4px;">
                        <span class="narrative-title" style="color: #14532d;">Fundamentación Legal / Técnica:</span>
                        <div class="provider-text">{{ $respuesta->fundamento_legal }}</div>
                    </div>
                @endif
            </div>
        @endif

        <table class="flex-table">
            <tr>
                <td style="width: 52%;">
                    <span class="f-label">Canal Oficial de Notificación</span>
                    <span class="f-value" style="font-weight: 500; font-size: 8.8px;">
                        Respuesta remitida al correo: <strong>{{ $consumidor['email'] ?? '-' }}</strong>
                    </span>
                </td>
                <td style="width: 48%;">
                    <span class="f-label">Cómputo Normativo (Ley N° 31435)</span>
                    <span class="f-value" style="font-weight: 500; font-size: 8.8px;">
                        <strong>15 días hábiles</strong> improrrogables &bull; Límite: <strong>{{ $reclamacion->vence_en?->format('d/m/Y') ?? '15 días hábiles' }}</strong>
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Pie de página legal INDECOPI -->
    <div class="footer-legal">
        <strong>RECLAMO:</strong> Disconformidad relacionada a los productos o servicios. &nbsp;&bull;&nbsp;
        <strong>QUEJA:</strong> Disconformidad no relacionada a los productos o servicios; o malestar respecto a la atención al público.<br>
        * La formulación del reclamo no impide acudir a otras vías de solución de controversias ni es requisito previo para interponer una denuncia ante el INDECOPI.<br>
        * El proveedor debe dar respuesta en un plazo no mayor a quince (15) días hábiles improrrogables (Ley N° 29571, Ley N° 31435 y D.S. N° 011-2011-PCM). Emitido en <strong>DatecLibro</strong> para <strong>{{ $empresa->razon_social ?: $empresa->name }}</strong>.
    </div>

</body>
</html>
