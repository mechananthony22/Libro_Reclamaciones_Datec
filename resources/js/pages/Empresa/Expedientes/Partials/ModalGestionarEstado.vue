<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

interface EstadoOpcion {
    value: string;
    label: string;
    permitido: boolean;
    es_actual?: boolean;
}

const props = defineProps<{
    abierto: boolean;
    expedienteId: number;
    numeroHoja: string;
    estadoActualLabel: string;
    estadosDisponibles: EstadoOpcion[];
}>();

const emit = defineEmits<{
    (e: 'cerrar'): void;
}>();

const formEstado = useForm({
    estado: '',
    motivo: '',
});

function submit() {
    formEstado.patch(`/expedientes/${props.expedienteId}/estado`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('cerrar');
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
        <div class="relative flex w-full max-w-lg flex-col rounded-2xl border border-slate-200 bg-white shadow-2xl overflow-hidden my-auto">
            <!-- Cabecera del Modal -->
            <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-md shadow-blue-500/25">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">
                            Gestionar Estado del Expediente
                        </h2>
                        <p class="text-xs text-slate-500">
                            Hoja <strong class="text-slate-800 font-mono">{{ numeroHoja }}</strong> · Estado actual: <span class="font-bold text-blue-700">{{ estadoActualLabel }}</span>
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

            <!-- Formulario -->
            <form @submit.prevent="submit" class="p-6 space-y-4 text-xs">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        Seleccionar Nuevo Estado <span class="text-rose-500">*</span>
                    </label>
                    <select
                        v-model="formEstado.estado"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-800 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
                        required
                    >
                        <option value="">-- Selecciona el nuevo estado --</option>
                        <option
                            v-for="est in estadosDisponibles"
                            :key="est.value"
                            :value="est.value"
                            :disabled="est.es_actual"
                        >
                            {{ est.label }} {{ est.es_actual ? '(Actual)' : (!est.permitido ? '(Transición no permitida)' : '') }}
                        </option>
                    </select>
                    <p v-if="formEstado.errors.estado" class="text-rose-600 mt-1 font-semibold text-[11px]">
                        {{ formEstado.errors.estado }}
                    </p>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        Motivo o Sustento del Cambio <span class="text-rose-500">*</span>
                    </label>
                    <textarea
                        v-model="formEstado.motivo"
                        rows="3"
                        placeholder="Describe el motivo de la transición para el registro inmutable de auditoría..."
                        class="w-full rounded-xl border border-slate-200 bg-white p-3.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 leading-relaxed"
                        required
                    ></textarea>
                    <p v-if="formEstado.errors.motivo" class="text-rose-600 mt-1 font-semibold text-[11px]">
                        {{ formEstado.errors.motivo }}
                    </p>
                </div>

                <!-- Mensaje de Auditoría Inmutable -->
                <div class="rounded-xl border border-amber-200/80 bg-amber-50/70 p-3.5 flex items-start gap-3 text-amber-900">
                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700 mt-0.5">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="text-[11px] leading-relaxed">
                        <strong class="font-bold text-amber-950">Aviso de Auditoría:</strong> El cambio quedará registrado en la bitácora con tu nombre, fecha, hora e IP. La fecha de presentación original y las pruebas del consumidor se conservan intactas.
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                    <button
                        type="button"
                        @click="emit('cerrar')"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 hover:text-slate-900 transition cursor-pointer"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        :disabled="formEstado.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-bold text-white shadow-md shadow-blue-500/20 hover:bg-blue-700 disabled:opacity-50 transition cursor-pointer"
                    >
                        <svg v-if="!formEstado.processing" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ formEstado.processing ? 'Actualizando...' : 'Guardar Transición' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

