<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface ExpedienteDetalle {
    id: number;
    numero_hoja: string;
    fecha_hora: string;
    tipo: string;
    tipo_label: string;
    tipo_bien: string;
    tipo_bien_label: string;
    monto_reclamado: string | null;
    moneda: string;
    descripcion_bien: string;
    numero_comprobante: string | null;
    fecha_contratacion: string | null;
    detalle_problema: string | null;
    pedido_consumidor: string | null;
    datos_consumidor: Record<string, any>;
    datos_legales_snapshot: Record<string, any>;
    estado: string;
    estado_label: string;
    vence_en: string | null;
    token_consulta: string;
    aviso_privacidad_version: string | null;
    aviso_privacidad_aceptado_en: string | null;
    aviso_privacidad_ip: string | null;
    establecimiento: {
        id: number;
        codigo: string;
        nombre: string;
        direccion: string;
    } | null;
    responsable: {
        id: number;
        name: string;
        email: string;
    } | null;
    asignado_en: string | null;
    semaforo: {
        color: 'success' | 'amber' | 'warning' | 'danger' | 'neutral';
        etiqueta: string;
        dias_restantes: number;
        vencido: boolean;
    } | null;
    adjuntos: Array<{
        id: number;
        nombre_original: string;
        mime_type: string;
        tamano_kb: number;
        created_at: string;
        url_descarga: string;
    }>;
    eventos: Array<{
        id: number;
        accion: string;
        descripcion: string;
        actor_tipo: string;
        actor_nombre: string | null;
        created_at: string;
        ip: string | null;
    }>;
    envios: Array<{
        id: number;
        tipo: string;
        canal: string;
        destinatario: string;
        estado: string;
        enviado_at: string | null;
        intentado_at: string | null;
        intentos: number;
        mensaje_error: string | null;
    }>;
    respuestas: Array<{
        id: number;
        decision: string;
        decision_label: string;
        propuesta_solucion: string;
        fundamento_legal: string | null;
        autor_nombre: string;
        autor_cargo: string | null;
        version: number;
        fecha_respuesta: string;
        notificado: boolean;
        notificado_en: string | null;
        adjuntos: Array<any>;
    }>;
}

interface Props {
    expediente: ExpedienteDetalle;
    usuarios_asignables: Array<{
        id: number;
        name: string;
        email: string;
    }>;
    decisiones_disponibles: Array<{
        value: string;
        label: string;
    }>;
    estados_disponibles: Array<{
        value: string;
        label: string;
        es_actual: boolean;
        permitido: boolean;
    }>;
}

const props = defineProps<Props>();

const consumidor = computed(() => props.expediente.datos_consumidor || {});
const apoderado = computed(() => {
    if (consumidor.value.es_menor_edad) {
        return {
            nombre: consumidor.value.apoderado_nombre,
            tipo_documento: consumidor.value.apoderado_tipo_documento,
            numero_documento: consumidor.value.apoderado_numero_documento,
            telefono: consumidor.value.apoderado_telefono,
            email: consumidor.value.apoderado_email,
        };
    }
    return null;
});

// Modales interactivos para gestión de responsable, estados controlados y respuesta formal
const modalAsignarAbierto = ref(false);
const modalEstadoAbierto = ref(false);
const modalRespuestaAbierto = ref(false);

const formAsignar = useForm({
    usuario_id: props.expediente.responsable ? props.expediente.responsable.id : '',
    motivo: '',
});

const formEstado = useForm({
    estado: '',
    motivo: '',
});

const formRespuesta = useForm({
    decision: 'procedente',
    propuesta_solucion: '',
    fundamento_legal: '',
    autor_cargo: '',
    notificar_inmediatamente: true,
    adjuntos: [] as File[],
});

function abrirModalRespuesta() {
    formRespuesta.reset();
    formRespuesta.decision = 'procedente';
    formRespuesta.notificar_inmediatamente = true;
    modalRespuestaAbierto.value = true;
}

function handleAdjuntosRespuesta(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files) {
        formRespuesta.adjuntos = Array.from(input.files);
    }
}

function submitRespuesta() {
    formRespuesta.post(`/expedientes/${props.expediente.id}/respuestas`, {
        preserveScroll: true,
        onSuccess: () => {
            modalRespuestaAbierto.value = false;
            formRespuesta.reset();
        },
    });
}

function reenviarNotificacion(respuestaId: number) {
    useForm({}).post(`/expedientes/${props.expediente.id}/respuestas/${respuestaId}/reenviar`, {
        preserveScroll: true,
    });
}

function abrirModalAsignar() {
    formAsignar.usuario_id = props.expediente.responsable ? props.expediente.responsable.id : '';
    formAsignar.motivo = '';
    modalAsignarAbierto.value = true;
}

function submitAsignar() {
    formAsignar.patch(`/expedientes/${props.expediente.id}/asignar`, {
        preserveScroll: true,
        onSuccess: () => {
            modalAsignarAbierto.value = false;
        },
    });
}

function abrirModalEstado(nuevoEstadoValor?: string) {
    formEstado.estado = nuevoEstadoValor || '';
    formEstado.motivo = '';
    modalEstadoAbierto.value = true;
}

function submitEstado() {
    formEstado.patch(`/expedientes/${props.expediente.id}/estado`, {
        preserveScroll: true,
        onSuccess: () => {
            modalEstadoAbierto.value = false;
        },
    });
}

// Estilos de badge para estado
function obtenerClaseEstado(estado: string): string {
    switch (estado) {
        case 'pendiente':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'en_tramite':
            return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'atendida':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'cerrada':
            return 'bg-slate-100 text-slate-700 border-slate-200';
        default:
            return 'bg-slate-50 text-slate-600 border-slate-200';
    }
}

// Estilos de badge para semáforo
function obtenerClaseSemaforo(color: string | undefined): string {
    switch (color) {
        case 'danger':
            return 'bg-rose-50 text-rose-700 border-rose-200';
        case 'warning':
            return 'bg-orange-50 text-orange-700 border-orange-200';
        case 'amber':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'success':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        default:
            return 'bg-slate-50 text-slate-600 border-slate-200';
    }
}
</script>

<template>
    <AdminLayout>
        <Head :title="`Expediente ${expediente.numero_hoja} | DatecLibro`" />

        <div class="space-y-6">
            <!-- Barra Superior de Navegación y Acciones -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link
                        href="/expedientes"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 transition shadow-2xs"
                        title="Volver a la bandeja"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>

                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900">
                                Hoja N° {{ expediente.numero_hoja }}
                            </h1>
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-extrabold border"
                                :class="expediente.tipo === 'reclamo' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-purple-50 text-purple-700 border-purple-200'"
                            >
                                {{ expediente.tipo_label }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Fecha y hora oficial de presentación: <strong class="text-slate-700">{{ expediente.fecha_hora }}</strong> (Zona Horaria America/Lima)
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Botón Registrar Respuesta Formal (RF-030) -->
                    <button
                        type="button"
                        @click="abrirModalRespuesta()"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-md shadow-emerald-500/20 hover:bg-emerald-700 transition cursor-pointer"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span>Emitir Respuesta Formal</span>
                    </button>

                    <!-- Botón Gestionar Estado Controlado -->
                    <button
                        type="button"
                        @click="abrirModalEstado()"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white shadow-md shadow-blue-500/20 hover:bg-blue-700 transition cursor-pointer"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Gestionar Estado</span>
                    </button>

                    <!-- Botón Asignar Responsable -->
                    <button
                        type="button"
                        @click="abrirModalAsignar()"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 hover:text-slate-900 transition cursor-pointer"
                    >
                        <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        <span>{{ expediente.responsable ? 'Reasignar Responsable' : 'Asignar Responsable' }}</span>
                    </button>

                    <!-- Botón de Descarga de PDF de Constancia Oficial -->
                    <a
                        :href="`/expedientes/${expediente.id}/constancia-pdf`"
                        target="_blank"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 hover:text-slate-900 transition"
                    >
                        <svg class="h-4 w-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Descargar PDF</span>
                    </a>
                </div>
            </div>

            <!-- Fila de Estado, Plazos Legales y Responsable -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Estado Actual -->
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Estado del Expediente</div>
                        <button
                            type="button"
                            @click="abrirModalEstado()"
                            class="text-[11px] font-bold text-blue-600 hover:text-blue-800 transition cursor-pointer"
                        >
                            Cambiar
                        </button>
                    </div>
                    <div class="mt-2 flex items-center gap-2">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-extrabold border"
                            :class="obtenerClaseEstado(expediente.estado)"
                        >
                            <span class="h-2 w-2 rounded-full bg-current"></span>
                            {{ expediente.estado_label }}
                        </span>
                    </div>
                    <div class="mt-2 text-[11px] text-slate-500">
                        Los datos originales registrados por el consumidor son inmutables conforme a ley.
                    </div>
                </div>

                <!-- Semáforo Legal y Plazo INDECOPI -->
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Semáforo de Plazo Legal (15 días hábiles)</div>
                    <div class="mt-2 flex items-center gap-2">
                        <span
                            v-if="expediente.semaforo"
                            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold border"
                            :class="obtenerClaseSemaforo(expediente.semaforo.color)"
                        >
                            <span class="h-2 w-2 rounded-full bg-current"></span>
                            {{ expediente.semaforo.etiqueta }}
                        </span>
                    </div>
                    <div class="mt-2 text-[11px] text-slate-500">
                        Fecha límite de respuesta: <strong class="text-slate-700">{{ expediente.vence_en || 'Por determinar' }}</strong>
                    </div>
                </div>

                <!-- Usuario Responsable Asignado -->
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Responsable Asignado</div>
                        <button
                            type="button"
                            @click="abrirModalAsignar()"
                            class="text-[11px] font-bold text-blue-600 hover:text-blue-800 transition cursor-pointer"
                        >
                            {{ expediente.responsable ? 'Editar' : 'Asignar' }}
                        </button>
                    </div>
                    <div class="mt-2 flex items-center gap-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100 text-slate-600 font-bold text-xs">
                            {{ expediente.responsable ? expediente.responsable.name.charAt(0) : '—' }}
                        </div>
                        <div>
                            <div class="text-xs font-bold text-slate-800">
                                {{ expediente.responsable ? expediente.responsable.name : 'Sin asignar aún' }}
                            </div>
                            <div class="text-[11px] text-slate-400">
                                {{ expediente.responsable ? expediente.responsable.email : 'Pendiente de asignación' }}
                                <template v-if="expediente.asignado_en"> • Asignado el {{ expediente.asignado_en }}</template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenido Principal: Datos del Consumidor, Bien y Hechos (Solo Lectura) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Columna Izquierda: Información de Origen (2 cols) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Sección 1: Identificación del Consumidor Reclamante -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                                <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span>1. Identificación del Consumidor Reclamante</span>
                            </h2>
                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold text-slate-600">
                                Solo lectura
                            </span>
                        </div>

                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                            <div>
                                <span class="text-slate-400 block text-[11px]">Nombres y Apellidos:</span>
                                <span class="font-bold text-slate-900 text-sm">
                                    {{ consumidor.nombres }} {{ consumidor.apellidos }}
                                </span>
                            </div>

                            <div>
                                <span class="text-slate-400 block text-[11px]">Documento de Identidad:</span>
                                <span class="font-bold text-slate-900">
                                    {{ (consumidor.tipo_documento || 'DNI').toUpperCase() }}: {{ consumidor.numero_documento }}
                                </span>
                            </div>

                            <div>
                                <span class="text-slate-400 block text-[11px]">Correo Electrónico:</span>
                                <a :href="`mailto:${consumidor.email}`" class="font-semibold text-blue-600 hover:underline">
                                    {{ consumidor.email }}
                                </a>
                            </div>

                            <div>
                                <span class="text-slate-400 block text-[11px]">Teléfono de Contacto:</span>
                                <span class="font-semibold text-slate-800">
                                    {{ consumidor.telefono || 'No especificado' }}
                                </span>
                            </div>

                            <div class="sm:col-span-2">
                                <span class="text-slate-400 block text-[11px]">Domicilio declarado:</span>
                                <span class="font-medium text-slate-800">
                                    {{ consumidor.direccion }}
                                    <template v-if="consumidor.distrito">
                                        ({{ consumidor.distrito }}, {{ consumidor.provincia }}, {{ consumidor.departamento }})
                                    </template>
                                </span>
                            </div>
                        </div>

                        <!-- Bloque de Apoderado / Tutor si es menor de edad -->
                        <div v-if="apoderado" class="mt-4 rounded-xl border border-blue-100 bg-blue-50/50 p-3.5 text-xs">
                            <div class="font-bold text-blue-900 mb-1 flex items-center gap-1.5">
                                <span class="h-2 w-2 rounded-full bg-blue-600"></span>
                                Consumidor Menor de Edad - Datos del Padre, Madre o Tutor:
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-slate-700">
                                <div><strong>Nombre:</strong> {{ apoderado.nombre }}</div>
                                <div><strong>Documento:</strong> {{ (apoderado.tipo_documento || 'DNI').toUpperCase() }} {{ apoderado.numero_documento }}</div>
                                <div><strong>Teléfono:</strong> {{ apoderado.telefono || '—' }}</div>
                                <div><strong>Email:</strong> {{ apoderado.email || '—' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección 2: Identificación del Bien o Servicio Contratado -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                                <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                                <span>2. Identificación del Bien Contratado</span>
                            </h2>
                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold text-slate-600">
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

                    <!-- Sección 3: Detalle de la Reclamación y Pedido del Consumidor -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs space-y-4">
                        <div class="border-b border-slate-100 pb-3">
                            <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                                <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span>3. Detalle de los Hechos y Pedido Concreto</span>
                            </h2>
                        </div>

                        <div>
                            <span class="text-slate-400 block text-[11px] uppercase font-bold mb-1">
                                Detalle del {{ expediente.tipo_label }}:
                            </span>
                            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 text-xs text-slate-800 whitespace-pre-wrap leading-relaxed">
                                {{ expediente.detalle_problema }}
                            </div>
                        </div>

                        <div>
                            <span class="text-slate-400 block text-[11px] uppercase font-bold mb-1">
                                Pedido del Consumidor:
                            </span>
                            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 text-xs text-slate-800 whitespace-pre-wrap leading-relaxed">
                                {{ expediente.pedido_consumidor }}
                            </div>
                        </div>
                    </div>

                    <!-- Respuestas Formales Emitidas por la Empresa  -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                                <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Respuestas Formales del Proveedor ({{ expediente.respuestas.length }})</span>
                            </h2>
                            <button
                                type="button"
                                @click="abrirModalRespuesta()"
                                class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 hover:bg-emerald-100 transition cursor-pointer"
                            >
                                + Nueva Respuesta
                            </button>
                        </div>

                        <div v-if="expediente.respuestas.length > 0" class="space-y-4">
                            <div
                                v-for="resp in expediente.respuestas"
                                :key="resp.id"
                                class="rounded-2xl border border-slate-200 bg-slate-50/60 p-5 space-y-3"
                            >
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-200/60 pb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-bold text-blue-700">
                                            Versión {{ resp.version }}
                                        </span>
                                        <span class="rounded-full bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 text-xs font-bold text-emerald-800">
                                            {{ resp.decision_label }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-2 text-xs text-slate-500">
                                        <span>Fecha: <strong>{{ resp.fecha_respuesta }}</strong></span>
                                        <span>•</span>
                                        <span>Autor: <strong>{{ resp.autor_nombre }}</strong> {{ resp.autor_cargo ? `(${resp.autor_cargo})` : '' }}</span>
                                    </div>
                                </div>

                                <div>
                                    <span class="text-slate-400 block text-[11px] uppercase font-bold mb-1">
                                        Propuesta de Solución / Respuesta Motivada:
                                    </span>
                                    <div class="rounded-xl bg-white border border-slate-200 p-3.5 text-xs text-slate-800 whitespace-pre-wrap leading-relaxed">
                                        {{ resp.propuesta_solucion }}
                                    </div>
                                </div>

                                <div v-if="resp.fundamento_legal">
                                    <span class="text-slate-400 block text-[11px] uppercase font-bold mb-1">
                                        Fundamentación Legal / Técnica:
                                    </span>
                                    <div class="rounded-xl bg-white border border-slate-200 p-3.5 text-xs text-slate-700 whitespace-pre-wrap leading-relaxed">
                                        {{ resp.fundamento_legal }}
                                    </div>
                                </div>

                                <!-- Archivos adjuntos de la respuesta -->
                                <div v-if="resp.adjuntos && resp.adjuntos.length > 0">
                                    <span class="text-slate-400 block text-[11px] uppercase font-bold mb-1">
                                        Documentos Probatorios Anexados ({{ resp.adjuntos.length }}):
                                    </span>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <div
                                            v-for="(adj, adjIndex) in resp.adjuntos"
                                            :key="adjIndex"
                                            class="flex items-center gap-2 rounded-lg bg-white border border-slate-200 p-2 text-xs text-slate-700"
                                        >
                                            <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                            </svg>
                                            <span class="truncate font-medium">{{ adj.nombre }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Estado de Notificación Formal y Reenvío -->
                                <div class="pt-2 border-t border-slate-200/60 flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold"
                                            :class="resp.notificado ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                            {{ resp.notificado ? `Notificado el ${resp.notificado_en}` : 'Notificación en cola / pendiente' }}
                                        </span>
                                    </div>

                                    <button
                                        type="button"
                                        @click="reenviarNotificacion(resp.id)"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-800 transition cursor-pointer"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        <span>Reenviar Notificación</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-xs text-slate-400 italic py-2">
                            Aún no se ha emitido una respuesta formal para esta reclamación.
                        </div>
                    </div>

                    <!-- Archivos y Evidencias Adjuntas por el Consumidor -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                                <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                </svg>
                                <span>Evidencias y Archivos Adjuntos ({{ expediente.adjuntos.length }})</span>
                            </h2>
                        </div>

                        <div v-if="expediente.adjuntos.length > 0" class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div
                                v-for="adj in expediente.adjuntos"
                                :key="adj.id"
                                class="flex items-center justify-between rounded-xl border border-slate-200 p-3 hover:bg-slate-50 transition"
                            >
                                <div class="min-w-0 flex items-center gap-2.5">
                                    <svg class="h-5 w-5 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                    <div class="min-w-0">
                                        <div class="text-xs font-bold text-slate-800 truncate" :title="adj.nombre_original">
                                            {{ adj.nombre_original }}
                                        </div>
                                        <div class="text-[10px] text-slate-400">
                                            {{ adj.tamano_kb }} KB • {{ adj.created_at }}
                                        </div>
                                    </div>
                                </div>

                                <a
                                    :href="adj.url_descarga"
                                    class="shrink-0 rounded-lg border border-slate-200 bg-white p-2 text-slate-600 hover:bg-blue-50 hover:text-blue-600 transition"
                                    title="Descargar archivo adjunto"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                </a>
                            </div>
                        </div>

                        <div v-else class="mt-4 text-xs text-slate-400 italic">
                            El consumidor no adjuntó archivos o comprobantes adicionales a este reclamo.
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Trazabilidad, Bitácora de Eventos y Auditoría -->
                <div class="space-y-6">
                    <!-- Ficha Legal del Proveedor al Momento del Registro -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs text-xs space-y-2">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 pb-2">
                            Snapshot Legal del Proveedor
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px]">Razón Social:</span>
                            <span class="font-bold text-slate-800">{{ expediente.datos_legales_snapshot?.razon_social || '—' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px]">RUC:</span>
                            <span class="font-bold text-slate-800">{{ expediente.datos_legales_snapshot?.ruc || '—' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px]">Dirección Fiscal:</span>
                            <span class="font-medium text-slate-700">{{ expediente.datos_legales_snapshot?.direccion || '—' }}</span>
                        </div>
                        <div class="pt-2 border-t border-slate-100 text-[10px] text-slate-400">
                            Aviso de Privacidad v{{ expediente.aviso_privacidad_version || '1.0' }} aceptado el {{ expediente.aviso_privacidad_aceptado_en }} desde IP {{ expediente.aviso_privacidad_ip }}.
                        </div>
                    </div>

                    <!-- Envíos y Notificaciones Formales -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs space-y-3">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 pb-2 flex items-center justify-between">
                            <span>Comunicaciones al Consumidor</span>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600">
                                {{ expediente.envios.length }}
                            </span>
                        </div>

                        <div v-if="expediente.envios.length > 0" class="space-y-2">
                            <div
                                v-for="env in expediente.envios"
                                :key="env.id"
                                class="rounded-xl border border-slate-100 bg-slate-50/50 p-2.5 text-xs"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-slate-800 uppercase text-[10px]">{{ env.canal }}</span>
                                    <span
                                        class="rounded-full px-2 py-0.5 text-[9px] font-bold"
                                        :class="env.estado === 'enviado' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'"
                                    >
                                        {{ env.estado }}
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-600 truncate mt-1">
                                    {{ env.destinatario }}
                                </div>
                                <div class="text-[10px] text-slate-400 mt-0.5">
                                    {{ env.enviado_at || env.intentado_at || 'Sin fecha registrada' }}
                                    <span v-if="env.intentos > 0" class="ml-1">· {{ env.intentos }} intento(s)</span>
                                </div>
                                <div
                                    v-if="env.mensaje_error"
                                    class="mt-1 rounded-lg bg-rose-50 px-2 py-1 text-[10px] text-rose-700"
                                >
                                    {{ env.mensaje_error }}
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-xs text-slate-400 italic">
                            Aún no se han enviado comunicaciones registradas.
                        </div>
                    </div>

                    <!-- Bitácora Inmutable de Eventos / Auditoría -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs space-y-3">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 pb-2">
                            Bitácora de Eventos y Auditoría
                        </div>

                        <div class="flow-root">
                            <ul role="list" class="-mb-6">
                                <li
                                    v-for="(ev, evIdx) in expediente.eventos"
                                    :key="ev.id"
                                    class="relative pb-6"
                                >
                                    <span
                                        v-if="evIdx !== expediente.eventos.length - 1"
                                        class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-slate-200"
                                        aria-hidden="true"
                                    ></span>
                                    <div class="relative flex space-x-3 items-start">
                                        <div>
                                            <span class="h-8 w-8 rounded-full bg-blue-50 border border-blue-200 flex items-center justify-center text-blue-600 text-xs shadow-2xs">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="min-w-0 flex-1 pt-0.5">
                                            <div class="text-xs font-bold text-slate-800">
                                                {{ ev.descripcion }}
                                            </div>
                                            <div class="text-[10px] text-slate-400 mt-0.5">
                                                {{ ev.created_at }} • {{ ev.actor_nombre || ev.actor_tipo }}
                                                <template v-if="ev.ip">• IP: {{ ev.ip }}</template>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: ASIGNAR RESPONSABLE -->
        <div
            v-if="modalAsignarAbierto"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-slate-200 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-base font-extrabold text-slate-900">
                        Asignar Responsable al Expediente
                    </h3>
                    <button
                        type="button"
                        @click="modalAsignarAbierto = false"
                        class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitAsignar" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">
                            Seleccionar Usuario de la Empresa
                        </label>
                        <select
                            v-model="formAsignar.usuario_id"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-800 focus:border-blue-500 focus:bg-white focus:outline-none"
                        >
                            <option value="">-- Sin asignar (Dejar libre) --</option>
                            <option
                                v-for="user in usuarios_asignables"
                                :key="user.id"
                                :value="user.id"
                            >
                                {{ user.name }} ({{ user.email }})
                            </option>
                        </select>
                        <p v-if="formAsignar.errors.usuario_id" class="text-rose-600 mt-1">
                            {{ formAsignar.errors.usuario_id }}
                        </p>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">
                            Motivo o Nota de Asignación (Opcional)
                        </label>
                        <textarea
                            v-model="formAsignar.motivo"
                            rows="2"
                            placeholder="Ej: Derivado al área de soporte técnico para peritaje de garantía..."
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs font-medium text-slate-800 focus:border-blue-500 focus:bg-white focus:outline-none"
                        ></textarea>
                        <p v-if="formAsignar.errors.motivo" class="text-rose-600 mt-1">
                            {{ formAsignar.errors.motivo }}
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="modalAsignarAbierto = false"
                            class="rounded-xl px-4 py-2 font-bold text-slate-600 hover:bg-slate-100 transition"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="formAsignar.processing"
                            class="rounded-xl bg-blue-600 px-4 py-2 font-bold text-white shadow-md shadow-blue-500/20 hover:bg-blue-700 disabled:opacity-50 transition cursor-pointer"
                        >
                            {{ formAsignar.processing ? 'Guardando...' : 'Confirmar Asignación' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: GESTIONAR ESTADO CONTROLADO -->
        <div
            v-if="modalEstadoAbierto"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-slate-200 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">
                            Gestionar Estado del Expediente
                        </h3>
                        <p class="text-[11px] text-slate-500">
                            Estado actual: <strong class="text-slate-800">{{ expediente.estado_label }}</strong>
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="modalEstadoAbierto = false"
                        class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitEstado" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">
                            Seleccionar Nuevo Estado <span class="text-rose-500">*</span>
                        </label>
                        <select
                            v-model="formEstado.estado"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-800 focus:border-blue-500 focus:bg-white focus:outline-none"
                            required
                        >
                            <option value="">-- Selecciona el nuevo estado --</option>
                            <option
                                v-for="est in estados_disponibles"
                                :key="est.value"
                                :value="est.value"
                                :disabled="est.es_actual"
                            >
                                {{ est.label }} {{ est.es_actual ? '(Actual)' : (!est.permitido ? '(Transición no permitida)' : '') }}
                            </option>
                        </select>
                        <p v-if="formEstado.errors.estado" class="text-rose-600 mt-1">
                            {{ formEstado.errors.estado }}
                        </p>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">
                            Motivo o Sustento del Cambio <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            v-model="formEstado.motivo"
                            rows="3"
                            placeholder="Describe el motivo de la transición para el registro inmutable de auditoría..."
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs font-medium text-slate-800 focus:border-blue-500 focus:bg-white focus:outline-none"
                            required
                        ></textarea>
                        <p v-if="formEstado.errors.motivo" class="text-rose-600 mt-1">
                            {{ formEstado.errors.motivo }}
                        </p>
                    </div>

                    <div class="rounded-xl bg-amber-50 border border-amber-200 p-3 text-[11px] text-amber-800 leading-relaxed">
                        <strong>Aviso de Auditoría:</strong> El cambio quedará registrado en la bitácora con tu nombre, fecha, hora e IP. La fecha de presentación original y las pruebas del consumidor se conservan intactas.
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="modalEstadoAbierto = false"
                            class="rounded-xl px-4 py-2 font-bold text-slate-600 hover:bg-slate-100 transition"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="formEstado.processing"
                            class="rounded-xl bg-blue-600 px-4 py-2 font-bold text-white shadow-md shadow-blue-500/20 hover:bg-blue-700 disabled:opacity-50 transition cursor-pointer"
                        >
                            {{ formEstado.processing ? 'Actualizando...' : 'Guardar Transición' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: REGISTRAR RESPUESTA FORMAL  -->
        <div
            v-if="modalRespuestaAbierto"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4 overflow-y-auto"
        >
            <div class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-xl border border-slate-200 space-y-4 my-8">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">
                            Emitir Respuesta Formal de Reclamación
                        </h3>
                        <p class="text-[11px] text-slate-500">
                            Expediente: <strong class="text-slate-800">{{ expediente.numero_hoja }}</strong> • Consumidor: {{ consumidor.nombres }} {{ consumidor.apellidos }}
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="modalRespuestaAbierto = false"
                        class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitRespuesta" class="space-y-4 text-xs">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">
                                Decisión / Sentido de la Respuesta <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="formRespuesta.decision"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-800 focus:border-blue-500 focus:bg-white focus:outline-none"
                                required
                            >
                                <option
                                    v-for="d in decisiones_disponibles"
                                    :key="d.value"
                                    :value="d.value"
                                >
                                    {{ d.label }}
                                </option>
                            </select>
                            <p v-if="formRespuesta.errors.decision" class="text-rose-600 mt-1">
                                {{ formRespuesta.errors.decision }}
                            </p>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">
                                Cargo del Autor / Firmante
                            </label>
                            <input
                                v-model="formRespuesta.autor_cargo"
                                type="text"
                                placeholder="Ej: Jefe de Experiencia y Garantías"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-800 focus:border-blue-500 focus:bg-white focus:outline-none"
                            />
                            <p v-if="formRespuesta.errors.autor_cargo" class="text-rose-600 mt-1">
                                {{ formRespuesta.errors.autor_cargo }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">
                            Propuesta de Solución y Respuesta Motivada <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            v-model="formRespuesta.propuesta_solucion"
                            rows="4"
                            placeholder="Redacta la respuesta formal detallando la solución, acciones tomadas o propuesta al consumidor..."
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs font-medium text-slate-800 focus:border-blue-500 focus:bg-white focus:outline-none leading-relaxed"
                            required
                        ></textarea>
                        <p v-if="formRespuesta.errors.propuesta_solucion" class="text-rose-600 mt-1">
                            {{ formRespuesta.errors.propuesta_solucion }}
                        </p>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">
                            Fundamentación Legal / Técnica (Opcional)
                        </label>
                        <textarea
                            v-model="formRespuesta.fundamento_legal"
                            rows="2"
                            placeholder="Cláusulas contractuales, informe técnico, políticas de garantía o normativa aplicable..."
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs font-medium text-slate-800 focus:border-blue-500 focus:bg-white focus:outline-none leading-relaxed"
                        ></textarea>
                        <p v-if="formRespuesta.errors.fundamento_legal" class="text-rose-600 mt-1">
                            {{ formRespuesta.errors.fundamento_legal }}
                        </p>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">
                            Documentos Probatorios Anexos (PDF, JPG, PNG - Máx. 5MB)
                        </label>
                        <input
                            type="file"
                            multiple
                            accept=".pdf,image/png,image/jpeg,image/webp"
                            @change="handleAdjuntosRespuesta"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"
                        />
                        <p v-if="formRespuesta.errors.adjuntos" class="text-rose-600 mt-1">
                            {{ formRespuesta.errors.adjuntos }}
                        </p>
                    </div>

                    <div class="rounded-xl bg-slate-50 border border-slate-200 p-3 flex items-start gap-2.5">
                        <input
                            v-model="formRespuesta.notificar_inmediatamente"
                            id="chk-notificar"
                            type="checkbox"
                            class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        />
                        <label for="chk-notificar" class="text-xs text-slate-700 cursor-pointer select-none">
                            <strong>Notificar inmediatamente al correo del consumidor</strong> ({{ consumidor.email }}). Se adjuntarán los documentos probatorios y se registrará la evidencia de entrega con reintentos automáticos.
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="modalRespuestaAbierto = false"
                            class="rounded-xl px-4 py-2 font-bold text-slate-600 hover:bg-slate-100 transition"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="formRespuesta.processing"
                            class="rounded-xl bg-emerald-600 px-5 py-2 font-bold text-white shadow-md shadow-emerald-500/20 hover:bg-emerald-700 disabled:opacity-50 transition cursor-pointer"
                        >
                            {{ formRespuesta.processing ? 'Registrando...' : 'Emitir y Guardar Respuesta' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
