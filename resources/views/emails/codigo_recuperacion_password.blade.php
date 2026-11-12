<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Código de Recuperación de Contraseña - DatecLibro</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 24px 16px;
        }
        .container {
            max-width: 560px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        }
        .header {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            color: #ffffff;
            padding: 32px 24px;
            text-align: center;
        }
        .logo-text {
            font-size: 22px;
            font-weight: 900;
            letter-spacing: -0.5px;
            color: #ffffff;
            margin: 0;
        }
        .logo-text span {
            color: #38bdf8;
        }
        .header h1 {
            margin: 12px 0 0 0;
            font-size: 18px;
            font-weight: 800;
            color: #ffffff;
        }
        .content {
            padding: 32px 28px;
            font-size: 14px;
            line-height: 1.6;
            color: #334155;
        }
        .greeting {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
        }
        .code-box {
            background-color: #f0f9ff;
            border: 2px dashed #0284c7;
            border-radius: 16px;
            padding: 24px 16px;
            margin: 24px 0;
            text-align: center;
        }
        .code-label {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #0369a1;
            margin-bottom: 8px;
            display: block;
        }
        .code-number {
            font-family: 'Courier New', Courier, monospace;
            font-size: 36px;
            font-weight: 900;
            letter-spacing: 10px;
            color: #0c4a6e;
            display: inline-block;
            margin: 0;
        }
        .expiry-badge {
            display: inline-block;
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 9999px;
            margin-top: 10px;
        }
        .notice-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #3b82f6;
            border-radius: 8px;
            padding: 14px 16px;
            margin: 20px 0;
            font-size: 12px;
            color: #475569;
            line-height: 1.5;
        }
        .footer {
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 20px 24px;
            font-size: 11px;
            color: #94a3b8;
            text-align: center;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-text">Datec<span>Libro</span></div>
            <h1>Recuperación de Contraseña</h1>
        </div>

        <div class="content">
            <div class="greeting">Hola, {{ $userName }}:</div>
            
            <p>
                Recibimos una solicitud para restablecer la contraseña de acceso a tu cuenta en <strong>DatecLibro</strong>.
            </p>

            <p>
                Para continuar y definir tu nueva contraseña, copia e ingresa el siguiente código de verificación de 6 dígitos en la pantalla:
            </p>

            <div class="code-box">
                <span class="code-label">Tu código de seguridad</span>
                <div class="code-number">{{ $codigo }}</div>
                <div>
                    <span class="expiry-badge">⏱ Válido por {{ $minutosExpiracion }} minutos</span>
                </div>
            </div>

            <div class="notice-card">
                <strong>¿No solicitaste este cambio?</strong><br>
                Si tú no realizaste esta solicitud, puedes ignorar este mensaje de forma segura. Tu contraseña actual no ha cambiado y tu cuenta sigue estando protegida.
            </div>
        </div>

        <div class="footer">
            Este es un mensaje automático de seguridad generado por la plataforma DatecLibro.<br>
            Por favor, no respondas a este correo. &bull; &copy; {{ date('Y') }} Datec Consulting S.A.C.
        </div>
    </div>
</body>
</html>

