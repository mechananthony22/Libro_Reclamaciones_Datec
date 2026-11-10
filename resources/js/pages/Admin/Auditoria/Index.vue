<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
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

const hayFiltrosActivos = computed(() =>
    Boolean(
        formFiltros.value.empresa_id ||
            formFiltros.value.modulo ||
            formFiltros.value.accion ||
            formFiltros.value.buscar ||
            formFiltros.value.fecha_desde ||
            formFiltros.value.fecha_hasta
    )
);

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

function iniciales(nombre: string): string {
    return (nombre || 'U')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p.charAt(0).toUpperCase())
        .join('');
}

function obtenerClaseModulo(modulo: string) {
    switch (modulo) {
        case 'seguridad':
            return { badge: 'bg-purple-50 text-purple-700 ring-purple-600/20', punto: 'bg-purple-500' };
        case 'empresas':
            return { badge: 'bg-blue-50 text-blue-700 ring-blue-600/20', punto: 'bg-blue-500' };
        case 'configuracion':
            return { badge: 'bg-amber-50 text-amber-700 ring-amber-600/20', punto: 'bg-amber-500' };
        case 'usuarios':
            return { badge: 'bg-indigo-50 text-indigo-700 ring-indigo-600/20', punto: 'bg-indigo-500' };
        case 'expedientes':
            return { badge: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20', punto: 'bg-emerald-500' };
        case 'soporte':
            return { badge: 'bg-rose-50 text-rose-700 ring-rose-600/20', punto: 'bg-rose-500' };
        default:
            return { badge: 'bg-slate-100 text-slate-600 ring-slate-400/20', punto: 'bg-slate-400' };
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Bitácora de Auditoría Global - Datec Libro" />

        <div class="space-y-6">
            <!-- Cabecera de Página -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex items-start gap-4">
                    <div class="hidden sm:flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-md shadow-blue-500/25">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            Superadministrador · Trazabilidad Forense
                        </div>
                        <h1 class="mt-0.5 text-2xl font-extrabold tracking-tight text-slate-900">
                            Bitácora de Auditoría
                        </h1>
                        <p class="mt-1 text-sm text-slate-500">
                            Historial inmutable de eventos, altas, cambios de configuración, accesos privilegiados y exportaciones.
                        </p>
                    </div>
                </div>

                <div class="inline-flex shrink-0 items-center gap-2 rounded-xl border border-slate-200/80 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-2xs">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    <span>{{ logs.total }} eventos registrados</span>
                </div>
            </div>

            <!-- Contenedor Principal: Filtros + Tabla + Paginación -->
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xs">
                <!-- Barra de Filtros -->
                <div class="space-y-3 border-b border-slate-100 p-4">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">Empresa</label>
                            <select
                                v-model="formFiltros.empresa_id"
                                @change="aplicarFiltros"
                                class="w-full cursor-pointer rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-700 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
                            >
                                <option value="">Todas las empresas</option>
                                <option v-for="emp in empresas" :key="emp.id" :value="emp.id">
                                    {{ emp.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">Módulo</label>
                            <select
                                v-model="formFiltros.modulo"
                                @change="aplicarFiltros"
                                class="w-full cursor-pointer rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-700 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
                            >
                                <option value="">Todos los módulos</option>
                                <option v-for="mod in modulos" :key="mod.value" :value="mod.value">
                                    {{ mod.label }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">Fecha desde</label>
                            <input
                                type="date"
                                v-model="formFiltros.fecha_desde"
                                @change="aplicarFiltros"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-800 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
                            />
                        </div>

                        <div>
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">Fecha hasta</label>
                            <input
                                type="date"
                                v-model="formFiltros.fecha_hasta"
                                @change="aplicarFiltros"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-800 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
                            />
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 pt-1 sm:flex-row sm:items-center sm:justify-between">
                        <div class="relative w-full sm:max-w-md">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                type="text"
                                v-model="formFiltros.buscar"
                                placeholder="Buscar por descripción, usuario, correo o dirección IP..."
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-xs font-medium text-slate-800 placeholder:text-slate-400 transition focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
                            />
                        </div>

                        <button
                            v-if="hayFiltrosActivos"
                            type="button"
                            @click="limpiarFiltros"
                            class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-xl px-3 py-2 text-xs font-bold text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Limpiar filtros
                        </button>
                    </div>
                </div>

                <!-- Tabla de Eventos -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            <tr>
                                <th scope="col" class="px-5 py-3">Fecha / Hora</th>
                                <th scope="col" class="px-5 py-3">Empresa</th>
                                <th scope="col" class="px-5 py-3">Actor</th>
                                <th scope="col" class="px-5 py-3">Módulo / Acción</th>
                                <th scope="col" class="px-5 py-3">Descripción</th>
                                <th scope="col" class="px-5 py-3">IP</th>
                                <th scope="col" class="px-5 py-3 text-right">Detalle</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-600">
                            <tr v-for="log in logs.data" :key="log.id" class="transition-colors hover:bg-slate-50/70">
                                <td class="whitespace-nowrap px-5 py-3.5 font-mono text-[11px] text-slate-500">
                                    {{ new Date(log.created_at).toLocaleString('es-PE') }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5">
                                    <span v-if="log.empresa" class="font-bold text-slate-900">{{ log.empresa.name }}</span>
                                    <span v-else class="inline-flex items-center rounded-lg bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-500">
                                        Global (Plataforma)
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-[10px] font-bold text-slate-700">
                                            {{ iniciales(log.actor_nombre) }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="truncate font-bold text-slate-800">{{ log.actor_nombre }}</div>
                                            <div class="truncate text-[10px] text-slate-400">{{ log.actor_email || log.actor_tipo }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[10px] font-bold capitalize ring-1',
                                            obtenerClaseModulo(log.modulo).badge
                                        ]"
                                    >
                                        <span :class="['h-1.5 w-1.5 rounded-full', obtenerClaseModulo(log.modulo).punto]"></span>
                                        {{ log.modulo }}
                                    </span>
                                    <div class="mt-1 font-mono text-[10px] text-slate-400">{{ log.accion }}</div>
                                </td>
                                <td class="max-w-md px-5 py-3.5 text-slate-700">
                                    {{ log.descripcion }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 font-mono text-[11px] text-slate-500">
                                    {{ log.ip || '—' }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                    <button
                                        v-if="log.datos_despues || log.datos_antes"
                                        type="button"
                                        @click="modalDetalle = log"
                                        class="inline-flex cursor-pointer items-center gap-1 rounded-lg bg-blue-50 px-2.5 py-1.5 text-[11px] font-bold text-blue-700 transition hover:bg-blue-600 hover:text-white"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Ver datos
                                    </button>
                                    <span v-else class="text-slate-300">—</span>
                                </td>
                            </tr>

                            <tr v-if="logs.data.length === 0">
                                <td colspan="7" class="px-5 py-14">
                                    <div class="flex flex-col items-center text-center">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
                                        </div>
                                        <p class="mt-3 text-sm font-bold text-slate-800">Sin eventos de auditoría</p>
                                        <p class="mt-1 text-xs text-slate-500">No se encontraron registros con los filtros seleccionados.</p>
                                        <button
                                            v-if="hayFiltrosActivos"
                                            type="button"
                                            @click="limpiarFiltros"
                                            class="mt-4 cursor-pointer rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50"
                                        >
                                            Limpiar filtros
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pie con Paginación -->
                <div class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50/50 px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-[11px] font-medium text-slate-500">
                        Página <span class="font-bold text-slate-700">{{ logs.current_page }}</span> de
                        <span class="font-bold text-slate-700">{{ logs.last_page }}</span> ({{ logs.total }} registros)
                    </div>
                    <div v-if="logs.last_page > 1" class="flex gap-1">
                        <template v-for="(link, i) in logs.links" :key="i">
                            <button
                                v-if="link.url"
                                type="button"
                                @click="router.visit(link.url)"
                                :class="[
                                    'min-w-8 cursor-pointer rounded-lg px-2.5 py-1 text-center text-xs font-bold transition',
                                    link.active
                                        ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                    : 'text-slate-600 hover:bg-white hover:text-slate-900'
                                ]"
                                v-html="link.label"
                            ></button>
                            <span
                                v-else
                                class="min-w-8 rounded-lg px-2.5 py-1 text-center text-xs text-slate-300"
                                v-html="link.label"
                            ></span>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Modal de Detalle de Auditoría Forense (Estilo CreateModal.vue) -->
            <div
                v-if="modalDetalle"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 sm:p-6 backdrop-blur-xs overflow-y-auto animate-fade-in"
                @click.self="modalDetalle = null"
            >
                <div class="relative flex max-h-[88vh] w-full max-w-xl flex-col rounded-2xl border border-slate-200 bg-white shadow-2xl overflow-hidden my-auto">
                    <!-- Cabecera del Modal -->
                    <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-md shadow-blue-500/25">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-base font-extrabold text-slate-900">
                                    Detalle de Auditoría Forense #{{ modalDetalle.id }}
                                </h2>
                                <p class="font-mono text-xs text-slate-500">
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
                            <span class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Descripción</span>
                            <p class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-3.5 text-slate-700 font-medium leading-relaxed">{{ modalDetalle.descripcion }}</p>
                        </div>

                        <div v-if="modalDetalle.datos_antes">
                            <span class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Datos previos (Antes)</span>
                            <pre class="overflow-x-auto rounded-xl bg-slate-900 p-3.5 font-mono text-[11px] text-slate-100 shadow-inner leading-normal">{{ JSON.stringify(modalDetalle.datos_antes, null, 2) }}</pre>
                        </div>

                        <div v-if="modalDetalle.datos_despues">
                            <span class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Datos registrados (Después)</span>
                            <pre class="overflow-x-auto rounded-xl bg-slate-900 p-3.5 font-mono text-[11px] text-slate-100 shadow-inner leading-normal">{{ JSON.stringify(modalDetalle.datos_despues, null, 2) }}</pre>
                        </div>

                        <div class="grid grid-cols-1 gap-2.5 rounded-xl border border-slate-200/80 bg-slate-50/70 p-3.5 text-[11px] text-slate-600 sm:grid-cols-2">
                            <div><strong class="font-bold text-slate-800">IP:</strong> <span class="font-mono text-slate-700">{{ modalDetalle.ip || 'No registrada' }}</span></div>
                            <div><strong class="font-bold text-slate-800">Actor:</strong> <span class="text-slate-700">{{ modalDetalle.actor_nombre }} ({{ modalDetalle.actor_email || modalDetalle.actor_tipo }})</span></div>
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
