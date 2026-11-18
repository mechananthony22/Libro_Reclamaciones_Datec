<script setup lang="ts">
interface ExpedienteDetalle {
    tipo_label: string;
    detalle_problema: string;
    pedido_consumidor: string;
}

defineProps<{
    expediente: ExpedienteDetalle;
}>();
</script>

<template>
    <div class="rounded-2xl border border-slate-200/80 bg-slate-50/40 p-5 transition hover:border-slate-300 space-y-4">
        <div class="border-b border-slate-200/60 pb-3 flex items-center justify-between">
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>3. Detalle de los Hechos y Pedido Concreto</span>
            </h3>
            <span class="rounded-full bg-blue-50 border border-blue-200 px-2.5 py-0.5 text-[10px] font-bold text-blue-700 uppercase">
                {{ expediente.tipo_label }}
            </span>
        </div>

        <div>
            <span class="text-slate-400 block text-[11px] uppercase font-bold mb-1">
                Detalle del {{ expediente.tipo_label }}:
            </span>
            <div class="rounded-xl border border-slate-200 bg-white p-4 text-xs text-slate-800 whitespace-pre-wrap leading-relaxed">
                {{ expediente.detalle_problema }}
            </div>
        </div>

        <div>
            <span class="text-slate-400 block text-[11px] uppercase font-bold mb-1">
                Pedido del Consumidor:
            </span>
            <div class="rounded-xl border border-slate-200 bg-white p-4 text-xs text-slate-800 whitespace-pre-wrap leading-relaxed">
                {{ expediente.pedido_consumidor }}
            </div>
        </div>
    </div>
</template>

