<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface EmpresaItem {
    id: number;
    name: string;
    razon_social: string;
    ruc: string;
    en_contingencia: boolean;
    contingencia_activada_en: string | null;
    contingencia_mensaje: string | null;
}

interface IncidenteItem {
    id: number;
    empresa_nombre: string;
    empresa_ruc: string | null;
    tipo: string;
    titulo: string;
    descripcion: string;
    iniciado_en: string | null;
    finalizado_en: string | null;
    iniciado_por: string | null;
    resultado_ensayo: string | null;
    estado: string;
    is_activo: boolean;
    is_ensayo: boolean;
}

interface Props {
    metricas: {
        total_empresas: number;
        en_contingencia: number;
        total_incidentes: number;
        total_ensayos: number;
    };
    empresas: EmpresaItem[];
    incidentes: IncidenteItem[];
}

const props = defineProps<Props>();

// Modales
const modalActivar = ref(false);
const modalDesactivar = ref(false);
const empresaSeleccionada = ref<EmpresaItem | null>(null);

const formActivar = useForm({
    titulo: 'Contingencia de Plataforma por Mantenimiento Central',
    tipo: 'mantenimiento',
    mensaje:
        'El servicio del portal de reclamaciones se encuentra en mantenimiento preventivo por supervisión de infraestructura.',
});

const formDesactivar = useForm({
    resolucion: 'Mantenimiento centralizado finalizado. Operación normal restablecida por Superadministrador.',
});

function abrirModalActivar(empresa: EmpresaItem) {
    empresaSeleccionada.value = empresa;
    formActivar.mensaje =
        empresa.contingencia_mensaje ||
        'El servicio del portal de reclamaciones se encuentra en mantenimiento preventivo por supervisión de infraestructura.';
    modalActivar.value = true;
}

function abrirModalDesactivar(empresa: EmpresaItem) {
    empresaSeleccionada.value = empresa;
    formDesactivar.resolucion =
        'Mantenimiento centralizado finalizado. Operación normal restablecida por Superadministrador.';
    modalDesactivar.value = true;
}

function confirmarActivar() {
    if (!empresaSeleccionada.value) return;

    formActivar.post(`/admin/contingencia/${empresaSeleccionada.value.id}/activar`, {
        onSuccess: () => {
            modalActivar.value = false;
            empresaSeleccionada.value = null;
        },
    });
}

function confirmarDesactivar() {
    if (!empresaSeleccionada.value) return;

    formDesactivar.post(`/admin/contingencia/${empresaSeleccionada.value.id}/desactivar`, {
        onSuccess: () => {
            modalDesactivar.value = false;
            empresaSeleccionada.value = null;
        },
    });
}

// Filtros locales para empresas
const buscarEmpresa = ref('');
const filtroEstadoEmpresa = ref<'todas' | 'contingencia' | 'normales'>('todas');

const empresasFiltradas = computed(() => {
    return props.empresas.filter((emp) => {
        const coincideBuscar =
            !buscarEmpresa.value ||
            emp.name.toLowerCase().includes(buscarEmpresa.value.toLowerCase()) ||
            emp.razon_social?.toLowerCase().includes(buscarEmpresa.value.toLowerCase()) ||
            emp.ruc?.includes(buscarEmpresa.value);

        if (!coincideBuscar) return false;

        if (filtroEstadoEmpresa.value === 'contingencia') {
            return emp.en_contingencia;
        }
        if (filtroEstadoEmpresa.value === 'normales') {
            return !emp.en_contingencia;
        }
        return true;
    });
});

// Filtros locales para incidentes
const filtroTipoIncidente = ref<'todos' | 'incidentes' | 'ensayos'>('todos');

const incidentesFiltrados = computed(() => {
    return props.incidentes.filter((inc) => {
        if (filtroTipoIncidente.value === 'ensayos') {
            return inc.is_ensayo;
        }
        if (filtroTipoIncidente.value === 'incidentes') {
            return !inc.is_ensayo;
        }
        return true;
    });
});

function iniciales(nombre: string): string {
    return (nombre || 'EM')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p.charAt(0).toUpperCase())
        .join('');
}

const claseEtiqueta = 'mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500';
const claseCampo =
    'w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500';
</script>

<template>
    <AdminLayout>
        <Head title="Contingencias y Ensayos - Superadmin Datec" />

        <div class="space-y-6">
            <!-- Cabecera de Página (Estilo Admin/Empresas) -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex items-start gap-4">
                    <div
                        class="hidden sm:flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-500 text-white shadow-md shadow-amber-500/25"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            Superadministrador · Continuidad Operativa & Ensayos DR
                        </div>
                        <h1 class="mt-0.5 text-2xl font-extrabold tracking-tight text-slate-900">
                            Contingencias y Ensayos de Recuperación
                        </h1>
                        <p class="mt-1 text-sm text-slate-500">
                            Supervisión centralizada del estado de disponibilidad, activación de planes de contingencia para clientes y auditoría de simulacros.
                        </p>
                    </div>
                </div>

                <!-- Indicador de Estado Global -->
                <div class="flex items-center gap-2">
                    <span
                        :class="[
                            'inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-bold shadow-2xs border transition',
                            metricas.en_contingencia > 0
                                ? 'bg-amber-50 text-amber-800 border-amber-200'
                                : 'bg-emerald-50 text-emerald-800 border-emerald-200'
                        ]"
                    >
                        <span
                            :class="[
                                'h-2 w-2 rounded-full',
                                metricas.en_contingencia > 0 ? 'bg-amber-500 animate-pulse' : 'bg-emerald-500'
                            ]"
                        ></span>
                        <span>
                            {{ metricas.en_contingencia > 0 ? `${metricas.en_contingencia} empresas en contingencia` : 'Plataforma 100% Operativa' }}
                        </span>
                    </span>
                </div>
            </div>

            <!-- Tarjetas de Métricas KPIs (Estilo Admin/Empresas) -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Empresas</span>
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-3xl font-black text-slate-900">{{ metricas.total_empresas }}</p>
                    <p class="mt-1 text-[11px] font-medium text-slate-400">Organizaciones registradas</p>
                </div>

                <div
                    :class="[
                        'rounded-2xl border p-5 shadow-2xs transition',
                        metricas.en_contingencia > 0
                            ? 'border-amber-300 bg-amber-50/40'
                            : 'border-slate-200/80 bg-white'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span
                            :class="[
                                'text-[11px] font-bold uppercase tracking-wider',
                                metricas.en_contingencia > 0 ? 'text-amber-800' : 'text-slate-400'
                            ]"
                        >
                            En Contingencia
                        </span>
                        <span
                            :class="[
                                'flex h-9 w-9 items-center justify-center rounded-xl',
                                metricas.en_contingencia > 0 ? 'bg-amber-100 text-amber-600' : 'bg-emerald-50 text-emerald-600'
                            ]"
                        >
                            <svg v-if="metricas.en_contingencia > 0" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <svg v-else class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </span>
                    </div>
                    <p
                        :class="[
                            'mt-3 text-3xl font-black',
                            metricas.en_contingencia > 0 ? 'text-amber-900' : 'text-slate-900'
                        ]"
                    >
                        {{ metricas.en_contingencia }}
                    </p>
                    <p
                        :class="[
                            'mt-1 text-[11px] font-medium',
                            metricas.en_contingencia > 0 ? 'text-amber-700' : 'text-slate-400'
                        ]"
                    >
                        {{ metricas.en_contingencia > 0 ? 'Con aviso público activo' : 'Todas con servicio normal' }}
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Incidentes</span>
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-50 text-purple-600">
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-3xl font-black text-slate-900">{{ metricas.total_incidentes }}</p>
                    <p class="mt-1 text-[11px] font-medium text-slate-400">Eventos históricos registrados</p>
                </div>

                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Ensayos Preventivos</span>
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-3xl font-black text-blue-600">{{ metricas.total_ensayos }}</p>
                    <p class="mt-1 text-[11px] font-medium text-slate-400">Simulacros DR completados</p>
                </div>
            </div>

            <!-- Sección 1: Control de Empresas y Modo de Contingencia (Estilo Admin/Empresas) -->
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xs">
                <!-- Barra superior de búsqueda y pestañas -->
                <div class="space-y-4 border-b border-slate-100 p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-slate-900">Control de Contingencia por Empresa</h2>
                                <p class="text-xs text-slate-500">Supervisa el estado operativo y activa canales alternativos de atención de reclamaciones.</p>
                            </div>
                        </div>

                        <!-- Pestañas de filtro de estado -->
                        <div class="flex gap-1 overflow-x-auto rounded-xl bg-slate-100/80 p-1">
                            <button
                                type="button"
                                @click="filtroEstadoEmpresa = 'todas'"
                                :class="[
                                    'cursor-pointer rounded-lg px-3 py-1.5 text-xs font-bold transition',
                                    filtroEstadoEmpresa === 'todas'
                                        ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                        : 'text-slate-600 hover:bg-white hover:text-slate-900'
                                ]"
                            >
                                Todas ({{ empresas.length }})
                            </button>
                            <button
                                type="button"
                                @click="filtroEstadoEmpresa = 'contingencia'"
                                :class="[
                                    'cursor-pointer rounded-lg px-3 py-1.5 text-xs font-bold transition',
                                    filtroEstadoEmpresa === 'contingencia'
                                        ? 'bg-amber-600 text-white shadow-md shadow-amber-600/20'
                                        : 'text-slate-600 hover:bg-white hover:text-slate-900'
                                ]"
                            >
                                En Contingencia ({{ metricas.en_contingencia }})
                            </button>
                            <button
                                type="button"
                                @click="filtroEstadoEmpresa = 'normales'"
                                :class="[
                                    'cursor-pointer rounded-lg px-3 py-1.5 text-xs font-bold transition',
                                    filtroEstadoEmpresa === 'normales'
                                        ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20'
                                        : 'text-slate-600 hover:bg-white hover:text-slate-900'
                                ]"
                            >
                                Operativas ({{ metricas.total_empresas - metricas.en_contingencia }})
                            </button>
                        </div>
                    </div>

                    <!-- Buscador de empresa -->
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            v-model="buscarEmpresa"
                            type="text"
                            placeholder="Buscar empresa por nombre comercial, razón social o RUC..."
                            class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-4 text-xs text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                    </div>
                </div>

                <!-- Tabla de Empresas -->
                <div v-if="empresasFiltradas.length === 0" class="rounded-xl border-dashed py-12 text-center">
                    <p class="text-xs font-semibold text-slate-500">No se encontraron empresas con los criterios seleccionados.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Organización / RUC</th>
                                <th class="px-5 py-3">Estado del Portal</th>
                                <th class="px-5 py-3">Mensaje Activo</th>
                                <th class="px-5 py-3 text-right">Acción Operativa</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="emp in empresasFiltradas" :key="emp.id" class="transition hover:bg-slate-50/60">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-xs font-black text-slate-700 shadow-2xs">
                                            {{ iniciales(emp.razon_social || emp.name) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900">{{ emp.razon_social || emp.name }}</div>
                                            <div class="text-[11px] text-slate-400">RUC: {{ emp.ruc || 'Sin RUC' }} &bull; {{ emp.name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5">
                                    <span
                                        v-if="emp.en_contingencia"
                                        class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-700 ring-1 ring-amber-600/20"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        En Contingencia
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700 ring-1 ring-emerald-600/20"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Normal / Operativo
                                    </span>
                                </td>
                                <td class="max-w-sm truncate px-5 py-3.5 text-slate-600" :title="emp.contingencia_mensaje || ''">
                                    <span v-if="emp.contingencia_mensaje" class="text-amber-900 font-medium">
                                        {{ emp.contingencia_mensaje }}
                                    </span>
                                    <span v-else class="text-slate-400 italic">
                                        Operación estándar del formulario digital.
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                    <button
                                        v-if="!emp.en_contingencia"
                                        type="button"
                                        @click="abrirModalActivar(emp)"
                                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-800 transition hover:bg-amber-100 shadow-2xs"
                                    >
                                        <svg class="h-3.5 w-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        <span>Activar</span>
                                    </button>
                                    <button
                                        v-else
                                        type="button"
                                        @click="abrirModalDesactivar(emp)"
                                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-800 transition hover:bg-emerald-100 shadow-2xs"
                                    >
                                        <svg class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Normalizar</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sección 2: Historial Global de Incidentes y Ensayos DR (Estilo Admin/Empresas) -->
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xs">
                <div class="space-y-4 border-b border-slate-100 p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-purple-50 text-purple-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-slate-900">Historial Global de Incidentes y Ensayos DR</h2>
                            </div>
                        </div>

                        <!-- Filtro de tipo de registro -->
                        <div class="flex gap-1 overflow-x-auto rounded-xl bg-slate-100/80 p-1">
                            <button
                                type="button"
                                @click="filtroTipoIncidente = 'todos'"
                                :class="[
                                    'cursor-pointer rounded-lg px-3 py-1.5 text-xs font-bold transition',
                                    filtroTipoIncidente === 'todos'
                                        ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                        : 'text-slate-600 hover:bg-white hover:text-slate-900'
                                ]"
                            >
                                Todos ({{ incidentes.length }})
                            </button>
                            <button
                                type="button"
                                @click="filtroTipoIncidente = 'ensayos'"
                                :class="[
                                    'cursor-pointer rounded-lg px-3 py-1.5 text-xs font-bold transition',
                                    filtroTipoIncidente === 'ensayos'
                                        ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                        : 'text-slate-600 hover:bg-white hover:text-slate-900'
                                ]"
                            >
                                Solo Ensayos DR ({{ metricas.total_ensayos }})
                            </button>
                            <button
                                type="button"
                                @click="filtroTipoIncidente = 'incidentes'"
                                :class="[
                                    'cursor-pointer rounded-lg px-3 py-1.5 text-xs font-bold transition',
                                    filtroTipoIncidente === 'incidentes'
                                        ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                        : 'text-slate-600 hover:bg-white hover:text-slate-900'
                                ]"
                            >
                                Contingencias Reales ({{ metricas.total_incidentes - metricas.total_ensayos }})
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="incidentesFiltrados.length === 0" class="rounded-xl border-dashed py-12 text-center">
                    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <p class="mt-3 text-xs font-semibold text-slate-600">No se registran eventos con este filtro</p>
                    <p class="mt-1 text-[11px] text-slate-400">Todos los incidentes y simulacros de recuperación quedarán auditados aquí.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Empresa</th>
                                <th class="px-5 py-3">Tipo / Título</th>
                                <th class="px-5 py-3">Inicio</th>
                                <th class="px-5 py-3">Cierre</th>
                                <th class="px-5 py-3">Iniciado Por</th>
                                <th class="px-5 py-3">Estado</th>
                                <th class="px-5 py-3">Detalle / Resultado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="inc in incidentesFiltrados" :key="inc.id" class="transition hover:bg-slate-50/60">
                                <td class="px-5 py-3.5">
                                    <div class="font-bold text-slate-900">{{ inc.empresa_nombre }}</div>
                                    <div class="text-[11px] text-slate-400">{{ inc.empresa_ruc || 'Central' }}</div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="font-bold text-slate-800">{{ inc.titulo }}</div>
                                    <div class="text-[11px] text-slate-500 capitalize">{{ inc.tipo.replace('_', ' ') }}</div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 font-medium text-slate-600">
                                    {{ inc.iniciado_en || '—' }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 font-medium text-slate-600">
                                    {{ inc.finalizado_en || 'En curso' }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 font-semibold text-slate-700">
                                    {{ inc.iniciado_por || 'Sistema' }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5">
                                    <span
                                        :class="[
                                            'inline-flex rounded-md px-2 py-0.5 text-[10px] font-bold',
                                            inc.is_activo
                                                ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20'
                                                : (inc.is_ensayo ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20' : 'bg-slate-100 text-slate-600 ring-1 ring-slate-400/20')
                                        ]"
                                    >
                                        {{ inc.is_activo ? 'Activo' : (inc.is_ensayo ? 'Ensayo DR' : 'Resuelto') }}
                                    </span>
                                </td>
                                <td class="max-w-xs truncate px-5 py-3.5 text-slate-600" :title="inc.resultado_ensayo || inc.descripcion">
                                    {{ inc.resultado_ensayo || inc.descripcion }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal 1: Activar Contingencia para Empresa (Estilo CreateModal.vue) -->
        <div
            v-if="modalActivar && empresaSeleccionada"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 sm:p-6 backdrop-blur-xs overflow-y-auto animate-fade-in"
            @click.self="modalActivar = false"
        >
            <div class="relative flex w-full max-w-lg flex-col rounded-2xl border border-slate-200 bg-white shadow-2xl overflow-hidden my-auto">
                <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white shadow-md shadow-amber-500/25">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-extrabold text-slate-900">Activar Modo Contingencia</h2>
                            <p class="text-xs text-slate-500">{{ empresaSeleccionada.razon_social || empresaSeleccionada.name }}</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="modalActivar = false"
                        class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-200 hover:text-slate-700 cursor-pointer"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="confirmarActivar" class="p-6 space-y-4 text-xs">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Título del Mantenimiento / Incidente <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="formActivar.titulo"
                            type="text"
                            required
                            maxlength="150"
                            placeholder="Ej. Ventana de mantenimiento programado"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-800 transition focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
                        />
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Mensaje Público para Consumidores <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            v-model="formActivar.mensaje"
                            rows="3"
                            required
                            placeholder="Mensaje claro que verán los usuarios en el portal..."
                            class="w-full rounded-xl border border-slate-200 bg-white p-3.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 transition focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500 leading-relaxed"
                        ></textarea>
                    </div>

                    <div class="rounded-xl border border-amber-200/80 bg-amber-50/70 p-3.5 text-[11px] text-amber-900 leading-relaxed">
                        <strong class="font-bold text-amber-950">Efecto en el Portal:</strong> Al activar la contingencia, el formulario digital de esta empresa mostrará el aviso de mantenimiento y ofrecerá únicamente los canales alternativos de atención conforme a normativa INDECOPI.
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="modalActivar = false"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 hover:text-slate-900 transition cursor-pointer"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="formActivar.processing"
                            class="inline-flex items-center gap-2 rounded-xl bg-amber-600 px-5 py-2.5 text-xs font-bold text-white shadow-md shadow-amber-500/20 hover:bg-amber-700 disabled:opacity-50 transition cursor-pointer"
                        >
                            <span>{{ formActivar.processing ? 'Activando...' : 'Confirmar y Activar' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal 2: Desactivar / Normalizar Empresa (Estilo CreateModal.vue) -->
        <div
            v-if="modalDesactivar && empresaSeleccionada"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 sm:p-6 backdrop-blur-xs overflow-y-auto animate-fade-in"
            @click.self="modalDesactivar = false"
        >
            <div class="relative flex w-full max-w-md flex-col rounded-2xl border border-slate-200 bg-white shadow-2xl overflow-hidden my-auto">
                <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-md shadow-emerald-500/25">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-extrabold text-slate-900">Normalizar Servicio</h2>
                            <p class="text-xs text-slate-500">{{ empresaSeleccionada.razon_social || empresaSeleccionada.name }}</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="modalDesactivar = false"
                        class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-200 hover:text-slate-700 cursor-pointer"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="confirmarDesactivar" class="p-6 space-y-4 text-xs">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Nota de Resolución / Cierre del Incidente <span class="text-slate-400 font-normal">(Opcional)</span>
                        </label>
                        <textarea
                            v-model="formDesactivar.resolucion"
                            rows="3"
                            placeholder="Describa la acción correctiva efectuada para restablecer la normalidad..."
                            class="w-full rounded-xl border border-slate-200 bg-white p-3.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 transition focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 leading-relaxed"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="modalDesactivar = false"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 hover:text-slate-900 transition cursor-pointer"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="formDesactivar.processing"
                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white shadow-md shadow-emerald-500/20 hover:bg-emerald-700 disabled:opacity-50 transition cursor-pointer"
                        >
                            <span>{{ formDesactivar.processing ? 'Restableciendo...' : 'Restablecer Operación' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
