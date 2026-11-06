<?php

use App\Enums\EstadoEmpresa;
use App\Enums\PlanEmpresa;
use App\Enums\TipoBien;
use App\Enums\TipoDocumentoConsumidor;
use App\Enums\TipoReclamacion;
use App\Models\Empresa;
use App\Models\Establecimiento;
use App\Models\Reclamacion;
use App\Services\CorrelativoService;

test('el formulario publico responde sin necesidad de iniciar sesion e identifica al proveedor', function () {
    $empresa = Empresa::create([
        'name' => 'Tienda Digital Demo',
        'slug' => 'tienda-digital-demo',
        'razon_social' => 'Tienda Digital Demo S.A.C.',
        'ruc' => '20123456789',
        'direccion' => 'Av. Los Libertadores 456, San Isidro, Lima',
        'email' => 'reclamos@tiendademo.pe',
        'telefono' => '01-4455667',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Basico,
        'activo' => true,
    ]);

    $response = $this->get(route('libro.publico', ['slug' => $empresa->slug]));

    $response->assertOk();
    $response->assertSee('Tienda Digital Demo');
    $response->assertSee('20123456789');
    $response->assertSee('Av. Los Libertadores 456, San Isidro, Lima');
});

test('valida los datos minimos del consumidor y el formato del documento', function () {
    $empresa = Empresa::create([
        'name' => 'Comercio Validador',
        'slug' => 'comercio-validador',
        'razon_social' => 'Comercio Validador S.A.C.',
        'ruc' => '20987654321',
        'direccion' => 'Av. Pardo 123, Miraflores',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Basico,
        'activo' => true,
    ]);

    // DNI inválido (debe tener 8 dígitos)
    $response = $this->post(route('libro.registrar', ['slug' => $empresa->slug]), [
        'tipo_documento' => TipoDocumentoConsumidor::DNI->value,
        'numero_documento' => '123', // Incompleto
        'nombres' => 'Juan Carlos',
        'apellidos' => 'Perez Gonzales',
        'email' => 'juan@ejemplo.com',
        'telefono' => '987654321',
        'direccion' => 'Calle Las Begonias 123',
        'es_menor_edad' => false,
        'tipo_bien' => TipoBien::Producto->value,
        'descripcion_bien' => 'Televisor Smart 55 pulgadas',
        'tipo' => TipoReclamacion::Reclamo->value,
        'detalle_problema' => 'El televisor presenta fallas en la pantalla desde el primer día.',
        'pedido_consumidor' => 'Solicito el cambio inmediato por un equipo nuevo.',
        'aviso_privacidad_leido' => true,
    ]);

    $response->assertSessionHasErrors(['numero_documento']);
});

test('exige datos del representante legal cuando el consumidor es menor de edad', function () {
    $empresa = Empresa::create([
        'name' => 'Jugueteria Central',
        'slug' => 'jugueteria-central',
        'razon_social' => 'Jugueteria Central S.A.C.',
        'ruc' => '20456123789',
        'direccion' => 'Jr. de la Union 789, Lima',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Basico,
        'activo' => true,
    ]);

    // Menor de edad sin datos del apoderado
    $response = $this->post(route('libro.registrar', ['slug' => $empresa->slug]), [
        'tipo_documento' => TipoDocumentoConsumidor::DNI->value,
        'numero_documento' => '78945612',
        'nombres' => 'Mateo',
        'apellidos' => 'Flores',
        'email' => 'apoderado@ejemplo.com',
        'telefono' => '955443322',
        'direccion' => 'Calle Los Pinos 450',
        'es_menor_edad' => true,
        'apoderado_nombre' => '',
        'apoderado_tipo_documento' => null,
        'apoderado_numero_documento' => '',
        'tipo_bien' => TipoBien::Producto->value,
        'descripcion_bien' => 'Bicicleta montañera aro 24',
        'tipo' => TipoReclamacion::Reclamo->value,
        'detalle_problema' => 'Faltaron piezas para el ensamblaje de la bicicleta.',
        'pedido_consumidor' => 'Entrega de las piezas faltantes.',
        'aviso_privacidad_leido' => true,
    ]);

    $response->assertSessionHasErrors(['apoderado_nombre', 'apoderado_tipo_documento', 'apoderado_numero_documento']);
});

test('distingue reclamo y queja y exige hechos, pedido y aceptacion del aviso de privacidad', function () {
    $empresa = Empresa::create([
        'name' => 'Comercio Retail Norte',
        'slug' => 'comercio-retail-norte',
        'razon_social' => 'Retail Norte S.A.C.',
        'ruc' => '20556677889',
        'direccion' => 'Av. Larco 800, Trujillo',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Basico,
        'activo' => true,
    ]);

    // Intento de envío sin hechos, sin pedido concreto y sin confirmar aviso de privacidad
    $response = $this->post(route('libro.registrar', ['slug' => $empresa->slug]), [
        'tipo_documento' => TipoDocumentoConsumidor::DNI->value,
        'numero_documento' => '12345678',
        'nombres' => 'Carlos',
        'apellidos' => 'Benitez',
        'email' => 'carlos@ejemplo.com',
        'telefono' => '988776655',
        'direccion' => 'Calle Real 100',
        'es_menor_edad' => false,
        'tipo_bien' => TipoBien::Servicio->value,
        'descripcion_bien' => 'Servicio de entrega a domicilio',
        'tipo' => '', // Vacío
        'detalle_problema' => '', // Vacío
        'pedido_consumidor' => '', // Vacío
        'aviso_privacidad_leido' => false, // No aceptado
    ]);

    $response->assertSessionHasErrors(['tipo', 'detalle_problema', 'pedido_consumidor', 'aviso_privacidad_leido']);
});

test('registra formalmente la queja con hechos, pedido concreto y trazabilidad del aviso de privacidad', function () {
    $empresa = Empresa::create([
        'name' => 'Tienda Tech Soluciones',
        'slug' => 'tienda-tech-soluciones',
        'razon_social' => 'Tech Soluciones S.A.C.',
        'ruc' => '20789456123',
        'direccion' => 'Av. Javier Prado Este 2500, San Borja',
        'email' => 'contacto@techsoluciones.pe',
        'telefono' => '01-2244668',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Basico,
        'activo' => true,
    ]);

    $establecimiento = Establecimiento::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'EST-01',
        'nombre' => 'Sede Central San Borja',
        'direccion' => 'Av. Javier Prado Este 2500',
        'es_principal' => true,
        'activo' => true,
    ]);

    $payload = [
        'tipo_documento' => TipoDocumentoConsumidor::DNI->value,
        'numero_documento' => '44889900',
        'nombres' => 'Ana Lucia',
        'apellidos' => 'Mendoza Ramos',
        'email' => 'anamendoza@ejemplo.pe',
        'telefono' => '998877665',
        'direccion' => 'Calle Bellavista 320, Dpto 401',
        'departamento' => 'Lima',
        'provincia' => 'Lima',
        'distrito' => 'Miraflores',
        'es_menor_edad' => false,

        'establecimiento_id' => $establecimiento->id,
        'tipo_bien' => TipoBien::Servicio->value,
        'descripcion_bien' => 'Servicio de soporte tecnico remoto y mantenimiento preventivo.',
        'monto_reclamado' => 450.50,
        'moneda' => 'PEN',
        'numero_comprobante' => 'F001-000341',
        'fecha_contratacion' => '2026-10-15',

        // Clasificación formal como Queja (atención al público)
        'tipo' => TipoReclamacion::Queja->value,
        'detalle_problema' => 'El asesor telefónico asignado se negó reiteradamente a transferir la llamada y brindó un trato descortés durante la atención de la incidencia.',
        'pedido_consumidor' => 'Capacitación al personal en atención al cliente y una disculpa formal por escrito por la mala experiencia recibida.',
        'aviso_privacidad_leido' => true,
        'aviso_privacidad_version' => '1.0',
    ];

    $response = $this->post(route('libro.registrar', ['slug' => $empresa->slug]), $payload);

    $reclamacion = Reclamacion::where('empresa_id', $empresa->id)->first();
    expect($reclamacion)->not->toBeNull();

    $response->assertRedirect(route('libro.confirmacion', [
        'slug' => $empresa->slug,
        'token' => $reclamacion->token_consulta,
    ]));

    $this->assertDatabaseHas('reclamaciones', [
        'empresa_id' => $empresa->id,
        'establecimiento_id' => $establecimiento->id,
        'tipo' => TipoReclamacion::Queja->value,
        'tipo_bien' => TipoBien::Servicio->value,
        'monto_reclamado' => 450.50,
        'moneda' => 'PEN',
        'numero_comprobante' => 'F001-000341',
        'aviso_privacidad_version' => '1.0',
    ]);

    $reclamacion = Reclamacion::where('empresa_id', $empresa->id)->first();
    expect($reclamacion)->not->toBeNull();
    // Verificación de clasificación elegida sin reinterpretación automática
    expect($reclamacion->tipo)->toBe(TipoReclamacion::Queja);
    // Verificación de hechos y pedido concreto en campos separados
    expect($reclamacion->detalle_problema)->toBe('El asesor telefónico asignado se negó reiteradamente a transferir la llamada y brindó un trato descortés durante la atención de la incidencia.');
    expect($reclamacion->pedido_consumidor)->toBe('Capacitación al personal en atención al cliente y una disculpa formal por escrito por la mala experiencia recibida.');
    // Verificación de evidencia y trazabilidad del aviso de privacidad
    expect($reclamacion->aviso_privacidad_version)->toBe('1.0');
    expect($reclamacion->aviso_privacidad_aceptado_en)->not->toBeNull();
    expect($reclamacion->datos_legales_snapshot['razon_social'])->toBe('Tech Soluciones S.A.C.');
    expect($reclamacion->datos_consumidor['nombres'])->toBe('Ana Lucia');
    // Verificación de generación de número correlativo atómico oficial
    expect($reclamacion->numero_hoja)->toBe('EST-01-00000001-'.now()->year);
    // Verificación de generación de número correlativo atómico oficial
    expect($reclamacion->numero_hoja)->toBe('EST-01-00000001-'.now()->year);
});

test('rechaza envíos automatizados si el campo honeypot trampa es completado por un bot', function () {
    $empresa = Empresa::create([
        'name' => 'Comercio Seguridad',
        'slug' => 'comercio-seguridad',
        'razon_social' => 'Seguridad y Comercio S.A.C.',
        'ruc' => '20192837465',
        'direccion' => 'Av. Primavera 1020, Surco',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Basico,
        'activo' => true,
    ]);

    $response = $this->post(route('libro.registrar', ['slug' => $empresa->slug]), [
        'tipo_documento' => TipoDocumentoConsumidor::DNI->value,
        'numero_documento' => '72345678',
        'nombres' => 'Bot Automatizado',
        'apellidos' => 'Crawler Test',
        'email' => 'bot@crawler.com',
        'telefono' => '999888777',
        'direccion' => 'Calle Ficticia 123',
        'es_menor_edad' => false,
        'tipo_bien' => TipoBien::Producto->value,
        'descripcion_bien' => 'Producto de prueba',
        'tipo' => TipoReclamacion::Reclamo->value,
        'detalle_problema' => 'Detalle de prueba para simular ataque de bot.',
        'pedido_consumidor' => 'Pedido de prueba automatizado.',
        'aviso_privacidad_leido' => true,
        // Campo honeypot completado por el crawler o bot automatizado
        'sitio_web_hp' => 'http://spam-site-crawler.org',
    ]);

    $response->assertSessionHasErrors(['sitio_web_hp']);
    $this->assertDatabaseMissing('reclamaciones', [
        'empresa_id' => $empresa->id,
        'email' => 'bot@crawler.com',
    ]);
});

test('rechaza envíos que se procesen a una velocidad instantánea no humana', function () {
    $empresa = Empresa::create([
        'name' => 'Comercio Anti Spam',
        'slug' => 'comercio-anti-spam',
        'razon_social' => 'Anti Spam Digital S.A.C.',
        'ruc' => '20918273645',
        'direccion' => 'Av. Canaval y Moreyra 450, San Isidro',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Basico,
        'activo' => true,
    ]);

    // Token emitido exactamente en el mismo segundo (0 segundos transcurridos)
    $tokenInstantaneo = encrypt(now()->timestamp);

    $response = $this->post(route('libro.registrar', ['slug' => $empresa->slug]), [
        'tipo_documento' => TipoDocumentoConsumidor::DNI->value,
        'numero_documento' => '81726354',
        'nombres' => 'Mario',
        'apellidos' => 'Perez',
        'email' => 'mario.perez@ejemplo.pe',
        'telefono' => '987123456',
        'direccion' => 'Av. República de Panamá 234',
        'es_menor_edad' => false,
        'tipo_bien' => TipoBien::Servicio->value,
        'descripcion_bien' => 'Suscripción mensual a servicios digitales',
        'tipo' => TipoReclamacion::Reclamo->value,
        'detalle_problema' => 'Falla en la activación de la cuenta contratada.',
        'pedido_consumidor' => 'Activación inmediata del servicio.',
        'aviso_privacidad_leido' => true,
        'token_seguridad' => $tokenInstantaneo,
    ]);

    $response->assertSessionHasErrors(['token_seguridad']);
});

test('sanitiza el contenido contra etiquetas HTML o inyecciones de código malicioso', function () {
    $empresa = Empresa::create([
        'name' => 'Comercio Sanitizador',
        'slug' => 'comercio-sanitizador',
        'razon_social' => 'Sanitizador S.A.C.',
        'ruc' => '20601020304',
        'direccion' => 'Av. El Sol 450, Cusco',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Basico,
        'activo' => true,
    ]);

    // Token con tiempo suficiente transcurrido (5 segundos antes)
    $tokenValido = encrypt(now()->subSeconds(5)->timestamp);

    $payload = [
        'tipo_documento' => TipoDocumentoConsumidor::DNI->value,
        'numero_documento' => '45678901',
        'nombres' => 'Pedro <script>alert("xss")</script>',
        'apellidos' => 'Gomez <b>Negrita</b>',
        'email' => 'pedro@ejemplo.com',
        'telefono' => '912345678',
        'direccion' => 'Calle Nueva 123 <iframe src="evil.com"></iframe>',
        'es_menor_edad' => false,
        'tipo_bien' => TipoBien::Producto->value,
        'descripcion_bien' => 'Laptop Gamer <img src="x" onerror="alert(1)"> 16GB RAM',
        'tipo' => TipoReclamacion::Reclamo->value,
        'detalle_problema' => 'El teclado falló y presentó corto circuito <script>eval("bad")</script> al encender.',
        'pedido_consumidor' => 'Cambio de equipo <u>inmediato</u> sin costo adicional.',
        'aviso_privacidad_leido' => true,
        'token_seguridad' => $tokenValido,
    ];

    $response = $this->post(route('libro.registrar', ['slug' => $empresa->slug]), $payload);

    $reclamacion = Reclamacion::where('empresa_id', $empresa->id)->first();
    expect($reclamacion)->not->toBeNull();

    $response->assertRedirect(route('libro.confirmacion', [
        'slug' => $empresa->slug,
        'token' => $reclamacion->token_consulta,
    ]));
    // Comprobar que ninguna etiqueta HTML sobrevivió a la sanitización
    expect($reclamacion->datos_consumidor['nombres'])->toBe('Pedro alert("xss")');
    expect($reclamacion->datos_consumidor['apellidos'])->toBe('Gomez Negrita');
    expect($reclamacion->descripcion_bien)->not->toContain('<img');
    expect($reclamacion->detalle_problema)->not->toContain('<script>');
    expect($reclamacion->pedido_consumidor)->not->toContain('<u>');
});

test('genera correlativos atómicos consecutivos sin duplicidad para la misma empresa', function () {
    $empresa = Empresa::create([
        'name' => 'Empresa Correlativa',
        'slug' => 'empresa-correlativa',
        'razon_social' => 'Correlativos S.A.C.',
        'ruc' => '20778899001',
        'direccion' => 'Av. República 500, San Isidro',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Basico,
        'activo' => true,
    ]);

    $correlativoService = app(CorrelativoService::class);
    $anioActual = (int) now()->year;

    $num1 = $correlativoService->generarProximoNumero($empresa);
    $num2 = $correlativoService->generarProximoNumero($empresa);
    $num3 = $correlativoService->generarProximoNumero($empresa);

    expect($num1)->toBe(sprintf('00000001-%d', $anioActual));
    expect($num2)->toBe(sprintf('00000002-%d', $anioActual));
    expect($num3)->toBe(sprintf('00000003-%d', $anioActual));
});

test('persiste el registro inmutable de origen y genera el primer evento en la tabla de auditoria', function () {
    $empresa = Empresa::create([
        'name' => 'Empresa Inmutable',
        'slug' => 'empresa-inmutable',
        'razon_social' => 'Inmutable Perú S.A.C.',
        'ruc' => '20889977665',
        'direccion' => 'Av. Brasil 1420, Jesús María',
        'email' => 'legal@inmutable.pe',
        'telefono' => '01-3322110',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Basico,
        'activo' => true,
    ]);

    $tokenValido = encrypt(now()->subSeconds(10)->timestamp);

    $payload = [
        'tipo_documento' => TipoDocumentoConsumidor::DNI->value,
        'numero_documento' => '10293847',
        'nombres' => 'Valeria',
        'apellidos' => 'Sotomayor',
        'email' => 'valeria@ejemplo.com',
        'telefono' => '944556677',
        'direccion' => 'Av. Salaverry 890',
        'es_menor_edad' => false,
        'tipo_bien' => TipoBien::Servicio->value,
        'descripcion_bien' => 'Servicio de consultoría contable',
        'tipo' => TipoReclamacion::Reclamo->value,
        'detalle_problema' => 'No se emitieron las declaraciones mensuales a tiempo generando recargos.',
        'pedido_consumidor' => 'Reembolso de la penalidad cobrada y regularización inmediata.',
        'aviso_privacidad_leido' => true,
        'token_seguridad' => $tokenValido,
    ];

    $response = $this->post(route('libro.registrar', ['slug' => $empresa->slug]), $payload);

    $reclamacion = Reclamacion::where('empresa_id', $empresa->id)->first();
    expect($reclamacion)->not->toBeNull();

    $response->assertRedirect(route('libro.confirmacion', [
        'slug' => $empresa->slug,
        'token' => $reclamacion->token_consulta,
    ]));

    // Verificación de existencia del evento inicial inmutable
    $this->assertDatabaseHas('reclamacion_eventos', [
        'empresa_id' => $empresa->id,
        'reclamacion_id' => $reclamacion->id,
        'accion' => 'presentacion_inicial',
        'actor_tipo' => 'consumidor',
    ]);

    // Comprobar la existencia del evento inicial en la relación del modelo
    $eventoInicial = $reclamacion->eventos()->where('accion', 'presentacion_inicial')->first();
    expect($eventoInicial)->not->toBeNull();

    expect($eventoInicial->accion)->toBe('presentacion_inicial');
    expect($eventoInicial->datos_despues['numero_hoja'])->toBe($reclamacion->numero_hoja);
    expect($eventoInicial->datos_despues['detalle_problema'])->toBe($reclamacion->detalle_problema);
    expect($eventoInicial->datos_despues['proveedor_snapshot']['razon_social'])->toBe('Inmutable Perú S.A.C.');
});

test('aplica limitacion de tasa rate limiting para bloquear intentos masivos continuos', function () {
    $empresa = Empresa::create([
        'name' => 'Empresa Rate Limit',
        'slug' => 'empresa-rate-limit',
        'razon_social' => 'Rate Limit S.A.C.',
        'ruc' => '20112233445',
        'direccion' => 'Av. Arequipa 1200, Lince',
        'estado' => EstadoEmpresa::Activa,
        'plan' => PlanEmpresa::Basico,
        'activo' => true,
    ]);

    $tokenValido = encrypt(now()->subSeconds(10)->timestamp);

    $payloadBase = [
        'tipo_documento' => TipoDocumentoConsumidor::DNI->value,
        'numero_documento' => '12345678',
        'nombres' => 'Usuario Test',
        'apellidos' => 'Limit',
        'email' => 'limit@ejemplo.com',
        'telefono' => '999111222',
        'direccion' => 'Calle Falsa 123',
        'es_menor_edad' => false,
        'tipo_bien' => TipoBien::Producto->value,
        'descripcion_bien' => 'Producto de prueba',
        'tipo' => TipoReclamacion::Reclamo->value,
        'detalle_problema' => 'Problema recurrente para probar el limitador de tasa.',
        'pedido_consumidor' => 'Solución para la prueba.',
        'aviso_privacidad_leido' => true,
        'token_seguridad' => $tokenValido,
    ];

    // Realizar 5 peticiones permitidas por el limitador
    for ($i = 0; $i < 5; $i++) {
        $this->post(route('libro.registrar', ['slug' => $empresa->slug]), $payloadBase);
    }

    // La 6ta petición dentro del mismo minuto debe recibir código 429 Too Many Requests
    $responseExceso = $this->post(route('libro.registrar', ['slug' => $empresa->slug]), $payloadBase);
    $responseExceso->assertStatus(429);
});
