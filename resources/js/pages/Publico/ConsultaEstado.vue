<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Props {
    empresa: {
        name: string;
        razon_social: string;
        ruc: string;
        color_primario: string;
        logo_path?: string | null;
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
        respuesta?: {
            decision: string;
            decision_label: string;
            propuesta_solucion: string;
            fundamento_legal: string | null;
            autor_nombre: string | null;
            autor_cargo: string | null;
            version: number;
            fecha_respuesta: string;
            adjuntos?: Array<any>;
        } | null;
        url_descarga_pdf: string;
    } | null;
    url_firmada: string | null;
}

const props = defineProps<Props>();
const logoError = ref(false);

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

    <div class="min-h-screen bg-[#F8FAFC] text-slate-800 antialiased font-sans selection:bg-blue-100 selection:text-blue-700 py-10 px-4 sm:px-6 lg:px-8 flex flex-col justify-between">
        <div class="max-w-3xl mx-auto w-full space-y-6">

            <!-- Barra Superior Institucional -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <Link
                    :href="`/l/${empresa.slug}`"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 hover:text-slate-900 transition self-start"
                >
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Volver al Libro de Reclamaciones</span>
                </Link>

                <div class="inline-flex items-center gap-2 rounded-xl border border-slate-200/80 bg-white px-3 py-1.5 shadow-2xs self-start sm:self-auto">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">RUC</span>
                    <span class="font-mono text-xs font-bold text-slate-800">{{ empresa.ruc }}</span>
                </div>
            </div>

            <!-- Cabecera de Página (Estilo Admin/Empresas) -->
            <div class="flex items-start gap-4">
                <div
                    v-if="empresa.logo_path && !logoError"
                    class="hidden sm:flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white border border-slate-200/80 p-1.5 shadow-md shadow-slate-200/50 overflow-hidden"
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
                    class="hidden sm:flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-white shadow-md shadow-blue-500/25 font-black text-sm"
                    :style="{ backgroundColor: empresa.color_primario || '#2563EB' }"
                >
                    {{ empresa.name.charAt(0).toUpperCase() }}
                </div>

                <div>
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        {{ empresa.razon_social }} · Libro de Reclamaciones
                    </div>
                    <h1 class="mt-0.5 text-2xl font-extrabold tracking-tight text-slate-900">
                        Consulta de Estado de Reclamación
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Ingresa el número de hoja y tu dato de verificación para consultar el avance oficial de tu expediente conforme a ley.
                    </p>
                </div>
            </div>

            <!-- Formulario de Consulta con Doble Factor de Verificación -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-7 shadow-2xs space-y-5">
                <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">
                            Verificación de Seguridad
                        </h2>
                        <p class="text-xs text-slate-500">
                            Por protección de datos personales (Ley N° 29733), solo quien acredite el documento o correo puede acceder al detalle.
                        </p>
                    </div>
                </div>

                <form @submit.prevent="consultar" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="codigo" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            N° de Hoja o Código de Seguimiento *
                        </label>
                        <input
                            id="codigo"
                            v-model="form.codigo"
                            type="text"
                            required
                            placeholder="ej. 00000001-2026 o código"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 font-mono text-xs font-semibold text-slate-800 placeholder:text-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            :class="{ 'border-rose-400': form.errors.codigo }"
                        />
                        <p v-if="form.errors.codigo" class="mt-1 text-[11px] font-semibold text-rose-600">
                            {{ form.errors.codigo }}
                        </p>
                    </div>

                    <div>
                        <label for="verificacion" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Verificación (N° Documento o Correo) *
                        </label>
                        <input
                            id="verificacion"
                            v-model="form.verificacion"
                            type="text"
                            required
                            placeholder="ej. DNI registrado o correo electrónico"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            :class="{ 'border-rose-400': form.errors.verificacion }"
                        />
                        <p v-if="form.errors.verificacion" class="mt-1 text-[11px] font-semibold text-rose-600">
                            {{ form.errors.verificacion }}
                        </p>
                    </div>

                    <div class="sm:col-span-2 pt-2 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <span class="text-[11px] text-slate-400">
                            🔒 Consulta segura aislada por comercio
                        </span>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-xs font-bold text-white shadow-md shadow-blue-500/20 hover:opacity-95 transition disabled:opacity-50 cursor-pointer"
                            :style="{ backgroundColor: empresa.color_primario || '#2563EB' }"
                        >
                            <span v-if="form.processing">Verificando...</span>
                            <span v-else>Consultar Estado</span>
                            <svg v-if="!form.processing" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Ficha del Expediente Encontrado (Detalle Protegido) -->
            <div v-if="expediente" class="rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-7 shadow-2xs space-y-6">
                <!-- Cabecera del expediente -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 pb-4 gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Expediente Oficial</span>
                            <h2 class="text-xl font-extrabold text-slate-900 font-mono tracking-tight">
                                Hoja N° {{ expediente.numero_hoja }}
                            </h2>
                        </div>
                    </div>

                    <!-- Estado Actual con Badge -->
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-400 font-medium">Estado:</span>
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wider ring-1"
                            :class="{
                                'bg-amber-50 text-amber-800 ring-amber-600/20': expediente.estado === 'pendiente',
                                'bg-blue-50 text-blue-800 ring-blue-600/20': expediente.estado === 'en_tramite',
                                'bg-emerald-50 text-emerald-800 ring-emerald-600/20': expediente.estado === 'atendida' || expediente.estado === 'cerrada',
                            }"
                        >
                            <span class="h-1.5 w-1.5 rounded-full" :class="{
                                'bg-amber-500': expediente.estado === 'pendiente',
                                'bg-blue-500': expediente.estado === 'en_tramite',
                                'bg-emerald-500': expediente.estado === 'atendida' || expediente.estado === 'cerrada',
                            }"></span>
                            {{ expediente.estado_label }}
                        </span>
                    </div>
                </div>

                <!-- Resumen de Datos Estilizado -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 rounded-xl border border-slate-100 bg-slate-50/70 p-4 text-xs">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">Tipo de Registro</span>
                        <span class="font-bold text-slate-800">{{ expediente.tipo_label }} ({{ expediente.tipo_bien }})</span>
                    </div>
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">Fecha y Hora de Ingreso</span>
                        <span class="font-semibold text-slate-800">{{ expediente.fecha_hora }}</span>
                    </div>
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">Consumidor Reclamante</span>
                        <span class="font-semibold text-slate-800">{{ expediente.consumidor_nombre }} ({{ expediente.consumidor_documento }})</span>
                    </div>
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">Correo Notificado</span>
                        <span class="font-semibold text-slate-800 font-mono">{{ expediente.consumidor_email }}</span>
                    </div>
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">Plazo Legal de Atención</span>
                        <span class="font-bold text-amber-700">Hasta el {{ expediente.vence_en }} (15 días hábiles)</span>
                    </div>
                    <div v-if="expediente.adjuntos_count > 0">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">Evidencias Adjuntas</span>
                        <span class="font-bold text-emerald-700">{{ expediente.adjuntos_count }} archivo(s)</span>
                    </div>
                </div>

                <!-- Detalle de Hechos y Pedido Concreto -->
                <div class="space-y-4">
                    <div>
                        <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Hechos Expuestos en el Registro Original
                        </h4>
                        <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 text-xs text-slate-800 whitespace-pre-wrap leading-relaxed">
                            {{ expediente.detalle_problema }}
                        </div>
                    </div>

                    <div>
                        <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Pedido Concreto Solicitado
                        </h4>
                        <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 text-xs text-slate-800 whitespace-pre-wrap leading-relaxed">
                            {{ expediente.pedido_consumidor }}
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN DE RESPUESTA FORMAL DEL PROVEEDOR (Si ya fue atendida) -->
                <div v-if="expediente.respuesta" class="rounded-2xl border border-emerald-200 bg-emerald-50/40 p-5 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-emerald-200/80 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-600 text-white shadow-xs">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                            </span>
                            <div>
                                <h3 class="text-xs font-extrabold uppercase tracking-wider text-emerald-900">
                                    Respuesta Formal de la Empresa
                                </h3>
                                <p class="text-[10px] text-emerald-700">
                                    Dictamen oficial emitido el {{ expediente.respuesta.fecha_respuesta }}
                                </p>
                            </div>
                        </div>

                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 border border-emerald-300 px-3 py-1 text-xs font-extrabold text-emerald-800">
                            {{ expediente.respuesta.decision_label }}
                        </span>
                    </div>

                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-800 block mb-1">
                            Propuesta de Solución / Descargo Oficial:
                        </span>
                        <div class="rounded-xl border border-emerald-200/80 bg-white p-4 text-xs text-slate-800 whitespace-pre-wrap leading-relaxed shadow-2xs">
                            {{ expediente.respuesta.propuesta_solucion }}
                        </div>
                    </div>

                    <div v-if="expediente.respuesta.fundamento_legal">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-800 block mb-1">
                            Fundamentación Legal / Técnica:
                        </span>
                        <div class="rounded-xl border border-emerald-200/80 bg-white p-4 text-xs text-slate-700 whitespace-pre-wrap leading-relaxed shadow-2xs">
                            {{ expediente.respuesta.fundamento_legal }}
                        </div>
                    </div>

                    <div v-if="expediente.respuesta.autor_nombre" class="text-[11px] text-emerald-700 pt-1">
                        Atendido por: <strong>{{ expediente.respuesta.autor_nombre }}</strong>
                        <span v-if="expediente.respuesta.autor_cargo"> ({{ expediente.respuesta.autor_cargo }})</span>
                    </div>
                </div>

                <!-- Alerta cuando aún no hay respuesta -->
                <div v-else class="rounded-xl border border-amber-200 bg-amber-50/60 p-4 text-xs text-amber-800 flex items-start gap-3">
                    <svg class="h-5 w-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <strong>Expediente en Proceso de Evaluación:</strong>
                        <p class="mt-0.5 text-amber-700">
                            La empresa cuenta con un plazo de hasta 15 días hábiles (hasta el {{ expediente.vence_en }}) para notificar la respuesta formal motivada a tu correo electrónico.
                        </p>
                    </div>
                </div>

                <!-- Descarga de Constancia Oficial -->
                <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <span class="text-xs text-slate-500">
                        Documento con validez legal según D.S. N° 011-2011-PCM.
                    </span>

                    <a
                        :href="expediente.url_descarga_pdf"
                        download
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-blue-500/20 hover:bg-blue-700 transition cursor-pointer"
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

        <!-- Pie de página formal -->
        <footer class="mt-12 text-center text-xs text-slate-400">
            Plataforma del Libro de Reclamaciones Virtual · Desarrollado por <span class="font-bold text-slate-600">Datec</span>
        </footer>
    </div>
</template>
