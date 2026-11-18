<script setup lang="ts">
defineProps<{
    form: any;
    empresa: { razon_social: string; color_primario?: string };
    colorPrimario?: string;
}>();

const emit = defineEmits<{
    (e: 'regresar'): void;
    (e: 'enviar'): void;
}>();
</script>

<template>
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-2xs space-y-6">
        <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-900">
                    3. Detalle de la Reclamación y Pedido del Consumidor
                </h3>
                <p class="text-xs text-slate-500">
                    Diferencia entre Reclamo o Queja según las directrices oficiales de INDECOPI.
                </p>
            </div>
        </div>

        <!-- Selección Reclamo vs Queja -->
        <div>
            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">
                Tipo de Afectación *
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <label
                    class="flex items-start gap-3 p-4 rounded-xl border transition cursor-pointer"
                    :class="form.tipo === 'reclamo' ? 'border-2 border-blue-500 bg-blue-50/40 ring-1 ring-blue-500/20' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/50'"
                >
                    <input
                        type="radio"
                        v-model="form.tipo"
                        value="reclamo"
                        class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500"
                    />
                    <div>
                        <div class="flex items-center gap-2 font-bold text-slate-900 text-xs sm:text-sm">
                            <span class="flex h-5 w-5 items-center justify-center rounded-lg bg-blue-100 text-xs text-blue-700 font-extrabold">R</span>
                            <span>Reclamo</span>
                        </div>
                        <p class="mt-1.5 text-xs text-slate-600 leading-relaxed">
                            Disconformidad directamente relacionada con los productos o servicios adquiridos o contratados.
                        </p>
                    </div>
                </label>

                <label
                    class="flex items-start gap-3 p-4 rounded-xl border transition cursor-pointer"
                    :class="form.tipo === 'queja' ? 'border-2 border-blue-500 bg-blue-50/40 ring-1 ring-blue-500/20' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/50'"
                >
                    <input
                        type="radio"
                        v-model="form.tipo"
                        value="queja"
                        class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500"
                    />
                    <div>
                        <div class="flex items-center gap-2 font-bold text-slate-900 text-xs sm:text-sm">
                            <span class="flex h-5 w-5 items-center justify-center rounded-lg bg-amber-100 text-xs text-amber-700 font-extrabold">Q</span>
                            <span>Queja</span>
                        </div>
                        <p class="mt-1.5 text-xs text-slate-600 leading-relaxed">
                            Malestar o descontento respecto a la atención al público brindada por el personal o aspectos no relacionados directamente con el bien.
                        </p>
                    </div>
                </label>
            </div>
            <p v-if="form.errors.tipo" class="mt-1 text-[11px] font-semibold text-rose-600">
                {{ form.errors.tipo }}
            </p>
        </div>

        <!-- Campo: Detalle de los Hechos -->
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    Detalle del Problema y Hechos Ocurridos *
                </label>
                <span class="text-[11px] font-mono text-slate-400">
                    {{ form.detalle_problema?.length || 0 }} / 3000
                </span>
            </div>
            <textarea
                v-model="form.detalle_problema"
                rows="4"
                maxlength="3000"
                placeholder="Describe de forma cronológica y detallada los hechos que sustentan tu reclamación o queja..."
                class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                :class="{ 'border-rose-400 focus:border-rose-500 focus:ring-rose-500': form.errors.detalle_problema }"
            ></textarea>
            <p v-if="form.errors.detalle_problema" class="mt-1 text-[11px] font-semibold text-rose-600">
                {{ form.errors.detalle_problema }}
            </p>
        </div>

        <!-- Campo: Pedido Concreto del Consumidor -->
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    Pedido Concreto del Consumidor *
                </label>
                <span class="text-[11px] font-mono text-slate-400">
                    {{ form.pedido_consumidor?.length || 0 }} / 1000
                </span>
            </div>
            <textarea
                v-model="form.pedido_consumidor"
                rows="3"
                maxlength="1000"
                placeholder="Indica concretamente lo que solicitas para solucionar tu reclamación (ej. devolución de dinero, cambio de producto, anulación de cobro, etc.)..."
                class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                :class="{ 'border-rose-400 focus:border-rose-500 focus:ring-rose-500': form.errors.pedido_consumidor }"
            ></textarea>
            <p v-if="form.errors.pedido_consumidor" class="mt-1 text-[11px] font-semibold text-rose-600">
                {{ form.errors.pedido_consumidor }}
            </p>
        </div>

        <!-- Anexión Opcional de Evidencias -->
        <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 sm:p-5 space-y-3">
            <div class="flex items-center justify-between">
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    Documentos o Evidencias de Sustento (Opcional)
                </label>
                <span class="text-[11px] text-slate-400 font-medium">Máx. 3 archivos de 5MB c/u</span>
            </div>
            <input
                type="file"
                multiple
                accept=".pdf,.jpg,.jpeg,.png,.webp"
                @change="(e: any) => form.adjuntos = Array.from(e.target.files || [])"
                class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition cursor-pointer"
            />
            <p class="text-[11px] text-slate-400">
                Formatos admitidos: PDF, JPG, PNG o WEBP. Puedes adjuntar fotos del producto, contratos, recibos o capturas.
            </p>
            <p v-if="form.errors.adjuntos" class="mt-1 text-[11px] font-semibold text-rose-600">
                {{ form.errors.adjuntos }}
            </p>
        </div>

        <!-- Aviso de Privacidad y Tratamiento de Datos -->
        <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 sm:p-5 space-y-3">
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">
                        Aviso de Privacidad y Protección de Datos Personales
                    </h4>
                </div>
            </div>

            <p class="text-xs text-slate-600 leading-relaxed">
                En cumplimiento de la <strong>Ley N° 29733 (Ley de Protección de Datos Personales)</strong> y su reglamento, se informa que <strong>{{ empresa.razon_social }}</strong> actúa como <em>Responsable del Tratamiento</em> de los datos recopilados, con la finalidad exclusiva de dar curso, investigar y resolver su reclamación en el marco legal del Código de Protección y Defensa del Consumidor. Sus datos no serán empleados con fines comerciales ni transferidos a terceros no autorizados.
            </p>

            <!-- Checkbox de toma de conocimiento -->
            <div class="pt-2">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input
                        v-model="form.aviso_privacidad_leido"
                        type="checkbox"
                        class="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                        :class="{ 'border-rose-400': form.errors.aviso_privacidad_leido }"
                    />
                    <span class="text-xs font-semibold text-slate-800 leading-snug">
                        He leído y tomo conocimiento del tratamiento de mis datos personales para la atención y seguimiento formal de esta reclamación legal. *
                    </span>
                </label>
                <p v-if="form.errors.aviso_privacidad_leido" class="mt-1 text-[11px] font-semibold text-rose-600">
                    {{ form.errors.aviso_privacidad_leido }}
                </p>
            </div>
        </div>

        <!-- Botones de Acción -->
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
                @click="emit('enviar')"
                :disabled="form.processing"
                class="inline-flex items-center gap-2 rounded-xl px-6 py-2.5 text-xs font-bold text-white shadow-md shadow-blue-500/20 hover:opacity-95 transition disabled:opacity-50 cursor-pointer"
                :style="{ backgroundColor: colorPrimario || '#2563EB' }"
            >
                <span v-if="form.processing">Procesando envío...</span>
                <span v-else>Presentar Reclamación / Queja</span>
                <svg v-if="!form.processing" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
            </button>
        </div>
    </div>
</template>

