<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso Restringido (403) - DatecLibro</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet" />
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Instrument Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #f1f5f9;
            color: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
        }
        .backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(4px);
            z-index: 10;
        }
        .modal-card {
            position: relative;
            z-index: 20;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1.25rem;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25);
            max-width: 28rem;
            width: 100%;
            overflow: hidden;
            animation: fadeIn 0.2s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.96); }
            to { opacity: 1; transform: scale(1); }
        }
        .modal-header {
            background: rgba(248, 250, 252, 0.85);
            border-bottom: 1px solid #f1f5f9;
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.875rem;
        }
        .icon-chip {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 0.75rem;
            background: #f59e0b;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25);
        }
        .icon-chip svg {
            width: 1.35rem;
            height: 1.35rem;
        }
        .modal-header h2 {
            font-size: 1rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.25;
        }
        .modal-header p {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.125rem;
        }
        .modal-body {
            padding: 1.5rem;
        }
        .alert-box {
            background: rgba(254, 243, 199, 0.75);
            border: 1px solid rgba(251, 191, 36, 0.8);
            border-radius: 0.75rem;
            padding: 0.875rem 1rem;
            color: #78350f;
            font-size: 0.8125rem;
            line-height: 1.45;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: flex-start;
            gap: 0.625rem;
        }
        .alert-box svg {
            width: 1.125rem;
            height: 1.125rem;
            color: #b45309;
            flex-shrink: 0;
            margin-top: 0.125rem;
        }
        .description {
            font-size: 0.75rem;
            line-height: 1.55;
            color: #475569;
            margin-bottom: 0.5rem;
        }
        .modal-footer {
            background: rgba(248, 250, 252, 0.6);
            border-top: 1px solid #f1f5f9;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.625rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.375rem;
            padding: 0.55rem 1rem;
            border-radius: 0.75rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease;
            border: none;
            font-family: inherit;
        }
        .btn-secondary {
            background: #ffffff;
            color: #334155;
            border: 1px solid #cbd5e1;
        }
        .btn-secondary:hover {
            background: #f8fafc;
            color: #0f172a;
        }
        .btn-primary {
            background: #2563eb;
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
        }
        .btn-primary:hover {
            background: #1d4ed8;
        }
        .badge-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #d97706;
            margin-bottom: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="backdrop"></div>

    <div class="modal-card">
        <div class="modal-header">
            <div class="icon-chip">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <div>
                <div class="badge-brand">Error 403 · Acceso Restringido</div>
                <h2>Cuenta o Servicio Inactivo</h2>
                <p>Plataforma DatecLibro</p>
            </div>
        </div>

        <div class="modal-body">
            <div class="alert-box">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>
                    {{ $exception->getMessage() ?: 'El servicio de tu empresa se encuentra temporalmente suspendido o inactivo.' }}
                </span>
            </div>

            <p class="description">
                El acceso a esta organización o cuenta de usuario ha sido pausado por motivos administrativos o de suscripción.
            </p>
            <p class="description">
                Si consideras que se trata de un error o necesitas regularizar el servicio de tu libro de reclamaciones, comunícate con la administración de tu empresa o con el soporte oficial de Datec.
            </p>
        </div>

        <div class="modal-footer">
            <button type="button" onclick="window.history.back()" class="btn btn-secondary">
                Regresar
            </button>
            <a href="/login" class="btn btn-primary">
                Ir al Inicio de Sesión
            </a>
        </div>
    </div>
</body>
</html>

