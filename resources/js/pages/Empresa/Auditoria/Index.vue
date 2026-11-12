<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface LogItem {
    id: number;
    empresa_id: number;
    user_id: number | null;
    user: { id: number; name: string; email: string } | null;
    actor_tipo: string;
    actor_nombre: string;
    actor_email: string | null;
    modulo: string;
    accion: string;
    descripcion: string;
    datos_antes: any;
    datos_despues: any;
    ip: string | null;
    user_agent: string | null;
    created_at: string;
}

interface Props {
    logs: {
        data: LogItem[];
        current_page: number;
        last_page: number;
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    modulos: Array<{ value: string; label: string }>;
    filtros: {
        modulo: string;
        buscar: string;
        fecha_desde: string;
        fecha_hasta: string;
    };
}

const props = defineProps<Props>();

const formFiltros = ref({
    modulo: props.filtros.modulo || '',
    buscar: props.filtros.buscar || '',
    fecha_desde: props.filtros.fecha_desde || '',
    fecha_hasta: props.filtros.fecha_hasta || '',
});

const modalDetalle = ref<LogItem | null>(null);

const urlExportacion = computed(() => {
    const params = new URLSearchParams();
    if (formFiltros.value.modulo) params.set('modulo', formFiltros.value.modulo);
    if (formFiltros.value.buscar) params.set('buscar', formFiltros.value.buscar);
    if (formFiltros.value.fecha_desde) params.set('fecha_desde', formFiltros.value.fecha_desde);
    if (formFiltros.value.fecha_hasta) params.set('fecha_hasta', formFiltros.value.fecha_hasta);
    const qs = params.toString();
    return `/configuracion/auditoria/exportar${qs ? '?' + qs : ''}`;
});

let timeoutBusqueda: any = null;

function aplicarFiltros() {
    router.get('/configuracion/auditoria', formFiltros.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function limpiarFiltros() {
    formFiltros.value = {
        modulo: '',
        buscar: '',
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

function obtenerClaseModulo(modulo: string): string {
    switch (modulo) {
        case 'seguridad':
            return 'bg-purple-50 text-purple-700 ring-1 ring-purple-600/20';
        case 'configuracion':
            return 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20';
        case 'usuarios':
            return 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-600/20';
        case 'establecimientos':
            return 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20';
        case 'expedientes':
            return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20';
        default:
            return 'bg-slate-100 text-slate-700 ring-1 ring-slate-400/20';
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Historial de Auditoría | DatecLibro" />

        <div class="space-y-6">
            <!-- Cabecera de Página (Estilo Admin/Empresas) -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex items-start gap-4">
                    <div
                        class="hidden sm:flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-md shadow-blue-500/25"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            Configuración de Empresa · Trazabilidad y Seguridad
                        </div>
                        <h1 class="mt-0.5 text-2xl font-extrabold tracking-tight text-slate-900">
                            Historial de Auditoría de la Empresa
                        </h1>
                        <p class="mt-1 text-sm text-slate-500">
                            Registro inmutable de trazabilidad corporativa: cambios de configuración, accesos, respuestas y descargas.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a
                        :href="urlExportacion"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-bold text-white shadow-md shadow-blue-500/20 transition hover:bg-blue-700 active:scale-95"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>Exportar CSV</span>
                    </a>
                    <span class="inline-flex items-center gap-2 rounded-xl border border-slate-200/80 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-2xs">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        <span>{{ logs.total }} eventos auditados</span>
                    </span>
                </div>
            </div>

            <!-- Filtros de Auditoría (Estilo Admin/Empresas) -->
            <div class="space-y-4 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Módulo</label>
                        <select
                            v-model="formFiltros.modulo"
                            @change="aplicarFiltros"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
                        >
                            <option value="">Todos los módulos</option>
                            <option v-for="mod in modulos" :key="mod.value" :value="mod.value">
                                {{ mod.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Fecha Desde</label>
                        <input
                            type="date"
                            v-model="formFiltros.fecha_desde"
                            @change="aplicarFiltros"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                    </div>

                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Fecha Hasta</label>
                        <input
                            type="date"
                            v-model="formFiltros.fecha_hasta"
                            @change="aplicarFiltros"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-3 border-t border-slate-100">
                    <div class="relative w-full sm:max-w-md">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            type="text"
                            v-model="formFiltros.buscar"
                            placeholder="Buscar por descripción, usuario, email o IP..."
                            class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-xs text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                    </div>

                    <button
                        type="button"
                        @click="limpiarFiltros"
                        class="cursor-pointer text-xs font-bold text-blue-600 transition hover:text-blue-800"
                    >
                        Limpiar filtros
                    </button>
                </div>
            </div>

            <!-- Tabla de Eventos de Auditoría (Estilo Admin/Empresas) -->
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="py-3 px-5">Fecha / Hora</th>
                                <th class="py-3 px-5">Actor</th>
                                <th class="py-3 px-5">Módulo</th>
                                <th class="py-3 px-5">Descripción del Evento</th>
                                <th class="py-3 px-5">Dirección IP</th>
                                <th class="py-3 px-5 text-right">Datos</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="log in logs.data" :key="log.id" class="transition hover:bg-slate-50/60">
                                <td class="whitespace-nowrap py-3.5 px-5 font-mono text-[11px] text-slate-500">
                                    {{ new Date(log.created_at).toLocaleString('es-PE') }}
                                </td>
                                <td class="py-3.5 px-5">
                                    <div class="font-bold text-slate-900">{{ log.actor_nombre }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono">{{ log.actor_email }}</div>
                                </td>
                                <td class="whitespace-nowrap py-3.5 px-5">
                                    <span :class="['inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-bold', obtenerClaseModulo(log.modulo)]">
                                        {{ log.modulo }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 max-w-md text-slate-700">
                                    {{ log.descripcion }}
                                </td>
                                <td class="whitespace-nowrap py-3.5 px-5 font-mono text-[11px] text-slate-500">
                                    {{ log.ip || '—' }}
                                </td>
                                <td class="whitespace-nowrap py-3.5 px-5 text-right">
                                    <button
                                        v-if="log.datos_despues || log.datos_antes"
                                        type="button"
                                        @click="modalDetalle = log"
                                        class="cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50"
                                    >
                                        Ver datos
                                    </button>
                                    <span v-else class="text-slate-300 text-xs">—</span>
                                </td>
                            </tr>
                            <tr v-if="logs.data.length === 0">
                                <td colspan="6" class="py-12 text-center text-slate-400">
                                    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <p class="mt-3 text-xs font-semibold text-slate-600">No se encontraron eventos de auditoría</p>
                                    <p class="mt-1 text-[11px] text-slate-400">Prueba ajustando el filtro de fechas o el término de búsqueda.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="logs.last_page > 1" class="flex items-center justify-between border-t border-slate-100 p-4 text-xs">
                    <span class="text-slate-500">
                        Página {{ logs.current_page }} de {{ logs.last_page }} ({{ logs.total }} registros)
                    </span>
                    <div class="flex items-center gap-1">
                        <template v-for="(link, i) in logs.links" :key="i">
                            <button
                                v-if="link.url"
                                type="button"
                                @click="router.visit(link.url)"
                                :class="[
                                    'cursor-pointer rounded-xl px-3 py-1.5 font-bold transition',
                                    link.active ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'text-slate-600 hover:bg-slate-100'
                                ]"
                                v-html="link.label"
                            ></button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Modal de Detalle de Auditoría (Estilo CreateModal.vue) -->
            <div
                v-if="modalDetalle"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 sm:p-6 backdrop-blur-xs overflow-y-auto animate-fade-in"
                @click.self="modalDetalle = null"
            >
                <div class="relative flex max-h-[88vh] w-full max-w-xl flex-col rounded-2xl border border-slate-200 bg-white shadow-2xl overflow-hidden my-auto">
                    <!-- Cabecera del Modal -->
                    <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-purple-600 text-white shadow-md shadow-purple-500/25">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-base font-extrabold text-slate-900">
                                    Detalle de Auditoría #{{ modalDetalle.id }}
                                </h2>
                                <p class="text-xs text-slate-500 font-mono">
                                    {{ modalDetalle.accion }} · {{ new Date(modalDetalle.created_at).toLocaleString('es-PE') }}
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="modalDetalle = null"
                            class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-200 hover:text-slate-700 cursor-pointer"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-6 space-y-4 text-xs">
                        <div>
                            <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Descripción de la Operación</span>
                            <p class="text-slate-700 bg-slate-50/80 p-3.5 rounded-xl border border-slate-200/80 font-medium leading-relaxed">{{ modalDetalle.descripcion }}</p>
                        </div>

                        <div v-if="modalDetalle.datos_antes">
                            <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Valores Anteriores (Antes)</span>
                            <pre class="bg-slate-900 text-slate-100 p-3.5 rounded-xl overflow-x-auto text-[11px] font-mono shadow-inner leading-normal">{{ JSON.stringify(modalDetalle.datos_antes, null, 2) }}</pre>
                        </div>

                        <div v-if="modalDetalle.datos_despues">
                            <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Valores Registrados (Después)</span>
                            <pre class="bg-slate-900 text-slate-100 p-3.5 rounded-xl overflow-x-auto text-[11px] font-mono shadow-inner leading-normal">{{ JSON.stringify(modalDetalle.datos_despues, null, 2) }}</pre>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 rounded-xl border border-slate-200/80 bg-slate-50/70 p-3.5 text-[11px] text-slate-600">
                            <div><strong class="font-bold text-slate-800">Dirección IP:</strong> <span class="font-mono text-slate-700">{{ modalDetalle.ip || 'No registrada' }}</span></div>
                            <div><strong class="font-bold text-slate-800">Actor Responsable:</strong> <span class="text-slate-700">{{ modalDetalle.actor_nombre }} ({{ modalDetalle.actor_email }})</span></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end border-t border-slate-100 bg-slate-50/50 px-6 py-4">
                        <button
                            type="button"
                            @click="modalDetalle = null"
                            class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 hover:text-slate-900 transition cursor-pointer"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
