<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Props {
    empresa: {
        name: string;
        razon_social: string;
        ruc: string;
        logo_path: string | null;
        color_primario: string;
        slug: string;
    };
    contingencia: {
        mensaje: string;
        canales: {
            email?: string;
            telefono?: string;
            direccion_fisica?: string;
            horario?: string;
        } | null;
        activada_en: string | null;
        estimada_fin: string | null;
    };
}

const props = defineProps<Props>();
const logoError = ref(false);

const formatearFecha = (fechaIso: string | null): string | null => {
    if (!fechaIso) return null;
    try {
        const d = new Date(fechaIso);
        return d.toLocaleString('es-PE', {
            dateStyle: 'medium',
            timeStyle: 'short',
        });
    } catch {
        return fechaIso;
    }
};
</script>

<template>
    <Head :title="`Aviso de Contingencia - ${empresa.razon_social || empresa.name}`">
        <meta name="robots" content="noindex, nofollow" />
    </Head>

    <div class="min-h-screen bg-[#F8FAFC] text-slate-800 antialiased font-sans selection:bg-blue-100 selection:text-blue-700 flex flex-col justify-between">
        <!-- Cabecera Institucional del Proveedor Reclamado (Idéntico a LibroReclamaciones.vue) -->
        <header class="border-b border-slate-200/80 bg-white sticky top-0 z-30 shadow-2xs">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3 sm:px-6">
                <!-- Logotipo e Identidad de la Empresa -->
                <div class="flex items-center gap-3">
                    <div
                        v-if="empresa.logo_path && !logoError"
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200/80 bg-white p-1 shadow-2xs overflow-hidden"
                    >
                        <img
                            :src="empresa.logo_path"
                            :alt="empresa.name"
                            class="h-full w-full object-contain"
                            @error="logoError = true"
                        />
                    </div>
                    <div
                        v-else
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-white font-black text-xs shadow-2xs"
                        :style="{ backgroundColor: empresa.color_primario || '#2563EB' }"
                    >
                        {{ (empresa.name || empresa.razon_social || 'E').charAt(0).toUpperCase() }}
                    </div>

                    <div>
                        <h1 class="font-extrabold text-slate-900 text-sm sm:text-base leading-tight">
                            {{ empresa.name || empresa.razon_social }}
                        </h1>
                        <p class="text-[11px] text-slate-400 font-mono">RUC: {{ empresa.ruc }}</p>
                    </div>
                </div>

                <!-- Distintivo Oficial -->
                <div class="flex items-center gap-2.5">
                    <div class="hidden sm:flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] font-semibold text-slate-600">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        <span>Sistema Conforme INDECOPI</span>
                    </div>

                    <Link
                        :href="`/l/${empresa.slug}/consultar`"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50 hover:text-slate-900"
                    >
                        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <span>Consultar Reclamo</span>
                    </Link>
                </div>
            </div>
        </header>

        <!-- Contenido Central de Contingencia -->
        <main class="mx-auto max-w-4xl px-4 py-8 sm:px-6 w-full flex-1 flex flex-col justify-center">
            <!-- Banner Alerta de Contingencia (Estilo Empresa/Soporte/Contingencia) -->
            <div class="rounded-2xl border border-amber-200/80 bg-amber-50/70 p-5 sm:p-6 shadow-2xs mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white shadow-md shadow-amber-500/25">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-sm sm:text-base font-bold text-amber-950">
                                    Canal de Registro Digital en Mantenimiento Preventivo
                                </h2>
                                <span class="rounded-full bg-amber-200 px-2.5 py-0.5 text-[10px] font-black text-amber-900 uppercase tracking-wider">
                                    Contingencia Activa
                                </span>
                            </div>
                            <p class="text-xs sm:text-sm text-amber-900/90 mt-1 leading-relaxed font-medium">
                                {{ contingencia.mensaje }}
                            </p>
                            <div v-if="contingencia.estimada_fin" class="mt-2.5 flex items-center gap-1.5 text-xs font-semibold text-amber-950">
                                <svg class="h-4 w-4 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Restablecimiento estimado: <strong>{{ formatearFecha(contingencia.estimada_fin) }}</strong></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Canales Alternativos de Atención Inmediata (Estilo Tarjetas Empresa) -->
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xs">
                <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Canales Alternativos de Atención y Reclamación</h3>
                            <p class="text-xs text-slate-500">Mecanismos de respaldo para interponer su reclamo o queja de manera inmediata.</p>
                        </div>
                    </div>
                </div>

                <div class="p-5 sm:p-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- Canal Correo -->
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4 transition hover:bg-slate-50">
                        <div class="flex items-center gap-2">
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-100/70 text-blue-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Correo Electrónico</span>
                        </div>
                        <a
                            v-if="contingencia.canales?.email"
                            :href="`mailto:${contingencia.canales.email}`"
                            class="mt-3 block text-xs font-bold text-blue-600 hover:text-blue-800 transition truncate"
                            :title="contingencia.canales.email"
                        >
                            {{ contingencia.canales.email }}
                        </a>
                        <p v-else class="mt-3 text-xs font-medium text-slate-400 italic">No disponible</p>
                        <p class="mt-0.5 text-[10px] text-slate-400 font-medium">Recepción digital directa de reclamaciones</p>
                    </div>

                    <!-- Canal Teléfono / WhatsApp -->
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4 transition hover:bg-slate-50">
                        <div class="flex items-center gap-2">
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100/70 text-emerald-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Teléfono / WhatsApp</span>
                        </div>
                        <a
                            v-if="contingencia.canales?.telefono"
                            :href="`tel:${contingencia.canales.telefono}`"
                            class="mt-3 block text-xs font-bold text-emerald-600 hover:text-emerald-800 transition truncate"
                            :title="contingencia.canales.telefono"
                        >
                            {{ contingencia.canales.telefono }}
                        </a>
                        <p v-else class="mt-3 text-xs font-medium text-slate-400 italic">No disponible</p>
                        <p class="mt-0.5 text-[10px] text-slate-400 font-medium">Línea de soporte y atención al cliente</p>
                    </div>

                    <!-- Canal Sede Física -->
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4 transition hover:bg-slate-50">
                        <div class="flex items-center gap-2">
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-100/70 text-amber-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Libro Físico en Sede</span>
                        </div>
                        <p class="mt-3 text-xs font-bold text-slate-900 leading-snug truncate" :title="contingencia.canales?.direccion_fisica">
                            {{ contingencia.canales?.direccion_fisica || 'Consulte en nuestro establecimiento' }}
                        </p>
                        <p class="mt-0.5 text-[10px] text-slate-400 font-medium">Hojas de reclamación físicas disponibles</p>
                    </div>

                    <!-- Horario -->
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4 transition hover:bg-slate-50">
                        <div class="flex items-center gap-2">
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-100/70 text-indigo-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Horario de Atención</span>
                        </div>
                        <p class="mt-3 text-xs font-bold text-slate-900 truncate" :title="contingencia.canales?.horario">
                            {{ contingencia.canales?.horario || 'Lunes a Viernes de 09:00 a 18:00' }}
                        </p>
                        <p class="mt-0.5 text-[10px] text-slate-400 font-medium">Horario corrido presencial y remoto</p>
                    </div>
                </div>

                <!-- Nota Legal INDECOPI (Estilo Footer Tarjeta) -->
                <div class="border-t border-slate-100 bg-slate-50/50 p-4 sm:p-5 text-xs text-slate-600 leading-relaxed font-normal">
                    <p class="text-[11px] leading-relaxed">
                        <strong class="font-bold text-slate-800">Nota Legal de Protección al Consumidor:</strong>
                        Conforme al Código de Protección y Defensa del Consumidor (Ley N° 29571) y directivas del INDECOPI, ante la indisponibilidad de la plataforma digital, el consumidor mantiene expeditos sus derechos mediante los canales alternativos arriba indicados o solicitando el Libro de Reclamaciones físico disponible en el establecimiento comercial. No se emitirán números de hoja virtual mientras el portal se encuentre en mantenimiento.
                    </p>
                </div>
            </div>
        </main>

        <!-- Pie de Página Institucional (Idéntico a LibroReclamaciones.vue) -->
        <footer class="border-t border-slate-200/80 bg-white py-4 mt-auto">
            <div class="mx-auto flex max-w-5xl flex-col sm:flex-row items-center justify-between gap-3 px-4 sm:px-6 text-xs text-slate-500">
                <div class="flex items-center gap-2">
                    <img
                        src="/logoDLR.png"
                        alt="DatecLibro"
                        class="h-6 w-auto object-contain"
                        onerror="this.style.display='none';"
                    />
                    <span>Libro de Reclamaciones conforme al <strong>D.S. 011-2011-PCM</strong></span>
                </div>
                <div class="flex items-center gap-4 text-[11px] font-medium text-slate-400">
                    <span>Plataforma Segura SSL</span>
                    <span>&bull;</span>
                    <span>INDECOPI Ley 29571</span>
                </div>
            </div>
        </footer>
    </div>
</template>

