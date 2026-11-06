<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    empresa: {
        name: string;
        razon_social: string;
        ruc: string;
        direccion: string;
        email: string | null;
        telefono: string | null;
        color_primario: string;
        slug: string;
    };
    reclamacion: {
        numero_hoja: string;
        tipo: string;
        tipo_label: string;
        tipo_bien_label: string;
        fecha_hora: string;
        vence_en: string;
        consumidor_nombre: string;
        consumidor_email: string;
        token_consulta: string;
        adjuntos_count: number;
    };
    url_descarga_pdf: string;
}

const props = defineProps<Props>();

// Copiar código de seguimiento al portapapeles
function copiarToken() {
    navigator.clipboard.writeText(props.reclamacion.token_consulta);
    alert('Código de seguimiento copiado al portapapeles.');
}
</script>

<template>
    <Head :title="`Constancia Registrada | ${reclamacion.numero_hoja} - ${empresa.name}`" />

    <div class="min-h-screen bg-[#FBFDFF] text-slate-800 antialiased font-sans selection:bg-blue-100 selection:text-blue-700 py-12 px-4 sm:px-6 lg:px-8 flex flex-col justify-center items-center relative overflow-hidden">
        <!-- Luces de fondo decorativas -->
        <div class="absolute -top-32 -left-32 h-96 w-96 rounded-full bg-emerald-100/50 blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-32 -right-32 h-96 w-96 rounded-full bg-blue-100/40 blur-3xl pointer-events-none"></div>

        <div class="w-full max-w-2xl relative z-10">
            <!-- Tarjeta Central de Éxito -->
            <div class="rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-10 shadow-xl shadow-slate-200/50 text-center">
                
                <!-- Ícono animado de éxito -->
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-emerald-50 text-emerald-600 shadow-md shadow-emerald-500/10 mb-6">
                    <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <span class="inline-block rounded-full bg-emerald-100/80 px-3.5 py-1 text-xs font-bold text-emerald-800 mb-3 uppercase tracking-wider">
                    Presentación Formal Confirmada
                </span>

                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    ¡Tu {{ reclamacion.tipo_label }} ha sido registrada!
                </h1>

                <p class="mt-2 text-sm text-slate-600 max-w-lg mx-auto leading-relaxed">
                    Se ha generado tu constancia digital conforme al Código de Protección y Defensa del Consumidor (Ley N° 29571) y normativa vigente de INDECOPI.
                </p>

                <!-- Cuadro de Datos Clave -->
                <div class="mt-8 rounded-2xl border border-slate-200 bg-slate-50/70 p-5 sm:p-6 text-left space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Número de Hoja Oficial</span>
                        <span class="font-mono text-base font-extrabold text-blue-700 bg-blue-50 border border-blue-200 px-3 py-1 rounded-xl">
                            {{ reclamacion.numero_hoja }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div>
                            <span class="text-slate-400 block font-medium">Proveedor:</span>
                            <span class="font-bold text-slate-800">{{ empresa.razon_social }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block font-medium">RUC Proveedor:</span>
                            <span class="font-bold text-slate-800 font-mono">{{ empresa.ruc }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block font-medium">Reclamante:</span>
                            <span class="font-bold text-slate-800">{{ reclamacion.consumidor_nombre }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block font-medium">Correo de Notificación:</span>
                            <span class="font-bold text-slate-800 font-mono">{{ reclamacion.consumidor_email }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block font-medium">Fecha y Hora de Ingreso:</span>
                            <span class="font-bold text-slate-800">{{ reclamacion.fecha_hora }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block font-medium">Plazo Límite de Atención:</span>
                            <span class="font-bold text-amber-700 font-semibold">{{ reclamacion.vence_en }} (15 días hábiles)</span>
                        </div>
                        <div v-if="reclamacion.adjuntos_count > 0" class="sm:col-span-2">
                            <span class="text-slate-400 block font-medium">Evidencias Adjuntas:</span>
                            <span class="font-bold text-emerald-700">{{ reclamacion.adjuntos_count }} archivo(s) anexado(s) exitosamente</span>
                        </div>
                    </div>

                    <!-- Código de Seguimiento Público Seguro -->
                    <div class="pt-3 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">
                                Código de Consulta Seguro:
                            </span>
                            <span class="font-mono text-xs font-semibold text-slate-700 break-all select-all">
                                {{ reclamacion.token_consulta }}
                            </span>
                        </div>
                        <button
                            type="button"
                            @click="copiarToken"
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-100 transition cursor-pointer"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <span>Copiar</span>
                        </button>
                    </div>
                </div>

                <!-- Botones Principales de Acción -->
                <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3.5">
                    <!-- Enlace directo para descarga de la constancia PDF -->
                    <a
                        :href="url_descarga_pdf"
                        download
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-full px-7 py-3 text-sm font-bold text-white shadow-lg shadow-blue-500/25 hover:opacity-95 transition cursor-pointer"
                        :style="{ backgroundColor: empresa.color_primario || '#2563EB' }"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>Descargar Constancia (PDF)</span>
                    </a>

                    <!-- Retornar a la portada del Libro -->
                    <Link
                        :href="`/l/${empresa.slug}`"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-full border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
                    >
                        <span>Volver al Libro de Reclamaciones</span>
                    </Link>
                </div>

                <!-- Aviso de Correo Transaccional -->
                <p class="mt-6 text-xs text-slate-400">
                    ✉️ Hemos despachado automáticamente un correo con tu constancia y código de seguimiento a <strong>{{ reclamacion.consumidor_email }}</strong>.
                </p>
            </div>
        </div>
    </div>
</template>

