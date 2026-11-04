<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface FalloJob {
    id: number;
    uuid: string;
    queue: string;
    job_name: string;
    error_resumen: string;
    failed_at: string;
}

interface EmpresaVencimiento {
    id: number;
    name: string;
    slug: string;
    plan: string;
    vigencia_hasta: string;
    estado: string;
}

interface Props {
    empresas: {
        total: number;
        activas: number;
        suspendidas: number;
        borrador: number;
        baja: number;
        planes: Array<{ plan: string; label: string; cantidad: number }>;
    };
    suscripciones: {
        por_vencer: EmpresaVencimiento[];
        vencidas: EmpresaVencimiento[];
        conteo_por_vencer: number;
        conteo_vencidas: number;
    };
    colas: {
        pendientes: number;
        fallidos: number;
        ultimos_fallos: FalloJob[];
    };
    invitaciones: {
        total: number;
        pendientes: number;
        aceptadas: number;
        expiradas: number;
    };
}

const props = defineProps<Props>();

function reintentarJob(id: number) {
    router.post(`/admin/monitoreo/colas/${id}/reintentar`, {}, {
        preserveScroll: true,
    });
}

function reintentarTodos() {
    router.post('/admin/monitoreo/colas/reintentar-todos', {}, {
        preserveScroll: true,
    });
}

function eliminarJob(id: number) {
    if (confirm(`¿Descartar el registro de error del trabajo #${id}?`)) {
        router.delete(`/admin/monitoreo/colas/${id}`, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Monitoreo Operativo (RF-005) - Datec Libro" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                        Monitoreo Operativo Global (RF-005)
                    </h1>
                    <p class="text-sm text-slate-500">
                        Supervisión técnica de empresas activas, colas asíncronas, errores de envío y vencimiento de suscripciones sin exposición de datos personales (PII).
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        v-if="colas.fallidos > 0"
                        type="button"
                        @click="reintentarTodos"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-amber-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-amber-700 transition"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Reintentar todos los trabajos fallidos ({{ colas.fallidos }})
                    </button>
                    <Link
                        href="/admin/empresas"
                        class="rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50"
                    >
                        Ver listado de empresas
                    </Link>
                </div>
            </div>

            <!-- TARJETAS DE SALUD DEL SISTEMA -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Empresas Activas -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Empresas Activas</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-extrabold text-slate-900">{{ empresas.activas }}</span>
                        <span class="text-xs text-slate-500">de {{ empresas.total }} registradas</span>
                    </div>
                    <div class="mt-2 text-xs text-slate-500">
                        {{ empresas.suspendidas }} suspendidas • {{ empresas.borrador }} en borrador
                    </div>
                </div>

                <!-- Estado de Colas Database -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Cola PostgreSQL</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7zm0 5h16" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-extrabold text-slate-900">{{ colas.pendientes }}</span>
                        <span class="text-xs text-slate-500">trabajos en cola</span>
                    </div>
                    <div class="mt-2 text-xs" :class="colas.pendientes > 0 ? 'text-blue-600 font-medium' : 'text-slate-500'">
                        Controlador database sobre PostgreSQL activo
                    </div>
                </div>

                <!-- Trabajos / Envíos Fallidos -->
                <div
                    class="rounded-xl border p-5 shadow-xs"
                    :class="colas.fallidos > 0 ? 'border-rose-200 bg-rose-50/40' : 'border-slate-200 bg-white'"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider" :class="colas.fallidos > 0 ? 'text-rose-700' : 'text-slate-500'">
                            Errores de Envío / Colas
                        </span>
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-lg"
                            :class="colas.fallidos > 0 ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-600'"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-extrabold" :class="colas.fallidos > 0 ? 'text-rose-700' : 'text-slate-900'">
                            {{ colas.fallidos }}
                        </span>
                        <span class="text-xs text-slate-500">trabajos fallidos</span>
                    </div>
                    <div class="mt-2 text-xs" :class="colas.fallidos > 0 ? 'text-rose-600 font-medium' : 'text-slate-500'">
                        {{ colas.fallidos > 0 ? 'Requieren revisión o reintento' : 'Sin errores pendientes en failed_jobs' }}
                    </div>
                </div>

                <!-- Suscripciones por Vencer -->
                <div
                    class="rounded-xl border p-5 shadow-xs"
                    :class="suscripciones.conteo_por_vencer > 0 || suscripciones.conteo_vencidas > 0 ? 'border-amber-200 bg-amber-50/40' : 'border-slate-200 bg-white'"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Suscripciones en Riesgo</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-extrabold text-amber-800">{{ suscripciones.conteo_por_vencer }}</span>
                        <span class="text-xs text-slate-500">por vencer (&le; 30d)</span>
                    </div>
                    <div class="mt-2 text-xs text-slate-500">
                        {{ suscripciones.conteo_vencidas }} suscripción(es) ya vencidas
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 2: DISTRIBUCIÓN DE PLANES & ESTADO DE INVITACIONES -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Distribución por Plan (RF-003) -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">
                        Distribución de Planes Comerciales
                    </h2>
                    <div class="mt-4 space-y-3">
                        <div
                            v-for="pl in empresas.planes"
                            :key="pl.plan"
                            class="flex items-center justify-between rounded-lg bg-slate-50 p-3"
                        >
                            <div class="flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full bg-blue-600"></span>
                                <span class="text-sm font-medium text-slate-800">{{ pl.label }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="font-mono text-sm font-bold text-slate-900">{{ pl.cantidad }}</span>
                                <span class="text-xs text-slate-500">
                                    ({{ empresas.total > 0 ? Math.round((pl.cantidad / empresas.total) * 100) : 0 }}%)
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estado de Cuentas e Invitaciones (RF-004) -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">
                        Estado de Invitaciones de Administradores
                    </h2>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div class="rounded-lg bg-slate-50 p-3.5 border border-slate-200">
                            <p class="text-xs font-semibold text-slate-500">Total Generadas</p>
                            <p class="mt-1 text-xl font-bold text-slate-900">{{ invitaciones.total }}</p>
                        </div>
                        <div class="rounded-lg bg-emerald-50 p-3.5 border border-emerald-200">
                            <p class="text-xs font-semibold text-emerald-800">Cuentas Activadas</p>
                            <p class="mt-1 text-xl font-bold text-emerald-900">{{ invitaciones.aceptadas }}</p>
                        </div>
                        <div class="rounded-lg bg-blue-50 p-3.5 border border-blue-200">
                            <p class="text-xs font-semibold text-blue-800">Pendientes (Vigentes)</p>
                            <p class="mt-1 text-xl font-bold text-blue-900">{{ invitaciones.pendientes }}</p>
                        </div>
                        <div class="rounded-lg bg-amber-50 p-3.5 border border-amber-200">
                            <p class="text-xs font-semibold text-amber-800">Expiradas sin activar</p>
                            <p class="mt-1 text-xl font-bold text-amber-900">{{ invitaciones.expiradas }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 3: TABLA DE SUSCRIPCIONES EN RIESGO / POR VENCER -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-xs overflow-hidden">
                <div class="border-b border-slate-200 bg-slate-50/70 px-6 py-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            Vencimientos y Expiraciones de Suscripciones (RF-003, RF-005)
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Seguimiento de empresas con fin de vigencia próximo o vencido para gestión de soporte o renovación.
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200">
                            <tr>
                                <th scope="col" class="px-6 py-3">Empresa</th>
                                <th scope="col" class="px-6 py-3">Plan</th>
                                <th scope="col" class="px-6 py-3">Fecha Fin Vigencia</th>
                                <th scope="col" class="px-6 py-3">Estado de Suscripción</th>
                                <th scope="col" class="px-6 py-3 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <!-- Empresas ya vencidas -->
                            <tr v-for="emp in suscripciones.vencidas" :key="'vencida-' + emp.id" class="bg-rose-50/30 hover:bg-rose-50/60">
                                <td class="px-6 py-3 font-semibold text-slate-900">
                                    {{ emp.name }}
                                </td>
                                <td class="px-6 py-3 capitalize">
                                    {{ emp.plan }}
                                </td>
                                <td class="px-6 py-3 font-mono text-xs text-rose-700 font-bold">
                                    {{ emp.vigencia_hasta }} (Vencida)
                                </td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center rounded-full bg-rose-100 border border-rose-200 px-2.5 py-0.5 text-xs font-semibold text-rose-800">
                                        Vencida
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <Link :href="`/admin/empresas/${emp.id}/edit`" class="text-xs font-semibold text-blue-600 hover:underline">
                                        Renovar / Editar
                                    </Link>
                                </td>
                            </tr>

                            <!-- Empresas por vencer -->
                            <tr v-for="emp in suscripciones.por_vencer" :key="'vence-' + emp.id" class="hover:bg-slate-50">
                                <td class="px-6 py-3 font-semibold text-slate-900">
                                    {{ emp.name }}
                                </td>
                                <td class="px-6 py-3 capitalize">
                                    {{ emp.plan }}
                                </td>
                                <td class="px-6 py-3 font-mono text-xs text-amber-700 font-semibold">
                                    {{ emp.vigencia_hasta }}
                                </td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center rounded-full bg-amber-100 border border-amber-200 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
                                        Próxima a vencer
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <Link :href="`/admin/empresas/${emp.id}/edit`" class="text-xs font-semibold text-blue-600 hover:underline">
                                        Extender plazo
                                    </Link>
                                </td>
                            </tr>

                            <tr v-if="suscripciones.vencidas.length === 0 && suscripciones.por_vencer.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                                    Todas las suscripciones con vigencia definida están al día (sin expiraciones en los próximos 30 días).
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SECCIÓN 4: ERRORES DE ENVÍO Y TRABAJOS FALLIDOS EN COLAS (RF-005) -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-xs overflow-hidden">
                <div class="border-b border-slate-200 bg-slate-50/70 px-6 py-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span>Registro de Errores de Envío y Colas Asíncronas</span>
                            <span
                                v-if="colas.fallidos > 0"
                                class="rounded-full bg-rose-100 border border-rose-200 px-2 py-0.5 text-xs font-bold text-rose-800"
                            >
                                {{ colas.fallidos }} fallo(s)
                            </span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Diagnóstico de incidencias en segundo plano (correos, PDFs, constancias). Muestra detalles técnicos protegiendo la privacidad (sin PII).
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200">
                            <tr>
                                <th scope="col" class="px-6 py-3">ID / Cola</th>
                                <th scope="col" class="px-6 py-3">Tipo de Trabajo</th>
                                <th scope="col" class="px-6 py-3">Diagnóstico de Error (Sanitizado)</th>
                                <th scope="col" class="px-6 py-3">Fecha y Hora</th>
                                <th scope="col" class="px-6 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr v-for="fallo in colas.ultimos_fallos" :key="fallo.id" class="hover:bg-slate-50">
                                <td class="px-6 py-3.5">
                                    <div class="font-mono text-xs font-bold text-slate-800">#{{ fallo.id }}</div>
                                    <div class="text-[11px] text-slate-500 font-mono">{{ fallo.queue }}</div>
                                </td>
                                <td class="px-6 py-3.5">
                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-800 font-mono">
                                        {{ fallo.job_name }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 max-w-md">
                                    <div class="font-mono text-xs text-rose-700 bg-rose-50 p-2 rounded-lg border border-rose-100 break-words">
                                        {{ fallo.error_resumen }}
                                    </div>
                                </td>
                                <td class="px-6 py-3.5 text-xs text-slate-500 whitespace-nowrap font-mono">
                                    {{ fallo.failed_at }}
                                </td>
                                <td class="px-6 py-3.5 text-right whitespace-nowrap space-x-2">
                                    <button
                                        type="button"
                                        @click="reintentarJob(fallo.id)"
                                        class="rounded-md bg-blue-50 border border-blue-200 px-2.5 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100"
                                    >
                                        Reintentar
                                    </button>
                                    <button
                                        type="button"
                                        @click="eliminarJob(fallo.id)"
                                        class="rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-100"
                                    >
                                        Descartar
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="colas.ultimos_fallos.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                                    <div class="flex flex-col items-center justify-center gap-1">
                                        <svg class="h-6 w-6 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="font-medium text-slate-800">No hay errores operativos registrados</span>
                                        <span class="text-xs text-slate-400">Las colas y procesos de segundo plano están funcionando con normalidad.</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

