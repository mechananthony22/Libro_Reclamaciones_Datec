<?php

use App\Mail\CodigoRecuperacionPasswordMailable;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    DB::table('password_reset_tokens')->truncate();
});

test('valida que el correo sea obligatorio y tenga formato valido al solicitar codigo', function () {
    $response = $this->post(route('password.enviar-codigo'), [
        'email' => 'no-es-correo',
    ]);

    $response->assertSessionHasErrors(['email']);
    Mail::assertNothingSent();
});

test('retorna error si el correo no se encuentra registrado en el sistema', function () {
    $response = $this->post(route('password.enviar-codigo'), [
        'email' => 'noexiste@ejemplo.com',
    ]);

    $response->assertSessionHasErrors(['email']);
    Mail::assertNothingSent();
});

test('genera codigo de 6 digitos y envia correo a un usuario registrado', function () {
    $user = User::factory()->create([
        'email' => 'usuario.test@gmail.com',
        'name' => 'Usuario Test',
    ]);

    $response = $this->post(route('password.enviar-codigo'), [
        'email' => 'usuario.test@gmail.com',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('codigo_enviado');

    // Verifica que se guardó el registro en password_reset_tokens
    $tokenRecord = DB::table('password_reset_tokens')->where('email', 'usuario.test@gmail.com')->first();
    expect($tokenRecord)->not->toBeNull();

    // Verifica que se envió el correo con el Mailable
    Mail::assertSent(CodigoRecuperacionPasswordMailable::class, function ($mail) use ($user) {
        return $mail->hasTo('usuario.test@gmail.com')
            && strlen($mail->codigo) === 6
            && $mail->userName === $user->name;
    });
});

test('bloquea solicitud duplicada de codigo antes de los 60 segundos (rate limit)', function () {
    $user = User::factory()->create([
        'email' => 'ratelimit@gmail.com',
    ]);

    // Primera solicitud
    $this->post(route('password.enviar-codigo'), ['email' => 'ratelimit@gmail.com']);

    // Segunda solicitud inmediata
    $response = $this->post(route('password.enviar-codigo'), ['email' => 'ratelimit@gmail.com']);
    $response->assertSessionHasErrors(['email']);
});

test('rechaza restablecimiento con codigo incorrecto o inexistente', function () {
    $user = User::factory()->create([
        'email' => 'codigo.invalido@gmail.com',
        'password' => Hash::make('password_vieja'),
    ]);

    DB::table('password_reset_tokens')->insert([
        'email' => 'codigo.invalido@gmail.com',
        'token' => Hash::make('123456'),
        'created_at' => now(),
    ]);

    $response = $this->post(route('password.restablecer'), [
        'email' => 'codigo.invalido@gmail.com',
        'codigo' => '999999', // Código incorrecto
        'password' => 'password_nueva_123',
        'password_confirmation' => 'password_nueva_123',
    ]);

    $response->assertSessionHasErrors(['codigo']);

    // La contraseña no debe haber cambiado
    expect(Hash::check('password_vieja', $user->fresh()->password))->toBeTrue();
});

test('rechaza codigo expirado luego de 15 minutos', function () {
    $user = User::factory()->create([
        'email' => 'codigo.expirado@gmail.com',
        'password' => Hash::make('password_vieja'),
    ]);

    DB::table('password_reset_tokens')->insert([
        'email' => 'codigo.expirado@gmail.com',
        'token' => Hash::make('654321'),
        'created_at' => Carbon::now()->subMinutes(16), // Expirado
    ]);

    $response = $this->post(route('password.restablecer'), [
        'email' => 'codigo.expirado@gmail.com',
        'codigo' => '654321',
        'password' => 'password_nueva_123',
        'password_confirmation' => 'password_nueva_123',
    ]);

    $response->assertSessionHasErrors(['codigo']);
    expect(Hash::check('password_vieja', $user->fresh()->password))->toBeTrue();
});

test('restablece exitosamente la contrasena con codigo valido', function () {
    $user = User::factory()->create([
        'email' => 'exito.recuperar@gmail.com',
        'password' => Hash::make('antigua_clave_123'),
    ]);

    DB::table('password_reset_tokens')->insert([
        'email' => 'exito.recuperar@gmail.com',
        'token' => Hash::make('555666'),
        'created_at' => now(),
    ]);

    $response = $this->post(route('password.restablecer'), [
        'email' => 'exito.recuperar@gmail.com',
        'codigo' => '555666',
        'password' => 'nueva_clave_segura_2026',
        'password_confirmation' => 'nueva_clave_segura_2026',
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('success');

    // La contraseña en la base de datos debe estar actualizada
    expect(Hash::check('nueva_clave_segura_2026', $user->fresh()->password))->toBeTrue();

    // El token debe haberse eliminado
    expect(DB::table('password_reset_tokens')->where('email', 'exito.recuperar@gmail.com')->exists())->toBeFalse();
});
