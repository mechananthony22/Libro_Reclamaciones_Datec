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
