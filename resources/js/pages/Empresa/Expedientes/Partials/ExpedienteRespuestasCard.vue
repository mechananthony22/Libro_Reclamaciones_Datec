<script setup lang="ts">
interface AdjuntoRespuesta {
    nombre: string;
    url?: string;
}

interface RespuestaFormal {
    id: number;
    version: number;
    decision_label: string;
    fecha_respuesta: string;
    autor_nombre: string;
    autor_cargo?: string;
    propuesta_solucion: string;
    fundamento_legal?: string;
    adjuntos?: AdjuntoRespuesta[];
    notificado: boolean;
    notificado_en?: string;
}

defineProps<{
    respuestas: RespuestaFormal[];
}>();

const emit = defineEmits<{
    (e: 'abrirModalRespuesta'): void;
    (e: 'reenviarNotificacion', id: number): void;
}>();
</script>

<template>
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Respuestas Formales del Proveedor ({{ respuestas.length }})</span>
            </h2>
            <button
                type="button"
                @click="emit('abrirModalRespuesta')"
                class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 hover:bg-emerald-100 transition cursor-pointer"
            >
                + Nueva Respuesta
            </button>
        </div>

        <div v-if="respuestas.length > 0" class="space-y-4">
            <div
                v-for="resp in respuestas"
                :key="resp.id"
                class="rounded-2xl border border-slate-200 bg-slate-50/60 p-5 space-y-3"
            >
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-200/60 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-bold text-blue-700">
                            Versión {{ resp.version }}
                        </span>
                        <span class="rounded-full bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 text-xs font-bold text-emerald-800">
                            {{ resp.decision_label }}
                        </span>
                    </div>

                    <div class="flex items-center gap-2 text-xs text-slate-500">
                        <span>Fecha: <strong>{{ resp.fecha_respuesta }}</strong></span>
                        <span>•</span>
                        <span>Autor: <strong>{{ resp.autor_nombre }}</strong> {{ resp.autor_cargo ? `(${resp.autor_cargo})` : '' }}</span>
                    </div>
                </div>

                <div>
                    <span class="text-slate-400 block text-[11px] uppercase font-bold mb-1">
                        Propuesta de Solución / Respuesta Motivada:
                    </span>
                    <div class="rounded-xl bg-white border border-slate-200 p-3.5 text-xs text-slate-800 whitespace-pre-wrap leading-relaxed">
                        {{ resp.propuesta_solucion }}
                    </div>
                </div>

                <div v-if="resp.fundamento_legal">
                    <span class="text-slate-400 block text-[11px] uppercase font-bold mb-1">
                        Fundamentación Legal / Técnica:
                    </span>
                    <div class="rounded-xl bg-white border border-slate-200 p-3.5 text-xs text-slate-700 whitespace-pre-wrap leading-relaxed">
                        {{ resp.fundamento_legal }}
                    </div>
                </div>

                <!-- Archivos adjuntos de la respuesta -->
                <div v-if="resp.adjuntos && resp.adjuntos.length > 0">
                    <span class="text-slate-400 block text-[11px] uppercase font-bold mb-1">
                        Documentos Probatorios Anexados ({{ resp.adjuntos.length }}):
                    </span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div
                            v-for="(adj, adjIndex) in resp.adjuntos"
                            :key="adjIndex"
                            class="flex items-center gap-2 rounded-lg bg-white border border-slate-200 p-2 text-xs text-slate-700"
                        >
                            <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                            </svg>
                            <span class="truncate font-medium">{{ adj.nombre }}</span>
                        </div>
                    </div>
                </div>

                <!-- Estado de Notificación Formal y Reenvío -->
                <div class="pt-2 border-t border-slate-200/60 flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold"
                            :class="resp.notificado ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                            {{ resp.notificado ? `Notificado el ${resp.notificado_en}` : 'Notificación en cola / pendiente' }}
                        </span>
                    </div>

                    <button
                        type="button"
                        @click="emit('reenviarNotificacion', resp.id)"
                        class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-800 transition cursor-pointer"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Reenviar Notificación</span>
                    </button>
                </div>
            </div>
        </div>

        <div v-else class="text-xs text-slate-400 italic py-2">
            Aún no se ha emitido una respuesta formal para esta reclamación.
        </div>
    </div>
</template>

