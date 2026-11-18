<script setup lang="ts">
interface EventoExpediente {
    id: number;
    descripcion: string;
    actor_nombre?: string;
    actor_tipo: string;
    created_at: string;
    ip?: string;
}

defineProps<{
    eventos: EventoExpediente[];
}>();
</script>

<template>
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Trazabilidad e Historial Inmutable ({{ eventos.length }})</span>
            </h2>
            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                Auditoría Forense INDECOPI
            </span>
        </div>

        <div class="flow-root">
            <ul role="list" class="-mb-8">
                <li
                    v-for="(ev, evIdx) in eventos"
                    :key="ev.id"
                    class="relative pb-8"
                >
                    <span
                        v-if="evIdx !== eventos.length - 1"
                        class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-slate-200"
                        aria-hidden="true"
                    ></span>
                    <div class="relative flex space-x-3 items-start">
                        <div>
                            <span class="h-8 w-8 rounded-full bg-blue-50 border border-blue-200 flex items-center justify-center text-blue-600 text-xs shadow-2xs">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                        </div>
                        <div class="min-w-0 flex-1 pt-0.5">
                            <div class="text-xs font-bold text-slate-800">
                                {{ ev.descripcion }}
                            </div>
                            <div class="text-[10px] text-slate-400 mt-0.5">
                                {{ ev.created_at }} • {{ ev.actor_nombre || ev.actor_tipo }}
                                <template v-if="ev.ip">• IP: {{ ev.ip }}</template>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</template>

