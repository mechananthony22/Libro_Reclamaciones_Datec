<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Constancia de Reclamación N° {{ $reclamacion->numero_hoja }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #334155; margin: 0; padding: 20px; background-color: #f8fafc;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        
        <!-- Encabezado con color primario -->
        <div style="background-color: {{ $empresa->color_primario ?: '#2563EB' }}; padding: 24px; text-align: center; color: #ffffff;">
            <h2 style="margin: 0; font-size: 20px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">
                Constancia de Presentación
            </h2>
            <p style="margin: 4px 0 0; font-size: 13px; opacity: 0.9;">
                Libro de Reclamaciones Virtual • {{ $empresa->razon_social }}
            </p>
        </div>

        <!-- Contenido principal -->
        <div style="padding: 28px 24px;">
            <p style="font-size: 15px; margin-top: 0;">
                Estimado(a) <strong>{{ $consumidor['nombres'] ?? 'Consumidor' }} {{ $consumidor['apellidos'] ?? '' }}</strong>:
            </p>

            <p style="font-size: 14px; color: #475569;">
                Le confirmamos que su <strong>{{ strtoupper($reclamacion->tipo->label()) }}</strong> ha sido registrada exitosamente en nuestro sistema con los siguientes datos oficiales:
            </p>

            <!-- Cuadro de Resumen -->
            <div style="background-color: #f1f5f9; border-radius: 8px; padding: 16px; margin: 20px 0; border-left: 4px solid {{ $empresa->color_primario ?: '#2563EB' }};">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr>
                        <td style="padding: 4px 0; color: #64748b; width: 45%;"><strong>N° de Hoja:</strong></td>
                        <td style="padding: 4px 0; font-weight: bold; color: #0f172a;">{{ $reclamacion->numero_hoja }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; color: #64748b;"><strong>Tipo:</strong></td>
                        <td style="padding: 4px 0; font-weight: bold; color: #0f172a;">{{ strtoupper($reclamacion->tipo->label()) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; color: #64748b;"><strong>Fecha y Hora:</strong></td>
                        <td style="padding: 4px 0; color: #0f172a;">{{ $reclamacion->fecha_hora?->format('d/m/Y H:i:s') ?? now()->format('d/m/Y H:i:s') }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; color: #64748b;"><strong>Plazo Legal de Respuesta:</strong></td>
                        <td style="padding: 4px 0; color: #0f172a;">15 días hábiles (Vence: {{ $reclamacion->vence_en?->format('d/m/Y') ?? '15 días hábiles' }})</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; color: #64748b;"><strong>Código de Consulta:</strong></td>
                        <td style="padding: 4px 0; font-family: monospace; font-size: 12px; color: #2563eb;">{{ $reclamacion->token_consulta }}</td>
                    </tr>
                </table>
            </div>

            <!-- Botón de Descarga del PDF Adjunto -->
            <div style="text-align: center; margin: 28px 0;">
                <a href="{{ $urlDescargaPdf }}" style="display: inline-block; background-color: {{ $empresa->color_primario ?: '#2563EB' }}; color: #ffffff; text-decoration: none; font-weight: bold; font-size: 14px; padding: 12px 24px; border-radius: 9999px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                    Descargar Constancia Oficial (PDF)
                </a>
            </div>

            <p style="font-size: 12px; color: #64748b; line-height: 1.5;">
                Adjunto a este correo encontrará una copia en formato PDF con la constancia formal de su reclamación conforme al D.S. N° 011-2011-PCM y el Código de Protección y Defensa del Consumidor (Ley N° 29571).
            </p>
        </div>

        <!-- Pie institucional -->
        <div style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 16px 24px; font-size: 11px; color: #94a3b8; text-align: center;">
            <p style="margin: 0;">
                {{ $empresa->razon_social }} • RUC {{ $empresa->ruc }} • {{ $empresa->direccion }}
            </p>
            <p style="margin: 4px 0 0;">
                Plataforma tecnológica provista por DatecLibro.
            </p>
        </div>
    </div>
</body>
</html>

