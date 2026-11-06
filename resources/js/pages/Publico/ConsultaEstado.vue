<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Props {
    empresa: {
        name: string;
        razon_social: string;
        ruc: string;
        color_primario: string;
        slug: string;
    };
    expediente: {
        numero_hoja: string;
        tipo: string;
        tipo_label: string;
        tipo_bien: string;
        descripcion_bien: string;
        monto_reclamado: string | null;
        estado: string;
        estado_label: string;
        fecha_hora: string;
        vence_en: string;
        consumidor_nombre: string;
        consumidor_email: string;
        consumidor_documento: string;
        detalle_problema: string;
        pedido_consumidor: string;
        token_consulta: string;
        adjuntos_count: number;
        url_descarga_pdf: string;
    } | null;
    url_firmada: string | null;
}

const props = defineProps<Props>();

const form = useForm({
    codigo: '',
    verificacion: '',
});

function consultar() {
    form.post(`/l/${props.empresa.slug}/consultar`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`Consultar Estado de Reclamación | ${empresa.name}`" />

    <div class="min-h-screen bg-[#FBFDFF] text-slate-800 antialiased font-sans selection:bg-blue-100 selection:text-blue-700 py-10 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
        <!-- Luces de fondo decorativas -->
        <div class="absolute -top-32 -right-32 h-96 w-96 rounded-full bg-blue-100/50 blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-32 -left-32 h-96 w-96 rounded-full bg-indigo-100/40 blur-3xl pointer-events-none"></div>

        <div class="max-w-3xl mx-auto relative z-10 space-y-6">
            
            <!-- Barra superior institucional -->
            <div class="flex items-center justify-between">
                <Link :href="`/l/${empresa.slug}`" class="inline-flex items-center gap-2 text-xs font-bold text-slate-600 hover:text-slate-900 transition">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Volver al Libro de Reclamaciones</span>
                </Link>

                <span class="text-xs font-mono text-slate-400 bg-white border border-slate-200 px-2.5 py-1 rounded-full shadow-2xs">
                    RUC {{ empresa.ruc }}
                </span>
            </div>

            <!-- Formulario de Consulta con Doble Factor de Verificación -->
            <div class="rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-xl shadow-slate-200/50">
                <div class="flex items-center gap-3 border-b border-slate-100 pb-4 mb-6">
                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">
                            Consulta Segura de Expediente
                        </h1>
                        <p class="text-xs text-slate-500">
                            {{ empresa.razon_social }} • Libro de Reclamaciones Oficial
                        </p>
                    </div>
                </div>

                <form @submit.prevent="consultar" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="codigo" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                            N° de Hoja o Código de Seguimiento *
                        </label>
                        <input
                            id="codigo"
                            v-model="form.codigo"
                            type="text"
                            required
                            placeholder="ej. 00000001-2026 o código"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-mono text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            :class="{ 'border-rose-400': form.errors.codigo }"
                        />
                        <p v-if="form.errors.codigo" class="mt-1 text-xs text-rose-600 font-medium">
                            {{ form.errors.codigo }}
                        </p>
                    </div>

                    <div>
                        <label for="verificacion" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Verificación (N° Documento o Correo) *
                        </label>
                        <input
                            id="verificacion"
                            v-model="form.verificacion"
                            type="text"
                            required
                            placeholder="ej. DNI registrado o correo electrónico"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            :class="{ 'border-rose-400': form.errors.verificacion }"
                        />
                        <p v-if="form.errors.verificacion" class="mt-1 text-xs text-rose-600 font-medium">
                            {{ form.errors.verificacion }}
                        </p>
                    </div>

                    <div class="sm:col-span-2 pt-2 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <span class="text-[11px] text-slate-400">
                            🔒 Por protección de datos personales (Ley N° 29733), solo quien acredite el documento o correo puede ver el contenido.
                        </span>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-full px-6 py-2.5 text-xs sm:text-sm font-bold text-white shadow-md shadow-blue-500/25 hover:opacity-95 transition disabled:opacity-50 cursor-pointer"
                            :style="{ backgroundColor: empresa.color_primario || '#2563EB' }"
                        >
                            <span v-if="form.processing">Verificando...</span>
                            <span v-else>Consultar Estado</span>
                            <svg v-if="!form.processing" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Ficha del Expediente Encontrado (Detalle de Estado Protegido) -->
            <div v-if="expediente" class="rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-xl shadow-slate-200/50 space-y-6 animate-fade-in">
                
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 pb-4 gap-3">
                    <div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Expediente Oficial</span>
                        <h2 class="text-2xl font-extrabold text-slate-900 font-mono tracking-tight">
                            Hoja N° {{ expediente.numero_hoja }}
                        </h2>
                    </div>

                    <!-- Estado Actual con Badge -->
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-400 font-medium">Estado:</span>
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1 text-xs font-bold uppercase tracking-wider"
                            :class="{
                                'bg-amber-50 text-amber-800 border border-amber-200': expediente.estado === 'pendiente',
                                'bg-blue-50 text-blue-800 border border-blue-200': expediente.estado === 'en_tramite',
                                'bg-emerald-50 text-emerald-800 border border-emerald-200': expediente.estado === 'atendida' || expediente.estado === 'cerrada',
                            }"
                        >
                            <span class="h-2 w-2 rounded-full" :class="{
                                'bg-amber-500 animate-pulse': expediente.estado === 'pendiente',
                                'bg-blue-500': expediente.estado === 'en_tramite',
                                'bg-emerald-500': expediente.estado === 'atendida' || expediente.estado === 'cerrada',
                            }"></span>
                            {{ expediente.estado_label }}
                        </span>
                    </div>
                </div>

                <!-- Resumen de Datos -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs bg-slate-50/70 rounded-2xl p-4 border border-slate-200/70">
                    <div>
                        <span class="text-slate-400 block font-medium">Tipo:</span>
                        <span class="font-bold text-slate-800">{{ expediente.tipo_label }} ({{ expediente.tipo_bien }})</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-medium">Fecha y Hora de Ingreso:</span>
                        <span class="font-bold text-slate-800">{{ expediente.fecha_hora }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-medium">Reclamante:</span>
                        <span class="font-bold text-slate-800">{{ expediente.consumidor_nombre }} ({{ expediente.consumidor_documento }})</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-medium">Correo Registrado:</span>
                        <span class="font-bold text-slate-800 font-mono">{{ expediente.consumidor_email }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-medium">Plazo Legal de Atención:</span>
                        <span class="font-bold text-amber-700">Hasta el {{ expediente.vence_en }} (15 días hábiles)</span>
                    </div>
                    <div v-if="expediente.adjuntos_count > 0">
                        <span class="text-slate-400 block font-medium">Evidencias Anexadas:</span>
                        <span class="font-bold text-emerald-700">{{ expediente.adjuntos_count }} archivo(s)</span>
                    </div>
                </div>

                <!-- Detalle de Hechos y Pedido Concreto -->
                <div class="space-y-4">
                    <div>
                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Hechos Expuestos en el Registro Original:
                        </h4>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5 text-xs text-slate-800 whitespace-pre-wrap leading-relaxed">
                            {{ expediente.detalle_problema }}
                        </div>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Pedido Concreto Solicitado:
                        </h4>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5 text-xs text-slate-800 whitespace-pre-wrap leading-relaxed">
                            {{ expediente.pedido_consumidor }}
                        </div>
                    </div>
                </div>

                <!-- Acciones y Descarga de Constancia Oficial -->
                <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <span class="text-xs text-slate-500">
                        Documento con validez legal según D.S. N° 011-2011-PCM.
                    </span>

                    <a
                        :href="expediente.url_descarga_pdf"
                        download
                        class="inline-flex items-center gap-2 rounded-full px-5 py-2.5 text-xs font-bold text-white shadow-md shadow-blue-500/20 hover:opacity-95 transition cursor-pointer"
                        :style="{ backgroundColor: empresa.color_primario || '#2563EB' }"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>Descargar Constancia Oficial (PDF)</span>
                    </a>
                </div>
            </div>

        </div>
    </div>
</template>

