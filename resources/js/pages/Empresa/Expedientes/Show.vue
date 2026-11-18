<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import ExpedienteConsumidorCard from './Partials/ExpedienteConsumidorCard.vue';
import ExpedienteBienCard from './Partials/ExpedienteBienCard.vue';
import ExpedienteDetalleCard from './Partials/ExpedienteDetalleCard.vue';
import ExpedienteRespuestasCard from './Partials/ExpedienteRespuestasCard.vue';
import ExpedienteAdjuntosCard from './Partials/ExpedienteAdjuntosCard.vue';
import ExpedienteTimelineCard from './Partials/ExpedienteTimelineCard.vue';
import ModalAsignarResponsable from './Partials/ModalAsignarResponsable.vue';
import ModalGestionarEstado from './Partials/ModalGestionarEstado.vue';
import ModalEmitirRespuesta from './Partials/ModalEmitirRespuesta.vue';

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
        tamano_humano: string;
        created_at: string;
        url_descarga: string;
        es_imagen: boolean;
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

// Control de modales
const modalAsignarAbierto = ref(false);
const modalEstadoAbierto = ref(false);
const modalRespuestaAbierto = ref(false);

function abrirModalRespuesta() {
    modalRespuestaAbierto.value = true;
}

function reenviarNotificacion(respuestaId: number) {
    useForm({}).post(`/expedientes/${props.expediente.id}/respuestas/${respuestaId}/reenviar`, {
        preserveScroll: true,
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

// Flag modo e-commerce / admin único
const HABILITAR_ASIGNACION_RESPONSABLE = false;
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
                            Fecha y hora oficial de presentación: <strong class="text-slate-700">{{ expediente.fecha_hora }}</strong> 
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
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

                    <button
                        type="button"
                        @click="modalEstadoAbierto = true"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 hover:text-slate-900 transition cursor-pointer"
                    >
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Cambiar Estado</span>
                    </button>

                    <a
                        :href="`/expedientes/${expediente.id}/descargar-pdf`"
                        target="_blank"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 hover:text-slate-900 transition"
                    >
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Descargar PDF</span>
                    </a>
                </div>
            </div>

            <!-- Resumen de Indicadores Clave -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Estado Actual -->
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Estado del Expediente</div>
                        <button
                            type="button"
                            @click="modalEstadoAbierto = true"
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
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Semáforo Legal (15 días hábiles)</div>
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

                <!-- Canal / Sede de Atención -->
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Canal / Establecimiento</div>
                        <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-bold text-blue-700">
                            {{ expediente.establecimiento ? 'Sede Física' : 'Online' }}
                        </span>
                    </div>
                    <div class="mt-2 flex items-center gap-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-50 text-blue-600 font-bold text-xs">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-slate-800 truncate">
                                {{ expediente.establecimiento ? expediente.establecimiento.nombre : 'Tienda Virtual / Web' }}
                            </div>
                            <div class="text-[11px] text-slate-400 truncate">
                                {{ expediente.establecimiento?.direccion || 'Venta no presencial (E-commerce)' }}
                            </div>
                        </div>
                    </div>
                    <div class="mt-2 text-[11px] text-slate-500">
                        Canal oficial registrado por el consumidor al presentar su reclamación.
                    </div>
                </div>
            </div>

            <!-- Cuerpo Principal: Bloque Unificado de Información -->
            <div class="space-y-6">
                <!-- Bloque Unificado de Información del Consumidor -->
                <div class="rounded-3xl border border-slate-200/90 bg-white shadow-xs overflow-hidden">
                    <div class="bg-gradient-to-r from-slate-50 via-blue-50/30 to-slate-50 px-6 py-4 border-b border-slate-200/80 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-600 text-white shadow-xs">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xs sm:text-sm font-extrabold uppercase tracking-wider text-slate-800">
                                    Información Presentada por el Consumidor
                                </h2>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-6">
                        <!-- 1. Consumidor -->
                        <ExpedienteConsumidorCard
                            :consumidor="consumidor"
                            :apoderado="apoderado"
                        />

                        <!-- 2. Bien Contratado -->
                        <ExpedienteBienCard
                            :expediente="expediente"
                        />

                        <!-- 3. Detalle de los Hechos -->
                        <ExpedienteDetalleCard
                            :expediente="expediente"
                        />
                    </div>
                </div>

                <!-- 4. Respuestas Formales -->
                <ExpedienteRespuestasCard
                    :respuestas="expediente.respuestas"
                    @abrir-modal-respuesta="abrirModalRespuesta"
                    @reenviar-notificacion="reenviarNotificacion"
                />

                <!-- 5. Archivos Adjuntos -->
                <ExpedienteAdjuntosCard
                    :adjuntos="expediente.adjuntos"
                />

                <!-- 6. Timeline de Eventos -->
                <ExpedienteTimelineCard
                    :eventos="expediente.eventos"
                />
            </div>
        </div>

        <!-- Modales extraídos -->
        <ModalAsignarResponsable
            v-if="HABILITAR_ASIGNACION_RESPONSABLE"
            :abierto="modalAsignarAbierto"
            :expediente-id="expediente.id"
            :numero-hoja="expediente.numero_hoja"
            :responsable-actual-id="expediente.responsable?.id"
            :usuarios-asignables="usuarios_asignables"
            @cerrar="modalAsignarAbierto = false"
        />

        <ModalGestionarEstado
            :abierto="modalEstadoAbierto"
            :expediente-id="expediente.id"
            :numero-hoja="expediente.numero_hoja"
            :estado-actual-label="expediente.estado_label"
            :estados-disponibles="estados_disponibles"
            @cerrar="modalEstadoAbierto = false"
        />

        <ModalEmitirRespuesta
            :abierto="modalRespuestaAbierto"
            :expediente-id="expediente.id"
            :numero-hoja="expediente.numero_hoja"
            :consumidor-nombre="`${consumidor.nombres || ''} ${consumidor.apellidos || ''}`.trim() || 'Consumidor'"
            :consumidor-email="consumidor.email"
            :decisiones-disponibles="decisiones_disponibles"
            @cerrar="modalRespuestaAbierto = false"
        />
    </AdminLayout>
</template>
