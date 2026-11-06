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
function obtenerClaseEstado(estado: string): string {
    switch (estado) {
        case 'pendiente':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'en_tramite':
            return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'atendida':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'cerrada':
            return 'bg-slate-100 text-slate-700 border-slate-200';
        default:
            return 'bg-slate-50 text-slate-600 border-slate-200';
    }
}

// Clases CSS del semáforo legal
function obtenerClaseSemaforo(color: string | undefined): string {
    switch (color) {
        case 'danger':
            return 'bg-rose-50 text-rose-700 border-rose-200';
        case 'warning':
            return 'bg-orange-50 text-orange-700 border-orange-200';
        case 'amber':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'success':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        default:
            return 'bg-slate-50 text-slate-600 border-slate-200';
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
</script>

<template>
    <AdminLayout>
        <Head title="Bandeja de Reclamaciones | DatecLibro" />

        <div class="space-y-6">
            <!-- Encabezado de la Bandeja -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">
                        Bandeja de Reclamaciones y Quejas
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Control y seguimiento oficial de expedientes conforme a la Ley N° 29571 y plazos de INDECOPI.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="exportar('csv')"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 shadow-xs hover:bg-slate-50 transition cursor-pointer"
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
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 shadow-xs hover:bg-slate-50 transition cursor-pointer"
                        title="Descargar reporte oficial de expedientes en PDF"
                    >
                        <svg class="h-4 w-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <span>Reporte PDF</span>
                    </button>

                    <span class="hidden sm:inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700 border border-blue-200">
                        <span class="h-2 w-2 rounded-full bg-blue-600 animate-pulse"></span>
                        <span>Plazo: 15 días hábiles</span>
                    </span>
                </div>
            </div>

            <!-- Tarjetas de Métricas de Estado (KPIs) -->
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                <button
                    type="button"
                    @click="filtrarPorEstado('')"
                    :class="[
                        'rounded-2xl border p-4 text-left transition cursor-pointer shadow-2xs',
                        formFiltros.estado === ''
                            ? 'border-blue-500 bg-blue-50/50 ring-2 ring-blue-500/20'
                            : 'border-slate-200 bg-white hover:border-slate-300'
                    ]"
                >
                    <div class="text-[11px] font-bold uppercase text-slate-400">Total Expedientes</div>
                    <div class="mt-1 text-2xl font-extrabold text-slate-900">{{ metricas.total }}</div>
                </button>

                <button
                    type="button"
                    @click="filtrarPorEstado('pendiente')"
                    :class="[
                        'rounded-2xl border p-4 text-left transition cursor-pointer shadow-2xs',
                        formFiltros.estado === 'pendiente'
                            ? 'border-amber-500 bg-amber-50/50 ring-2 ring-amber-500/20'
                            : 'border-slate-200 bg-white hover:border-slate-300'
                    ]"
                >
                    <div class="text-[11px] font-bold uppercase text-amber-600 flex items-center gap-1">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                        Pendientes
                    </div>
                    <div class="mt-1 text-2xl font-extrabold text-slate-900">{{ metricas.pendientes }}</div>
                </button>

                <button
                    type="button"
                    @click="filtrarPorEstado('en_tramite')"
                    :class="[
                        'rounded-2xl border p-4 text-left transition cursor-pointer shadow-2xs',
                        formFiltros.estado === 'en_tramite'
                            ? 'border-blue-500 bg-blue-50/50 ring-2 ring-blue-500/20'
                            : 'border-slate-200 bg-white hover:border-slate-300'
                    ]"
                >
                    <div class="text-[11px] font-bold uppercase text-blue-600 flex items-center gap-1">
                        <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                        En Trámite
                    </div>
                    <div class="mt-1 text-2xl font-extrabold text-slate-900">{{ metricas.en_tramite }}</div>
                </button>

                <button
                    type="button"
                    @click="filtrarPorEstado('atendida')"
                    :class="[
                        'rounded-2xl border p-4 text-left transition cursor-pointer shadow-2xs',
                        formFiltros.estado === 'atendida'
                            ? 'border-emerald-500 bg-emerald-50/50 ring-2 ring-emerald-500/20'
                            : 'border-slate-200 bg-white hover:border-slate-300'
                    ]"
                >
                    <div class="text-[11px] font-bold uppercase text-emerald-600 flex items-center gap-1">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        Atendidas
                    </div>
                    <div class="mt-1 text-2xl font-extrabold text-slate-900">{{ metricas.atendidas }}</div>
                </button>

                <button
                    type="button"
                    @click="filtrarPorEstado('cerrada')"
                    :class="[
                        'rounded-2xl border p-4 text-left transition cursor-pointer shadow-2xs',
                        formFiltros.estado === 'cerrada'
                            ? 'border-slate-500 bg-slate-100 ring-2 ring-slate-500/20'
                            : 'border-slate-200 bg-white hover:border-slate-300'
                    ]"
                >
                    <div class="text-[11px] font-bold uppercase text-slate-600 flex items-center gap-1">
                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                        Cerradas
                    </div>
                    <div class="mt-1 text-2xl font-extrabold text-slate-900">{{ metricas.cerradas }}</div>
                </button>
            </div>

            <!-- Barra de Filtros Multicriterio -->
            <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-2xs space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3">
                    <!-- Búsqueda Global -->
                    <div class="lg:col-span-4 relative">
                        <input
                            v-model="formFiltros.buscar"
                            type="text"
                            placeholder="Buscar por hoja (ej. 00000001-2026), consumidor, DNI..."
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 pl-9 pr-3 py-2 text-xs font-medium text-slate-800 placeholder-slate-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Filtro por Tipo (Reclamo / Queja) -->
                    <div class="lg:col-span-2">
                        <select
                            v-model="formFiltros.tipo"
                            @change="aplicarFiltros"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs font-medium text-slate-800 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
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
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs font-medium text-slate-800 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
                        >
                            <option value="">Todas las sedes</option>
                            <option v-for="est in establecimientos" :key="est.id" :value="est.id">
                                {{ est.nombre }}
                            </option>
                        </select>
                    </div>

                    <!-- Rango de Fechas -->
                    <div class="lg:col-span-3 flex items-center gap-2">
                        <input
                            v-model="formFiltros.fecha_desde"
                            @change="aplicarFiltros"
                            type="date"
                            title="Fecha desde"
                            class="w-1/2 rounded-xl border border-slate-200 bg-slate-50/50 px-2 py-2 text-xs font-medium text-slate-700 focus:border-blue-500 focus:bg-white focus:outline-none"
                        />
                        <span class="text-slate-300">-</span>
                        <input
                            v-model="formFiltros.fecha_hasta"
                            @change="aplicarFiltros"
                            type="date"
                            title="Fecha hasta"
                            class="w-1/2 rounded-xl border border-slate-200 bg-slate-50/50 px-2 py-2 text-xs font-medium text-slate-700 focus:border-blue-500 focus:bg-white focus:outline-none"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs pt-1">
                    <span class="text-slate-500">
                        Mostrando <strong class="text-slate-800">{{ expedientes.total }}</strong> expedientes registrados
                    </span>

                    <button
                        type="button"
                        @click="limpiarFiltros"
                        class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition cursor-pointer"
                    >
                        Limpiar filtros
                    </button>
                </div>
            </div>

            <!-- Tabla de Expedientes -->
            <div class="rounded-2xl border border-slate-200/90 bg-white overflow-hidden shadow-2xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50/75 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                <th class="py-3 px-4">Hoja / Fecha</th>
                                <th class="py-3 px-4">Tipo</th>
                                <th class="py-3 px-4">Consumidor</th>
                                <th class="py-3 px-4">Bien / Monto</th>
                                <th class="py-3 px-4">Estado</th>
                                <th class="py-3 px-4">Semáforo Legal</th>
                                <th class="py-3 px-4">Responsable</th>
                                <th class="py-3 px-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                            <tr
                                v-for="exp in expedientes.data"
                                :key="exp.id"
                                class="hover:bg-blue-50/30 transition group"
                            >
                                <!-- Hoja / Fecha -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="font-extrabold text-slate-900 group-hover:text-blue-600 transition">
                                        {{ exp.numero_hoja }}
                                    </div>
                                    <div class="text-[11px] text-slate-400">
                                        {{ exp.fecha_hora }}
                                    </div>
                                </td>

                                <!-- Tipo (Reclamo / Queja) -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-extrabold border"
                                        :class="exp.tipo === 'reclamo' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-purple-50 text-purple-700 border-purple-200'"
                                    >
                                        {{ exp.tipo_label }}
                                    </span>
                                </td>

                                <!-- Consumidor -->
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900 truncate max-w-[200px]">
                                        {{ exp.consumidor_nombre }}
                                    </div>
                                    <div class="text-[11px] text-slate-400">
                                        {{ exp.consumidor_documento }}
                                    </div>
                                </td>

                                <!-- Bien / Monto -->
                                <td class="py-3.5 px-4">
                                    <div class="truncate max-w-[180px] text-slate-800" :title="exp.descripcion_bien">
                                        {{ exp.descripcion_bien }}
                                    </div>
                                    <div class="text-[11px] font-semibold text-slate-500">
                                        {{ exp.monto_reclamado || 'Sin monto' }}
                                    </div>
                                </td>

                                <!-- Estado -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[10px] font-extrabold border"
                                        :class="obtenerClaseEstado(exp.estado)"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                        {{ exp.estado_label }}
                                    </span>
                                </td>

                                <!-- Semáforo Legal -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div v-if="exp.semaforo" class="flex items-center gap-2">
                                        <span
                                            class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-bold border"
                                            :class="obtenerClaseSemaforo(exp.semaforo.color)"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                            {{ exp.semaforo.etiqueta }}
                                        </span>
                                    </div>
                                    <span v-else class="text-[11px] text-slate-400">—</span>
                                </td>

                                <!-- Responsable -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div v-if="exp.responsable" class="text-xs text-slate-800 font-semibold">
                                        {{ exp.responsable.name }}
                                    </div>
                                    <span v-else class="text-[11px] text-slate-400 italic">Sin asignar</span>
                                </td>

                                <!-- Acciones -->
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <Link
                                        :href="`/expedientes/${exp.id}`"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-2xs hover:bg-blue-50 hover:text-blue-700 hover:border-blue-200 transition"
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
                                    <svg class="mx-auto h-10 w-10 text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="text-sm font-semibold text-slate-600">No se encontraron expedientes</p>
                                    <p class="text-xs text-slate-400 mt-1">Prueba cambiando los criterios de filtro o término de búsqueda.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="expedientes.last_page > 1" class="border-t border-slate-100 p-4 flex items-center justify-between text-xs">
                    <span class="text-slate-500">
                        Página {{ expedientes.current_page }} de {{ expedientes.last_page }}
                    </span>

                    <div class="flex items-center gap-1">
                        <template v-for="(link, i) in expedientes.links" :key="i">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    'rounded-lg px-2.5 py-1 font-bold transition',
                                    link.active
                                        ? 'bg-blue-600 text-white shadow-2xs'
                                        : 'text-slate-600 hover:bg-slate-100'
                                ]"
                                v-html="link.label"
                            />
                            <span v-else class="px-2 py-1 text-slate-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

