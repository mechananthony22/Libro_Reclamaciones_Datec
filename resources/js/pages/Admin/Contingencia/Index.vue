<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
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

const modalActivar = ref(false);
const empresaSeleccionada = ref<EmpresaItem | null>(null);

const formActivar = useForm({
    titulo: 'Contingencia de Plataforma por Mantenimiento Central',
    tipo: 'mantenimiento',
    mensaje: 'El servicio del portal de reclamaciones se encuentra en mantenimiento preventivo por supervisión de infraestructura.',
});

function abrirModalActivar(empresa: EmpresaItem) {
    empresaSeleccionada.value = empresa;
    formActivar.mensaje = empresa.contingencia_mensaje || 'El servicio del portal de reclamaciones se encuentra en mantenimiento preventivo por supervisión de infraestructura.';
    modalActivar.value = true;
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

function desactivar(empresa: EmpresaItem) {
    useForm({
        resolucion: 'Normalizado por Superadministrador',
    }).post(`/admin/contingencia/${empresa.id}/desactivar`);
}
</script>

<template>
    <AdminLayout>
        <Head title="Contingencias y Ensayos - Superadmin" />

        <div class="space-y-6">
            <!-- Encabezado Oficial -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-blue-50 px-2 py-0.5 text-[11px] font-bold tracking-wide text-blue-700 border border-blue-200 uppercase">
                            Supervisión de Plataforma
                        </span>
                        <span class="text-xs text-slate-400">&bull;</span>
                        <span class="text-xs font-semibold text-slate-500">Continuidad & Ensayos DR</span>
                    </div>
                    <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">
                        Contingencias y Ensayos de Recuperación
                    </h1>
                    <p class="mt-1 text-xs text-slate-500 max-w-2xl">
                        Supervisión centralizada del estado de disponibilidad, activación de planes de contingencia para clientes y auditoría de simulacros preventivos de desastres.
                    </p>
                </div>
            </div>

            <!-- Métricas Clave -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs">
                    <div class="flex items-center justify-between text-slate-400">
                        <span class="text-xs font-bold uppercase tracking-wider">Total Empresas</span>
                        <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-black text-slate-900">{{ metricas.total_empresas }}</span>
                        <span class="text-xs font-semibold text-slate-400">clientes</span>
                    </div>
                </div>

                <div
                    :class="[
                        'rounded-2xl border p-5 shadow-2xs',
                        metricas.en_contingencia > 0
                            ? 'bg-amber-50/70 border-amber-300'
                            : 'bg-white border-slate-200'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span
                            :class="[
                                'text-xs font-bold uppercase tracking-wider',
                                metricas.en_contingencia > 0 ? 'text-amber-800' : 'text-slate-400'
                            ]"
                        >
                            En Contingencia
                        </span>
                        <span
                            v-if="metricas.en_contingencia > 0"
                            class="relative flex h-3 w-3"
                        >
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
                        </span>
                        <svg v-else class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span
                            :class="[
                                'text-2xl font-black',
                                metricas.en_contingencia > 0 ? 'text-amber-900' : 'text-slate-900'
                            ]"
                        >
                            {{ metricas.en_contingencia }}
                        </span>
                        <span class="text-xs font-semibold text-slate-400">empresas en mantenimiento</span>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs">
                    <div class="flex items-center justify-between text-slate-400">
                        <span class="text-xs font-bold uppercase tracking-wider">Total Incidentes</span>
                        <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-black text-slate-900">{{ metricas.total_incidentes }}</span>
                        <span class="text-xs font-semibold text-slate-400">históricos</span>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs">
                    <div class="flex items-center justify-between text-slate-400">
                        <span class="text-xs font-bold uppercase tracking-wider">Ensayos Preventivos</span>
                        <svg class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-black text-blue-600">{{ metricas.total_ensayos }}</span>
                        <span class="text-xs font-semibold text-slate-400">simulacros</span>
                    </div>
                </div>
            </div>

            <!-- Tabla de Control de Empresas y Modo de Contingencia -->
            <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-2xs">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Estado de Contingencia por Empresa</h2>
                        <p class="text-xs text-slate-400">Control de activación directa y publicación de canales alternativos.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-[11px] font-bold uppercase text-slate-500 tracking-wider">
                            <tr>
                                <th class="px-5 py-3">Empresa / RUC</th>
                                <th class="px-5 py-3">Estado de Portal</th>
                                <th class="px-5 py-3">Mensaje Activo</th>
                                <th class="px-5 py-3 text-right">Acción Operativa</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="emp in empresas" :key="emp.id" class="hover:bg-slate-50/60 transition">
                                <td class="px-5 py-3.5">
                                    <div class="font-bold text-slate-900">{{ emp.razon_social || emp.name }}</div>
                                    <div class="text-[11px] text-slate-400">RUC: {{ emp.ruc || 'Sin RUC' }}</div>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span
                                        v-if="emp.en_contingencia"
                                        class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-bold text-amber-800"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        En Contingencia
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-bold text-emerald-800"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Normal / Operativo
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-slate-600 max-w-sm truncate" :title="emp.contingencia_mensaje || ''">
                                    {{ emp.contingencia_mensaje || '—' }}
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <button
                                        v-if="!emp.en_contingencia"
                                        type="button"
                                        @click="abrirModalActivar(emp)"
                                        class="rounded-xl bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700 border border-amber-200 hover:bg-amber-100 transition"
                                    >
                                        Activar Contingencia
                                    </button>
                                    <button
                                        v-else
                                        type="button"
                                        @click="desactivar(emp)"
                                        class="rounded-xl bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition"
                                    >
                                        Normalizar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabla de Historial Global de Incidentes -->
            <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-2xs">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Historial Global de Incidentes y Ensayos DR</h2>
                        <p class="text-xs text-slate-400">Auditoría inmutable de eventos de indisponibilidad y simulacros técnicos.</p>
                    </div>
                </div>

                <div v-if="incidentes.length === 0" class="p-8 text-center text-xs text-slate-400">
                    No se registran eventos en el historial global.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-[11px] font-bold uppercase text-slate-500 tracking-wider">
                            <tr>
                                <th class="px-5 py-3">Empresa</th>
                                <th class="px-5 py-3">Tipo / Título</th>
                                <th class="px-5 py-3">Inicio</th>
                                <th class="px-5 py-3">Cierre</th>
                                <th class="px-5 py-3">Estado</th>
                                <th class="px-5 py-3">Detalle / Conclusión</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="inc in incidentes" :key="inc.id" class="hover:bg-slate-50/60 transition">
                                <td class="px-5 py-3.5">
                                    <div class="font-bold text-slate-900">{{ inc.empresa_nombre }}</div>
                                    <div class="text-[11px] text-slate-400">{{ inc.empresa_ruc || 'Central' }}</div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="font-bold text-slate-800">{{ inc.titulo }}</div>
                                    <div class="text-[11px] text-slate-400 capitalize">{{ inc.tipo.replace('_', ' ') }}</div>
                                </td>
                                <td class="px-5 py-3.5 text-slate-600 font-medium whitespace-nowrap">
                                    {{ inc.iniciado_en || '—' }}
                                </td>
                                <td class="px-5 py-3.5 text-slate-600 font-medium whitespace-nowrap">
                                    {{ inc.finalizado_en || 'En curso' }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
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
                                <td class="px-5 py-3.5 text-slate-600 max-w-xs truncate" :title="inc.resultado_ensayo || inc.descripcion">
                                    {{ inc.resultado_ensayo || inc.descripcion }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Activar Contingencia -->
        <div v-if="modalActivar && empresaSeleccionada" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-slate-200">
                <div class="flex items-center gap-3 text-amber-600 mb-4">
                    <div class="h-10 w-10 rounded-xl bg-amber-100 flex items-center justify-center shrink-0">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Activar Contingencia</h3>
                        <p class="text-xs text-slate-500">{{ empresaSeleccionada.razon_social || empresaSeleccionada.name }}</p>
                    </div>
                </div>

                <form @submit.prevent="confirmarActivar" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Título del Mantenimiento</label>
                        <input
                            type="text"
                            v-model="formActivar.titulo"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs"
                        />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Mensaje Público</label>
                        <textarea
                            v-model="formActivar.mensaje"
                            rows="3"
                            required
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs"
                        ></textarea>
                    </div>

                    <div class="mt-5 flex justify-end gap-2 pt-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="modalActivar = false"
                            class="rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="formActivar.processing"
                            class="rounded-xl bg-amber-600 px-4 py-2 text-xs font-bold text-white hover:bg-amber-700 disabled:opacity-50"
                        >
                            Activar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

