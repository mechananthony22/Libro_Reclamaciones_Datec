<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Expedientes de Reclamaciones - {{ $empresa->name }}</title>
    <style>
        @page {
            margin: 10mm 10mm 14mm 10mm;
            size: a4 landscape;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 8.6px;
            line-height: 1.35;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        /* Franja superior roja estilo PDF Oficial */
        .top-red-bar {
            height: 5px;
            background-color: #dc2626;
            border-radius: 3px;
            margin-bottom: 9px;
        }

        /* Cabecera flexible */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 9px;
        }
        .header-left {
            width: 60%;
            vertical-align: middle;
        }
        .header-right {
            width: 40%;
            text-align: right;
            vertical-align: middle;
        }
        .report-kicker {
            display: inline-block;
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            font-size: 7.5px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 2px 8px;
            border-radius: 4px;
            margin-bottom: 3px;
        }
        .empresa-title {
            font-size: 14px;
            font-weight: 900;
            color: #0f172a;
            margin: 0 0 2px 0;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .empresa-sub {
            font-size: 8.5px;
            color: #475569;
            margin: 0;
        }
        .ruc-tag {
            display: inline-block;
            background-color: #dc2626;
            color: #ffffff;
            font-weight: 800;
            font-size: 8px;
            padding: 1.5px 7px;
            border-radius: 4px;
            margin-right: 5px;
        }

        /* Caja derecha de auditoría */
        .audit-box {
            display: inline-block;
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-right: 4px solid #dc2626;
            border-radius: 6px;
            padding: 6px 11px;
            text-align: right;
        }
        .audit-title {
            font-size: 8.5px;
            font-weight: 900;
            color: #b91c1c;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 2px;
        }
        .audit-meta {
            font-size: 8px;
            color: #7f1d1d;
            line-height: 1.35;
        }

        /* Indicadores KPI en diseño abierto */
        .kpi-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            background-color: #fff1f2;
            border: 1px solid #fecdd3;
            border-radius: 6px;
        }
        .kpi-card {
            width: 25%;
            padding: 6px 10px;
            vertical-align: middle;
            border-right: 1px dashed #fda4af;
        }
        .kpi-card:last-child {
            border-right: none;
        }
        .kpi-label {
            font-size: 7px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #9f1239;
            display: block;
            margin-bottom: 2px;
        }
        .kpi-value {
            font-size: 12.5px;
            font-weight: 900;
            color: #0f172a;
        }
        .kpi-sub {
            font-size: 7.5px;
            color: #64748b;
            margin-left: 4px;
            font-weight: 600;
        }

        /* Barra de filtros aplicados */
        .filter-bar {
            background-color: #fafafa;
            border: 1px solid #e2e8f0;
            border-left: 3.5px solid #dc2626;
            border-radius: 5px;
            padding: 5px 10px;
            margin-bottom: 9px;
            font-size: 8px;
            color: #334155;
        }
        .filter-pill {
            display: inline-block;
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 3px;
            padding: 1px 6px;
            font-weight: 700;
            color: #b91c1c;
            margin-right: 4px;
        }

        /* Tabla Principal Flexible */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .data-table th {
            background-color: #b91c1c;
            color: #ffffff;
            font-size: 7.5px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 6.5px 6px;
            text-align: left;
            border-bottom: 2px solid #991b1b;
        }
        .data-table td {
            padding: 5.5px 6px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
            font-size: 8px;
            color: #1e293b;
            word-wrap: break-word;
        }
        .data-table tr:nth-child(even) td {
            background-color: #fffafa;
        }

        /* Etiquetas y Badges */
        .hoja-code {
            font-family: 'Courier New', Courier, monospace;
            font-weight: 900;
            font-size: 8.3px;
            color: #b91c1c;
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            padding: 1.5px 5px;
            border-radius: 4px;
            display: inline-block;
        }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 4px;
            font-size: 7px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .badge-reclamo {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }
        .badge-queja {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }
        .badge-pendiente {
            background-color: #ffedd5;
            color: #c2410c;
            border: 1px solid #fdba74;
        }
        .badge-en_tramite {
            background-color: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .badge-atendida {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        .badge-cerrada {
            background-color: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
        }

        .semaforo-pill {
            display: inline-block;
            margin-top: 2px;
            padding: 1px 5px;
            border-radius: 3px;
            font-size: 6.8px;
            font-weight: 800;
        }
        .sem-danger {
            background-color: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .sem-warning {
            background-color: #fffbeb;
            color: #d97706;
            border: 1px solid #fde68a;
        }
        .sem-ok {
            background-color: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        .monto-pill {
            display: inline-block;
            margin-top: 2px;
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            font-weight: 800;
            font-size: 7.5px;
            padding: 1px 5px;
            border-radius: 3px;
        }

        /* Pie de página fijo */
        .footer {
            position: fixed;
            bottom: -4mm;
            left: 0;
            right: 0;
            background-color: #ffffff;
            border-top: 1.5px solid #fecaca;
            padding-top: 4px;
            font-size: 7.5px;
            color: #64748b;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }
        .footer-table td {
            padding: 0;
            vertical-align: middle;
        }
        .footer strong {
            color: #b91c1c;
        }
    </style>
</head>
<body>
    @php
        $coleccion = collect($expedientes);
        $totalExpedientes = $coleccion->count();
        $totalReclamos = $coleccion->where('tipo', 'reclamo')->count();
        $totalQuejas = $coleccion->where('tipo', 'queja')->count();
        $totalEnProceso = $coleccion->whereIn('estado', ['pendiente', 'en_tramite'])->count();
        $totalResueltos = $coleccion->whereIn('estado', ['atendida', 'cerrada'])->count();
    @endphp

    <div class="top-red-bar"></div>

    <!-- Cabecera Ejecutiva -->
    <table class="header-table">
        <tr>
            <td class="header-left">
                <div class="report-kicker">Reporte Oficial de Fiscalización y Gestión &bull; INDECOPI</div>
                <div class="empresa-title">{{ $empresa->razon_social ?: $empresa->name }}</div>
                <div class="empresa-sub">
                    <span class="ruc-tag">RUC: {{ $empresa->ruc ?? 'No especificado' }}</span>
                    Nombre Comercial: <strong>{{ $empresa->name }}</strong>
                    @if(!empty($empresa->direccion))
                        &bull; {{ $empresa->direccion }}
                    @endif
                </div>
            </td>
            <td class="header-right">
                <div class="audit-box">
                    <div class="audit-title">Libro de Reclamaciones Digital</div>
                    <div class="audit-meta">
                        <strong>Fecha de Emisión:</strong> {{ $generadoEn }}<br>
                        <strong>Operador / Auditor:</strong> {{ $usuarioNombre }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Franja de Indicadores KPI -->
    <table class="kpi-table">
        <tr>
            <td class="kpi-card">
                <span class="kpi-label">Total de Expedientes</span>
                <span class="kpi-value" style="color: #b91c1c;">{{ $totalExpedientes }}</span>
                <span class="kpi-sub">registros exportados</span>
            </td>
            <td class="kpi-card">
                <span class="kpi-label">Tipología (Reclamos / Quejas)</span>
                <span class="kpi-value" style="color: #dc2626;">{{ $totalReclamos }}</span>
                <span class="kpi-sub">Reclamos &bull;</span>
                <span class="kpi-value" style="color: #b45309; margin-left: 3px;">{{ $totalQuejas }}</span>
                <span class="kpi-sub">Quejas</span>
            </td>
            <td class="kpi-card">
                <span class="kpi-label">Pendientes / En Trámite</span>
                <span class="kpi-value" style="color: #c2410c;">{{ $totalEnProceso }}</span>
                <span class="kpi-sub">en cómputo de plazo</span>
            </td>
            <td class="kpi-card">
                <span class="kpi-label">Atendidos / Cerrados</span>
                <span class="kpi-value" style="color: #15803d;">{{ $totalResueltos }}</span>
                <span class="kpi-sub">con respuesta emitida</span>
            </td>
        </tr>
    </table>

    <!-- Barra de Filtros y Marco Normativo -->
    <div class="filter-bar">
        <strong style="color: #991b1b;">Criterios de consulta:</strong>&nbsp;
        @if(!empty($filtros['buscar']))
            <span class="filter-pill">Búsqueda: "{{ $filtros['buscar'] }}"</span>
        @endif
        @if(!empty($filtros['estado']))
            <span class="filter-pill">Estado: {{ strtoupper(str_replace('_', ' ', $filtros['estado'])) }}</span>
        @endif
        @if(!empty($filtros['tipo']))
            <span class="filter-pill">Tipo: {{ strtoupper($filtros['tipo']) }}</span>
        @endif
        @if(!empty($filtros['fecha_desde']) || !empty($filtros['fecha_hasta']))
            <span class="filter-pill">Periodo: {{ $filtros['fecha_desde'] ?? 'Inicio' }} al {{ $filtros['fecha_hasta'] ?? 'Actual' }}</span>
        @endif
        @if(empty($filtros['buscar']) && empty($filtros['estado']) && empty($filtros['tipo']) && empty($filtros['fecha_desde']) && empty($filtros['fecha_hasta']))
            <span class="filter-pill">Todos los expedientes históricos</span>
        @endif
        &nbsp;&bull;&nbsp; <strong style="color: #991b1b;">Marco Legal:</strong> Ley N° 29571, Ley N° 31435 y D.S. N° 011-2011-PCM (Plazo máximo de 15 días hábiles improrrogables).
    </div>

    <!-- Tabla de Expedientes -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 9%;">N° Hoja</th>
                <th style="width: 8%;">Emisión</th>
                <th style="width: 7%;">Tipo</th>
                <th style="width: 18%;">Consumidor Reclamante</th>
                <th style="width: 13%;">Sede / Establecimiento</th>
                <th style="width: 16%;">Bien Contratado y Monto</th>
                <th style="width: 10%;">Vencimiento Legal</th>
                <th style="width: 9%;">Estado</th>
                <th style="width: 10%;">Responsable</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expedientes as $exp)
                <tr>
                    <td>
                        <span class="hoja-code">{{ $exp['numero_hoja'] }}</span>
                    </td>
                    <td style="font-weight: 600; color: #334155;">
                        {{ $exp['fecha_hora'] }}
                    </td>
                    <td>
                        <span class="badge badge-{{ $exp['tipo'] }}">{{ $exp['tipo_label'] }}</span>
                    </td>
                    <td>
                        <strong style="color: #0f172a; font-size: 8.3px;">{{ $exp['consumidor_nombre'] }}</strong><br>
                        <span style="color: #475569; font-weight: 600; font-size: 7.5px;">{{ $exp['consumidor_documento'] }}</span>
                        @if(!empty($exp['consumidor_email']))
                            <br><span style="color: #64748b; font-size: 7.2px;">{{ $exp['consumidor_email'] }}</span>
                        @endif
                    </td>
                    <td style="color: #334155; font-weight: 600;">
                        {{ $exp['establecimiento_nombre'] }}
                    </td>
                    <td>
                        <strong style="color: #0f172a;">{{ $exp['tipo_bien'] }}:</strong>
                        <span style="color: #334155;">{{ Str::limit($exp['descripcion_bien'], 55) }}</span>
                        @if($exp['monto_reclamado'])
                            <br><span class="monto-pill">{{ $exp['monto_reclamado'] }}</span>
                        @endif
                    </td>
                    <td>
                        <strong style="color: #0f172a;">{{ $exp['vence_en'] ?? '-' }}</strong>
                        @if(!empty($exp['semaforo']))
                            @php
                                $semColor = $exp['semaforo']['color'] ?? '';
                                $semClass = $semColor === 'danger' ? 'sem-danger' : ($semColor === 'warning' ? 'sem-warning' : 'sem-ok');
                            @endphp
                            <br>
                            <span class="semaforo-pill {{ $semClass }}">
                                {{ $exp['semaforo']['etiqueta'] ?? '' }}
                            </span>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-{{ $exp['estado'] }}">{{ $exp['estado_label'] }}</span>
                    </td>
                    <td style="color: #334155; font-weight: 600;">
                        {{ $exp['responsable_nombre'] ?? 'Sin asignar' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; padding: 22px; color: #64748b; font-size: 9px;">
                        No se encontraron expedientes registrados con los criterios de filtrado seleccionados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Pie de página confidencial -->
    <div class="footer">
        <table class="footer-table">
            <tr>
                <td style="text-align: left;">
                    <strong>CONFIDENCIAL</strong> &bull; Documento protegido conforme a la Ley de Protección de Datos Personales N° 29733.
                </td>
                <td style="text-align: right;">
                    Certificado por plataforma <strong>DatecLibro</strong> para <strong>{{ $empresa->razon_social ?: $empresa->name }}</strong>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
