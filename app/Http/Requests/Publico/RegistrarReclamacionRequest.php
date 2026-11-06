<?php

namespace App\Http\Requests\Publico;

use App\Enums\TipoBien;
use App\Enums\TipoDocumentoConsumidor;
use App\Enums\TipoReclamacion;
use App\Models\Empresa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RegistrarReclamacionRequest extends FormRequest
{
    /**
     * Determina si el usuario tiene autorización para realizar la solicitud.
     * Al ser un formulario público de libre acceso, se autoriza a cualquier visitante.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Sanitización previa de los campos de entrada para neutralizar cargas maliciosas,
     * secuencias de inyección HTML/scripts y caracteres de control invisibles.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'nombres' => $this->sanitizarTexto($this->input('nombres')),
            'apellidos' => $this->sanitizarTexto($this->input('apellidos')),
            'direccion' => $this->sanitizarTexto($this->input('direccion')),
            'descripcion_bien' => $this->sanitizarTexto($this->input('descripcion_bien')),
            'detalle_problema' => $this->sanitizarTexto($this->input('detalle_problema')),
            'pedido_consumidor' => $this->sanitizarTexto($this->input('pedido_consumidor')),
        ]);
    }

    /**
     * Limpia cadenas de texto removiendo etiquetas y caracteres de control no imprimibles.
     */
    protected function sanitizarTexto(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $limpio = strip_tags($valor);

        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $limpio);
    }

    /**
     * Reglas de validación aplicables a los datos de la reclamación y del consumidor.
     */
    public function rules(): array
    {
        return [
            // Medidas de protección anti-bot no intrusivas y accesibles
            'sitio_web_hp' => ['nullable', 'max:0'],
            'token_seguridad' => ['nullable', 'string'],

            // Identificación y datos obligatorios del consumidor reclamante
            'tipo_documento' => ['required', Rule::enum(TipoDocumentoConsumidor::class)],
            'numero_documento' => ['required', 'string', 'max:20'],
            'nombres' => ['required', 'string', 'max:150'],
            'apellidos' => ['nullable', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            'telefono' => ['required', 'string', 'max:30'],
            'direccion' => ['required', 'string', 'max:255'],
            'departamento' => ['nullable', 'string', 'max:100'],
            'provincia' => ['nullable', 'string', 'max:100'],
            'distrito' => ['nullable', 'string', 'max:100'],

            // Datos del apoderado o tutor legal en caso de que el consumidor sea menor de edad
            'es_menor_edad' => ['required', 'boolean'],
            'apoderado_nombre' => ['nullable', 'required_if:es_menor_edad,true', 'string', 'max:255'],
            'apoderado_tipo_documento' => ['nullable', 'required_if:es_menor_edad,true', Rule::enum(TipoDocumentoConsumidor::class)],
            'apoderado_numero_documento' => ['nullable', 'required_if:es_menor_edad,true', 'string', 'max:20'],
            'apoderado_telefono' => ['nullable', 'string', 'max:30'],
            'apoderado_email' => ['nullable', 'email', 'max:255'],

            // Identificación y características del bien o servicio materia del reclamo
            'establecimiento_id' => ['nullable', 'integer'],
            'tipo_bien' => ['required', Rule::enum(TipoBien::class)],
            'descripcion_bien' => ['required', 'string', 'min:5', 'max:1500'],
            'monto_reclamado' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'moneda' => ['nullable', 'required_with:monto_reclamado', 'in:PEN,USD'],
            'numero_comprobante' => ['nullable', 'string', 'max:100'],
            'fecha_contratacion' => ['nullable', 'date', 'before_or_equal:today'],

            // Clasificación formal del reclamo o queja
            'tipo' => ['required', Rule::enum(TipoReclamacion::class)],

            // Hechos ocurridos y pretensión o pedido concreto del consumidor
            'detalle_problema' => ['required', 'string', 'min:10', 'max:3000'],
            'pedido_consumidor' => ['required', 'string', 'min:5', 'max:1000'],

            // Conformidad y toma de conocimiento del aviso legal de tratamiento de datos personales
            'aviso_privacidad_leido' => ['accepted'],
            'aviso_privacidad_version' => ['nullable', 'string', 'max:20'],

            // Archivos adjuntos y evidencias del consumidor (Opcionales, máx 3 archivos de 5MB c/u)
            'adjuntos' => ['nullable', 'array', 'max:3'],
            'adjuntos.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    /**
     * Validaciones complementarias personalizadas según las especificaciones de INDECOPI.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                // Detección y bloqueo no intrusivo de robots mediante campo trampa (honeypot)
                if (! empty($this->input('sitio_web_hp'))) {
                    $validator->errors()->add('sitio_web_hp', 'Se ha detectado una sumisión automatizada no permitida.');
                }

                // Control de tiempo mínimo de llenado del formulario para prevenir envíos automatizados instantáneos
                $tokenSeguridad = $this->input('token_seguridad');
                if ($tokenSeguridad) {
                    try {
                        $emisionTimestamp = (int) decrypt($tokenSeguridad);
                        $tiempoTranscurrido = now()->timestamp - $emisionTimestamp;
                        if ($tiempoTranscurrido < 2) {
                            $validator->errors()->add('token_seguridad', 'La solicitud se procesó con excesiva velocidad. Por favor, revisa tus datos antes de enviar.');
                        }
                    } catch (\Throwable $e) {
                        $validator->errors()->add('token_seguridad', 'El identificador de seguridad del formulario no es válido.');
                    }
                }

                // Detección y bloqueo no intrusivo de robots mediante campo trampa (honeypot)
                if (! empty($this->input('sitio_web_hp'))) {
                    $validator->errors()->add('sitio_web_hp', 'Se ha detectado una sumisión automatizada no permitida.');
                }

                // Control de tiempo mínimo de llenado del formulario para prevenir envíos automatizados instantáneos
                $tokenSeguridad = $this->input('token_seguridad');
                if ($tokenSeguridad) {
                    try {
                        $emisionTimestamp = (int) decrypt($tokenSeguridad);
                        $tiempoTranscurrido = now()->timestamp - $emisionTimestamp;
                        if ($tiempoTranscurrido < 2) {
                            $validator->errors()->add('token_seguridad', 'La solicitud se procesó con excesiva velocidad. Por favor, revisa tus datos antes de enviar.');
                        }
                    } catch (\Throwable $e) {
                        $validator->errors()->add('token_seguridad', 'El identificador de seguridad del formulario no es válido.');
                    }
                }

                $tipoDoc = $this->input('tipo_documento');
                $numeroDoc = trim((string) $this->input('numero_documento'));

                // Validación de longitud y formato numérico para DNI (8 dígitos)
                if ($tipoDoc === TipoDocumentoConsumidor::DNI->value && ! preg_match('/^[0-9]{8}$/', $numeroDoc)) {
                    $validator->errors()->add('numero_documento', 'El DNI debe contener exactamente 8 dígitos numéricos.');
                }

                // Validación de longitud y formato numérico para RUC (11 dígitos)
                if ($tipoDoc === TipoDocumentoConsumidor::RUC->value && ! preg_match('/^[0-9]{11}$/', $numeroDoc)) {
                    $validator->errors()->add('numero_documento', 'El RUC debe contener exactamente 11 dígitos numéricos.');
                }

                // Validación de formato alfanumérico para Carné de Extranjería (8 a 12 caracteres)
                if ($tipoDoc === TipoDocumentoConsumidor::CarnetExtranjeria->value && ! preg_match('/^[a-zA-Z0-9]{8,12}$/', $numeroDoc)) {
                    $validator->errors()->add('numero_documento', 'El Carné de Extranjería debe contener entre 8 y 12 caracteres alfanuméricos.');
                }

                // Validación de formato para Pasaporte (6 a 12 caracteres alfanuméricos)
                if ($tipoDoc === TipoDocumentoConsumidor::Pasaporte->value && ! preg_match('/^[a-zA-Z0-9]{6,12}$/', $numeroDoc)) {
                    $validator->errors()->add('numero_documento', 'El Pasaporte debe tener entre 6 y 12 caracteres alfanuméricos.');
                }

                // Validación de formato del documento del representante cuando es menor de edad
                if ($this->boolean('es_menor_edad')) {
                    $apoderadoTipo = $this->input('apoderado_tipo_documento');
                    $apoderadoNum = trim((string) $this->input('apoderado_numero_documento'));

                    if ($apoderadoTipo === TipoDocumentoConsumidor::DNI->value && ! preg_match('/^[0-9]{8}$/', $apoderadoNum)) {
                        $validator->errors()->add('apoderado_numero_documento', 'El DNI del apoderado debe contener 8 dígitos numéricos.');
                    }
                }

                // Validación de pertenencia del establecimiento a la empresa activa (protección de integridad)
                $establecimientoId = $this->input('establecimiento_id');
                if (! empty($establecimientoId)) {
                    $empresa = Empresa::current();
                    if ($empresa) {
                        $pertenece = $empresa->establecimientos()->where('id', $establecimientoId)->where('activo', true)->exists();
                        if (! $pertenece) {
                            $validator->errors()->add('establecimiento_id', 'El establecimiento seleccionado no es válido para esta empresa.');
                        }
                    }
                }
            },
        ];
    }

    /**
     * Mensajes descriptivos en español para los errores de validación.
     */
    public function messages(): array
    {
        return [
            'tipo_documento.required' => 'Selecciona un tipo de documento de identidad.',
            'numero_documento.required' => 'Ingresa tu número de documento.',
            'nombres.required' => 'El campo nombres es obligatorio.',
            'email.required' => 'El correo electrónico es indispensable para remitir la constancia de reclamación.',
            'email.email' => 'El formato del correo electrónico no es válido.',
            'telefono.required' => 'El teléfono de contacto es obligatorio.',
            'direccion.required' => 'La dirección de domicilio es requerida por la normativa vigente.',
            'apoderado_nombre.required_if' => 'Debes ingresar los nombres completos del padre, madre o apoderado.',
            'apoderado_tipo_documento.required_if' => 'Selecciona el tipo de documento del apoderado.',
            'apoderado_numero_documento.required_if' => 'Ingresa el número de documento del apoderado.',
            'tipo_bien.required' => 'Indica si tu reclamo o queja corresponde a un Producto o Servicio.',
            'descripcion_bien.required' => 'Ingresa el detalle o descripción del producto o servicio contratado.',
            'moneda.required_with' => 'Indica la moneda correspondiente al monto reclamado.',
            'fecha_contratacion.before_or_equal' => 'La fecha de compra o contratación no puede ser futura.',
            'tipo.required' => 'Debes seleccionar si tu presentación corresponde a un Reclamo o a una Queja.',
            'detalle_problema.required' => 'Debes describir los hechos y el detalle del problema acontecido.',
            'detalle_problema.min' => 'El detalle del problema debe contener al menos 10 caracteres.',
            'detalle_problema.max' => 'El detalle del problema no puede superar los 3000 caracteres.',
            'pedido_consumidor.required' => 'Debes especificar tu pedido o la solución concreta que solicitas.',
            'pedido_consumidor.min' => 'El pedido concreto debe contener al menos 5 caracteres.',
            'pedido_consumidor.max' => 'El pedido concreto no puede superar los 1000 caracteres.',
            'aviso_privacidad_leido.accepted' => 'Debes confirmar la lectura y toma de conocimiento del aviso de privacidad y protección de datos.',
            'adjuntos.max' => 'Solo puedes adjuntar un máximo de 3 archivos de evidencia.',
            'adjuntos.*.mimes' => 'Los archivos adjuntos deben estar en formato PDF, JPG, PNG o WEBP.',
            'adjuntos.*.max' => 'Cada archivo adjunto no debe exceder los 5 MB de tamaño.',
        ];
    }
}
