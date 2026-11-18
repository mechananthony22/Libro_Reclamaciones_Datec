<script setup lang="ts">
defineProps<{
    form: any;
    establecimientos?: Array<{ id: number; nombre: string; codigo: string; direccion: string; es_principal?: boolean }>;
    colorPrimario?: string;
}>();

const emit = defineEmits<{
    (e: 'continuar'): void;
    (e: 'regresar'): void;
}>();
</script>

<template>
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-2xs space-y-6">
        <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-900">
                    2. Identificación del Bien o Servicio Contratado
                </h3>
                <p class="text-xs text-slate-500">
                    Indica con exactitud las características del bien adquirido o servicio solicitado.
                </p>
            </div>
        </div>

        <!-- Selector de Sede o Establecimiento Asociado -->
        <div v-if="establecimientos && establecimientos.length > 0">
            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                Establecimiento / Sede Relacionada
            </label>
            <select
                v-model="form.establecimiento_id"
                class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-800 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
            >
                <option
                    v-for="est in establecimientos"
                    :key="est.id"
                    :value="est.id"
                >
                    {{ est.nombre }} ({{ est.codigo }}) — {{ est.direccion }}
                    {{ est.es_principal ? ' [Principal]' : '' }}
                </option>
            </select>
        </div>

        <!-- Tarjetas de Selección: Producto o Servicio -->
        <div>
            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">
                Tipo de Bien *
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <label
                    class="flex items-start gap-3 p-4 rounded-xl border transition cursor-pointer"
                    :class="form.tipo_bien === 'producto' ? 'border-2 border-blue-500 bg-blue-50/40 ring-1 ring-blue-500/20' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/50'"
                >
                    <input
                        type="radio"
                        v-model="form.tipo_bien"
                        value="producto"
                        class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500"
                    />
                    <div>
                        <div class="flex items-center gap-2 font-bold text-slate-900 text-xs sm:text-sm">
                            <span>Producto</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500 leading-relaxed">
                            Bienes tangibles muebles (ropa, electrodomésticos, equipos electrónicos, alimentos, etc.).
                        </p>
                    </div>
                </label>

                <label
                    class="flex items-start gap-3 p-4 rounded-xl border transition cursor-pointer"
                    :class="form.tipo_bien === 'servicio' ? 'border-2 border-blue-500 bg-blue-50/40 ring-1 ring-blue-500/20' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/50'"
                >
                    <input
                        type="radio"
                        v-model="form.tipo_bien"
                        value="servicio"
                        class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500"
                    />
                    <div>
                        <div class="flex items-center gap-2 font-bold text-slate-900 text-xs sm:text-sm">
                            <span>Servicio</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500 leading-relaxed">
                            Prestaciones intangibles o actividades contratadas (transporte, software, suscripciones, asesoría, etc.).
                        </p>
                    </div>
                </label>
            </div>
            <p v-if="form.errors.tipo_bien" class="mt-1 text-[11px] font-semibold text-rose-600">
                {{ form.errors.tipo_bien }}
            </p>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <!-- Monto Reclamado y Moneda -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                    Monto Reclamado (Opcional)
                </label>
                <div class="flex gap-2">
                    <select
                        v-model="form.moneda"
                        class="w-24 shrink-0 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-bold text-slate-800 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
                    >
                        <option value="PEN">S/ (PEN)</option>
                        <option value="USD">$ (USD)</option>
                    </select>
                    <input
                        v-model="form.monto_reclamado"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 font-mono text-xs font-medium text-slate-800 placeholder:text-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    />
                </div>
                <p v-if="form.errors.monto_reclamado" class="mt-1 text-[11px] font-semibold text-rose-600">
                    {{ form.errors.monto_reclamado }}
                </p>
            </div>

            <!-- Número de Comprobante de Pago o Contrato -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                    N° de Boleta, Factura, Ticket o Contrato
                </label>
                <input
                    v-model="form.numero_comprobante"
                    type="text"
                    placeholder="ej. B001-00012345 o PED-9821"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 font-mono text-xs font-medium text-slate-800 placeholder:text-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                />
            </div>

            <!-- Fecha de Contratación / Compra -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                    Fecha de Compra o Contratación
                </label>
                <input
                    v-model="form.fecha_contratacion"
                    type="date"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-medium text-slate-800 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                />
            </div>

            <!-- Descripción Concreta del Producto o Servicio -->
            <div class="sm:col-span-2">
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                    Descripción del Producto o Servicio *
                </label>
                <textarea
                    v-model="form.descripcion_bien"
                    rows="3"
                    placeholder="Indica la marca, modelo, código o descripción puntual del producto o servicio contratado..."
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    :class="{ 'border-rose-400 focus:border-rose-500 focus:ring-rose-500': form.errors.descripcion_bien }"
                ></textarea>
                <p v-if="form.errors.descripcion_bien" class="mt-1 text-[11px] font-semibold text-rose-600">
                    {{ form.errors.descripcion_bien }}
                </p>
            </div>
        </div>

        <!-- Botones de Avance y Retroceso -->
        <div class="flex items-center justify-between pt-4 border-t border-slate-100">
            <button
                type="button"
                @click="emit('regresar')"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 hover:text-slate-900 transition cursor-pointer"
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                </svg>
                <span>Paso Anterior</span>
            </button>

            <button
                type="button"
                @click="emit('continuar')"
                class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-xs font-bold text-white shadow-md shadow-blue-500/20 hover:opacity-95 transition cursor-pointer"
                :style="{ backgroundColor: colorPrimario || '#2563EB' }"
            >
                <span>Continuar al Paso 3</span>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </button>
        </div>
    </div>
</template>

