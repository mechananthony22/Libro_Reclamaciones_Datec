<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EnviarCodigoRecuperacionRequest;
use App\Http\Requests\Auth\RestablecerPasswordRequest;
use App\Mail\CodigoRecuperacionPasswordMailable;
use App\Models\User;
use App\Services\AuditoriaService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Controlador para la recuperación y autoservicio de contraseñas de usuarios
 * mediante código de verificación de 6 dígitos enviado por correo electrónico.
 */
class RecuperarPasswordController extends Controller
{
    /**
     * Genera y envía por correo electrónico un código de 6 dígitos para recuperación.
     */
    public function enviarCodigo(EnviarCodigoRecuperacionRequest $request): RedirectResponse
    {
        $email = strtolower(trim($request->validated('email')));

        $user = User::where('email', $email)->first();

        // Si el usuario no existe en la base de datos
        if (! $user) {
            return back()->withErrors([
                'email' => 'No encontramos ninguna cuenta registrada con este correo electrónico.',
            ]);
        }

        // Control de tasa (Rate Limiting preventivo): no permitir enviar más de 1 código por minuto
        $ultimoToken = DB::table('password_reset_tokens')->where('email', $email)->first();
        if ($ultimoToken && $ultimoToken->created_at) {
            $haceUnMinuto = Carbon::parse($ultimoToken->created_at)->addSeconds(60);
            if ($haceUnMinuto->isFuture()) {
                $segundosRestantes = $haceUnMinuto->diffInSeconds(now());

                return back()->withErrors([
                    'email' => "Ya se envió un código recientemente. Por favor espera {$segundosRestantes} segundos antes de solicitar uno nuevo.",
                ]);
            }
        }

        // Generar código numérico de 6 dígitos
        $codigo = (string) random_int(100000, 999999);

        // Guardar el código hasheado en password_reset_tokens con vigencia de 15 minutos
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($codigo),
                'created_at' => now(),
            ]
        );

        // Envío del correo electrónico mediante el mailable
        try {
            Mail::to($email)->send(new CodigoRecuperacionPasswordMailable(
                codigo: $codigo,
                userName: $user->name,
                minutosExpiracion: 15
            ));
        } catch (\Throwable $e) {
            Log::error('Error al enviar correo de recuperación de contraseña', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'email' => 'Hubo un inconveniente temporal al conectar con el servidor de correos. Intenta nuevamente.',
            ]);
        }

        return back()->with('codigo_enviado', [
            'email' => $email,
            'mensaje' => "Se ha enviado un código de seguridad de 6 dígitos a {$email}. Revisa tu bandeja de entrada o carpeta de spam.",
        ]);
    }

    /**
     * Valida el código de 6 dígitos y actualiza la contraseña del usuario.
     */
    public function restablecer(RestablecerPasswordRequest $request): RedirectResponse
    {
        $email = strtolower(trim($request->validated('email')));
        $codigoIngresado = trim($request->validated('codigo'));

        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $record) {
            return back()->withErrors([
                'codigo' => 'No hay ninguna solicitud de recuperación activa para este correo. Solicita un nuevo código.',
            ]);
        }

        // Verificar expiración (15 minutos)
        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            return back()->withErrors([
                'codigo' => 'El código de recuperación ha expirado. Por favor solicita uno nuevo.',
            ]);
        }

        // Validar el código de 6 dígitos con el hash almacenado
        if (! Hash::check($codigoIngresado, $record->token)) {
            return back()->withErrors([
                'codigo' => 'El código de verificación de 6 dígitos es incorrecto.',
            ]);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return back()->withErrors([
                'email' => 'No se encontró el usuario asociado a esta cuenta.',
            ]);
        }

        // Actualizar la contraseña del usuario y limpiar sesiones y tokens
        DB::transaction(function () use ($user, $request, $email) {
            $user->password = Hash::make($request->validated('password'));
            $user->setRememberToken(Str::random(60));
            $user->save();

            // Eliminar el token de recuperación consumido
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            // Invalida sesiones activas para seguridad
            DB::table('sessions')->where('user_id', $user->id)->delete();
        });

        app(AuditoriaService::class)->registrar(
            'password_restablecido',
            'seguridad',
            "El usuario {$user->email} restableció su contraseña satisfactoriamente mediante código por correo.",
            ['user_id' => $user->id, 'email' => $user->email]
        );

        return redirect()->route('login')
            ->with('success', '¡Tu contraseña ha sido restablecida con éxito! Ya puedes iniciar sesión con tus nuevas credenciales.');
    }
}
