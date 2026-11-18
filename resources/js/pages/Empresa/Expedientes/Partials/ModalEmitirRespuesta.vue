<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

interface OpcionDecision {
    value: string;
    label: string;
}

const props = defineProps<{
    abierto: boolean;
    expedienteId: number;
    numeroHoja: string;
    consumidorNombre: string;
    consumidorEmail?: string;
    decisionesDisponibles: OpcionDecision[];
}>();

const emit = defineEmits<{
    (e: 'cerrar'): void;
}>();

const formRespuesta = useForm({
    decision: 'aceptado',
    propuesta_solucion: '',
    fundamento_legal: '',
    autor_cargo: '',
    notificar_inmediatamente: true,
    adjuntos: [] as File[],
});

function handleAdjuntos(e: Event) {
    const target = e.target as HTMLInputElement;
    if (target.files) {
        formRespuesta.adjuntos = Array.from(target.files);
    }
}

function submit() {
    formRespuesta.post(`/expedientes/${props.expedienteId}/respuestas`, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            emit('cerrar');
            formRespuesta.reset();
        },
    });
}
</script>

<template>
    <div
        v-if="abierto"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 sm:p-6 backdrop-blur-xs overflow-y-auto animate-fade-in"
        @click.self="emit('cerrar')"
    >
        <div class="relative flex max-h-[92vh] w-full max-w-2xl flex-col rounded-2xl border border-slate-200 bg-white shadow-2xl overflow-hidden my-auto">
            <!-- Cabecera del Modal -->
            <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-md shadow-emerald-500/25">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">
                            Emitir Respuesta Formal de Reclamación
                        </h2>
                        <p class="text-xs text-slate-500">
                            Expediente: <strong class="text-slate-800 font-mono">{{ numeroHoja }}</strong> · Consumidor: <span class="font-semibold text-slate-700">{{ consumidorNombre }}</span>
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="emit('cerrar')"
                    class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-200 hover:text-slate-700 cursor-pointer"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Formulario con scroll -->
            <form @submit.prevent="submit" class="flex flex-1 flex-col overflow-hidden">
                <div class="flex-1 overflow-y-auto p-6 space-y-4 text-xs">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                                Decisión / Sentido de la Respuesta <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="formRespuesta.decision"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-800 transition focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 cursor-pointer"
                                required
                            >
                                <option
                                    v-for="d in decisionesDisponibles"
                                    :key="d.value"
                                    :value="d.value"
                                >
                                    {{ d.label }}
                                </option>
                            </select>
                            <p v-if="formRespuesta.errors.decision" class="text-rose-600 mt-1 font-semibold text-[11px]">
                                {{ formRespuesta.errors.decision }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                                Cargo del Autor / Firmante
                            </label>
                            <input
                                v-model="formRespuesta.autor_cargo"
                                type="text"
                                placeholder="Ej: Jefe de Experiencia y Garantías"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 transition focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                            <p v-if="formRespuesta.errors.autor_cargo" class="text-rose-600 mt-1 font-semibold text-[11px]">
                                {{ formRespuesta.errors.autor_cargo }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Propuesta de Solución y Respuesta Motivada <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            v-model="formRespuesta.propuesta_solucion"
                            rows="4"
                            placeholder="Redacta la respuesta formal detallando la solución, acciones tomadas o propuesta al consumidor..."
                            class="w-full rounded-xl border border-slate-200 bg-white p-3.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 transition focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 leading-relaxed"
                            required
                        ></textarea>
                        <p v-if="formRespuesta.errors.propuesta_solucion" class="text-rose-600 mt-1 font-semibold text-[11px]">
                            {{ formRespuesta.errors.propuesta_solucion }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Fundamentación Legal / Técnica (Opcional)
                        </label>
                        <textarea
                            v-model="formRespuesta.fundamento_legal"
                            rows="2"
                            placeholder="Cláusulas contractuales, informe técnico, políticas de garantía o normativa aplicable..."
                            class="w-full rounded-xl border border-slate-200 bg-white p-3.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 transition focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 leading-relaxed"
                        ></textarea>
                        <p v-if="formRespuesta.errors.fundamento_legal" class="text-rose-600 mt-1 font-semibold text-[11px]">
                            {{ formRespuesta.errors.fundamento_legal }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Documentos Probatorios Anexos (PDF, JPG, PNG - Máx. 5MB)
                        </label>
                        <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50/50 p-2.5 transition hover:bg-slate-50">
                            <input
                                type="file"
                                multiple
                                accept=".pdf,image/png,image/jpeg,image/webp"
                                @change="handleAdjuntos"
                                class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer"
                            />
                        </div>
                        <p v-if="formRespuesta.errors.adjuntos" class="text-rose-600 mt-1 font-semibold text-[11px]">
                            {{ formRespuesta.errors.adjuntos }}
                        </p>
                    </div>

                    <!-- Notificación Automática al Consumidor -->
                    <div v-if="consumidorEmail" class="rounded-xl border border-blue-200/80 bg-blue-50/60 p-3.5 flex items-start gap-3">
                        <input
                            v-model="formRespuesta.notificar_inmediatamente"
                            id="chk-notificar-modal"
                            type="checkbox"
                            class="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer"
                        />
                        <label for="chk-notificar-modal" class="text-xs text-slate-700 cursor-pointer select-none leading-relaxed">
                            <strong class="font-bold text-slate-900">Notificar inmediatamente al correo del consumidor</strong> (<span class="font-medium text-blue-700">{{ consumidorEmail }}</span>).
                            <span class="block text-[11px] text-slate-500 mt-0.5">Se adjuntarán los documentos probatorios y se registrará la evidencia formal de entrega con trazabilidad inmutable.</span>
                        </label>
                    </div>
                </div>

                <!-- Pie del Modal -->
                <div class="flex items-center justify-end gap-2.5 border-t border-slate-100 bg-slate-50/50 px-6 py-4">
                    <button
                        type="button"
                        @click="emit('cerrar')"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 hover:text-slate-900 transition cursor-pointer"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        :disabled="formRespuesta.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white shadow-md shadow-emerald-500/25 hover:bg-emerald-700 disabled:opacity-50 transition cursor-pointer"
                    >
                        <svg v-if="!formRespuesta.processing" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ formRespuesta.processing ? 'Registrando...' : 'Emitir y Guardar Respuesta' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

