<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface EmpresaLegal {
    name: string;
    razon_social: string;
    ruc: string;
    direccion: string;
    email?: string;
    telefono?: string;
    slug: string;
    color_primario?: string;
}

defineProps<{
    empresa: EmpresaLegal;
}>();

const emit = defineEmits<{
    (e: 'iniciarRegistro'): void;
}>();
</script>

<template>
    <div class="space-y-6">
        <!-- Introducción y Marco Normativo de INDECOPI -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-2xs">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-start gap-4">
                    <div class="hidden sm:flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-md shadow-blue-500/25">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            Normativa Legal · D.S. N° 011-2011-PCM · INDECOPI
                        </div>
                        <h2 class="mt-0.5 text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
                            Libro de Reclamaciones Virtual
                        </h2>
                        <p class="mt-1 text-sm text-slate-500 max-w-3xl leading-relaxed">
                            Conforme a lo establecido en el Código de Protección y Defensa del Consumidor, ponemos a tu disposición nuestro formulario virtual para el ingreso de reclamaciones o quejas sobre los productos o servicios contratados.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Datos Legales del Proveedor Reclamado -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-2xs space-y-4">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">
                        1. Identificación del Proveedor Reclamado
                    </h3>
                    <p class="text-xs text-slate-500">Datos fiscales oficiales del establecimiento</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 text-xs">
                <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-3">
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">Razón Social</span>
                    <span class="font-bold text-slate-900">{{ empresa.razon_social }}</span>
                </div>
                <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-3">
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">Número de RUC</span>
                    <span class="font-mono font-bold text-slate-900">{{ empresa.ruc }}</span>
                </div>
                <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-3 sm:col-span-2 lg:col-span-1">
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">Domicilio Legal</span>
                    <span class="font-semibold text-slate-800">{{ empresa.direccion }}</span>
                </div>
                <div v-if="empresa.email" class="rounded-xl border border-slate-100 bg-slate-50/60 p-3">
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">Correo Electrónico</span>
                    <span class="font-semibold text-slate-800 font-mono">{{ empresa.email }}</span>
                </div>
                <div v-if="empresa.telefono" class="rounded-xl border border-slate-100 bg-slate-50/60 p-3">
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">Teléfono Oficial</span>
                    <span class="font-semibold text-slate-800">{{ empresa.telefono }}</span>
                </div>
            </div>
        </div>

        <!-- Cuadros informativos: Diferencia entre Reclamo y Queja -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-xs font-black text-blue-700 shadow-2xs">R</span>
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm">¿Qué es un Reclamo?</h4>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Directo al bien o servicio</span>
                    </div>
                </div>
                <p class="mt-3 text-xs text-slate-600 leading-relaxed">
                    Disconformidad relacionada directamente a los bienes adquiridos o a los servicios contratados con nuestro comercio.
                </p>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-xs font-black text-amber-700 shadow-2xs">Q</span>
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm">¿Qué es una Queja?</h4>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Atención al cliente</span>
                    </div>
                </div>
                <p class="mt-3 text-xs text-slate-600 leading-relaxed">
                    Malestar o descontento respecto a la atención recibida por parte del personal o disconformidad que no tiene relación directa con el bien o servicio.
                </p>
            </div>
        </div>

        <!-- Tarjeta de acción para iniciar el llenado del formulario -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 text-center shadow-2xs space-y-4">
            <h3 class="text-lg font-extrabold text-slate-900">
                ¿Deseas registrar un Reclamo o Queja?
            </h3>
            <p class="text-xs text-slate-500 max-w-xl mx-auto">
                El proceso es directo, no requiere cuenta y recibirás una constancia formal con número único de seguimiento y descarga en PDF.
            </p>
            <div class="pt-2 flex flex-wrap items-center justify-center gap-3">
                <button
                    type="button"
                    @click="emit('iniciarRegistro')"
                    class="inline-flex items-center gap-2 rounded-xl px-6 py-3 text-xs font-bold text-white shadow-md shadow-blue-500/20 hover:opacity-95 transition cursor-pointer"
                    :style="{ backgroundColor: empresa.color_primario || '#2563EB' }"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span>Iniciar Registro de Reclamación</span>
                </button>

                <Link
                    :href="`/l/${empresa.slug}/consultar`"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 hover:text-slate-900 transition"
                >
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <span>Consultar Estado de Reclamación</span>
                </Link>
            </div>
        </div>
    </div>
</template>

