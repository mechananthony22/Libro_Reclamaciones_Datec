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
    mensaje:
        props.empresa.contingencia_mensaje ||
        'El portal de Libro de Reclamaciones se encuentra temporalmente en mantenimiento preventivo. Puede utilizar nuestros canales alternativos.',
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
    resultado:
        'Ensayo completado satisfactoriamente. Se constató la operatividad del correo oficial y línea telefónica de atención de quejas. No se generaron registros ficticios.',
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

const claseEtiqueta = 'mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500';
const claseCampo =
    'w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 placeholder:text-slate-400 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500';
</script>

<template>
    <AdminLayout>
        <Head title="Plan de Contingencia - Configuración" />

        <div class="space-y-6">
            <!-- Cabecera de Página (Estilo Create.vue) -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex items-start gap-4">
                    <div
                        :class="[
                            'hidden sm:flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-white shadow-md transition',
                            empresa.en_contingencia
                                ? 'bg-amber-600 shadow-amber-600/25 ring-2 ring-amber-400/50'
                                : 'bg-amber-500 shadow-amber-500/25'
                        ]"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            Continuidad Operativa · INDECOPI Ley N° 29571
                        </div>
                        <h1 class="mt-0.5 text-2xl font-extrabold tracking-tight text-slate-900">
                            Plan de Contingencia y Recuperación
                        </h1>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-if="!empresa.en_contingencia"
                        type="button"
                        @click="modalEnsayo = true"
                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50"
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
                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-amber-600 px-4 py-2 text-xs font-bold text-white shadow-md shadow-amber-600/20 transition hover:bg-amber-700"
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
                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-md shadow-emerald-600/20 transition hover:bg-emerald-700"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Desactivar y Normalizar</span>
                    </button>
                </div>
            </div>

            <!-- Card 1: Estado Operativo Actual (Estilo Create.vue) -->
            <div
                :class="[
                    'space-y-4 rounded-2xl border p-6 shadow-2xs transition',
                    empresa.en_contingencia
                        ? 'border-amber-300 bg-amber-50/50'
                        : 'border-slate-200/80 bg-white'
                ]"
            >
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-3">
                        <div
                            :class="[
                                'flex h-9 w-9 shrink-0 items-center justify-center rounded-xl',
                                empresa.en_contingencia
                                    ? 'bg-amber-500 text-white shadow-xs'
                                    : 'bg-emerald-50 text-emerald-600'
                            ]"
                        >
                            <svg v-if="empresa.en_contingencia" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">
                                {{ empresa.en_contingencia ? 'Modo de Contingencia Activo en Portal Público' : 'Portal Público Operando con Normalidad' }}
                            </h2>
                            <p class="text-xs text-slate-500">
                                {{ empresa.en_contingencia ? 'El formulario digital público está suspendido con aviso de contingencia.' : 'El formulario digital de reclamaciones está disponible y recibiendo registros de consumidores.' }}
                            </p>
                        </div>
                    </div>
                    <span
                        :class="[
                            'inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold tracking-wider uppercase',
                            empresa.en_contingencia
                                ? 'bg-amber-100 text-amber-800'
                                : 'bg-emerald-100 text-emerald-800'
                        ]"
                    >
                        {{ empresa.en_contingencia ? 'Alerta Pública Activa' : 'Operativo 100%' }}
                    </span>
                </div>

                <div v-if="empresa.en_contingencia" class="rounded-xl border border-amber-200 bg-white p-4">
                    <p class="text-xs font-semibold text-amber-900">
                        {{ empresa.contingencia_mensaje }}
                    </p>
                    <div v-if="empresa.contingencia_activada_en" class="mt-3 flex flex-wrap items-center gap-4 text-[11px] font-semibold text-amber-800">
                        <span class="flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                            Activado el: {{ empresa.contingencia_activada_en }}
                        </span>
                        <span v-if="empresa.contingencia_estimada_fin" class="text-slate-500">
                            • Fin estimado: {{ empresa.contingencia_estimada_fin }}
                        </span>
                    </div>
                </div>

                <div v-else class="text-xs text-slate-600">
                    <p>
                        Todos los servicios de recepción de quejas, reclamaciones y generación de comprobantes digitales están funcionando sin interrupciones.
                    </p>
                </div>
            </div>

            <!-- Card 2: Canales Alternativos de Atención (Estilo Create.vue) -->
            <div class="space-y-4 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-2xs">
                <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Canales Alternativos de Atención</h2>
                        <p class="text-xs text-slate-500">Medios de respaldo visibles al consumidor en caso de indisponibilidad técnica.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4 transition hover:bg-slate-50">
                        <div class="flex items-center gap-2 text-slate-400">
                            <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Correo de Emergencia</span>
                        </div>
                        <p class="mt-2 text-xs font-bold text-slate-800 truncate" :title="empresa.contingencia_canal_alternativo?.email">
                            {{ empresa.contingencia_canal_alternativo?.email || 'No asignado' }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4 transition hover:bg-slate-50">
                        <div class="flex items-center gap-2 text-slate-400">
                            <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Teléfono / WhatsApp</span>
                        </div>
                        <p class="mt-2 text-xs font-bold text-slate-800 truncate" :title="empresa.contingencia_canal_alternativo?.telefono">
                            {{ empresa.contingencia_canal_alternativo?.telefono || 'No asignado' }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4 transition hover:bg-slate-50">
                        <div class="flex items-center gap-2 text-slate-400">
                            <svg class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Libro Físico / Domicilio</span>
                        </div>
                        <p class="mt-2 text-xs font-bold text-slate-800 truncate" :title="empresa.contingencia_canal_alternativo?.direccion_fisica">
                            {{ empresa.contingencia_canal_alternativo?.direccion_fisica || 'No asignado' }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4 transition hover:bg-slate-50">
                        <div class="flex items-center gap-2 text-slate-400">
                            <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Horario de Atención</span>
                        </div>
                        <p class="mt-2 text-xs font-bold text-slate-800 truncate" :title="empresa.contingencia_canal_alternativo?.horario">
                            {{ empresa.contingencia_canal_alternativo?.horario || 'Lunes a Viernes' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Card 3: Bitácora Legal de Incidentes y Ensayos (Estilo Create.vue) -->
            <div class="space-y-4 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-2xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-purple-50 text-purple-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Bitácora Legal de Incidentes y Ensayos</h2>
                            <p class="text-xs text-slate-500">Trazabilidad obligatoria de periodos de contingencia y simulacros documentados.</p>
                        </div>
                    </div>
                    <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">
                        {{ incidentes.length }} registros
                    </span>
                </div>

                <div v-if="incidentes.length === 0" class="rounded-xl border border-dashed border-slate-200 py-12 text-center">
                    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <p class="mt-3 text-xs font-semibold text-slate-600">Sin eventos registrados</p>
                    <p class="mt-1 text-[11px] text-slate-400">No se han registrado eventos de contingencia técnica ni simulacros en este periodo.</p>
                </div>

                <div v-else class="overflow-x-auto rounded-xl border border-slate-200/80">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Tipo / Título</th>
                                <th class="px-4 py-3">Inicio</th>
                                <th class="px-4 py-3">Cierre</th>
                                <th class="px-4 py-3">Iniciado Por</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3">Detalle / Resultado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="inc in incidentes" :key="inc.id" class="transition hover:bg-slate-50/60">
                                <td class="px-4 py-3.5">
                                    <div class="font-bold text-slate-900">{{ inc.titulo }}</div>
                                    <div class="text-[11px] text-slate-500 capitalize">{{ inc.tipo.replace('_', ' ') }}</div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 font-medium text-slate-600">
                                    {{ inc.iniciado_en || '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 font-medium text-slate-600">
                                    {{ inc.finalizado_en || 'En curso' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 font-semibold text-slate-700">
                                    {{ inc.iniciado_por || 'Sistema' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5">
                                    <span :class="['inline-flex rounded-md px-2 py-0.5 text-[10px]', estadoBadge(inc).clase]">
                                        {{ estadoBadge(inc).texto }}
                                    </span>
                                </td>
                                <td class="max-w-xs truncate px-4 py-3.5 text-slate-600" :title="inc.resultado_ensayo || inc.descripcion">
                                    {{ inc.resultado_ensayo || inc.descripcion }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal 1: Activar Contingencia (Estilo CreateModal.vue) -->
        <div
            v-if="modalActivar"
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
                            <p class="text-xs text-slate-500">Publicará el aviso de mantenimiento y suspenderá el formulario virtual.</p>
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

                <form @submit.prevent="enviarActivar" class="p-6 space-y-4 text-xs">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Título del Incidente / Mantenimiento <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="formActivar.titulo"
                            type="text"
                            required
                            maxlength="150"
                            placeholder="Ej. Ventana de mantenimiento técnico programado"
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
                            placeholder="Mensaje explicativo visible en el portal para el consumidor..."
                            class="w-full rounded-xl border border-slate-200 bg-white p-3.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 transition focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500 leading-relaxed"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Correo Alternativo</label>
                            <input
                                v-model="formActivar.email"
                                type="email"
                                placeholder="reclamos@empresa.pe"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-medium text-slate-800 transition focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
                            />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Teléfono Alternativo</label>
                            <input
                                v-model="formActivar.telefono"
                                type="text"
                                placeholder="Ej. 01 2345678"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-medium text-slate-800 transition focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Domicilio para Libro Físico</label>
                            <input
                                v-model="formActivar.direccion_fisica"
                                type="text"
                                placeholder="Av. Principal 123"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-medium text-slate-800 transition focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
                            />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Término Estimado (Opcional)</label>
                            <input
                                v-model="formActivar.estimada_fin"
                                type="datetime-local"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-medium text-slate-800 transition focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
                            />
                        </div>
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
                            <span>{{ formActivar.processing ? 'Activando...' : 'Confirmar Activación' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal 2: Desactivar Contingencia (Estilo CreateModal.vue) -->
        <div
            v-if="modalDesactivar"
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
                            <h2 class="text-base font-extrabold text-slate-900">Restablecer Operación Normal</h2>
                            <p class="text-xs text-slate-500">Se reanudará el registro público ordinario del Libro.</p>
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

                <form @submit.prevent="enviarDesactivar" class="p-6 space-y-4 text-xs">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Nota de Resolución / Cierre del Incidente <span class="text-slate-400 font-normal">(Opcional)</span>
                        </label>
                        <textarea
                            v-model="formDesactivar.resolucion"
                            rows="3"
                            placeholder="Detalle la solución aplicada o la confirmación de estabilidad del servicio..."
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
                            <span>{{ formDesactivar.processing ? 'Restableciendo...' : 'Restablecer Servicio' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal 3: Registrar Ensayo DR (Estilo CreateModal.vue) -->
        <div
            v-if="modalEnsayo"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 sm:p-6 backdrop-blur-xs overflow-y-auto animate-fade-in"
            @click.self="modalEnsayo = false"
        >
            <div class="relative flex w-full max-w-lg flex-col rounded-2xl border border-slate-200 bg-white shadow-2xl overflow-hidden my-auto">
                <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-md shadow-blue-500/25">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-extrabold text-slate-900">Simulacro / Ensayo DR</h2>
                            <p class="text-xs text-slate-500">Auditoría obligatoria de continuidad sin generar expedientes falsos.</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="modalEnsayo = false"
                        class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-200 hover:text-slate-700 cursor-pointer"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="enviarEnsayo" class="p-6 space-y-4 text-xs">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Título del Ensayo <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="formEnsayo.titulo"
                            type="text"
                            required
                            maxlength="150"
                            placeholder="Ej. Prueba periódica de contingencia y canales alternos Q1"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-800 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Descripción del Escenario de Prueba <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            v-model="formEnsayo.descripcion"
                            rows="2"
                            required
                            placeholder="Detalle de las condiciones simuladas (caída de ISP, falla en pasarela, etc.)..."
                            class="w-full rounded-xl border border-slate-200 bg-white p-3.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 leading-relaxed"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Resultado y Conclusiones del Ensayo <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            v-model="formEnsayo.resultado"
                            rows="3"
                            required
                            placeholder="Resultados obtenidos, tiempo de respuesta de los canales y conformidad..."
                            class="w-full rounded-xl border border-slate-200 bg-white p-3.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 leading-relaxed"
                        ></textarea>
                    </div>

                    <div class="rounded-xl border border-blue-200/80 bg-blue-50/60 p-3.5 text-[11px] text-blue-900 leading-relaxed">
                        <strong class="font-bold text-blue-950">Garantía de Integridad:</strong> Este ensayo quedará registrado de inmediato en la bitácora legal de auditoría del tenant sin alterar el estado operativo del portal público ni crear reclamos simulados.
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="modalEnsayo = false"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 hover:text-slate-900 transition cursor-pointer"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="formEnsayo.processing"
                            class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-bold text-white shadow-md shadow-blue-500/20 hover:bg-blue-700 disabled:opacity-50 transition cursor-pointer"
                        >
                            <svg v-if="!formEnsayo.processing" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>{{ formEnsayo.processing ? 'Guardando...' : 'Guardar Ensayo en Bitácora' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
