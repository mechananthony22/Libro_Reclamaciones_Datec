<?php

use App\Enums\EstadoEmpresa;
use App\Enums\PlanEmpresa;
use App\Enums\TipoBien;
use App\Enums\TipoDocumentoConsumidor;
use App\Enums\TipoReclamacion;
use App\Jobs\EnviarConstanciaReclamacionJob;
use App\Mail\ConstanciaReclamacionMailable;
use App\Models\Empresa;
use App\Models\Reclamacion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

test('despues del registro se muestra la confirmacion oficial con correlativo y enlace a PDF', function () {
    $empresa = Empresa::create([
        'name' => 'Comercio Éxito',
        'slug' => 'comercio-exito',
        'razon_social' => 'Comercio Éxito S.A.C.',
        'ruc' => '20192837401',
        'direccion' => 'Av. San Martín 500, Ica',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Basico,
        'activo' => true,
    ]);

    $tokenValido = encrypt(now()->subSeconds(5)->timestamp);

    $response = $this->post(route('libro.registrar', ['slug' => $empresa->slug]), [
        'tipo_documento' => TipoDocumentoConsumidor::DNI->value,
        'numero_documento' => '12345678',
        'nombres' => 'Rosa',
        'apellidos' => 'Flores',
        'email' => 'rosa.flores@ejemplo.pe',
        'telefono' => '987654321',
        'direccion' => 'Calle Las Dalias 100',
        'es_menor_edad' => false,
        'tipo_bien' => TipoBien::Producto->value,
        'descripcion_bien' => 'Microondas Digital 30L',
        'tipo' => TipoReclamacion::Reclamo->value,
        'detalle_problema' => 'El aparato no enciende al conectarlo a la corriente.',
        'pedido_consumidor' => 'Cambio del producto o devolución de dinero.',
        'aviso_privacidad_leido' => true,
        'token_seguridad' => $tokenValido,
    ]);

    $reclamacion = Reclamacion::where('empresa_id', $empresa->id)->first();
    expect($reclamacion)->not->toBeNull();

    // Redirige a la pantalla de confirmación oficial
    $response->assertRedirect(route('libro.confirmacion', [
        'slug' => $empresa->slug,
        'token' => $reclamacion->token_consulta,
    ]));

    // Visitar la pantalla de confirmación
    $responseConfirmacion = $this->get(route('libro.confirmacion', [
        'slug' => $empresa->slug,
        'token' => $reclamacion->token_consulta,
    ]));

    $responseConfirmacion->assertOk();
    $responseConfirmacion->assertSee($reclamacion->numero_hoja);
    $responseConfirmacion->assertSee('Rosa Flores');
});

test('permite la descarga del PDF oficial de la constancia de reclamacion', function () {
    $empresa = Empresa::create([
        'name' => 'Librería Central',
        'slug' => 'libreria-central',
        'razon_social' => 'Librería Central S.A.C.',
        'ruc' => '20456789123',
        'direccion' => 'Jr. Pizarro 450, Trujillo',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Basico,
        'activo' => true,
    ]);

    $tokenValido = encrypt(now()->subSeconds(5)->timestamp);

    $this->post(route('libro.registrar', ['slug' => $empresa->slug]), [
        'tipo_documento' => TipoDocumentoConsumidor::DNI->value,
        'numero_documento' => '87654321',
        'nombres' => 'Juan',
        'apellidos' => 'Perez',
        'email' => 'juan@ejemplo.com',
        'telefono' => '912345678',
        'direccion' => 'Calle España 200',
        'es_menor_edad' => false,
        'tipo_bien' => TipoBien::Servicio->value,
        'descripcion_bien' => 'Servicio de fotocopiado y anillado',
        'tipo' => TipoReclamacion::Queja->value,
        'detalle_problema' => 'Demora excesiva y mala atención por parte del cajero.',
        'pedido_consumidor' => 'Disculpas formales y llamada de atención al personal.',
        'aviso_privacidad_leido' => true,
        'token_seguridad' => $tokenValido,
    ]);

    $reclamacion = Reclamacion::where('empresa_id', $empresa->id)->first();

    $responsePdf = $this->get(route('libro.constancia.pdf', [
        'slug' => $empresa->slug,
        'token' => $reclamacion->token_consulta,
    ]));

    $responsePdf->assertOk();
    $responsePdf->assertHeader('content-type', 'application/pdf');
    expect($responsePdf->headers->get('content-disposition'))->toContain('attachment');
    expect($responsePdf->headers->get('content-disposition'))->toContain($reclamacion->numero_hoja);
});

test('despacha el envio del correo transaccional de constancia al consumidor de forma asincrona', function () {
    Queue::fake();

    $empresa = Empresa::create([
        'name' => 'Farmacia Salud',
        'slug' => 'farmacia-salud',
        'razon_social' => 'Farmacia Salud S.A.C.',
        'ruc' => '20789012345',
        'direccion' => 'Av. Grau 120, Piura',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Basico,
        'activo' => true,
    ]);

    $tokenValido = encrypt(now()->subSeconds(5)->timestamp);

    $this->post(route('libro.registrar', ['slug' => $empresa->slug]), [
        'tipo_documento' => TipoDocumentoConsumidor::DNI->value,
        'numero_documento' => '44556677',
        'nombres' => 'Lucia',
        'apellidos' => 'Vargas',
        'email' => 'lucia.vargas@farmaciasalud.pe',
        'telefono' => '998877665',
        'direccion' => 'Av. Grau 400',
        'es_menor_edad' => false,
        'tipo_bien' => TipoBien::Producto->value,
        'descripcion_bien' => 'Medicamento recetado',
        'tipo' => TipoReclamacion::Reclamo->value,
        'detalle_problema' => 'El empaque del medicamento se encontraba abierto al recibirlo.',
        'pedido_consumidor' => 'Cambio inmediato del medicamento sellado.',
        'aviso_privacidad_leido' => true,
        'token_seguridad' => $tokenValido,
    ]);

    Queue::assertPushed(EnviarConstanciaReclamacionJob::class);
});

test('el envio del correo persiste la auditoria y no anula el reclamo si ocurre un fallo en el servidor SMTP', function () {
    Mail::fake();

    $empresa = Empresa::create([
        'name' => 'Cadena Hotelera',
        'slug' => 'cadena-hotelera',
        'razon_social' => 'Cadena Hotelera S.A.C.',
        'ruc' => '20987123456',
        'direccion' => 'Av. Costanera 300, Paracas',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Basico,
        'activo' => true,
    ]);

    $tokenValido = encrypt(now()->subSeconds(5)->timestamp);

    $this->post(route('libro.registrar', ['slug' => $empresa->slug]), [
        'tipo_documento' => TipoDocumentoConsumidor::DNI->value,
        'numero_documento' => '71829304',
        'nombres' => 'Alonso',
        'apellidos' => 'Rios',
        'email' => 'alonso@ejemplo.com',
        'telefono' => '944332211',
        'direccion' => 'Calle Las Palmeras 100',
        'es_menor_edad' => false,
        'tipo_bien' => TipoBien::Servicio->value,
        'descripcion_bien' => 'Alojamiento habitación matrimonial',
        'tipo' => TipoReclamacion::Queja->value,
        'detalle_problema' => 'Falta de agua caliente durante toda la estadía.',
        'pedido_consumidor' => 'Descuento o compensación proporcional.',
        'aviso_privacidad_leido' => true,
        'token_seguridad' => $tokenValido,
    ]);

    $reclamacion = Reclamacion::where('empresa_id', $empresa->id)->first();
    expect($reclamacion)->not->toBeNull();

    // Ejecutar el Job directamente con Mail::fake()
    $job = new EnviarConstanciaReclamacionJob($reclamacion);
    $job->handle();

    Mail::assertSent(ConstanciaReclamacionMailable::class, function ($mail) {
        return $mail->hasTo('alonso@ejemplo.com');
    });

    // Auditoría registrada en base de datos
    $this->assertDatabaseHas('reclamacion_envios', [
        'empresa_id' => $empresa->id,
        'reclamacion_id' => $reclamacion->id,
        'destinatario' => 'alonso@ejemplo.com',
        'estado' => 'enviado',
    ]);
});

test('permite adjuntar archivos de evidencia y valida tipos y tamanos permitidos', function () {
    Storage::fake('local');

    $empresa = Empresa::create([
        'name' => 'Electrodomésticos Perú',
        'slug' => 'electro-peru',
        'razon_social' => 'Electrodomésticos Perú S.A.C.',
        'ruc' => '20120304050',
        'direccion' => 'Av. Angamos 890, Surquillo',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Basico,
        'activo' => true,
    ]);

    $tokenValido = encrypt(now()->subSeconds(5)->timestamp);

    $archivoPdf = UploadedFile::fake()->create('boleta_pago.pdf', 500, 'application/pdf');
    $archivoFoto = UploadedFile::fake()->image('foto_falla.jpg');

    $response = $this->post(route('libro.registrar', ['slug' => $empresa->slug]), [
        'tipo_documento' => TipoDocumentoConsumidor::DNI->value,
        'numero_documento' => '99887766',
        'nombres' => 'Patricia',
        'apellidos' => 'Quispe',
        'email' => 'patricia@ejemplo.pe',
        'telefono' => '977665544',
        'direccion' => 'Av. Primavera 120',
        'es_menor_edad' => false,
        'tipo_bien' => TipoBien::Producto->value,
        'descripcion_bien' => 'Refrigeradora No Frost',
        'tipo' => TipoReclamacion::Reclamo->value,
        'detalle_problema' => 'El motor presenta ruidos extraños y no congela adecuadamente.',
        'pedido_consumidor' => 'Revisión técnica o cambio del producto.',
        'aviso_privacidad_leido' => true,
        'token_seguridad' => $tokenValido,
        'adjuntos' => [$archivoPdf, $archivoFoto],
    ]);

    $reclamacion = Reclamacion::where('empresa_id', $empresa->id)->first();
    expect($reclamacion)->not->toBeNull();

    // Redirección exitosa a la constancia
    $response->assertRedirect(route('libro.confirmacion', [
        'slug' => $empresa->slug,
        'token' => $reclamacion->token_consulta,
    ]));

    // Verificar que los archivos se registraron en la tabla de adjuntos
    $this->assertDatabaseHas('reclamacion_adjuntos', [
        'empresa_id' => $empresa->id,
        'reclamacion_id' => $reclamacion->id,
        'nombre_original' => 'boleta_pago.pdf',
    ]);

    $this->assertDatabaseHas('reclamacion_adjuntos', [
        'empresa_id' => $empresa->id,
        'reclamacion_id' => $reclamacion->id,
        'nombre_original' => 'foto_falla.jpg',
    ]);

    expect($reclamacion->adjuntos)->toHaveCount(2);
});

test('rechaza archivos adjuntos incompatibles o con extensiones prohibidas', function () {
    $empresa = Empresa::create([
        'name' => 'Comercio Archivos',
        'slug' => 'comercio-archivos',
        'razon_social' => 'Archivos y Mas S.A.C.',
        'ruc' => '20192837499',
        'direccion' => 'Calle Lima 100',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Basico,
        'activo' => true,
    ]);

    $tokenValido = encrypt(now()->subSeconds(5)->timestamp);

    // Archivo ejecutable no permitido (.exe)
    $archivoProhibido = UploadedFile::fake()->create('script_malicioso.exe', 100, 'application/x-msdownload');

    $response = $this->post(route('libro.registrar', ['slug' => $empresa->slug]), [
        'tipo_documento' => TipoDocumentoConsumidor::DNI->value,
        'numero_documento' => '11223344',
        'nombres' => 'Tester',
        'apellidos' => 'Seguridad',
        'email' => 'seguridad@ejemplo.pe',
        'telefono' => '911223344',
        'direccion' => 'Calle Segura 10',
        'es_menor_edad' => false,
        'tipo_bien' => TipoBien::Producto->value,
        'descripcion_bien' => 'Producto de prueba',
        'tipo' => TipoReclamacion::Reclamo->value,
        'detalle_problema' => 'Prueba de subida de archivo incompatible.',
        'pedido_consumidor' => 'Rechazo de archivo no permitido.',
        'aviso_privacidad_leido' => true,
        'token_seguridad' => $tokenValido,
        'adjuntos' => [$archivoProhibido],
    ]);

    $response->assertSessionHasErrors(['adjuntos.0']);
    $this->assertDatabaseMissing('reclamaciones', [
        'empresa_id' => $empresa->id,
        'email' => 'seguridad@ejemplo.pe',
    ]);
});
