<?php

namespace App\Models;

use App\Enums\RolEmpresa;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class InvitacionEmpresa extends Model
{
    protected $table = 'invitaciones_empresa';

    protected $fillable = [
        'empresa_id',
        'email',
        'nombre',
        'rol',
        'token',
        'expires_at',
        'accepted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    /**
     * Accesor y mutador seguro para el rol de invitación.
     */
    protected function rol(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => RolEmpresa::tryFrom((string) $value) ?? RolEmpresa::AdminEmpresa,
            set: fn ($value) => $value instanceof RolEmpresa ? $value->value : (string) ($value ?: RolEmpresa::AdminEmpresa->value),
        );
    }

    /**
     * @return BelongsTo<Empresa, $this>
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public static function generarPara(Empresa $empresa, string $email, ?string $nombre = null, RolEmpresa $rol = RolEmpresa::AdminEmpresa, int $horasExpiracion = 72): self
    {
        return self::create([
            'empresa_id' => $empresa->id,
            'email' => strtolower(trim($email)),
            'nombre' => $nombre ? trim($nombre) : null,
            'rol' => $rol,
            'token' => Str::random(48),
            'expires_at' => now()->addHours($horasExpiracion),
        ]);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    public function isValid(): bool
    {
        return ! $this->isExpired() && ! $this->isAccepted();
    }
}
