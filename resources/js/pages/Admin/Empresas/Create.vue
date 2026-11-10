<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface Props {
    estados: Array<{ value: string; label: string }>;
    planes: Array<{ value: string; label: string }>;
}

const props = defineProps<Props>();

const form = useForm({
    name: '',
    razon_social: '',
    ruc: '',
    slug: '',
    direccion: '',
    email: '',
    telefono: '',
    representante_nombre: '',
    representante_email: '',
    representante_telefono: '',
    color_primario: '#2563EB',
    plan: 'corporativo',
    estado: 'borrador',
    vigencia_desde: new Date().toISOString().split('T')[0],
    vigencia_hasta: '',
    limite_reclamaciones_mes: '',
});

function submit() {
    form.post('/admin/empresas');
}

// Clases de presentación compartidas por los campos del formulario
const claseEtiqueta = 'mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500';
const claseCampo =
    'w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500';
</script>

<template>
    <AdminLayout>
        <Head title="Nueva Empresa - Datec Libro" />

        <div class="mx-auto max-w-4xl space-y-6">
            <!-- Cabecera de Página -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex items-start gap-4">
                    <div class="hidden sm:flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-md shadow-blue-500/25">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            Empresas Clientes · Nuevo registro
                        </div>
                        <h1 class="mt-0.5 text-2xl font-extrabold tracking-tight text-slate-900">
                            Registrar Nueva Empresa
                        </h1>
                        <p class="mt-1 text-sm text-slate-500">
                            Crea una organización cliente con sus datos legales, plan y configuración inicial.
                        </p>
                    </div>
                </div>

                <Link
                    href="/admin/empresas"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Volver al listado</span>
                </Link>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Identificación legal y comercial -->
                <div class="space-y-4 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-2xs">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Identificación Legal y Comercial</h2>
                            <p class="text-xs text-slate-500">Datos tributarios, enlace público y medios de contacto.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label :class="claseEtiqueta">Nombre Comercial / Nombre de Fantasía *</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="Ej. Tienda ABC"
                                :class="claseCampo"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-[11px] text-rose-600">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label :class="claseEtiqueta">Razón Social *</label>
                            <input
                                v-model="form.razon_social"
                                type="text"
                                required
                                placeholder="Ej. ABC RETAIL S.A.C."
                                :class="claseCampo"
                            />
                            <p v-if="form.errors.razon_social" class="mt-1 text-[11px] text-rose-600">{{ form.errors.razon_social }}</p>
                        </div>

                        <div>
                            <label :class="claseEtiqueta">RUC (11 dígitos) *</label>
                            <input
                                v-model="form.ruc"
                                type="text"
                                maxlength="11"
                                required
                                placeholder="20123456789"
                                :class="[claseCampo, 'font-mono']"
                            />
                            <p class="mt-1 text-[11px] text-slate-400">Debe iniciar con 10 o 20 (normativa SUNAT).</p>
                            <p v-if="form.errors.ruc" class="mt-1 text-[11px] text-rose-600">{{ form.errors.ruc }}</p>
                        </div>

                        <div class="sm:col-span-2">
                            <label :class="claseEtiqueta">Dirección Web Personalizada (Enlace del Libro - Opcional)</label>
                            <div class="flex">
                                <span class="inline-flex items-center rounded-l-xl border border-r-0 border-slate-200 bg-slate-50 px-3 font-mono text-[11px] font-semibold text-slate-500">
                                    https://libro.datec.pe/l/
                                </span>
                                <input
                                    v-model="form.slug"
                                    type="text"
                                    placeholder="tienda-abc (se genera automáticamente si se deja vacío)"
                                    :class="[claseCampo, 'rounded-l-none font-mono']"
                                />
                            </div>
                            <p v-if="form.errors.slug" class="mt-1 text-[11px] text-rose-600">{{ form.errors.slug }}</p>
                        </div>

                        <div class="sm:col-span-2">
                            <label :class="claseEtiqueta">Domicilio Legal / Dirección *</label>
                            <input
                                v-model="form.direccion"
                                type="text"
                                required
                                placeholder="Av. Javier Prado Este 1234, San Isidro, Lima"
                                :class="claseCampo"
                            />
                            <p v-if="form.errors.direccion" class="mt-1 text-[11px] text-rose-600">{{ form.errors.direccion }}</p>
                        </div>

                        <div>
                            <label :class="claseEtiqueta">Correo de Contacto</label>
                            <input
                                v-model="form.email"
                                type="email"
                                placeholder="contacto@tiendaabc.pe"
                                :class="claseCampo"
                            />
                        </div>

                        <div>
                            <label :class="claseEtiqueta">Teléfono de Contacto</label>
                            <input
                                v-model="form.telefono"
                                type="text"
                                placeholder="+51 987654321"
                                :class="claseCampo"
                            />
                        </div>
                    </div>
                </div>

                <!-- Representante operativo -->
                <div class="space-y-4 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-2xs">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Representante Operativo de la Empresa</h2>
                            <p class="text-xs text-slate-500">Persona de contacto responsable de la operación del libro.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label :class="claseEtiqueta">Nombre del Representante</label>
                            <input
                                v-model="form.representante_nombre"
                                type="text"
                                placeholder="Carlos Mendoza"
                                :class="claseCampo"
                            />
                        </div>
                        <div>
                            <label :class="claseEtiqueta">Correo del Representante</label>
                            <input
                                v-model="form.representante_email"
                                type="email"
                                placeholder="carlos@tiendaabc.pe"
                                :class="claseCampo"
                            />
                        </div>
                        <div>
                            <label :class="claseEtiqueta">Teléfono</label>
                            <input
                                v-model="form.representante_telefono"
                                type="text"
                                placeholder="+51 912345678"
                                :class="claseCampo"
                            />
                        </div>
                    </div>
                </div>

                <!-- Plan, suscripción y estado -->
                <div class="space-y-4 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-2xs">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Plan de Suscripción y Estado del Servicio</h2>
                            <p class="text-xs text-slate-500">Condiciones comerciales, vigencia e identidad visual.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label :class="claseEtiqueta">Plan Asignado</label>
                            <div class="flex items-center justify-between rounded-xl border border-blue-200 bg-blue-50/60 px-3.5 py-2 text-xs font-bold text-blue-900 shadow-2xs">
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                    </svg>
                                    <span>Plan Corporativo / E-Commerce</span>
                                </div>
                                <span class="rounded-full bg-blue-600 px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider text-white">
                                    Full Activo
                                </span>
                            </div>
                            <p class="mt-1 text-[11px] text-slate-400">Acceso completo a libro virtual, auditoría y alertas.</p>
                        </div>

                        <div>
                            <label :class="claseEtiqueta">Estado Inicial del Servicio *</label>
                            <select v-model="form.estado" :class="[claseCampo, 'cursor-pointer font-semibold']">
                                <option v-for="e in estados" :key="e.value" :value="e.value">{{ e.label }}</option>
                            </select>
                        </div>

                        <div>
                            <label :class="claseEtiqueta">Vigencia Desde</label>
                            <input v-model="form.vigencia_desde" type="date" :class="claseCampo" />
                        </div>

                        <div>
                            <label :class="claseEtiqueta">Vigencia Hasta (Opcional)</label>
                            <input v-model="form.vigencia_hasta" type="date" :class="claseCampo" />
                        </div>

                        <div>
                            <label :class="claseEtiqueta">Límite Reclamaciones/Mes (Opcional)</label>
                            <input
                                v-model="form.limite_reclamaciones_mes"
                                type="number"
                                min="1"
                                placeholder="Ilimitado si se deja vacío"
                                :class="claseCampo"
                            />
                        </div>

                        <div>
                            <label :class="claseEtiqueta">Color Primario de Marca</label>
                            <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-1.5 pr-3">
                                <input
                                    v-model="form.color_primario"
                                    type="color"
                                    class="h-8 w-12 cursor-pointer rounded-lg border border-slate-200 bg-white p-0.5"
                                />
                                <span class="rounded-lg bg-slate-100 px-2 py-1 font-mono text-[11px] font-semibold text-slate-600">{{ form.color_primario }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Barra de acciones -->
                <div class="flex items-center justify-end gap-2">
                    <Link
                        href="/admin/empresas"
                        class="rounded-xl px-4 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-100"
                    >
                        Cancelar
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-blue-500/20 transition hover:bg-blue-700 disabled:opacity-50"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ form.processing ? 'Registrando...' : 'Crear y Registrar Empresa' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
