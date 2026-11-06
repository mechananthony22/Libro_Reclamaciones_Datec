<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Expedientes de Reclamaciones - {{ $empresa->name }}</title>
    <style>
        @page {
            margin: 12mm 10mm 15mm 10mm;
            size: a4 landscape;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 9px;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            border-bottom: 2px solid #0284c7;
            padding-bottom: 8px;
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
        .title {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 2px 0;
            text-transform: uppercase;
        }
        .subtitle {
            font-size: 9px;
            color: #64748b;
            margin: 0;
        }
        .meta-pill {
            display: inline-block;
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 8px;
            color: #334155;
            margin-top: 3px;
        }
        .summary-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 10px;
            font-size: 8.5px;
            color: #334155;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .data-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 6px 4px;
            text-align: left;
            border: 1px solid #0f172a;
        }
        .data-table td {
            padding: 5px 4px;
            border: 1px solid #cbd5e1;
            vertical-align: top;
            font-size: 8px;
        }
        .data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .badge {
            display: inline-block;
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-reclamo { background-color: #dbeafe; color: #1e40af; }
        .badge-queja { background-color: #fef3c7; color: #92400e; }
        .badge-pendiente { background-color: #fef3c7; color: #92400e; }
        .badge-en_tramite { background-color: #dbeafe; color: #1e40af; }
        .badge-atendida { background-color: #dcfce7; color: #166534; }
        .badge-cerrada { background-color: #f1f5f9; color: #475569; }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 7.5px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td class="header-left">
                <div class="title">{{ $empresa->name }}</div>
                <div class="subtitle">
                    RUC: {{ $empresa->ruc ?? 'No especificado' }} &bull; Razón Social: {{ $empresa->razon_social ?? $empresa->name }}
                </div>
                <div class="meta-pill">
                    <strong>Reporte Oficial de Expedientes</strong> &bull; Libro de Reclamaciones
                </div>
            </td>
            <td class="header-right">
                <div style="font-weight: bold; font-size: 8.5px; color: #334155;">Generado: {{ $generadoEn }}</div>
                <div style="font-size: 8px; color: #64748b;">Usuario: {{ $usuarioNombre }}</div>
                <div style="font-size: 8px; color: #64748b;">Total de registros: {{ count($expedientes) }}</div>
            </td>
        </tr>
    </table>

    <div class="summary-box">
        <strong>Filtros aplicados:</strong>
        @if(!empty($filtros['buscar'])) Búsqueda: "{{ $filtros['buscar'] }}" &bull; @endif
        @if(!empty($filtros['estado'])) Estado: {{ ucfirst(str_replace('_', ' ', $filtros['estado'])) }} &bull; @endif
        @if(!empty($filtros['tipo'])) Tipo: {{ ucfirst($filtros['tipo']) }} &bull; @endif
        @if(!empty($filtros['fecha_desde']) || !empty($filtros['fecha_hasta']))
            Rango: {{ $filtros['fecha_desde'] ?? 'Inicio' }} al {{ $filtros['fecha_hasta'] ?? 'Actual' }} &bull;
        @endif
        Normativa: D.S. N° 011-2011-PCM / Ley N° 29571 (Plazo legal de 15 días hábiles).
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 8%;">N° Hoja</th>
                <th style="width: 8%;">Fecha</th>
                <th style="width: 7%;">Tipo</th>
                <th style="width: 17%;">Consumidor</th>
                <th style="width: 13%;">Sede / Sucursal</th>
                <th style="width: 15%;">Bien Reclamado</th>
                <th style="width: 10%;">Vencimiento</th>
                <th style="width: 10%;">Estado</th>
                <th style="width: 12%;">Responsable</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expedientes as $exp)
                <tr>
                    <td style="font-family: monospace; font-weight: bold;">{{ $exp['numero_hoja'] }}</td>
                    <td>{{ $exp['fecha_hora'] }}</td>
                    <td>
                        <span class="badge badge-{{ $exp['tipo'] }}">{{ $exp['tipo_label'] }}</span>
                    </td>
                    <td>
                        <strong>{{ $exp['consumidor_nombre'] }}</strong><br>
                        <span style="color: #64748b;">{{ $exp['consumidor_documento'] }}</span><br>
                        <span style="color: #64748b;">{{ $exp['consumidor_email'] }}</span>
                    </td>
                    <td>{{ $exp['establecimiento_nombre'] }}</td>
                    <td>
                        <strong>{{ $exp['tipo_bien'] }}:</strong> {{ Str::limit($exp['descripcion_bien'], 50) }}
                        @if($exp['monto_reclamado'])
                            <br><span style="color: #0284c7; font-weight: bold;">{{ $exp['monto_reclamado'] }}</span>
                        @endif
                    </td>
                    <td>
                        {{ $exp['vence_en'] ?? '-' }}<br>
                        <span style="font-size: 7px; color: {{ $exp['semaforo']['color'] === 'danger' ? '#dc2626' : ($exp['semaforo']['color'] === 'warning' ? '#d97706' : '#16a34a') }}; font-weight: bold;">
                            {{ $exp['semaforo']['etiqueta'] ?? '' }}
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-{{ $exp['estado'] }}">{{ $exp['estado_label'] }}</span>
                    </td>
                    <td>{{ $exp['responsable_nombre'] ?? 'Sin asignar' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; padding: 20px; color: #64748b;">
                        No se encontraron expedientes con los criterios seleccionados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Confidencial &bull; Reporte emitido conforme a la Ley de Protección de Datos Personales N° 29733 &bull; Libro de Reclamaciones DatecLibro &bull; Página <span class="pagenum"></span>
    </div>
</body>
</html>

