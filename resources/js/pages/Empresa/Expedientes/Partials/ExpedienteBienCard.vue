<script setup lang="ts">
interface Establecimiento {
    id: number;
    nombre: string;
    codigo: string;
    direccion?: string;
}

interface ExpedienteBien {
    tipo_bien_label: string;
    descripcion_bien: string;
    monto_reclamado?: string | null;
    moneda?: string | null;
    numero_comprobante?: string | null;
    fecha_contratacion?: string | null;
    establecimiento?: Establecimiento | null;
}

defineProps<{
    expediente: ExpedienteBien;
}>();
</script>

<template>
    <div class="rounded-2xl border border-slate-200/80 bg-slate-50/40 p-5 transition hover:border-slate-300">
        <div class="flex items-center justify-between border-b border-slate-200/60 pb-3">
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                <span>2. Identificación del Bien Contratado</span>
            </h3>
            <span class="rounded-full bg-white border border-slate-200 px-2.5 py-0.5 text-[10px] font-bold text-slate-700">
                {{ expediente.tipo_bien_label }}
            </span>
        </div>

        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div class="sm:col-span-2">
                <span class="text-slate-400 block text-[11px]">Descripción del Producto o Servicio:</span>
                <span class="font-bold text-slate-900">
                    {{ expediente.descripcion_bien }}
                </span>
            </div>

            <div>
                <span class="text-slate-400 block text-[11px]">Monto Reclamado:</span>
                <span class="font-bold text-slate-900">
                    {{ expediente.monto_reclamado ? `${expediente.moneda} ${expediente.monto_reclamado}` : 'No consignado' }}
                </span>
            </div>

            <div>
                <span class="text-slate-400 block text-[11px]">N° de Comprobante / Contrato:</span>
                <span class="font-semibold text-slate-800">
                    {{ expediente.numero_comprobante || 'No consignado' }}
                </span>
            </div>

            <div>
                <span class="text-slate-400 block text-[11px]">Fecha de Adquisición o Contratación:</span>
                <span class="font-semibold text-slate-800">
                    {{ expediente.fecha_contratacion || 'No consignada' }}
                </span>
            </div>

            <div>
                <span class="text-slate-400 block text-[11px]">Sede / Establecimiento Comercial:</span>
                <span class="font-semibold text-slate-800">
                    {{ expediente.establecimiento ? `${expediente.establecimiento.nombre} (${expediente.establecimiento.codigo})` : 'Sede Principal' }}
                </span>
            </div>
        </div>
    </div>
</template>

