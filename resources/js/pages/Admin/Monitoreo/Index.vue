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
        <Head title="Monitoreo Operativo - Datec Libro" />

        <div class="space-y-8">
            <!-- Cabecera de Página con Estilo SaaS Moderno -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50/70 px-3 py-1 text-xs font-bold uppercase tracking-wider text-blue-700 mb-2">
                        <span class="flex h-2 w-2 rounded-full bg-blue-600 animate-pulse"></span>
                        <span>Supervisión de Infraestructura Global</span>
                    </div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">
                        Monitoreo Operativo
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Supervisión técnica de empresas activas, colas asíncronas, errores de envío y vencimiento de suscripciones.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        v-if="colas.fallidos > 0"
                        type="button"
                        @click="reintentarTodos"
                        class="inline-flex items-center gap-2 rounded-full bg-amber-600 px-5 py-2.5 text-xs font-bold text-white shadow-md shadow-amber-500/25 hover:bg-amber-700 transition cursor-pointer"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Reintentar trabajos fallidos ({{ colas.fallidos }})</span>
                    </button>
                    <Link
                        href="/admin/empresas"
                        class="rounded-full border border-slate-300 bg-white px-5 py-2.5 text-xs font-bold text-slate-700 shadow-xs hover:bg-slate-50 transition"
                    >
                        Ver listado de empresas
                    </Link>
                </div>
            </div>

            <!-- Tarjetas de Salud del Sistema (Tipo SmartWing) -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Empresas Activas -->
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Empresas Activas</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 font-bold">
                            ✓
                        </span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-extrabold text-slate-900">{{ empresas.activas }}</span>
                        <span class="text-xs text-slate-500">de {{ empresas.total }} en plataforma</span>
                    </div>
                    <div class="mt-2 text-xs text-slate-400">
                        {{ empresas.suspendidas }} en pausa • {{ empresas.borrador }} en borrador
                    </div>
                </div>

                <!-- Cola de Procesamiento PostgreSQL -->
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Cola PostgreSQL</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-100 text-blue-700 font-bold">
                            ⚡
                        </span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-extrabold text-slate-900">{{ colas.pendientes }}</span>
                        <span class="text-xs text-slate-500">trabajos en espera</span>
                    </div>
                    <div class="mt-2 text-xs text-slate-400">
                        Controlador database sobre PostgreSQL activo
                    </div>
                </div>

                <!-- Trabajos Fallidos -->
                <div
                    class="rounded-2xl border p-5 shadow-xs hover:shadow-md transition"
                    :class="colas.fallidos > 0 ? 'border-rose-200 bg-rose-50/40' : 'border-slate-200 bg-white'"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider" :class="colas.fallidos > 0 ? 'text-rose-700' : 'text-slate-500'">
                            Errores de Envío
                        </span>
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-xl"
                            :class="colas.fallidos > 0 ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-600'"
                        >
                            !
                        </span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-extrabold" :class="colas.fallidos > 0 ? 'text-rose-700' : 'text-slate-900'">
                            {{ colas.fallidos }}
                        </span>
                        <span class="text-xs text-slate-500">fallos registrados</span>
                    </div>
                    <div class="mt-2 text-xs" :class="colas.fallidos > 0 ? 'text-rose-600 font-semibold' : 'text-slate-400'">
                        {{ colas.fallidos > 0 ? 'Requieren revisión de envío' : 'Sin errores pendientes en failed_jobs' }}
                    </div>
                </div>

                <!-- Suscripciones en Riesgo -->
                <div
                    class="rounded-2xl border p-5 shadow-xs hover:shadow-md transition"
                    :class="suscripciones.conteo_por_vencer > 0 || suscripciones.conteo_vencidas > 0 ? 'border-amber-200 bg-amber-50/40' : 'border-slate-200 bg-white'"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Suscripciones en Riesgo</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-100 text-amber-700 font-bold">
                            ⏱
                        </span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-extrabold text-amber-800">{{ suscripciones.conteo_por_vencer }}</span>
                        <span class="text-xs text-slate-500">por vencer (&le; 30d)</span>
                    </div>
                    <div class="mt-2 text-xs text-slate-400">
                        {{ suscripciones.conteo_vencidas }} suscripción(es) expiradas
                    </div>
                </div>
            </div>

            <!-- Gráfica de Distribución de Planes y Estado de Invitaciones -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Planes Comerciales -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">
                        Distribución de Planes Comerciales
                    </h2>
                    <div class="space-y-3">
                        <div
                            v-for="pl in empresas.planes"
                            :key="pl.plan"
                            class="flex items-center justify-between rounded-xl bg-slate-50/70 border border-slate-200/60 p-3.5"
                        >
                            <div class="flex items-center gap-2.5">
                                <span class="h-2.5 w-2.5 rounded-full bg-blue-600"></span>
                                <span class="text-xs sm:text-sm font-semibold text-slate-800">{{ pl.label }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="font-mono text-sm font-extrabold text-slate-900">{{ pl.cantidad }}</span>
                                <span class="text-xs text-slate-400">
                                    ({{ empresas.total > 0 ? Math.round((pl.cantidad / empresas.total) * 100) : 0 }}%)
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estado de Invitaciones de Administradores -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">
                        Estado de Invitaciones de Administradores
                    </h2>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl bg-slate-50 p-4 border border-slate-200">
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Generadas</p>
                            <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ invitaciones.total }}</p>
                        </div>
                        <div class="rounded-xl bg-emerald-50/70 p-4 border border-emerald-200">
                            <p class="text-xs font-bold text-emerald-800 uppercase tracking-wider">Cuentas Activadas</p>
                            <p class="mt-1 text-2xl font-extrabold text-emerald-900">{{ invitaciones.aceptadas }}</p>
                        </div>
                        <div class="rounded-xl bg-blue-50/70 p-4 border border-blue-200">
                            <p class="text-xs font-bold text-blue-800 uppercase tracking-wider">Pendientes</p>
                            <p class="mt-1 text-2xl font-extrabold text-blue-900">{{ invitaciones.pendientes }}</p>
                        </div>
                        <div class="rounded-xl bg-amber-50/70 p-4 border border-amber-200">
                            <p class="text-xs font-bold text-amber-800 uppercase tracking-wider">Expiradas</p>
                            <p class="mt-1 text-2xl font-extrabold text-amber-900">{{ invitaciones.expiradas }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Suscripciones en Riesgo -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-xs overflow-hidden">
                <div class="border-b border-slate-200 bg-slate-50/70 px-6 py-4">
                    <h2 class="text-base font-bold text-slate-900">
                        Vencimientos y Expiraciones de Suscripciones
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Seguimiento preventivo para renovación de servicios antes del plazo límite.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50 text-[11px] uppercase font-bold text-slate-500 border-b border-slate-200">
                            <tr>
                                <th scope="col" class="px-6 py-3.5">Empresa</th>
                                <th scope="col" class="px-6 py-3.5">Plan</th>
                                <th scope="col" class="px-6 py-3.5">Fin de Vigencia</th>
                                <th scope="col" class="px-6 py-3.5">Estado</th>
                                <th scope="col" class="px-6 py-3.5 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <!-- Empresas vencidas -->
                            <tr v-for="emp in suscripciones.vencidas" :key="'vencida-' + emp.id" class="bg-rose-50/30 hover:bg-rose-50/60 transition-colors">
                                <td class="px-6 py-3.5 font-bold text-slate-900">
                                    {{ emp.name }}
                                </td>
                                <td class="px-6 py-3.5 capitalize font-medium">
                                    {{ emp.plan }}
                                </td>
                                <td class="px-6 py-3.5 font-mono text-xs text-rose-700 font-bold">
                                    {{ emp.vigencia_hasta }} (Vencida)
                                </td>
                                <td class="px-6 py-3.5">
                                    <span class="inline-flex items-center rounded-full bg-rose-100 border border-rose-200 px-2.5 py-0.5 text-xs font-bold text-rose-800">
                                        Expirada
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-right">
                                    <Link :href="`/admin/empresas/${emp.id}/edit`" class="text-xs font-bold text-blue-600 hover:text-blue-800 hover:underline">
                                        Renovar / Editar
                                    </Link>
                                </td>
                            </tr>

                            <!-- Empresas por vencer -->
                            <tr v-for="emp in suscripciones.por_vencer" :key="'vence-' + emp.id" class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-6 py-3.5 font-bold text-slate-900">
                                    {{ emp.name }}
                                </td>
                                <td class="px-6 py-3.5 capitalize font-medium">
                                    {{ emp.plan }}
                                </td>
                                <td class="px-6 py-3.5 font-mono text-xs text-amber-700 font-bold">
                                    {{ emp.vigencia_hasta }}
                                </td>
                                <td class="px-6 py-3.5">
                                    <span class="inline-flex items-center rounded-full bg-amber-100 border border-amber-200 px-2.5 py-0.5 text-xs font-bold text-amber-800">
                                        Próxima a vencer
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-right">
                                    <Link :href="`/admin/empresas/${emp.id}/edit`" class="text-xs font-bold text-blue-600 hover:text-blue-800 hover:underline">
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

            <!-- Tabla de Errores Técnicos y Diagnóstico -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-xs overflow-hidden">
                <div class="border-b border-slate-200 bg-slate-50/70 px-6 py-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span>Registro de Errores de Envío y Diagnóstico</span>
                            <span
                                v-if="colas.fallidos > 0"
                                class="rounded-full bg-rose-100 border border-rose-200 px-2.5 py-0.5 text-xs font-bold text-rose-800"
                            >
                                {{ colas.fallidos }} fallo(s)
                            </span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Diagnóstico técnico de incidencias asíncronas con sanitización de datos personales.
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50 text-[11px] uppercase font-bold text-slate-500 border-b border-slate-200">
                            <tr>
                                <th scope="col" class="px-6 py-3.5">ID / Cola</th>
                                <th scope="col" class="px-6 py-3.5">Tipo de Trabajo</th>
                                <th scope="col" class="px-6 py-3.5">Diagnóstico Técnico</th>
                                <th scope="col" class="px-6 py-3.5">Fecha y Hora</th>
                                <th scope="col" class="px-6 py-3.5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="fallo in colas.ultimos_fallos" :key="fallo.id" class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-6 py-3.5">
                                    <div class="font-mono text-xs font-bold text-slate-800">#{{ fallo.id }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono">{{ fallo.queue }}</div>
                                </td>
                                <td class="px-6 py-3.5">
                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-800 font-mono">
                                        {{ fallo.job_name }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 max-w-md">
                                    <div class="font-mono text-xs text-rose-700 bg-rose-50 p-2.5 rounded-xl border border-rose-100 break-words">
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
                                        class="rounded-full bg-blue-50 border border-blue-200 px-3 py-1.5 text-xs font-bold text-blue-700 hover:bg-blue-100 transition cursor-pointer"
                                    >
                                        Reintentar
                                    </button>
                                    <button
                                        type="button"
                                        @click="eliminarJob(fallo.id)"
                                        class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-100 transition cursor-pointer"
                                    >
                                        Descartar
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="colas.ultimos_fallos.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                                    <div class="flex flex-col items-center justify-center gap-1.5">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 font-bold">
                                            ✓
                                        </div>
                                        <span class="font-bold text-slate-800">Todo funcionando con normalidad</span>
                                        <span class="text-xs text-slate-400">No hay errores operativos ni trabajos pendientes en cola de fallos.</span>
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
