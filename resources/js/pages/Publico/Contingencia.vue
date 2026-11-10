<script setup>
import { Head } from '@inertiajs/vue3'

const props = defineProps({
    empresa: {
        type: Object,
        required: true,
    },
    contingencia: {
        type: Object,
        required: true,
    },
})

const formatearFecha = (fechaIso) => {
    if (!fechaIso) return null
    try {
        const d = new Date(fechaIso)
        return d.toLocaleString('es-PE', {
            dateStyle: 'medium',
            timeStyle: 'short',
        })
    } catch {
        return fechaIso
    }
}
</script>

<template>
    <Head :title="`Aviso de Contingencia - ${empresa.razon_social || empresa.name}`" />

    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-amber-50/20 to-slate-100 py-12 px-4 sm:px-6 lg:px-8 flex flex-col justify-center items-center">
        <div class="w-full max-w-3xl">
            <!-- Encabezado con Identidad del Proveedor -->
            <div class="text-center mb-8">
                <div v-if="empresa.logo_url" class="inline-flex justify-center mb-4">
                    <img :src="empresa.logo_url" :alt="empresa.razon_social" class="h-16 w-auto max-w-xs object-contain" />
                </div>
                <div v-else class="inline-flex items-center justify-center h-16 w-16 rounded-2xl bg-amber-500/10 text-amber-600 mb-4 shadow-sm border border-amber-200">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>

                <h1 class="text-2xl font-black text-slate-900 tracking-tight sm:text-3xl">
                    {{ empresa.razon_social || empresa.name }}
                </h1>
                <p v-if="empresa.ruc" class="text-sm font-semibold text-slate-500 mt-1">
                    RUC: {{ empresa.ruc }}
                </p>
            </div>

            <!-- Tarjeta Central de Contingencia -->
            <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/60 border border-slate-200/80 overflow-hidden">
                <!-- Barra de Estado -->
                <div class="bg-gradient-to-r from-amber-500 to-amber-600 px-6 py-4 text-white flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-white"></span>
                        </span>
                        <span class="font-extrabold text-sm uppercase tracking-wider">
                            Plan de Contingencia Operativa Activo
                        </span>
                    </div>
                    <span class="text-xs font-semibold bg-white/20 px-3 py-1 rounded-full">
                        Mantenimiento Técnico
                    </span>
                </div>

                <div class="p-6 sm:p-8 space-y-6">
                    <!-- Mensaje Oficial -->
                    <div class="rounded-2xl bg-amber-50/80 border border-amber-200/80 p-5 text-amber-950">
                        <div class="flex items-start gap-3">
                            <svg class="h-6 w-6 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <h2 class="font-bold text-base text-amber-900">
                                    Canal de Registro Digital Temporalmente No Disponible
                                </h2>
                                <p class="text-sm text-amber-800 mt-1.5 leading-relaxed font-medium">
                                    {{ contingencia.mensaje }}
                                </p>
                            </div>
                        </div>

                        <div v-if="contingencia.estimada_fin" class="mt-4 pt-3 border-t border-amber-200/70 flex items-center gap-2 text-xs font-semibold text-amber-900">
                            <svg class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Restablecimiento estimado: <strong>{{ formatearFecha(contingencia.estimada_fin) }}</strong></span>
                        </div>
                    </div>

                    <!-- Canales Alternativos de Atención Inmediata -->
                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800 mb-4 flex items-center gap-2">
                            <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            Canales Alternativos de Atención y Reclamación
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Canal Correo -->
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 transition hover:border-blue-300 hover:bg-white">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-xs font-semibold text-slate-400">Correo Electrónico</div>
                                        <a
                                            v-if="contingencia.canales?.email"
                                            :href="`mailto:${contingencia.canales.email}`"
                                            class="text-sm font-bold text-blue-600 hover:underline truncate block"
                                        >
                                            {{ contingencia.canales.email }}
                                        </a>
                                        <div v-else class="text-xs text-slate-500 italic">No disponible</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Canal Teléfono / WhatsApp -->
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 transition hover:border-emerald-300 hover:bg-white">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-xs font-semibold text-slate-400">Teléfono / Soporte</div>
                                        <a
                                            v-if="contingencia.canales?.telefono"
                                            :href="`tel:${contingencia.canales.telefono}`"
                                            class="text-sm font-bold text-emerald-600 hover:underline truncate block"
                                        >
                                            {{ contingencia.canales.telefono }}
                                        </a>
                                        <div v-else class="text-xs text-slate-500 italic">No disponible</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Canal Domicilio Físico -->
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 transition hover:border-purple-300 hover:bg-white">
                                <div class="flex items-start gap-3">
                                    <div class="h-10 w-10 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center shrink-0">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-xs font-semibold text-slate-400">Libro Físico en Sede Principal</div>
                                        <div class="text-xs font-bold text-slate-700 leading-snug">
                                            {{ contingencia.canales?.direccion_fisica || 'Consulte en nuestro establecimiento' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Horario -->
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 transition hover:border-slate-300 hover:bg-white">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-xl bg-slate-200 text-slate-600 flex items-center justify-center shrink-0">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-xs font-semibold text-slate-400">Horario de Atención</div>
                                        <div class="text-xs font-bold text-slate-700">
                                            {{ contingencia.canales?.horario || 'Lunes a Viernes de 09:00 a 18:00' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nota Legal INDECOPI -->
                    <div class="rounded-2xl bg-slate-100/70 p-4 border border-slate-200 text-xs text-slate-600 leading-relaxed font-normal">
                        <strong class="font-bold text-slate-800">Nota Legal de Protección al Consumidor:</strong>
                        Conforme al Código de Protección y Defensa del Consumidor (Ley N° 29571) y directivas del INDECOPI, ante la indisponibilidad de la plataforma digital, el consumidor mantiene expeditos sus derechos mediante los canales alternativos arriba indicados o solicitando el Libro de Reclamaciones físico disponible en el establecimiento comercial. No se emitirán números de hoja virtual mientras el portal se encuentre en mantenimiento.
                    </div>
                </div>
            </div>

            <!-- Pie de página -->
            <div class="mt-8 text-center text-xs text-slate-400 font-medium">
                Plataforma de Libro de Reclamaciones &bull; DatecLibro &bull; Conforme a Ley 29571
            </div>
        </div>
    </div>
</template>

