<?php

namespace App\Models;

use App\Enums\EstadoEmpresa;
use App\Enums\EstadoSuscripcion;
use App\Enums\PlanEmpresa;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Models\Tenant;

class Empresa extends Tenant
{
    protected $table = 'tenants';

    protected $fillable = [
        'name',
        'slug',
        'razon_social',
        'ruc',
        'direccion',
        'email',
        'telefono',
        'representante_nombre',
        'representante_email',
        'representante_telefono',
        'logo_path',
        'color_primario',
        'activo',
        'estado',
        'motivo_cambio_estado',
        'fecha_cambio_estado',
        'plan',
        'estado_suscripcion',
        'vigencia_desde',
        'vigencia_hasta',
        'limite_reclamaciones_mes',
        'emails_notificaciones',
        'notificar_nuevo_reclamo',
        'notificar_vencimiento_preventivo',
        'notificar_vencido',
        'dias_anticipacion_alerta',
        'en_contingencia',
        'contingencia_mensaje',
        'contingencia_canal_alternativo',
        'contingencia_activada_en',
        'contingencia_estimada_fin',
        'fecha_baja',
        'motivo_baja',
        'retencion_hasta',
        'salida_autorizada_por_id',
        'paquete_salida_generado_en',
        'paquete_salida_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'estado' => EstadoEmpresa::class,
            'plan' => PlanEmpresa::class,
            'estado_suscripcion' => EstadoSuscripcion::class,
            'fecha_cambio_estado' => 'datetime',
            'vigencia_desde' => 'date',
            'vigencia_hasta' => 'date',
            'limite_reclamaciones_mes' => 'integer',
            'emails_notificaciones' => 'array',
            'notificar_nuevo_reclamo' => 'boolean',
            'notificar_vencimiento_preventivo' => 'boolean',
            'notificar_vencido' => 'boolean',
            'dias_anticipacion_alerta' => 'integer',
            'en_contingencia' => 'boolean',
            'contingencia_canal_alternativo' => 'array',
            'contingencia_activada_en' => 'datetime',
            'contingencia_estimada_fin' => 'datetime',
            'fecha_baja' => 'datetime',
            'retencion_hasta' => 'date',
            'paquete_salida_generado_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'empresa_usuario', 'empresa_id', 'user_id')
            ->withPivot('rol', 'activo')
            ->withTimestamps();
    }

    /**
     * @return HasMany<InvitacionEmpresa, $this>
     */
    public function invitaciones(): HasMany
    {
        return $this->hasMany(InvitacionEmpresa::class, 'empresa_id');
    }

    /**
     * @return HasMany<Establecimiento, $this>
     */
    public function establecimientos(): HasMany
    {
        return $this->hasMany(Establecimiento::class, 'empresa_id');
    }

    /**
     * @return HasMany<SoporteAutorizacion, $this>
     */
    public function soporteAutorizaciones(): HasMany
    {
        return $this->hasMany(SoporteAutorizacion::class, 'empresa_id');
    }

    /**
     * @return HasMany<ContingenciaIncidente, $this>
     */
    public function contingenciaIncidentes(): HasMany
    {
        return $this->hasMany(ContingenciaIncidente::class, 'empresa_id');
    }

    /**
     * @return HasMany<EmpresaSlugHistorial, $this>
     */
    public function slugsHistoricos(): HasMany
    {
        return $this->hasMany(EmpresaSlugHistorial::class, 'empresa_id');
    }

    /**
     * Usuario de Datec que autorizó o procesó la salida de la empresa.
     */
    public function salidaAutorizadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salida_autorizada_por_id');
    }

    /**
     * Determina si la empresa se encuentra bajo modo de contingencia activo.
     */
    public function estaEnContingencia(): bool
    {
        return (bool) $this->en_contingencia;
    }

    /**
     * Obtiene los canales alternativos de atención ante contingencias.
     *
     * @return array{email: ?string, telefono: ?string, direccion_fisica: ?string, horario: ?string}
     */
    public function canalesContingencia(): array
    {
        $canales = is_array($this->contingencia_canal_alternativo) ? $this->contingencia_canal_alternativo : [];

        return [
            'email' => $canales['email'] ?? $this->email,
            'telefono' => $canales['telefono'] ?? $this->telefono,
            'direccion_fisica' => $canales['direccion_fisica'] ?? $this->direccion,
            'horario' => $canales['horario'] ?? 'Lunes a Viernes de 09:00 a 18:00',
        ];
    }

    public function isBajaProgramada(): bool
    {
        return $this->estado === EstadoEmpresa::BajaProgramada;
    }

    /**
     * Determina si la empresa se encuentra en periodo de custodia legal de pruebas posterior a su baja.
     */
    public function estaBajoRetencionLegal(): bool
    {
        return $this->isBajaProgramada() && (! $this->retencion_hasta || $this->retencion_hasta->isFuture());
    }

    /**
     * Verifica si existe una autorización activa de acceso delegado para soporte técnico.
     */
    public function tieneAccesoDelegadoActivo(): bool
    {
        return $this->soporteAutorizaciones()
            ->where('estado', 'autorizado')
            ->where('valido_hasta', '>', now())
            ->exists();
    }

    /**
     * Obtiene la autorización de soporte técnico vigente actualmente, si existe.
     */
    public function autorizacionSoporteActiva(): ?SoporteAutorizacion
    {
        return $this->soporteAutorizaciones()
            ->where('estado', 'autorizado')
            ->where('valido_hasta', '>', now())
            ->latest('autorizado_en')
            ->first();
    }

    /**
     * Recopila todos los destinatarios configurados para notificaciones de la empresa.
     *
     * @return array<string>
     */
    public function destinatariosNotificaciones(): array
    {
        $emails = [];

        // 1. Correos adicionales configurados específicamente para alertas
        if (! empty($this->emails_notificaciones) && is_array($this->emails_notificaciones)) {
            $emails = array_merge($emails, $this->emails_notificaciones);
        }

        // 2. Correo oficial de la empresa
        if ($this->email) {
            $emails[] = $this->email;
        }

        // 3. Correo del representante legal / operativo
        if ($this->representante_email) {
            $emails[] = $this->representante_email;
        }

        return array_values(array_unique(array_filter($emails, fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))));
    }

    public function isActiva(): bool
    {
        return $this->activo && $this->estado === EstadoEmpresa::Activa;
    }

    public function isSuspendida(): bool
    {
        return $this->estado === EstadoEmpresa::Suspendida;
    }

    public function isBorrador(): bool
    {
        return $this->estado === EstadoEmpresa::Borrador;
    }

    /**
     * Valida si la empresa cumple los requisitos legales mínimos para habilitar el Libro
     */
    public function tieneMinimosLegales(): bool
    {
        $rucValido = ! empty($this->ruc) && strlen($this->ruc) === 11 && (str_starts_with($this->ruc, '10') || str_starts_with($this->ruc, '20'));
        $razonSocialValida = ! empty(trim($this->razon_social ?? ''));
        $direccionValida = ! empty(trim($this->direccion ?? ''));
        $contactoValido = ! empty(trim($this->email ?? '')) || ! empty(trim($this->telefono ?? ''));

        return $rucValido && $razonSocialValida && $direccionValida && $contactoValido;
    }

    /**
     * Devuelve el checklist de cumplimiento para la interfaz y auditoría.
     *
     * @return array<string, array{titulo: string, cumplido: bool, detalle: string}>
     */
    public function checklistMinimosLegales(): array
    {
        $rucValido = ! empty($this->ruc) && strlen($this->ruc) === 11 && (str_starts_with($this->ruc, '10') || str_starts_with($this->ruc, '20'));
        $razonSocialValida = ! empty(trim($this->razon_social ?? ''));
        $direccionValida = ! empty(trim($this->direccion ?? ''));
        $emailValido = ! empty(trim($this->email ?? '')) && filter_var($this->email, FILTER_VALIDATE_EMAIL);

        return [
            'ruc' => [
                'titulo' => 'RUC válido (11 dígitos, SUNAT)',
                'cumplido' => $rucValido,
                'detalle' => $rucValido ? "RUC {$this->ruc} registrado" : 'Pendiente o formato no válido',
            ],
            'razon_social' => [
                'titulo' => 'Razón Social oficial',
                'cumplido' => $razonSocialValida,
                'detalle' => $razonSocialValida ? $this->razon_social : 'Pendiente de registrar',
            ],
            'direccion' => [
                'titulo' => 'Domicilio Legal / Fiscal',
                'cumplido' => $direccionValida,
                'detalle' => $direccionValida ? $this->direccion : 'Pendiente de registrar',
            ],
            'email' => [
                'titulo' => 'Correo de notificación de reclamaciones',
                'cumplido' => (bool) $emailValido,
                'detalle' => $emailValido ? (string) $this->email : 'Requerido para recepción de avisos',
            ],
        ];
    }
}
