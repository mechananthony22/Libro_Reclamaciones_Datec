<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface IncidenteItem {
    id: number;
    tipo: string;
    titulo: string;
    descripcion: string;
    canal_alternativo: Record<string, string> | null;
    iniciado_en: string | null;
    finalizado_en: string | null;
    iniciado_por: string | null;
    finalizado_por: string | null;
    resultado_ensayo: string | null;
    estado: string;
    is_activo: boolean;
    is_ensayo: boolean;
}

interface EmpresaData {
    id: number;
    name: string;
    razon_social: string;
    en_contingencia: boolean;
    contingencia_mensaje: string | null;
    contingencia_canal_alternativo: {
        email?: string;
        telefono?: string;
        direccion_fisica?: string;
        horario?: string;
    } | null;
    contingencia_activada_en: string | null;
    contingencia_estimada_fin: string | null;
}

interface Props {
    empresa: EmpresaData;
    incidentes: IncidenteItem[];
}

const props = defineProps<Props>();

const modalActivar = ref(false);
const modalDesactivar = ref(false);
const modalEnsayo = ref(false);

const formActivar = useForm({
    titulo: 'Mantenimiento Preventivo de Plataforma',
    tipo: 'mantenimiento',
    mensaje: props.empresa.contingencia_mensaje || 'El portal de Libro de Reclamaciones se encuentra temporalmente en mantenimiento preventivo. Puede utilizar nuestros canales alternativos.',
    estimada_fin: '',
    email: props.empresa.contingencia_canal_alternativo?.email || '',
    telefono: props.empresa.contingencia_canal_alternativo?.telefono || '',
    direccion_fisica: props.empresa.contingencia_canal_alternativo?.direccion_fisica || '',
    horario: props.empresa.contingencia_canal_alternativo?.horario || 'Lunes a Viernes de 09:00 a 18:00',
});

const formDesactivar = useForm({
    resolucion: 'Mantenimiento completado satisfactoriamente. Operación restablecida.',
});

const formEnsayo = useForm({
    titulo: 'Simulacro y Ensayo Periódico de Contingencia',
    descripcion: 'Verificación preventiva de operatividad técnica, comunicación y canales alternativos de soporte.',
    resultado: 'Ensayo completado satisfactoriamente. Se constató la operatividad del correo oficial y línea telefónica de atención de quejas. No se generaron registros ficticios.',
});

function enviarActivar() {
    formActivar.post('/configuracion/contingencia/activar', {
        onSuccess: () => {
            modalActivar.value = false;
        },
    });
}

function enviarDesactivar() {
    formDesactivar.post('/configuracion/contingencia/desactivar', {
        onSuccess: () => {
            modalDesactivar.value = false;
        },
    });
}

function enviarEnsayo() {
    formEnsayo.post('/configuracion/contingencia/ensayo', {
        onSuccess: () => {
            modalEnsayo.value = false;
        },
    });
}

function estadoBadge(incidente: IncidenteItem) {
    if (incidente.is_activo) {
        return {
            clase: 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20 font-bold',
            texto: 'En Contingencia',
        };
    }
    if (incidente.is_ensayo) {
        return {
            clase: 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20 font-semibold',
            texto: 'Ensayo DR Completado',
        };
    }
    return {
        clase: 'bg-slate-100 text-slate-700 ring-1 ring-slate-400/20 font-medium',
        texto: 'Resuelto / Finalizado',
    };
}
</script>

<template>
    <AdminLayout>
        <Head title="Plan de Contingencia - Configuración" />

        <div class="space-y-6">
            <!-- Encabezado Oficial -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-bold tracking-wide text-amber-700 border border-amber-200 uppercase">
                            Continuidad Operativa
                        </span>
                        <span class="text-xs text-slate-400">&bull;</span>
                        <span class="text-xs font-semibold text-slate-500">Ley N° 29571 &bull; INDECOPI</span>
                    </div>
                    <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">
                        Plan de Contingencia y Recuperación
                    </h1>
                    <p class="mt-1 text-xs text-slate-500 max-w-2xl">
                        Gestión de indisponibilidad técnica, avisos públicos de mantenimiento preventivo, definición de canales alternativos de atención y registro de simulacros de recuperación.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-if="!empresa.en_contingencia"
                        type="button"
                        @click="modalEnsayo = true"
                        class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-2xs border border-slate-200 hover:bg-slate-50 hover:text-slate-900 transition"
                    >
                        <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Registrar Ensayo DR</span>
                    </button>

                    <button
                        v-if="!empresa.en_contingencia"
                        type="button"
                        @click="modalActivar = true"
                        class="inline-flex items-center gap-2 rounded-xl bg-amber-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-amber-700 transition"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>Activar Contingencia</span>
                    </button>

                    <button
                        v-else
                        type="button"
                        @click="modalDesactivar = true"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Desactivar y Normalizar</span>
                    </button>
                </div>
            </div>

            <!-- Banner de Estado Operativo Activo -->
            <div
                :class="[
                    'rounded-2xl border p-5 transition shadow-2xs',
                    empresa.en_contingencia
                        ? 'bg-gradient-to-r from-amber-50 to-orange-50 border-amber-300'
                        : 'bg-white border-slate-200'
                ]"
            >
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div
                            :class="[
                                'h-11 w-11 rounded-xl flex items-center justify-center shrink-0 shadow-2xs',
                                empresa.en_contingencia
                                    ? 'bg-amber-500 text-white'
                                    : 'bg-emerald-100 text-emerald-700'
                            ]"
                        >
                            <svg v-if="empresa.en_contingencia" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <svg v-else class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-sm font-bold text-slate-900">
                                    {{ empresa.en_contingencia ? 'Modo de Contingencia Activo en Portal Público' : 'Portal Público Operando con Normalidad' }}
                                </h2>
                                <span
                                    :class="[
                                        'rounded-full px-2 py-0.5 text-[10px] font-bold tracking-wider uppercase',
                                        empresa.en_contingencia
                                            ? 'bg-amber-100 text-amber-800'
                                            : 'bg-emerald-100 text-emerald-800'
                                    ]"
                                >
                                    {{ empresa.en_contingencia ? 'Alerta Pública' : 'Operativo 100%' }}
                                </span>
                            </div>

                            <p v-if="empresa.en_contingencia" class="text-xs text-amber-900 mt-1 font-medium">
                                {{ empresa.contingencia_mensaje }}
                            </p>
                            <p v-else class="text-xs text-slate-500 mt-1">
                                El formulario digital de reclamaciones está disponible y recibiendo registros de consumidores conforme a normativa.
                            </p>

                            <div v-if="empresa.en_contingencia && empresa.contingencia_activada_en" class="mt-2 flex flex-wrap items-center gap-3 text-[11px] text-amber-800 font-semibold">
                                <span>Activado: {{ empresa.contingencia_activada_en }}</span>
                                <span v-if="empresa.contingencia_estimada_fin">&bull; Término estimado: {{ empresa.contingencia_estimada_fin }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="empresa.en_contingencia" class="shrink-0 flex items-center gap-2">
                        <button
                            type="button"
                            @click="modalDesactivar = true"
                            class="rounded-xl bg-white px-3.5 py-2 text-xs font-bold text-amber-900 border border-amber-300 hover:bg-amber-50 shadow-2xs transition"
                        >
                            Finalizar Contingencia
                        </button>
                    </div>
                </div>
            </div>

            <!-- Canales Alternativos Configurados -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3 flex items-center gap-2">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Canales de Contingencia Definidos para Consumidores
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
                    <div class="rounded-xl bg-slate-50 p-3 border border-slate-100">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Correo de Emergencia</span>
                        <span class="font-bold text-slate-800 truncate block">{{ empresa.contingencia_canal_alternativo?.email || 'No asignado' }}</span>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-3 border border-slate-100">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Teléfono / WhatsApp</span>
                        <span class="font-bold text-slate-800 truncate block">{{ empresa.contingencia_canal_alternativo?.telefono || 'No asignado' }}</span>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-3 border border-slate-100">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Libro Físico / Domicilio</span>
                        <span class="font-bold text-slate-800 truncate block">{{ empresa.contingencia_canal_alternativo?.direccion_fisica || 'No asignado' }}</span>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-3 border border-slate-100">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Horario de Atención</span>
                        <span class="font-bold text-slate-800 truncate block">{{ empresa.contingencia_canal_alternativo?.horario || 'Lunes a Viernes' }}</span>
                    </div>
                </div>
            </div>

            <!-- Tabla de Historial de Incidentes y Simulacros -->
            <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-2xs">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">
                            Bitácora de Incidentes y Ensayos de Recuperación
                        </h2>
                        <p class="text-xs text-slate-400">
                            Trazabilidad obligatoria de periodos de contingencia y simulacros documentados.
                        </p>
                    </div>
                    <span class="text-xs font-semibold text-slate-400">
                        {{ incidentes.length }} registros
                    </span>
                </div>

                <div v-if="incidentes.length === 0" class="p-8 text-center text-xs text-slate-400">
                    No se han registrado eventos de contingencia técnica ni simulacros en este periodo.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-[11px] font-bold uppercase text-slate-500 tracking-wider">
                            <tr>
                                <th class="px-5 py-3">Tipo / Título</th>
                                <th class="px-5 py-3">Inicio</th>
                                <th class="px-5 py-3">Cierre</th>
                                <th class="px-5 py-3">Iniciado Por</th>
                                <th class="px-5 py-3">Estado</th>
                                <th class="px-5 py-3">Detalle / Resultado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="inc in incidentes" :key="inc.id" class="hover:bg-slate-50/60 transition">
                                <td class="px-5 py-3.5">
                                    <div class="font-bold text-slate-900">{{ inc.titulo }}</div>
                                    <div class="text-[11px] text-slate-500 capitalize">{{ inc.tipo.replace('_', ' ') }}</div>
                                </td>
                                <td class="px-5 py-3.5 text-slate-600 font-medium whitespace-nowrap">
                                    {{ inc.iniciado_en || '—' }}
                                </td>
                                <td class="px-5 py-3.5 text-slate-600 font-medium whitespace-nowrap">
                                    {{ inc.finalizado_en || 'En curso' }}
                                </td>
                                <td class="px-5 py-3.5 text-slate-700 font-semibold whitespace-nowrap">
                                    {{ inc.iniciado_por || 'Sistema' }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span :class="['inline-flex rounded-md px-2 py-0.5 text-[10px]', estadoBadge(inc).clase]">
                                        {{ estadoBadge(inc).texto }}
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
        <div v-if="modalActivar" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl border border-slate-200">
                <div class="flex items-center gap-3 text-amber-600 mb-4">
                    <div class="h-10 w-10 rounded-xl bg-amber-100 flex items-center justify-center shrink-0">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Activar Modo de Contingencia</h3>
                        <p class="text-xs text-slate-500">Publicará el aviso de mantenimiento y suspenderá el formulario virtual.</p>
                    </div>
                </div>

                <form @submit.prevent="enviarActivar" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Título del Incidente / Mantenimiento</label>
                        <input
                            type="text"
                            v-model="formActivar.titulo"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
                        />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Mensaje Público para Consumidores</label>
                        <textarea
                            v-model="formActivar.mensaje"
                            rows="3"
                            required
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Correo Alternativo de Atención</label>
                            <input
                                type="email"
                                v-model="formActivar.email"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs"
                            />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Teléfono Alternativo</label>
                            <input
                                type="text"
                                v-model="formActivar.telefono"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Domicilio para Libro Físico</label>
                            <input
                                type="text"
                                v-model="formActivar.direccion_fisica"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs"
                            />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Término Estimado (opcional)</label>
                            <input
                                type="datetime-local"
                                v-model="formActivar.estimada_fin"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs"
                            />
                        </div>
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
                            Confirmar Activación
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Desactivar Contingencia -->
        <div v-if="modalDesactivar" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-slate-200">
                <div class="flex items-center gap-3 text-emerald-600 mb-4">
                    <div class="h-10 w-10 rounded-xl bg-emerald-100 flex items-center justify-center shrink-0">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Restablecer Operación Normal</h3>
                        <p class="text-xs text-slate-500">Se reanudará el registro público ordinario del Libro.</p>
                    </div>
                </div>

                <form @submit.prevent="enviarDesactivar" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nota de Resolución / Cierre del Incidente</label>
                        <textarea
                            v-model="formDesactivar.resolucion"
                            rows="3"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        ></textarea>
                    </div>

                    <div class="mt-5 flex justify-end gap-2 pt-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="modalDesactivar = false"
                            class="rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="formDesactivar.processing"
                            class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700 disabled:opacity-50"
                        >
                            Restablecer Servicio
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Registrar Ensayo DR -->
        <div v-if="modalEnsayo" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl border border-slate-200">
                <div class="flex items-center gap-3 text-blue-600 mb-4">
                    <div class="h-10 w-10 rounded-xl bg-blue-100 flex items-center justify-center shrink-0">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Simulacro / Ensayo de Recuperación (DR)</h3>
                        <p class="text-xs text-slate-500">Auditoría obligatoria de continuidad sin generar expedientes falsos.</p>
                    </div>
                </div>

                <form @submit.prevent="enviarEnsayo" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Título del Ensayo</label>
                        <input
                            type="text"
                            v-model="formEnsayo.titulo"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs"
                        />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Descripción del Escenario de Prueba</label>
                        <textarea
                            v-model="formEnsayo.descripcion"
                            rows="2"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Resultado y Conclusiones del Ensayo</label>
                        <textarea
                            v-model="formEnsayo.resultado"
                            rows="3"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs"
                        ></textarea>
                    </div>

                    <div class="rounded-xl bg-blue-50/70 p-3 text-[11px] text-blue-900 border border-blue-200">
                        <strong>Garantía de Integridad:</strong> Este ensayo quedará registrado de inmediato en la bitácora legal de auditoría del tenant sin alterar el estado operativo del portal público ni crear reclamos simulados.
                    </div>

                    <div class="mt-5 flex justify-end gap-2 pt-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="modalEnsayo = false"
                            class="rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="formEnsayo.processing"
                            class="rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white hover:bg-blue-700 disabled:opacity-50"
                        >
                            Guardar Ensayo en Bitácora
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

