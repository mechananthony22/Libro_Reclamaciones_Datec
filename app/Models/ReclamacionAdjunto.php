<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo representativo de un archivo o evidencia digital adjuntada a una reclamación.
 * Gestiona el almacenamiento seguro y el aislamiento multitenant estricto.
 */
class ReclamacionAdjunto extends Model
{
    use BelongsToEmpresa;
    use HasFactory;

    protected $table = 'reclamacion_adjuntos';

    protected $fillable = [
        'empresa_id',
        'reclamacion_id',
        'nombre_original',
        'storage_path',
        'mime_type',
        'tamano_bytes',
        'hash_sha256',
        'origen',
    ];

    /**
     * Reclamación a la que pertenece el archivo adjunto.
     */
    public function reclamacion(): BelongsTo
    {
        return $this->belongsTo(Reclamacion::class, 'reclamacion_id');
    }

    /**
     * Empresa o tenant propietario del expediente y del archivo.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}
