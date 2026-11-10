<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
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
    salud?: {
        estado_global: 'optimo' | 'advertencia' | 'critico';
        alertas_activas: Array<{
            id: number;
            tipo: string;
            severidad: string;
            titulo: string;
            descripcion: string;
            valor_actual: string;
            umbral_definido: string;
            notificado_datec_en: string | null;
        }>;
        metricas: {
            jobs_pendientes: number;
            jobs_fallidos: number;
            envios_fallidos: number;
            contingencias_activas: number;
        };
        umbrales: Record<string, { warn: number; crit: number }>;
        recursos: {
            base_datos: {
                driver: string;
                tamano_formateado: string;
                tamano_mb: number;
                estado: string;
            };
            almacenamiento: {
                espacio_libre_gb: number;
                ruta: string;
            };
            conteos: {
                empresas_total: number;
                reclamaciones_total: number;
                auditorias_total: number;
            };
            servidor: {
                php_version: string;
                laravel_version: string;
                zona_horaria: string;
            };
        };
    };
}

const props = defineProps<Props>();

const porcentajeActivas = computed(() =>
    props.empresas.total > 0 ? Math.round((props.empresas.activas / props.empresas.total) * 100) : 0
);

function iniciales(nombre: string): string {
    return nombre
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p.charAt(0).toUpperCase())
        .join('');
}

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

function verificarSalud() {
    router.post('/admin/monitoreo/verificar-salud', {}, {
        preserveScroll: true,
    });
}

function resolverAlerta(alertaId: number) {
    router.post(`/admin/monitoreo/alertas/${alertaId}/resolver`, {}, {
        preserveScroll: true,
    });
}
</script>

<template>
    <AdminLayout>
        <Head title="Monitoreo Global - Datec Libro" />

        <div class="space-y-6">
            <!-- Cabecera de Página -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex items-start gap-4">
                    <div class="hidden sm:flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-md shadow-blue-500/25">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            Superadministrador · Supervisión de Infraestructura
                        </div>
                        <h1 class="mt-0.5 text-2xl font-extrabold tracking-tight text-slate-900">
                            Monitoreo Global
                        </h1>
                        <p class="mt-1 text-sm text-slate-500">
                            Supervisión técnica de empresas activas, colas asíncronas, errores de envío y vencimiento de suscripciones.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 shrink-0">
                    <button
                        type="button"
                        @click="verificarSalud"
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50 hover:text-slate-900"
                    >
                        <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Evaluar Umbrales</span>
                    </button>

                    <button
                        v-if="colas.fallidos > 0"
                        type="button"
                        @click="reintentarTodos"
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-amber-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-amber-500/20 transition hover:bg-amber-700"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Reintentar fallidos ({{ colas.fallidos }})</span>
                    </button>
                    <Link
                        href="/admin/empresas"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-600 shadow-2xs transition hover:bg-slate-50 hover:text-slate-900"
                    >
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span>Ver empresas</span>
                    </Link>
                </div>
            </div>

            <!-- Panel de Salud de Plataforma, Uso de Recursos y Alertas Operativas -->
            <div v-if="salud" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-3">
                        <div
                            :class="[
                                'flex h-10 w-10 items-center justify-center rounded-xl shrink-0 font-black',
                                salud.estado_global === 'optimo'
                                    ? 'bg-emerald-50 text-emerald-600 border border-emerald-200'
                                    : (salud.estado_global === 'advertencia' ? 'bg-amber-50 text-amber-600 border border-amber-200' : 'bg-rose-50 text-rose-600 border border-rose-200')
                            ]"
                        >
                            <svg v-if="salud.estado_global === 'optimo'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-sm font-bold text-slate-900">Estado Operativo de Plataforma y Recursos</h2>
                                <span
                                    :class="[
                                        'rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                                        salud.estado_global === 'optimo'
                                            ? 'bg-emerald-100 text-emerald-800'
                                            : (salud.estado_global === 'advertencia' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800')
                                    ]"
                                >
                                    {{ salud.estado_global === 'optimo' ? 'Óptimo' : (salud.estado_global === 'advertencia' ? 'Advertencia' : 'Crítico') }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-400">
                                Diagnóstico continuo de tareas en cola, fallos de mensajería, contingencias y almacenamiento.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Lista de Alertas Activas si existen -->
                <div v-if="salud.alertas_activas.length > 0" class="space-y-2">
                    <div
                        v-for="alerta in salud.alertas_activas"
                        :key="alerta.id"
                        :class="[
                            'flex flex-col sm:flex-row sm:items-center sm:justify-between p-3.5 rounded-xl border gap-3',
                            alerta.severidad === 'critica'
                                ? 'bg-rose-50/70 border-rose-300 text-rose-950'
                                : 'bg-amber-50/70 border-amber-300 text-amber-950'
                        ]"
                    >
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-2 w-2 rounded-full shrink-0" :class="alerta.severidad === 'critica' ? 'bg-rose-600 animate-ping' : 'bg-amber-600'"></span>
                            <div>
                                <div class="font-bold text-xs">{{ alerta.titulo }}</div>
                                <div class="text-[11px] opacity-90 mt-0.5">{{ alerta.descripcion }}</div>
                                <div class="text-[10px] opacity-75 mt-1 font-mono">
                                    Valor Actual: {{ alerta.valor_actual }} &bull; Umbral: {{ alerta.umbral_definido }}
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="resolverAlerta(alerta.id)"
                            class="shrink-0 self-end sm:self-center rounded-lg bg-white px-3 py-1.5 text-xs font-bold shadow-2xs border border-slate-200 hover:bg-slate-50 transition"
                        >
                            Resolver Alerta
                        </button>
                    </div>
                </div>

                <!-- Grilla de Umbrales Operativos -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                    <div class="rounded-xl bg-slate-50 p-3 border border-slate-100">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Tareas en Cola</span>
                        <div class="flex items-baseline justify-between">
                            <span class="text-base font-black text-slate-800">{{ salud.metricas.jobs_pendientes }}</span>
                            <span class="text-[10px] text-slate-400">Máx: 50</span>
                        </div>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-3 border border-slate-100">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Trabajos Fallidos</span>
                        <div class="flex items-baseline justify-between">
                            <span class="text-base font-black text-slate-800">{{ salud.metricas.jobs_fallidos }}</span>
                            <span class="text-[10px] text-slate-400">Máx: 5</span>
                        </div>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-3 border border-slate-100">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Envíos Fallidos Correo</span>
                        <div class="flex items-baseline justify-between">
                            <span class="text-base font-black text-slate-800">{{ salud.metricas.envios_fallidos }}</span>
                            <span class="text-[10px] text-slate-400">Máx: 5</span>
                        </div>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-3 border border-slate-100">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Clientes en Contingencia</span>
                        <div class="flex items-baseline justify-between">
                            <span class="text-base font-black text-slate-800">{{ salud.metricas.contingencias_activas }}</span>
                            <span class="text-[10px] text-slate-400">Alerta: &gt; 0</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tarjetas de Salud del Sistema -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Empresas Activas -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Empresas Activas</span>
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-slate-900">{{ empresas.activas }}</span>
                        <span class="text-xs font-medium text-slate-400">de {{ empresas.total }}</span>
                    </div>
                    <div class="mt-2 flex items-center gap-2">
                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-emerald-500 transition-all" :style="{ width: `${porcentajeActivas}%` }"></div>
                        </div>
                        <span class="text-[11px] font-bold text-emerald-700">{{ porcentajeActivas }}%</span>
                    </div>
                </div>

                <!-- Cola de Procesamiento PostgreSQL -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Cola PostgreSQL</span>
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-slate-900">{{ colas.pendientes }}</span>
                        <span class="text-xs font-medium text-slate-400">en espera</span>
                    </div>
                    <p class="mt-1 text-[11px] font-medium text-slate-400">Worker asíncrono sobre base de datos</p>
                </div>

                <!-- Trabajos Fallidos -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Errores de Envío</span>
                        <span
                            :class="[
                                'flex h-9 w-9 items-center justify-center rounded-xl',
                                colas.fallidos > 0 ? 'bg-rose-50 text-rose-600' : 'bg-slate-100 text-slate-500'
                            ]"
                        >
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span :class="['text-3xl font-black', colas.fallidos > 0 ? 'text-rose-600' : 'text-slate-900']">
                            {{ colas.fallidos }}
                        </span>
                        <span class="text-xs font-medium text-slate-400">fallos registrados</span>
                    </div>
                    <p :class="['mt-1 text-[11px] font-medium', colas.fallidos > 0 ? 'text-rose-600 font-bold' : 'text-slate-400']">
                        {{ colas.fallidos > 0 ? 'Requieren revisión técnica' : 'Sin errores pendientes en cola' }}
                    </p>
                </div>

                <!-- Suscripciones en Riesgo -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Suscripciones</span>
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-slate-900">{{ suscripciones.conteo_por_vencer }}</span>
                        <span class="text-xs font-medium text-slate-400">por vencer (&le; 30d)</span>
                    </div>
                    <p class="mt-1 text-[11px] font-medium text-slate-400">
                        {{ suscripciones.conteo_vencidas }} suscripción(es) expiradas
                    </p>
                </div>
            </div>

            <!-- Distribución de Planes y Estado de Invitaciones -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Planes Comerciales -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Distribución de Planes Comerciales</h2>
                            <p class="text-xs text-slate-500">Participación de suscripciones activas por nivel de servicio</p>
                        </div>
                    </div>

                    <div class="mt-4 space-y-3">
                        <div
                            v-for="pl in empresas.planes"
                            :key="pl.plan"
                            class="rounded-xl border border-slate-100 bg-slate-50/70 p-3.5"
                        >
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-blue-600"></span>
                                    <span class="font-bold text-slate-800">{{ pl.label }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-black text-slate-900">{{ pl.cantidad }}</span>
                                    <span class="rounded-md bg-white px-1.5 py-0.5 text-[10px] font-bold text-slate-500 border border-slate-200/80">
                                        {{ empresas.total > 0 ? Math.round((pl.cantidad / empresas.total) * 100) : 0 }}%
                                    </span>
                                </div>
                            </div>
                            <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-slate-200/70">
                                <div
                                    class="h-full rounded-full bg-blue-600 transition-all"
                                    :style="{ width: `${empresas.total > 0 ? Math.round((pl.cantidad / empresas.total) * 100) : 0}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estado de Invitaciones de Administradores -->
                <div class="flex flex-col justify-between rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs">
                    <div>
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-sm font-bold text-slate-900">Estado de Invitaciones de Administradores</h2>
                                    <p class="text-xs text-slate-500">Seguimiento de enlaces de activación enviados a clientes</p>
                                </div>
                            </div>
                            <span class="shrink-0 rounded-lg border border-slate-200/80 bg-slate-50 px-2.5 py-1 font-mono text-[11px] font-bold text-slate-700">
                                {{ invitaciones.total }} total
                            </span>
                        </div>

                        <!-- Barra segmentada de proporción de invitaciones -->
                        <div class="mt-4 space-y-1.5">
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="font-semibold text-slate-500">Tasa de activación de enlaces</span>
                                <span class="font-bold text-slate-800">
                                    {{ invitaciones.total > 0 ? Math.round((invitaciones.aceptadas / invitaciones.total) * 100) : 0 }}% completado
                                </span>
                            </div>
                            <div class="flex h-2 w-full overflow-hidden rounded-full bg-slate-100 p-0.5 gap-0.5">
                                <div
                                    v-if="invitaciones.aceptadas > 0"
                                    class="h-full rounded-full bg-emerald-500 transition-all"
                                    :style="{ width: `${(invitaciones.aceptadas / invitaciones.total) * 100}%` }"
                                    title="Activadas"
                                ></div>
                                <div
                                    v-if="invitaciones.pendientes > 0"
                                    class="h-full rounded-full bg-blue-600 transition-all"
                                    :style="{ width: `${(invitaciones.pendientes / invitaciones.total) * 100}%` }"
                                    title="Pendientes"
                                ></div>
                                <div
                                    v-if="invitaciones.expiradas > 0"
                                    class="h-full rounded-full bg-amber-500 transition-all"
                                    :style="{ width: `${(invitaciones.expiradas / invitaciones.total) * 100}%` }"
                                    title="Expiradas"
                                ></div>
                            </div>
                        </div>

                        <!-- Grid flexible de métricas de invitaciones -->
                        <div class="mt-4 grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                            <!-- Total Generadas -->
                            <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/70 p-3 transition hover:border-slate-200 hover:bg-white">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-slate-600 shadow-2xs ring-1 ring-slate-200/70">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-xs font-bold text-slate-800">Total Generadas</p>
                                        <p class="truncate text-[11px] text-slate-400">Enlaces emitidos</p>
                                    </div>
                                </div>
                                <span class="ml-2 font-mono text-lg font-black text-slate-900">{{ invitaciones.total }}</span>
                            </div>

                            <!-- Cuentas Activadas -->
                            <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/70 p-3 transition hover:border-slate-200 hover:bg-white">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-500/20">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-xs font-bold text-slate-800">Cuentas Activadas</p>
                                        <p class="truncate text-[11px] text-slate-400">Accesos confirmados</p>
                                    </div>
                                </div>
                                <div class="ml-2 flex items-center gap-1.5">
                                    <span class="font-mono text-lg font-black text-slate-900">{{ invitaciones.aceptadas }}</span>
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                </div>
                            </div>

                            <!-- Pendientes -->
                            <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/70 p-3 transition hover:border-slate-200 hover:bg-white">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-500/20">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-xs font-bold text-slate-800">Pendientes</p>
                                        <p class="truncate text-[11px] text-slate-400">En espera de uso</p>
                                    </div>
                                </div>
                                <div class="ml-2 flex items-center gap-1.5">
                                    <span class="font-mono text-lg font-black text-slate-900">{{ invitaciones.pendientes }}</span>
                                    <span class="h-1.5 w-1.5 rounded-full bg-blue-600"></span>
                                </div>
                            </div>

                            <!-- Expiradas -->
                            <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/70 p-3 transition hover:border-slate-200 hover:bg-white">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-500/20">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-xs font-bold text-slate-800">Expiradas</p>
                                        <p class="truncate text-[11px] text-slate-400">Plazo caducado</p>
                                    </div>
                                </div>
                                <div class="ml-2 flex items-center gap-1.5">
                                    <span class="font-mono text-lg font-black text-slate-900">{{ invitaciones.expiradas }}</span>
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Suscripciones en Riesgo -->
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xs">
                <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Vencimientos y Expiraciones de Suscripciones</h2>
                        <p class="text-xs text-slate-500">Seguimiento preventivo para renovación de servicios antes del plazo límite</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            <tr>
                                <th scope="col" class="px-5 py-3">Empresa</th>
                                <th scope="col" class="px-5 py-3">Plan</th>
                                <th scope="col" class="px-5 py-3">Fin de Vigencia</th>
                                <th scope="col" class="px-5 py-3">Estado</th>
                                <th scope="col" class="px-5 py-3 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-600">
                            <!-- Empresas vencidas -->
                            <tr v-for="emp in suscripciones.vencidas" :key="'vencida-' + emp.id" class="bg-rose-50/20 transition-colors hover:bg-rose-50/50">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-rose-600 text-[11px] font-black text-white">
                                            {{ iniciales(emp.name) }}
                                        </div>
                                        <span class="font-bold text-slate-900">{{ emp.name }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center rounded-lg bg-blue-50 px-2 py-1 text-[11px] font-bold capitalize text-blue-700">
                                        {{ emp.plan }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 font-mono text-[11px] font-bold text-rose-700">
                                    {{ emp.vigencia_hasta }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-1 text-[11px] font-bold text-rose-700 ring-1 ring-rose-600/20">
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                        Expirada
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <Link
                                        :href="`/admin/empresas/${emp.id}/edit`"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-3 py-1.5 text-[11px] font-bold text-blue-700 transition hover:bg-blue-600 hover:text-white"
                                    >
                                        Renovar / Editar
                                    </Link>
                                </td>
                            </tr>

                            <!-- Empresas por vencer -->
                            <tr v-for="emp in suscripciones.por_vencer" :key="'vence-' + emp.id" class="transition-colors hover:bg-slate-50/70">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-[11px] font-black text-white">
                                            {{ iniciales(emp.name) }}
                                        </div>
                                        <span class="font-bold text-slate-900">{{ emp.name }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center rounded-lg bg-blue-50 px-2 py-1 text-[11px] font-bold capitalize text-blue-700">
                                        {{ emp.plan }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 font-mono text-[11px] font-bold text-amber-700">
                                    {{ emp.vigencia_hasta }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-700 ring-1 ring-amber-600/20">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                        Próxima a vencer
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <Link
                                        :href="`/admin/empresas/${emp.id}/edit`"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-3 py-1.5 text-[11px] font-bold text-blue-700 transition hover:bg-blue-600 hover:text-white"
                                    >
                                        Extender plazo
                                    </Link>
                                </td>
                            </tr>

                            <tr v-if="suscripciones.vencidas.length === 0 && suscripciones.por_vencer.length === 0">
                                <td colspan="5" class="px-5 py-12">
                                    <div class="flex flex-col items-center text-center">
                                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <p class="mt-2.5 text-sm font-bold text-slate-800">Suscripciones al día</p>
                                        <p class="mt-0.5 text-xs text-slate-500">No existen empresas con vencimiento en los próximos 30 días.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabla de Errores Técnicos y Diagnóstico -->
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xs">
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <div
                            :class="[
                                'flex h-9 w-9 items-center justify-center rounded-xl',
                                colas.fallidos > 0 ? 'bg-rose-50 text-rose-600' : 'bg-slate-100 text-slate-600'
                            ]"
                        >
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-sm font-bold text-slate-900">Registro de Errores de Envío y Diagnóstico</h2>
                                <span
                                    v-if="colas.fallidos > 0"
                                    class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-0.5 text-[11px] font-bold text-rose-700 ring-1 ring-rose-600/20"
                                >
                                    {{ colas.fallidos }} fallo(s)
                                </span>
                            </div>
                            <p class="text-xs text-slate-500">Diagnóstico técnico de incidencias asíncronas con sanitización de datos personales</p>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            <tr>
                                <th scope="col" class="px-5 py-3">ID / Cola</th>
                                <th scope="col" class="px-5 py-3">Tipo de Trabajo</th>
                                <th scope="col" class="px-5 py-3">Diagnóstico Técnico</th>
                                <th scope="col" class="px-5 py-3">Fecha y Hora</th>
                                <th scope="col" class="px-5 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-600">
                            <tr v-for="fallo in colas.ultimos_fallos" :key="fallo.id" class="transition-colors hover:bg-slate-50/70">
                                <td class="whitespace-nowrap px-5 py-3.5">
                                    <div class="font-mono text-xs font-bold text-slate-900">#{{ fallo.id }}</div>
                                    <div class="font-mono text-[11px] text-slate-400">{{ fallo.queue }}</div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5">
                                    <span class="rounded-lg bg-slate-100 px-2.5 py-1 font-mono text-[11px] font-semibold text-slate-700">
                                        {{ fallo.job_name }}
                                    </span>
                                </td>
                                <td class="max-w-md px-5 py-3.5">
                                    <div class="break-words rounded-xl border border-rose-200/70 bg-rose-50/70 p-2.5 font-mono text-[11px] text-rose-700">
                                        {{ fallo.error_resumen }}
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 font-mono text-[11px] text-slate-500">
                                    {{ fallo.failed_at }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            type="button"
                                            @click="reintentarJob(fallo.id)"
                                            class="inline-flex cursor-pointer items-center gap-1 rounded-lg bg-blue-50 px-3 py-1.5 text-[11px] font-bold text-blue-700 transition hover:bg-blue-600 hover:text-white"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            Reintentar
                                        </button>
                                        <button
                                            type="button"
                                            @click="eliminarJob(fallo.id)"
                                            title="Descartar registro"
                                            class="cursor-pointer rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="colas.ultimos_fallos.length === 0">
                                <td colspan="5" class="px-5 py-12">
                                    <div class="flex flex-col items-center text-center">
                                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                        <p class="mt-2.5 text-sm font-bold text-slate-800">Todo funcionando con normalidad</p>
                                        <p class="mt-0.5 text-xs text-slate-500">No hay errores operativos ni trabajos pendientes en la cola de fallos.</p>
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
