<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface ExpedienteItem {
    id: number;
    numero_hoja: string;
    fecha_hora: string;
    tipo: string;
    tipo_label: string;
    tipo_bien: string;
    descripcion_bien: string;
    monto_reclamado: string | null;
    estado: string;
    estado_label: string;
    consumidor_nombre: string;
    consumidor_documento: string;
    establecimiento: { id: number; nombre: string } | null;
    responsable: { id: number; name: string } | null;
    vence_en: string | null;
    semaforo: {
        color: 'success' | 'amber' | 'warning' | 'danger' | 'neutral';
        etiqueta: string;
        dias_restantes: number;
        vencido: boolean;
    } | null;
}

interface Props {
    expedientes: {
        data: ExpedienteItem[];
        current_page: number;
        last_page: number;
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filtros: {
        buscar: string;
        estado: string;
        tipo: string;
        establecimiento_id: string;
        fecha_desde: string;
        fecha_hasta: string;
    };
    metricas: {
        total: number;
        pendientes: number;
        en_tramite: number;
        atendidas: number;
        cerradas: number;
    };
    establecimientos: Array<{ id: number; codigo: string; nombre: string }>;
    estados: Array<{ value: string; label: string }>;
    tipos: Array<{ value: string; label: string }>;
}

const props = defineProps<Props>();

const formFiltros = ref({
    buscar: props.filtros.buscar || '',
    estado: props.filtros.estado || '',
    tipo: props.filtros.tipo || '',
    establecimiento_id: props.filtros.establecimiento_id || '',
    fecha_desde: props.filtros.fecha_desde || '',
    fecha_hasta: props.filtros.fecha_hasta || '',
});

let timeoutBusqueda: any = null;

function aplicarFiltros() {
    router.get('/expedientes', formFiltros.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function filtrarPorEstado(estadoValor: string) {
    formFiltros.value.estado = estadoValor;
    aplicarFiltros();
}

function limpiarFiltros() {
    formFiltros.value = {
        buscar: '',
        estado: '',
        tipo: '',
        establecimiento_id: '',
        fecha_desde: '',
        fecha_hasta: '',
    };
    aplicarFiltros();
}

watch(
    () => formFiltros.value.buscar,
    () => {
        clearTimeout(timeoutBusqueda);
        timeoutBusqueda = setTimeout(() => {
            aplicarFiltros();
        }, 350);
    }
);

// Clases CSS de insignia para estados
function getBadgeEstado(estado: string) {
    switch (estado) {
        case 'pendiente':
            return { clase: 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20', punto: 'bg-amber-500' };
        case 'en_tramite':
            return { clase: 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20', punto: 'bg-blue-500' };
        case 'atendida':
            return { clase: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20', punto: 'bg-emerald-500' };
        case 'cerrada':
            return { clase: 'bg-slate-100 text-slate-700 ring-1 ring-slate-400/20', punto: 'bg-slate-400' };
        default:
            return { clase: 'bg-slate-50 text-slate-600 ring-1 ring-slate-400/20', punto: 'bg-slate-400' };
    }
}

// Clases CSS del semáforo legal
function getBadgeSemaforo(color: string | undefined) {
    switch (color) {
        case 'danger':
            return { clase: 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20', punto: 'bg-rose-500' };
        case 'warning':
            return { clase: 'bg-orange-50 text-orange-700 ring-1 ring-orange-600/20', punto: 'bg-orange-500' };
        case 'amber':
            return { clase: 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20', punto: 'bg-amber-500' };
        case 'success':
            return { clase: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20', punto: 'bg-emerald-500' };
        default:
            return { clase: 'bg-slate-50 text-slate-600 ring-1 ring-slate-400/20', punto: 'bg-slate-400' };
    }
}

// Descarga de reporte exportado con filtros actuales
function exportar(formato: 'csv' | 'pdf') {
    const params = new URLSearchParams();
    if (formFiltros.value.buscar) params.append('buscar', formFiltros.value.buscar);
    if (formFiltros.value.estado) params.append('estado', formFiltros.value.estado);
    if (formFiltros.value.tipo) params.append('tipo', formFiltros.value.tipo);
    if (formFiltros.value.establecimiento_id) params.append('establecimiento_id', formFiltros.value.establecimiento_id);
    if (formFiltros.value.fecha_desde) params.append('fecha_desde', formFiltros.value.fecha_desde);
    if (formFiltros.value.fecha_hasta) params.append('fecha_hasta', formFiltros.value.fecha_hasta);
    params.append('formato', formato);

    window.location.href = `/expedientes/exportar?${params.toString()}`;
}

// =========================================================================
// MODO E-COMMERCE / ADMINISTRADOR ÚNICO:
// Cambiar a 'true' para reactivar la columna de responsable asignado en la bandeja
// =========================================================================
const HABILITAR_COLUMNA_RESPONSABLE = false;
</script>

<template>
    <AdminLayout>
        <Head title="Bandeja de Reclamaciones | DatecLibro" />

        <div class="space-y-6">
            <!-- Cabecera de Página (Estilo Admin/Empresas) -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex items-start gap-4">
                    <div
                        class="hidden sm:flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-md shadow-blue-500/25"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            Atención de Reclamos · Libro de Reclamaciones Virtual
                        </div>
                        <h1 class="mt-0.5 text-2xl font-extrabold tracking-tight text-slate-900">
                            Bandeja de Reclamaciones y Quejas
                        </h1>
                        <p class="mt-1 text-sm text-slate-500">
                            Control y seguimiento oficial de expedientes conforme a la Ley N° 29571 y plazos de INDECOPI.
                        </p>
                    </div>
                </div>

                <!-- Botones de Acción Superior (Exportar y Semáforo) -->
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        @click="exportar('csv')"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50"
                        title="Exportar registros filtrados a CSV / Excel"
                    >
                        <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Exportar CSV</span>
                    </button>

                    <button
                        type="button"
                        @click="exportar('pdf')"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50"
                        title="Descargar reporte oficial de expedientes en PDF"
                    >
                        <svg class="h-4 w-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <span>Reporte PDF</span>
                    </button>

                    <span class="inline-flex items-center gap-2 rounded-xl border border-blue-200/80 bg-blue-50/70 px-3.5 py-2 text-xs font-bold text-blue-700 shadow-2xs">
                        <span class="h-2 w-2 rounded-full bg-blue-600 animate-pulse"></span>
                        <span>Plazo Legal: 15 días hábiles</span>
                    </span>
                </div>
            </div>

            <!-- Tarjetas de Métricas de Estado (KPIs Interactivos - Estilo Admin/Empresas) -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                <!-- Total Expedientes -->
                <button
                    type="button"
                    @click="filtrarPorEstado('')"
                    :class="[
                        'rounded-2xl border p-5 text-left shadow-2xs transition cursor-pointer',
                        formFiltros.estado === ''
                            ? 'border-blue-500 bg-blue-50/40 ring-2 ring-blue-500/20'
                            : 'border-slate-200/80 bg-white hover:border-slate-300'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Casos</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-3xl font-black text-slate-900">{{ metricas.total }}</p>
                    <p class="mt-1 text-[11px] font-medium text-slate-400">Todos los expedientes</p>
                </button>

                <!-- Pendientes -->
                <button
                    type="button"
                    @click="filtrarPorEstado('pendiente')"
                    :class="[
                        'rounded-2xl border p-5 text-left shadow-2xs transition cursor-pointer',
                        formFiltros.estado === 'pendiente'
                            ? 'border-amber-500 bg-amber-50/40 ring-2 ring-amber-500/20'
                            : 'border-slate-200/80 bg-white hover:border-slate-300'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Pendientes</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-3xl font-black text-amber-900">{{ metricas.pendientes }}</p>
                    <p class="mt-1 text-[11px] font-medium text-amber-600">Sin gestión iniciada</p>
                </button>

                <!-- En Trámite -->
                <button
                    type="button"
                    @click="filtrarPorEstado('en_tramite')"
                    :class="[
                        'rounded-2xl border p-5 text-left shadow-2xs transition cursor-pointer',
                        formFiltros.estado === 'en_tramite'
                            ? 'border-blue-500 bg-blue-50/40 ring-2 ring-blue-500/20'
                            : 'border-slate-200/80 bg-white hover:border-slate-300'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-blue-700">En Trámite</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-3xl font-black text-blue-900">{{ metricas.en_tramite }}</p>
                    <p class="mt-1 text-[11px] font-medium text-blue-600">En revisión / asignados</p>
                </button>

                <!-- Atendidas -->
                <button
                    type="button"
                    @click="filtrarPorEstado('atendida')"
                    :class="[
                        'rounded-2xl border p-5 text-left shadow-2xs transition cursor-pointer',
                        formFiltros.estado === 'atendida'
                            ? 'border-emerald-500 bg-emerald-50/40 ring-2 ring-emerald-500/20'
                            : 'border-slate-200/80 bg-white hover:border-slate-300'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Atendidas</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-3xl font-black text-emerald-900">{{ metricas.atendidas }}</p>
                    <p class="mt-1 text-[11px] font-medium text-emerald-600">Respuesta notificada</p>
                </button>

                <!-- Cerradas -->
                <button
                    type="button"
                    @click="filtrarPorEstado('cerrada')"
                    :class="[
                        'rounded-2xl border p-5 text-left shadow-2xs transition cursor-pointer',
                        formFiltros.estado === 'cerrada'
                            ? 'border-slate-500 bg-slate-100 ring-2 ring-slate-500/20'
                            : 'border-slate-200/80 bg-white hover:border-slate-300'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Cerradas</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-3xl font-black text-slate-900">{{ metricas.cerradas }}</p>
                    <p class="mt-1 text-[11px] font-medium text-slate-400">Proceso concluido</p>
                </button>
            </div>

            <!-- Barra de Filtros Multicriterio (Estilo Admin/Empresas) -->
            <div class="space-y-4 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-12">
                    <!-- Búsqueda Global -->
                    <div class="relative lg:col-span-4">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            v-model="formFiltros.buscar"
                            type="text"
                            placeholder="Buscar por hoja (ej. 00000001-2026), consumidor, DNI..."
                            class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-xs text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                    </div>

                    <!-- Filtro por Tipo (Reclamo / Queja) -->
                    <div class="lg:col-span-2">
                        <select
                            v-model="formFiltros.tipo"
                            @change="aplicarFiltros"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
                        >
                            <option value="">Todos los tipos</option>
                            <option v-for="t in tipos" :key="t.value" :value="t.value">
                                {{ t.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Filtro por Sede / Establecimiento -->
                    <div class="lg:col-span-3">
                        <select
                            v-model="formFiltros.establecimiento_id"
                            @change="aplicarFiltros"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
                        >
                            <option value="">Todas las sedes</option>
                            <option v-for="est in establecimientos" :key="est.id" :value="est.id">
                                {{ est.nombre }}
                            </option>
                        </select>
                    </div>

                    <!-- Rango de Fechas -->
                    <div class="flex items-center gap-2 lg:col-span-3">
                        <input
                            v-model="formFiltros.fecha_desde"
                            @change="aplicarFiltros"
                            type="date"
                            title="Fecha desde"
                            class="w-1/2 rounded-xl border border-slate-200 bg-white px-2 py-2 text-xs font-medium text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                        <span class="text-slate-300">-</span>
                        <input
                            v-model="formFiltros.fecha_hasta"
                            @change="aplicarFiltros"
                            type="date"
                            title="Fecha hasta"
                            class="w-1/2 rounded-xl border border-slate-200 bg-white px-2 py-2 text-xs font-medium text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-between border-t border-slate-100 pt-3 text-xs">
                    <span class="text-slate-500">
                        Mostrando <strong class="text-slate-800">{{ expedientes.total }}</strong> expedientes registrados
                    </span>

                    <button
                        type="button"
                        @click="limpiarFiltros"
                        class="cursor-pointer text-xs font-bold text-blue-600 transition hover:text-blue-800"
                    >
                        Limpiar filtros
                    </button>
                </div>
            </div>

            <!-- Tabla de Expedientes (Estilo Admin/Empresas) -->
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="py-3 px-5">Hoja / Fecha</th>
                                <th class="py-3 px-4">Tipo</th>
                                <th class="py-3 px-4">Consumidor</th>
                                <th class="py-3 px-4">Bien / Monto</th>
                                <th class="py-3 px-4">Estado</th>
                                <th class="py-3 px-4">Semáforo Legal</th>
                                <th class="py-3 px-4">{{ HABILITAR_COLUMNA_RESPONSABLE ? 'Responsable' : 'Sede / Canal' }}</th>
                                <th class="py-3 px-5 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr
                                v-for="exp in expedientes.data"
                                :key="exp.id"
                                class="transition hover:bg-slate-50/60"
                            >
                                <!-- Hoja / Fecha -->
                                <td class="whitespace-nowrap py-3.5 px-5">
                                    <div class="font-mono font-bold text-slate-900">
                                        {{ exp.numero_hoja }}
                                    </div>
                                    <div class="text-[11px] text-slate-400">
                                        {{ exp.fecha_hora }}
                                    </div>
                                </td>

                                <!-- Tipo (Reclamo / Queja) -->
                                <td class="whitespace-nowrap py-3.5 px-4">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-bold ring-1"
                                        :class="exp.tipo === 'reclamo' ? 'bg-indigo-50 text-indigo-700 ring-indigo-600/20' : 'bg-purple-50 text-purple-700 ring-purple-600/20'"
                                    >
                                        {{ exp.tipo_label }}
                                    </span>
                                </td>

                                <!-- Consumidor -->
                                <td class="py-3.5 px-4">
                                    <div class="max-w-[200px] truncate font-bold text-slate-900">
                                        {{ exp.consumidor_nombre }}
                                    </div>
                                    <div class="text-[11px] text-slate-400">
                                        {{ exp.consumidor_documento }}
                                    </div>
                                </td>

                                <!-- Bien / Monto -->
                                <td class="py-3.5 px-4">
                                    <div class="max-w-[180px] truncate font-medium text-slate-800" :title="exp.descripcion_bien">
                                        {{ exp.descripcion_bien }}
                                    </div>
                                    <div class="text-[11px] font-semibold text-slate-500">
                                        {{ exp.monto_reclamado || 'Sin monto' }}
                                    </div>
                                </td>

                                <!-- Estado -->
                                <td class="whitespace-nowrap py-3.5 px-4">
                                    <span
                                        :class="['inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[10px] font-bold', getBadgeEstado(exp.estado).clase]"
                                    >
                                        <span :class="['h-1.5 w-1.5 rounded-full', getBadgeEstado(exp.estado).punto]"></span>
                                        {{ exp.estado_label }}
                                    </span>
                                </td>

                                <!-- Semáforo Legal -->
                                <td class="whitespace-nowrap py-3.5 px-4">
                                    <div v-if="exp.semaforo" class="flex items-center gap-2">
                                        <span
                                            :class="['inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[10px] font-bold', getBadgeSemaforo(exp.semaforo.color).clase]"
                                        >
                                            <span :class="['h-1.5 w-1.5 rounded-full', getBadgeSemaforo(exp.semaforo.color).punto]"></span>
                                            {{ exp.semaforo.etiqueta }}
                                        </span>
                                    </div>
                                    <span v-else class="text-[11px] text-slate-400">—</span>
                                </td>

                                <!-- Sede / Canal (Modo E-commerce) o Responsable (Modo Multi-usuario) -->
                                <td v-if="!HABILITAR_COLUMNA_RESPONSABLE" class="whitespace-nowrap py-3.5 px-4">
                                    <div class="text-xs font-semibold text-slate-800">
                                        {{ exp.establecimiento?.nombre || 'Tienda Virtual' }}
                                    </div>
                                    <div class="text-[10px] text-slate-400">
                                        {{ exp.establecimiento ? 'Sede Física' : 'Online / Web' }}
                                    </div>
                                </td>
                                <td v-else class="whitespace-nowrap py-3.5 px-4">
                                    <div v-if="exp.responsable" class="text-xs font-semibold text-slate-800">
                                        {{ exp.responsable.name }}
                                    </div>
                                    <span v-else class="text-[11px] italic text-slate-400">Sin asignar</span>
                                </td>

                                <!-- Acciones -->
                                <td class="whitespace-nowrap py-3.5 px-5 text-right">
                                    <Link
                                        :href="`/expedientes/${exp.id}`"
                                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-2xs transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700"
                                    >
                                        <span>Ver Ficha</span>
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </Link>
                                </td>
                            </tr>

                            <tr v-if="expedientes.data.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <p class="mt-3 text-xs font-semibold text-slate-600">No se encontraron expedientes</p>
                                    <p class="mt-1 text-[11px] text-slate-400">Prueba cambiando los criterios de filtro o término de búsqueda.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="expedientes.last_page > 1" class="flex items-center justify-between border-t border-slate-100 p-4 text-xs">
                    <span class="text-slate-500">
                        Página {{ expedientes.current_page }} de {{ expedientes.last_page }}
                    </span>

                    <div class="flex items-center gap-1">
                        <template v-for="(link, i) in expedientes.links" :key="i">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    'rounded-xl px-3 py-1.5 font-bold transition',
                                    link.active
                                        ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                        : 'text-slate-600 hover:bg-slate-100'
                                ]"
                                v-html="link.label"
                            />
                            <span v-else class="px-3 py-1.5 text-slate-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
