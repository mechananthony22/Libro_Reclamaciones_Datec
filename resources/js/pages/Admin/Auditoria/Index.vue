<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface LogItem {
    id: number;
    empresa_id: number | null;
    empresa: { id: number; name: string; slug: string } | null;
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
    empresas: Array<{ id: number; name: string; slug: string }>;
    modulos: Array<{ value: string; label: string }>;
    filtros: {
        empresa_id: string;
        modulo: string;
        accion: string;
        buscar: string;
        fecha_desde: string;
        fecha_hasta: string;
    };
}

const props = defineProps<Props>();

const formFiltros = ref({
    empresa_id: props.filtros.empresa_id || '',
    modulo: props.filtros.modulo || '',
    accion: props.filtros.accion || '',
    buscar: props.filtros.buscar || '',
    fecha_desde: props.filtros.fecha_desde || '',
    fecha_hasta: props.filtros.fecha_hasta || '',
});

const modalDetalle = ref<LogItem | null>(null);

let timeoutBusqueda: any = null;

function aplicarFiltros() {
    router.get('/admin/auditoria', formFiltros.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function limpiarFiltros() {
    formFiltros.value = {
        empresa_id: '',
        modulo: '',
        accion: '',
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
            return 'bg-purple-50 text-purple-700 border-purple-200';
        case 'empresas':
            return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'configuracion':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'usuarios':
            return 'bg-indigo-50 text-indigo-700 border-indigo-200';
        case 'expedientes':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        default:
            return 'bg-slate-50 text-slate-700 border-slate-200';
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Bitácora de Auditoría Global | Superadmin Datec" />

        <div class="space-y-6">
            <!-- Encabezado -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-5">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-slate-900">
                        Bitácora Central de Auditoría
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Historial inmutable de eventos, altas, cambios de configuración, accesos privilegiados y exportaciones.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        <span>{{ logs.total }} eventos registrados</span>
                    </span>
                </div>
            </div>

            <!-- Filtros de Auditoría -->
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-2xs space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Empresa</label>
                        <select
                            v-model="formFiltros.empresa_id"
                            @change="aplicarFiltros"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 focus:border-blue-500 focus:outline-none"
                        >
                            <option value="">Todas las empresas</option>
                            <option v-for="emp in empresas" :key="emp.id" :value="emp.id">
                                {{ emp.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Módulo</label>
                        <select
                            v-model="formFiltros.modulo"
                            @change="aplicarFiltros"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 focus:border-blue-500 focus:outline-none"
                        >
                            <option value="">Todos los módulos</option>
                            <option v-for="mod in modulos" :key="mod.value" :value="mod.value">
                                {{ mod.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Fecha Desde</label>
                        <input
                            type="date"
                            v-model="formFiltros.fecha_desde"
                            @change="aplicarFiltros"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 focus:border-blue-500 focus:outline-none"
                        />
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Fecha Hasta</label>
                        <input
                            type="date"
                            v-model="formFiltros.fecha_hasta"
                            @change="aplicarFiltros"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 focus:border-blue-500 focus:outline-none"
                        />
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2 border-t border-slate-100">
                    <div class="relative w-full sm:max-w-md">
                        <input
                            type="text"
                            v-model="formFiltros.buscar"
                            placeholder="Buscar por descripción, usuario, email o IP..."
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 pl-9 pr-3 py-2 text-xs font-medium text-slate-800 placeholder-slate-400 focus:border-blue-500 focus:bg-white focus:outline-none"
                        />
                        <svg class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <button
                        type="button"
                        @click="limpiarFiltros"
                        class="text-xs font-bold text-slate-500 hover:text-slate-800 transition cursor-pointer"
                    >
                        Limpiar filtros
                    </button>
                </div>
            </div>

            <!-- Tabla de Eventos de Auditoría -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/70 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                <th class="py-3 px-4">Fecha / Hora</th>
                                <th class="py-3 px-4">Empresa</th>
                                <th class="py-3 px-4">Actor</th>
                                <th class="py-3 px-4">Módulo / Acción</th>
                                <th class="py-3 px-4">Descripción</th>
                                <th class="py-3 px-4">IP</th>
                                <th class="py-3 px-4 text-right">Detalle</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-slate-50/80 transition">
                                <td class="py-3 px-4 whitespace-nowrap font-mono text-[11px] text-slate-500">
                                    {{ new Date(log.created_at).toLocaleString('es-PE') }}
                                </td>
                                <td class="py-3 px-4 font-semibold text-slate-800">
                                    <span v-if="log.empresa">{{ log.empresa.name }}</span>
                                    <span v-else class="text-slate-400 italic">Global (Plataforma)</span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-800">{{ log.actor_nombre }}</div>
                                    <div class="text-[10px] text-slate-400">{{ log.actor_email || log.actor_tipo }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <span :class="['inline-flex rounded-md px-2 py-0.5 text-[10px] font-bold border', obtenerClaseModulo(log.modulo)]">
                                        {{ log.modulo }}
                                    </span>
                                    <div class="font-mono text-[10px] text-slate-500 mt-0.5">{{ log.accion }}</div>
                                </td>
                                <td class="py-3 px-4 max-w-md text-slate-700">
                                    {{ log.descripcion }}
                                </td>
                                <td class="py-3 px-4 font-mono text-[10px] text-slate-500 whitespace-nowrap">
                                    {{ log.ip || '-' }}
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <button
                                        v-if="log.datos_despues || log.datos_antes"
                                        type="button"
                                        @click="modalDetalle = log"
                                        class="rounded-lg bg-slate-100 px-2 py-1 text-[11px] font-bold text-slate-700 hover:bg-slate-200 transition cursor-pointer"
                                    >
                                        Ver datos
                                    </button>
                                    <span v-else class="text-slate-300 text-[11px]">-</span>
                                </td>
                            </tr>
                            <tr v-if="logs.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    No se encontraron eventos de auditoría con los filtros seleccionados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="logs.last_page > 1" class="flex items-center justify-between border-t border-slate-100 px-4 py-3 bg-slate-50/50">
                    <div class="text-xs text-slate-500">
                        Página {{ logs.current_page }} de {{ logs.last_page }} ({{ logs.total }} registros)
                    </div>
                    <div class="flex items-center gap-1">
                        <template v-for="(link, i) in logs.links" :key="i">
                            <button
                                v-if="link.url"
                                type="button"
                                @click="router.visit(link.url)"
                                :class="[
                                    'rounded-lg px-2.5 py-1 text-xs font-bold transition cursor-pointer',
                                    link.active ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-200'
                                ]"
                                v-html="link.label"
                            ></button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Modal de Detalle de Auditoría Forense -->
            <div
                v-if="modalDetalle"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4"
            >
                <div class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-xl border border-slate-200 space-y-4 max-h-[85vh] overflow-y-auto">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Detalle de Auditoría #{{ modalDetalle.id }}</h3>
                            <p class="text-xs text-slate-500 font-mono">{{ modalDetalle.accion }} &bull; {{ new Date(modalDetalle.created_at).toLocaleString('es-PE') }}</p>
                        </div>
                        <button
                            type="button"
                            @click="modalDetalle = null"
                            class="text-slate-400 hover:text-slate-700 text-lg font-bold"
                        >
                            &times;
                        </button>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div>
                            <span class="font-bold text-slate-700">Descripción:</span>
                            <p class="mt-0.5 text-slate-600 bg-slate-50 p-2.5 rounded-xl border border-slate-200">{{ modalDetalle.descripcion }}</p>
                        </div>

                        <div v-if="modalDetalle.datos_antes">
                            <span class="font-bold text-slate-700">Datos Previos (Antes):</span>
                            <pre class="mt-1 bg-slate-900 text-slate-100 p-3 rounded-xl overflow-x-auto text-[11px] font-mono">{{ JSON.stringify(modalDetalle.datos_antes, null, 2) }}</pre>
                        </div>

                        <div v-if="modalDetalle.datos_despues">
                            <span class="font-bold text-slate-700">Datos Registrados / Modificados (Después):</span>
                            <pre class="mt-1 bg-slate-900 text-slate-100 p-3 rounded-xl overflow-x-auto text-[11px] font-mono">{{ JSON.stringify(modalDetalle.datos_despues, null, 2) }}</pre>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-[11px] text-slate-500 border-t border-slate-100 pt-3">
                            <div><strong>IP:</strong> {{ modalDetalle.ip || 'No registrada' }}</div>
                            <div><strong>Actor:</strong> {{ modalDetalle.actor_nombre }} ({{ modalDetalle.actor_email }})</div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button
                            type="button"
                            @click="modalDetalle = null"
                            class="rounded-xl bg-slate-100 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-200 transition cursor-pointer"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

