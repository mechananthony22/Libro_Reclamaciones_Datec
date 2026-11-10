<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\EmpresaSlugHistorial;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Servicio encargado de la reconfiguración segura de slugs e identificadores de empresas,
 * asegurando la prevención de colisiones, reserva permanente de URLs previas
 * y soporte para redirecciones automáticas HTTP 301.
 */
class SlugReconfigurationService
{
    /**
     * Lista de palabras y prefijos reservados por la plataforma para evitar conflictos de enrutamiento.
     *
     * @var array<int, string>
     */
    public const PALABRAS_RESERVADAS = [
        'admin',
        'api',
        'auditoria',
        'auth',
        'cambiar-empresa',
        'configuracion',
        'contingencia',
        'dashboard',
        'empresa',
        'empresas',
        'expedientes',
        'invitacion',
        'l',
        'login',
        'logout',
        'monitoreo',
        'notificaciones',
        'personalizacion',
        'publico',
        'reclamaciones',
        'soporte',
        'superadmin',
        'usuarios',
    ];

    public function __construct(
        protected AuditoriaService $auditoriaService
    ) {}

    /**
     * Valida si un slug cumple con el formato técnico reglamentario.
     */
    public function esSlugValido(string $slug): bool
    {
        $slug = trim(strtolower($slug));

        if (strlen($slug) < 3 || strlen($slug) > 100) {
            return false;
        }

        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            return false;
        }

        if (in_array($slug, self::PALABRAS_RESERVADAS, true)) {
            return false;
        }

        return true;
    }

    /**
     * Comprueba si un slug se encuentra libre y no colisiona con empresas activas
     * ni con slugs históricos en cuarentena de otros proveedores.
     */
    public function esSlugDisponible(string $slug, ?int $ignorarEmpresaId = null): bool
    {
        $slug = Str::slug(trim($slug));

        if (! $this->esSlugValido($slug)) {
            return false;
        }

        // 1. Verificar si ya está en uso como slug actual de otra empresa
        $existeEnTenants = Empresa::query()
            ->where('slug', $slug)
            ->when($ignorarEmpresaId, fn ($q) => $q->where('id', '!=', $ignorarEmpresaId))
            ->exists();

        if ($existeEnTenants) {
            return false;
        }

        // 2. Verificar si está reservado en el historial de redirecciones de otra empresa
        $existeEnHistorial = EmpresaSlugHistorial::query()
            ->where('slug_antiguo', $slug)
            ->when($ignorarEmpresaId, fn ($q) => $q->where('empresa_id', '!=', $ignorarEmpresaId))
            ->exists();

        if ($existeEnHistorial) {
            return false;
        }

        return true;
    }

    /**
     * Ejecuta el cambio de slug de manera atómica, resguardando el slug anterior
     * en el historial para activar redirecciones automáticas 301.
     *
     * @throws ValidationException
     */
    public function cambiarSlug(
        Empresa $empresa,
        string $nuevoSlug,
        ?string $motivo = null,
        ?User $actor = null
    ): bool {
        $nuevoSlug = Str::slug(trim($nuevoSlug));
        $slugAnterior = $empresa->slug;

        if ($nuevoSlug === $slugAnterior) {
            return true;
        }

        if (! $this->esSlugValido($nuevoSlug)) {
            throw ValidationException::withMessages([
                'slug' => 'El enlace permanente ingresado no es válido o utiliza una palabra reservada del sistema.',
            ]);
        }

        if (! $this->esSlugDisponible($nuevoSlug, $empresa->id)) {
            throw ValidationException::withMessages([
                'slug' => 'El enlace permanente ingresado ya se encuentra en uso o reservado para redirección por otra empresa.',
            ]);
        }

        DB::transaction(function () use ($empresa, $slugAnterior, $nuevoSlug, $motivo, $actor) {
            // Guardar el slug anterior en el historial inmutable
            EmpresaSlugHistorial::create([
                'empresa_id' => $empresa->id,
                'slug_antiguo' => $slugAnterior,
                'motivo' => $motivo ?? 'Reconfiguración formal de identificador permanente',
                'cambiado_por_id' => $actor?->id,
            ]);

            // Actualizar el slug principal del tenant
            $empresa->update([
                'slug' => $nuevoSlug,
            ]);

            // Asentar en la bitácora legal de auditoría
            $this->auditoriaService->registrar(
                accion: 'reconfigurar_slug',
                modulo: 'identidad',
                descripcion: "Se reconfiguró el enlace permanente de la empresa de '{$slugAnterior}' a '{$nuevoSlug}'. La dirección anterior mantendrá redirección 301 permanente.",
                opciones: [
                    'empresa_id' => $empresa->id,
                    'user' => $actor,
                    'datos_antes' => ['slug' => $slugAnterior],
                    'datos_despues' => [
                        'slug' => $nuevoSlug,
                        'motivo' => $motivo,
                    ],
                ]
            );
        });

        return true;
    }

    /**
     * Resuelve si un slug corresponde a una dirección histórica y devuelve la empresa destino para redirección 301.
     */
    public function obtenerRedireccionSiExiste(string $slug): ?Empresa
    {
        $registro = EmpresaSlugHistorial::query()
            ->where('slug_antiguo', $slug)
            ->latest('id')
            ->with('empresa')
            ->first();

        return $registro?->empresa;
    }
}
