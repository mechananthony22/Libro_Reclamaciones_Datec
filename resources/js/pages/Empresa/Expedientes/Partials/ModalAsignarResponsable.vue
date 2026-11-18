<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

interface Usuario {
    id: number;
    name: string;
    email: string;
}

const props = defineProps<{
    abierto: boolean;
    expedienteId: number;
    numeroHoja: string;
    responsableActualId?: number | null;
    usuariosAsignables: Usuario[];
}>();

const emit = defineEmits<{
    (e: 'cerrar'): void;
}>();

const formAsignar = useForm({
    usuario_id: props.responsableActualId ? String(props.responsableActualId) : '',
    motivo: '',
});

function submit() {
    formAsignar.patch(`/expedientes/${props.expedienteId}/asignar`, {
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
        <div class="relative flex w-full max-w-md flex-col rounded-2xl border border-slate-200 bg-white shadow-2xl overflow-hidden my-auto">
            <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-md shadow-indigo-500/25">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">
                            Asignar Responsable
                        </h2>
                        <p class="text-xs text-slate-500">
                            Expediente: <strong class="text-slate-800 font-mono">{{ numeroHoja }}</strong>
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

            <form @submit.prevent="submit" class="p-6 space-y-4 text-xs">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        Seleccionar Usuario de la Empresa
                    </label>
                    <select
                        v-model="formAsignar.usuario_id"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-800 transition focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer"
                    >
                        <option value="">-- Sin asignar (Dejar libre) --</option>
                        <option
                            v-for="user in usuariosAsignables"
                            :key="user.id"
                            :value="user.id"
                        >
                            {{ user.name }} ({{ user.email }})
                        </option>
                    </select>
                    <p v-if="formAsignar.errors.usuario_id" class="text-rose-600 mt-1 font-semibold text-[11px]">
                        {{ formAsignar.errors.usuario_id }}
                    </p>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        Motivo o Nota de Asignación (Opcional)
                    </label>
                    <textarea
                        v-model="formAsignar.motivo"
                        rows="2"
                        placeholder="Ej: Derivado al área de soporte técnico para peritaje de garantía..."
                        class="w-full rounded-xl border border-slate-200 bg-white p-3.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 transition focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                    ></textarea>
                    <p v-if="formAsignar.errors.motivo" class="text-rose-600 mt-1 font-semibold text-[11px]">
                        {{ formAsignar.errors.motivo }}
                    </p>
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
                        :disabled="formAsignar.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-md shadow-indigo-500/20 hover:bg-indigo-700 disabled:opacity-50 transition cursor-pointer"
                    >
                        <span>{{ formAsignar.processing ? 'Guardando...' : 'Confirmar Asignación' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

