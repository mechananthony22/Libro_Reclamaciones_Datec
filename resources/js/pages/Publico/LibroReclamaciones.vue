<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

// Definición de tipos para establecimientos comerciales asociados al proveedor
interface Establecimiento {
    id: number;
    codigo: string;
    nombre: string;
    direccion: string;
    es_principal: boolean;
}

// Catálogo de tipos de documento de identidad admitidos
interface TipoDocumento {
    value: string;
    label: string;
    longitud: number | null;
}

// Opciones de tipo de bien contratado (Producto o Servicio)
interface TipoBienItem {
    value: string;
    label: string;
    descripcion: string;
}

// Opciones de clasificación formal (Reclamo o Queja)
interface TipoReclamacionItem {
    value: string;
    label: string;
    descripcion: string;
}

// Propiedades recibidas desde el controlador de Laravel
interface Props {
    empresa: {
        name: string;
        razon_social: string;
        ruc: string;
        direccion: string;
        email: string | null;
        telefono: string | null;
        logo_path: string | null;
        color_primario: string;
        slug: string;
    };
    establecimientos: Establecimiento[];
    tipos_documento: TipoDocumento[];
    tipos_bien: TipoBienItem[];
    tipos_reclamacion?: TipoReclamacionItem[];
    aviso_privacidad_version?: string;
    token_seguridad?: string;
}

const props = defineProps<Props>();
const page = usePage();

// Control de navegación por pasos y visibilidad del formulario interactivo
const pasoActual = ref<number>(1);
const mostrarFormulario = ref<boolean>(false);

// Establecer por defecto el establecimiento principal de la empresa si existe
const establecimientoDefault = computed(() => {
    if (!props.establecimientos || props.establecimientos.length === 0) return null;
    const principal = props.establecimientos.find((e) => e.es_principal);
    return principal ? principal.id : props.establecimientos[0].id;
});

// Inicialización del formulario reactivo con Inertia useForm
const form = useForm({
    // Sección 1: Datos de identificación y contacto del consumidor
    tipo_documento: 'dni',
    numero_documento: '',
    nombres: '',
    apellidos: '',
    email: '',
    telefono: '',
    direccion: '',
    departamento: '',
    provincia: '',
    distrito: '',

    // Sección condicional: Datos del apoderado o tutor si el consumidor es menor de edad
    es_menor_edad: false,
    apoderado_nombre: '',
    apoderado_tipo_documento: 'dni',
    apoderado_numero_documento: '',
    apoderado_telefono: '',
    apoderado_email: '',

    // Sección 2: Identificación del bien o servicio materia de la reclamación
    establecimiento_id: establecimientoDefault.value,
    tipo_bien: 'producto',
    descripcion_bien: '',
    monto_reclamado: '',
    moneda: 'PEN',
    numero_comprobante: '',
    fecha_contratacion: '',

    // Sección 3: Clasificación formal, detalle de hechos, pedido concreto y aviso legal
    tipo: 'reclamo',
    detalle_problema: '',
    pedido_consumidor: '',
    aviso_privacidad_leido: false,
    aviso_privacidad_version: props.aviso_privacidad_version || '1.0',

    // Archivos adjuntos y evidencias del consumidor
    adjuntos: [] as File[],

    // Protección anti-abuso y validación de autenticidad de envío
    token_seguridad: props.token_seguridad || '',
    sitio_web_hp: '',
});

// Captura de mensaje de confirmación flash enviado desde el servidor
const flashSuccess = computed(() => {
    return (page.props as any).flash?.success || null;
});

// Restricción de longitud según el tipo de documento seleccionado
const longitudMaximaDoc = computed(() => {
    if (form.tipo_documento === 'dni') return 8;
    if (form.tipo_documento === 'ruc') return 11;
    return 15;
});

// Texto de ayuda para orientar al consumidor sobre el formato del documento
const placeholderDoc = computed(() => {
    if (form.tipo_documento === 'dni') return '8 dígitos (ej. 45678912)';
    if (form.tipo_documento === 'ruc') return '11 dígitos (ej. 20123456789)';
    if (form.tipo_documento === 'ce') return 'Carné de extranjería';
    return 'Número de pasaporte';
});

// Inicia el flujo del formulario interactivo
const iniciarRegistro = () => {
    mostrarFormulario.value = true;
    pasoActual.value = 1;
};

// Validación del Paso 1: Datos del consumidor antes de avanzar
const validarPaso1 = () => {
    form.clearErrors();
    let valido = true;

    if (!form.numero_documento.trim()) {
        form.setError('numero_documento', 'Ingresa el número de documento.');
        valido = false;
    } else if (form.tipo_documento === 'dni' && !/^[0-9]{8}$/.test(form.numero_documento.trim())) {
        form.setError('numero_documento', 'El DNI debe contener exactamente 8 dígitos.');
        valido = false;
    } else if (form.tipo_documento === 'ruc' && !/^[0-9]{11}$/.test(form.numero_documento.trim())) {
        form.setError('numero_documento', 'El RUC debe contener exactamente 11 dígitos.');
        valido = false;
    }

    if (!form.nombres.trim()) {
        form.setError('nombres', 'Los nombres son obligatorios.');
        valido = false;
    }

    if (form.tipo_documento !== 'ruc' && !form.apellidos.trim()) {
        form.setError('apellidos', 'Los apellidos son obligatorios.');
        valido = false;
    }

    if (!form.email.trim()) {
        form.setError('email', 'El correo electrónico es requerido.');
        valido = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.trim())) {
        form.setError('email', 'Ingresa un correo electrónico válido.');
        valido = false;
    }

    if (!form.telefono.trim()) {
        form.setError('telefono', 'El teléfono es obligatorio.');
        valido = false;
    }

    if (!form.direccion.trim()) {
        form.setError('direccion', 'La dirección es obligatoria.');
        valido = false;
    }

    // Validar datos obligatorios del representante si el consumidor es menor de edad
    if (form.es_menor_edad) {
        if (!form.apoderado_nombre.trim()) {
            form.setError('apoderado_nombre', 'Ingresa los nombres del padre, madre o apoderado.');
            valido = false;
        }
        if (!form.apoderado_numero_documento.trim()) {
            form.setError('apoderado_numero_documento', 'Ingresa el documento del apoderado.');
            valido = false;
        }
    }

    if (valido) {
        pasoActual.value = 2;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

// Validación del Paso 2: Bien o servicio contratado antes de avanzar
const validarPaso2 = () => {
    form.clearErrors();
    let valido = true;

    if (!form.descripcion_bien.trim()) {
        form.setError('descripcion_bien', 'Describe brevemente el producto o servicio contratado.');
        valido = false;
    } else if (form.descripcion_bien.trim().length < 5) {
        form.setError('descripcion_bien', 'La descripción debe tener al menos 5 caracteres.');
        valido = false;
    }

    if (valido) {
        pasoActual.value = 3;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

// Validación del Paso 3 y envío seguro del formulario completo
const enviarRegistro = () => {
    form.clearErrors();
    let valido = true;

    if (!form.detalle_problema.trim()) {
        form.setError('detalle_problema', 'Debes detallar los hechos y motivos de tu reclamación o queja.');
        valido = false;
    } else if (form.detalle_problema.trim().length < 10) {
        form.setError('detalle_problema', 'El detalle de los hechos debe contener al menos 10 caracteres.');
        valido = false;
    }

    if (!form.pedido_consumidor.trim()) {
        form.setError('pedido_consumidor', 'Debes indicar el pedido concreto o la solución que solicitas.');
        valido = false;
    } else if (form.pedido_consumidor.trim().length < 5) {
        form.setError('pedido_consumidor', 'El pedido concreto debe contener al menos 5 caracteres.');
        valido = false;
    }

    if (!form.aviso_privacidad_leido) {
        form.setError('aviso_privacidad_leido', 'Debes confirmar la toma de conocimiento del aviso legal de tratamiento de datos personales.');
        valido = false;
    }

    if (valido) {
        form.post(`/l/${props.empresa.slug}/registrar`, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                pasoActual.value = 1;
                mostrarFormulario.value = false;
            },
        });
    }
};
</script>

<template>
    <div class="min-h-screen bg-[#FBFDFF] text-slate-800 antialiased font-sans selection:bg-blue-100 selection:text-blue-700 flex flex-col justify-between">
        <Head :title="`Libro de Reclamaciones Virtual - ${empresa.name}`">
            <meta name="robots" content="noindex, nofollow" />
        </Head>

        <!-- Cabecera Institucional del Proveedor Reclamado -->
        <header class="border-b border-slate-200 bg-white shadow-xs sticky top-0 z-30">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3 sm:px-6">
                <!-- Logotipo e Identidad de la Empresa -->
                <div class="flex items-center gap-3">
                    <div
                        v-if="!empresa.logo_path"
                        class="flex h-11 w-11 items-center justify-center rounded-xl text-white font-bold text-base shadow-xs"
                        :style="{ backgroundColor: empresa.color_primario }"
                    >
                        {{ empresa.name.charAt(0).toUpperCase() }}
                    </div>
                    <img
                        v-else
                        :src="empresa.logo_path"
                        :alt="empresa.name"
                        class="h-10 max-w-[150px] object-contain"
                    />

                    <div>
                        <h1 class="font-bold text-slate-900 text-base sm:text-lg leading-tight">{{ empresa.name }}</h1>
                        <p class="text-xs text-slate-500 font-mono">RUC: {{ empresa.ruc }}</p>
                    </div>
                </div>

                <!-- Distintivo oficial del Libro de Reclamaciones -->
                <div class="flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50/70 px-3.5 py-1 text-xs font-semibold text-blue-900">
                    <svg class="h-4 w-4 text-blue-600 hidden sm:inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Libro Virtual</span>
                </div>
            </div>
        </header>

        <!-- Mensaje de confirmación de registro exitoso -->
        <div v-if="flashSuccess" class="mx-auto max-w-5xl px-4 pt-6 sm:px-6 w-full">
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-xs flex items-start gap-3 text-emerald-900">
                <svg class="h-6 w-6 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-sm">
                    <p class="font-bold text-emerald-950">Registro completado con éxito</p>
                    <p class="mt-1 text-emerald-800">{{ flashSuccess }}</p>
                </div>
            </div>
        </div>

        <!-- Contenido Principal -->
        <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 grow w-full">
            <div class="space-y-6">

                <!-- Vista Inicial: Bienvenida, Base Legal y Datos del Proveedor -->
                <div v-if="!mostrarFormulario" class="space-y-6">
                    <!-- Introducción y Marco Normativo de INDECOPI -->
                    <div class="rounded-2xl bg-white border border-slate-200 p-6 sm:p-8 shadow-xs">
                        <div class="inline-flex items-center gap-2 rounded-md bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 mb-3">
                            <span>Conforme al D.S. N° 011-2011-PCM • INDECOPI</span>
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
                            Libro de Reclamaciones Virtual
                        </h2>
                        <p class="mt-2 text-sm text-slate-600 max-w-3xl leading-relaxed">
                            Conforme a lo establecido en el Código de Protección y Defensa del Consumidor, ponemos a tu disposición nuestro formulario virtual para el ingreso de reclamaciones o quejas sobre los productos o servicios contratados.
                        </p>
                    </div>

                    <!-- Datos Legales del Proveedor Reclamado (Razón social, RUC, Domicilio) -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 border-b border-slate-100 pb-2">
                            1. Identificación del Proveedor Reclamado
                        </h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 text-sm">
                            <div>
                                <span class="block text-xs font-semibold text-slate-500">Razón Social:</span>
                                <span class="font-medium text-slate-900">{{ empresa.razon_social }}</span>
                            </div>
                            <div>
                                <span class="block text-xs font-semibold text-slate-500">Número de RUC:</span>
                                <span class="font-mono font-medium text-slate-900">{{ empresa.ruc }}</span>
                            </div>
                            <div class="sm:col-span-2">
                                <span class="block text-xs font-semibold text-slate-500">Domicilio Legal:</span>
                                <span class="font-medium text-slate-900">{{ empresa.direccion }}</span>
                            </div>
                            <div v-if="empresa.email">
                                <span class="block text-xs font-semibold text-slate-500">Correo Electrónico:</span>
                                <span class="font-medium text-slate-900">{{ empresa.email }}</span>
                            </div>
                            <div v-if="empresa.telefono">
                                <span class="block text-xs font-semibold text-slate-500">Teléfono:</span>
                                <span class="font-medium text-slate-900">{{ empresa.telefono }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Cuadros informativos: Diferencia entre Reclamo y Queja -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                            <div class="flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">R</span>
                                <h4 class="font-bold text-slate-900 text-sm">¿Qué es un Reclamo?</h4>
                            </div>
                            <p class="mt-2 text-xs text-slate-600 leading-relaxed">
                                Disconformidad relacionada directamente a los bienes adquiridos o a los servicios contratados con nuestro comercio.
                            </p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                            <div class="flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-amber-100 text-xs font-bold text-amber-700">Q</span>
                                <h4 class="font-bold text-slate-900 text-sm">¿Qué es una Queja?</h4>
                            </div>
                            <p class="mt-2 text-xs text-slate-600 leading-relaxed">
                                Malestar o descontento respecto a la atención recibida por parte del personal o disconformidad que no tiene relación directa con el bien o servicio.
                            </p>
                        </div>
                    </div>

                    <!-- Tarjeta de acción para iniciar el llenado del formulario -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 text-center shadow-xs">
                        <h3 class="text-lg font-bold text-slate-900">
                            ¿Deseas registrar un Reclamo o Queja?
                        </h3>
                        <p class="mt-1 text-xs text-slate-500 max-w-xl mx-auto">
                            El proceso es directo, no requiere cuenta y recibirás una constancia formal con número único de seguimiento.
                        </p>
                        <div class="mt-5 flex flex-wrap items-center justify-center gap-3">
                            <button
                                type="button"
                                @click="iniciarRegistro"
                                class="inline-flex items-center gap-2.5 rounded-full px-7 py-3.5 text-sm font-bold text-white shadow-lg shadow-blue-500/25 hover:opacity-95 transition cursor-pointer"
                                :style="{ backgroundColor: empresa.color_primario }"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span>Iniciar Registro de Reclamación</span>
                            </button>

                            <Link
                                :href="route('libro.consulta', { slug: empresa.slug })"
                                class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-6 py-3.5 text-sm font-semibold text-slate-700 hover:bg-slate-100 hover:text-slate-900 transition"
                            >
                                <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <span>Consultar Estado de Reclamación</span>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Formulario Interactivo de Registro en 3 Pasos -->
                <div v-else class="space-y-6">
                    <!-- Indicador de avance y pasos del formulario -->
                    <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-xs">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    @click="mostrarFormulario = false"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-slate-800 transition cursor-pointer"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                    </svg>
                                    Volver
                                </button>
                                <span class="h-4 w-px bg-slate-200"></span>
                                <h3 class="text-sm font-bold text-slate-900">
                                    Paso {{ pasoActual }} de 3:
                                    <span v-if="pasoActual === 1">Datos del Consumidor Reclamante</span>
                                    <span v-else-if="pasoActual === 2">Identificación del Bien o Servicio</span>
                                    <span v-else>Detalle de los Hechos y Pedido</span>
                                </h3>
                            </div>

                            <div class="flex items-center gap-2 text-xs font-bold">
                                <span
                                    class="flex h-6 w-6 items-center justify-center rounded-full transition"
                                    :class="pasoActual === 1 ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-700'"
                                >
                                    1
                                </span>
                                <span class="text-slate-400">→</span>
                                <span
                                    class="flex h-6 w-6 items-center justify-center rounded-full transition"
                                    :class="pasoActual === 2 ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-700'"
                                >
                                    2
                                </span>
                                <span class="text-slate-400">→</span>
                                <span
                                    class="flex h-6 w-6 items-center justify-center rounded-full transition"
                                    :class="pasoActual === 3 ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-700'"
                                >
                                    3
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Alerta de validación o seguridad si ocurre un error de control de abuso -->
                    <div v-if="form.errors.sitio_web_hp || form.errors.token_seguridad" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs font-semibold text-rose-700">
                        {{ form.errors.sitio_web_hp || form.errors.token_seguridad }}
                    </div>

                    <!-- Campo trampa accesible no visible para humanos destinado a la mitigación no intrusiva de bots -->
                    <div aria-hidden="true" class="opacity-0 absolute pointer-events-none -left-[9999px] h-0 w-0 overflow-hidden" tabindex="-1">
                        <label for="sitio_web_hp">Dejar este campo vacío</label>
                        <input
                            id="sitio_web_hp"
                            v-model="form.sitio_web_hp"
                            type="text"
                            name="sitio_web_hp"
                            tabindex="-1"
                            autocomplete="off"
                        />
                    </div>

                    <!-- Paso 1: Identificación y Datos del Consumidor Reclamante -->
                    <div v-show="pasoActual === 1" class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-xs space-y-6">
                        <div class="border-b border-slate-100 pb-4">
                            <h3 class="text-lg font-bold text-slate-900">
                                1. Identificación del Consumidor Reclamante
                            </h3>
                            <p class="text-xs text-slate-500 mt-1">
                                Ingresa la información solicitada conforme al formato oficial del Libro de Reclamaciones.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <!-- Selector del Tipo de Documento de Identidad -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                    Tipo de Documento *
                                </label>
                                <select
                                    v-model="form.tipo_documento"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                >
                                    <option v-for="tipo in tipos_documento" :key="tipo.value" :value="tipo.value">
                                        {{ tipo.label }}
                                    </option>
                                </select>
                            </div>

                            <!-- Campo para Número de Documento con máscara y longitud dinámica -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                    Número de Documento *
                                </label>
                                <input
                                    v-model="form.numero_documento"
                                    type="text"
                                    :maxlength="longitudMaximaDoc"
                                    :placeholder="placeholderDoc"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-mono text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    :class="{ 'border-rose-400 focus:border-rose-500 focus:ring-rose-500': form.errors.numero_documento }"
                                />
                                <p v-if="form.errors.numero_documento" class="mt-1 text-xs text-rose-600">
                                    {{ form.errors.numero_documento }}
                                </p>
                            </div>

                            <!-- Nombres o Razón Social según corresponda -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                    {{ form.tipo_documento === 'ruc' ? 'Razón Social o Nombre Comercial *' : 'Nombres Completos *' }}
                                </label>
                                <input
                                    v-model="form.nombres"
                                    type="text"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    :class="{ 'border-rose-400 focus:border-rose-500 focus:ring-rose-500': form.errors.nombres }"
                                />
                                <p v-if="form.errors.nombres" class="mt-1 text-xs text-rose-600">
                                    {{ form.errors.nombres }}
                                </p>
                            </div>

                            <!-- Apellidos para personas naturales -->
                            <div v-if="form.tipo_documento !== 'ruc'">
                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                    Apellidos Completos *
                                </label>
                                <input
                                    v-model="form.apellidos"
                                    type="text"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    :class="{ 'border-rose-400 focus:border-rose-500 focus:ring-rose-500': form.errors.apellidos }"
                                />
                                <p v-if="form.errors.apellidos" class="mt-1 text-xs text-rose-600">
                                    {{ form.errors.apellidos }}
                                </p>
                            </div>

                            <!-- Correo Electrónico del Consumidor -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                    Correo Electrónico *
                                </label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    placeholder="correo@ejemplo.com"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    :class="{ 'border-rose-400 focus:border-rose-500 focus:ring-rose-500': form.errors.email }"
                                />
                                <p class="mt-1 text-xs text-slate-400">
                                    A esta dirección te enviaremos la constancia oficial de tu reclamación.
                                </p>
                                <p v-if="form.errors.email" class="mt-1 text-xs text-rose-600">
                                    {{ form.errors.email }}
                                </p>
                            </div>

                            <!-- Teléfono o Celular de Contacto -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                    Teléfono / Celular de Contacto *
                                </label>
                                <input
                                    v-model="form.telefono"
                                    type="text"
                                    placeholder="ej. 987654321"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    :class="{ 'border-rose-400 focus:border-rose-500 focus:ring-rose-500': form.errors.telefono }"
                                />
                                <p v-if="form.errors.telefono" class="mt-1 text-xs text-rose-600">
                                    {{ form.errors.telefono }}
                                </p>
                            </div>

                            <!-- Domicilio o Dirección del Consumidor -->
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                    Domicilio / Dirección Completa *
                                </label>
                                <input
                                    v-model="form.direccion"
                                    type="text"
                                    placeholder="Av., Calle, N°, Urbanización o referencia"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    :class="{ 'border-rose-400 focus:border-rose-500 focus:ring-rose-500': form.errors.direccion }"
                                />
                                <p v-if="form.errors.direccion" class="mt-1 text-xs text-rose-600">
                                    {{ form.errors.direccion }}
                                </p>
                            </div>

                            <!-- Ubicación Geográfica: Departamento, Provincia y Distrito -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                    Departamento
                                </label>
                                <input
                                    v-model="form.departamento"
                                    type="text"
                                    placeholder="ej. Lima"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                    Provincia / Distrito
                                </label>
                                <div class="grid grid-cols-2 gap-2">
                                    <input
                                        v-model="form.provincia"
                                        type="text"
                                        placeholder="Provincia"
                                        class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    />
                                    <input
                                        v-model="form.distrito"
                                        type="text"
                                        placeholder="Distrito"
                                        class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Bloque Condicional para Consumidor Menor de Edad -->
                        <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 sm:p-5 space-y-4">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input
                                    v-model="form.es_menor_edad"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                />
                                <span class="text-sm font-bold text-slate-800">
                                    El consumidor que presenta el reclamo es menor de edad
                                </span>
                            </label>

                            <!-- Datos del Padre, Madre o Representante Legal -->
                            <div v-if="form.es_menor_edad" class="pt-3 border-t border-slate-200 space-y-4">
                                <p class="text-xs text-slate-600 font-medium">
                                    Conforme a la normativa de INDECOPI, el reclamo de un menor de edad debe estar respaldado por uno de sus padres o representante legal:
                                </p>

                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-bold text-slate-700 mb-1">
                                            Nombres y Apellidos del Padre, Madre o Apoderado *
                                        </label>
                                        <input
                                            v-model="form.apoderado_nombre"
                                            type="text"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            :class="{ 'border-rose-400': form.errors.apoderado_nombre }"
                                        />
                                        <p v-if="form.errors.apoderado_nombre" class="mt-1 text-xs text-rose-600">
                                            {{ form.errors.apoderado_nombre }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">
                                            Tipo Documento Apoderado *
                                        </label>
                                        <select
                                            v-model="form.apoderado_tipo_documento"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                        >
                                            <option value="dni">DNI</option>
                                            <option value="ce">Carné de Extranjería</option>
                                            <option value="pasaporte">Pasaporte</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">
                                            N° Documento Apoderado *
                                        </label>
                                        <input
                                            v-model="form.apoderado_numero_documento"
                                            type="text"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm font-mono text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            :class="{ 'border-rose-400': form.errors.apoderado_numero_documento }"
                                        />
                                        <p v-if="form.errors.apoderado_numero_documento" class="mt-1 text-xs text-rose-600">
                                            {{ form.errors.apoderado_numero_documento }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">
                                            Teléfono Apoderado (Opcional)
                                        </label>
                                        <input
                                            v-model="form.apoderado_telefono"
                                            type="text"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">
                                            Correo Apoderado (Opcional)
                                        </label>
                                        <input
                                            v-model="form.apoderado_email"
                                            type="email"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botón de avance hacia el Paso 2 -->
                        <div class="flex justify-end pt-4 border-t border-slate-100">
                            <button
                                type="button"
                                @click="validarPaso1"
                                class="inline-flex items-center gap-2 rounded-full px-7 py-2.5 text-sm font-bold text-white shadow-md shadow-blue-500/20 hover:opacity-95 transition cursor-pointer"
                                :style="{ backgroundColor: empresa.color_primario }"
                            >
                                <span>Continuar al Paso 2</span>
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Paso 2: Identificación del Bien o Servicio Contratado -->
                    <div v-show="pasoActual === 2" class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-xs space-y-6">
                        <div class="border-b border-slate-100 pb-4">
                            <h3 class="text-lg font-bold text-slate-900">
                                2. Identificación del Bien o Servicio Contratado
                            </h3>
                            <p class="text-xs text-slate-500 mt-1">
                                Indica con exactitud las características del bien adquirido o servicio solicitado.
                            </p>
                        </div>

                        <!-- Selector de Sede o Establecimiento Asociado -->
                        <div v-if="establecimientos && establecimientos.length > 0">
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Establecimiento / Sede Relacionada
                            </label>
                            <select
                                v-model="form.establecimiento_id"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
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
                            <label class="block text-xs font-bold text-slate-700 mb-2">
                                Tipo de Bien *
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <label
                                    class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition"
                                    :class="form.tipo_bien === 'producto' ? 'border-blue-500 bg-blue-50/50 ring-1 ring-blue-500' : 'border-slate-200 bg-white hover:bg-slate-50'"
                                >
                                    <input
                                        type="radio"
                                        v-model="form.tipo_bien"
                                        value="producto"
                                        class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500"
                                    />
                                    <div>
                                        <div class="flex items-center gap-1.5 font-bold text-slate-900 text-sm">
                                            <span>📦</span>
                                            <span>Producto</span>
                                        </div>
                                        <p class="mt-1 text-xs text-slate-500">
                                            Artículo físico, bien digital, software, equipo o producto adquirido.
                                        </p>
                                    </div>
                                </label>

                                <label
                                    class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition"
                                    :class="form.tipo_bien === 'servicio' ? 'border-blue-500 bg-blue-50/50 ring-1 ring-blue-500' : 'border-slate-200 bg-white hover:bg-slate-50'"
                                >
                                    <input
                                        type="radio"
                                        v-model="form.tipo_bien"
                                        value="servicio"
                                        class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500"
                                    />
                                    <div>
                                        <div class="flex items-center gap-1.5 font-bold text-slate-900 text-sm">
                                            <span>🛠️</span>
                                            <span>Servicio</span>
                                        </div>
                                        <p class="mt-1 text-xs text-slate-500">
                                            Atención, consultoría, desarrollo, soporte técnico, mantenimiento o servicio brindado.
                                        </p>
                                    </div>
                                </label>
                            </div>
                            <p v-if="form.errors.tipo_bien" class="mt-1 text-xs text-rose-600">
                                {{ form.errors.tipo_bien }}
                            </p>
                        </div>

                        <!-- Detalle y descripción del producto o servicio -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Descripción del Producto o Servicio Contratado *
                            </label>
                            <textarea
                                v-model="form.descripcion_bien"
                                rows="3"
                                placeholder="Indica nombre del producto, modelo, plan contratado, características principales..."
                                class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                :class="{ 'border-rose-400 focus:border-rose-500 focus:ring-rose-500': form.errors.descripcion_bien }"
                            ></textarea>
                            <div class="flex items-center justify-between mt-1 text-xs text-slate-400">
                                <span>Sé lo más específico posible con el bien objeto del reclamo.</span>
                                <span>{{ form.descripcion_bien.length }}/1500 caracteres</span>
                            </div>
                            <p v-if="form.errors.descripcion_bien" class="mt-1 text-xs text-rose-600">
                                {{ form.errors.descripcion_bien }}
                            </p>
                        </div>

                        <!-- Monto y moneda reclamados cuando proceda -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                    Moneda
                                </label>
                                <select
                                    v-model="form.moneda"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                >
                                    <option value="PEN">Soles (S/ - PEN)</option>
                                    <option value="USD">Dólares (US$ - USD)</option>
                                </select>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                    Monto Reclamado (Opcional, cuando proceda)
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-xs font-bold text-slate-400">
                                        {{ form.moneda === 'PEN' ? 'S/' : '$' }}
                                    </span>
                                    <input
                                        v-model="form.monto_reclamado"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="0.00"
                                        class="w-full rounded-xl border border-slate-300 bg-white pl-9 pr-3.5 py-2.5 text-sm font-mono text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                        :class="{ 'border-rose-400': form.errors.monto_reclamado }"
                                    />
                                </div>
                                <p v-if="form.errors.monto_reclamado" class="mt-1 text-xs text-rose-600">
                                    {{ form.errors.monto_reclamado }}
                                </p>
                            </div>
                        </div>

                        <!-- Referencias adicionales de comprobante de compra y fecha -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-slate-100">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                    N° de Comprobante / Pedido (Opcional)
                                </label>
                                <input
                                    v-model="form.numero_comprobante"
                                    type="text"
                                    placeholder="ej. B001-000452 o Pedido #10852"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                />
                                <p class="mt-1 text-xs text-slate-400">
                                    Boleta, factura o código de orden de compra.
                                </p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                    Fecha de Compra o Contratación (Opcional)
                                </label>
                                <input
                                    v-model="form.fecha_contratacion"
                                    type="date"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    :class="{ 'border-rose-400': form.errors.fecha_contratacion }"
                                />
                                <p v-if="form.errors.fecha_contratacion" class="mt-1 text-xs text-rose-600">
                                    {{ form.errors.fecha_contratacion }}
                                </p>
                            </div>
                        </div>

                        <!-- Botones de Acción: Regresar y Avanzar al Paso 3 -->
                        <div class="flex items-center justify-between pt-6 border-t border-slate-100">
                            <button
                                type="button"
                                @click="pasoActual = 1"
                                class="inline-flex items-center gap-1.5 rounded-full border border-slate-300 bg-white px-6 py-2.5 text-xs sm:text-sm font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                <span>Paso Anterior</span>
                            </button>

                            <button
                                type="button"
                                @click="validarPaso2"
                                class="inline-flex items-center gap-2 rounded-full px-7 py-2.5 text-sm font-bold text-white shadow-md shadow-blue-500/20 hover:opacity-95 transition cursor-pointer"
                                :style="{ backgroundColor: empresa.color_primario }"
                            >
                                <span>Continuar al Paso 3</span>
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Paso 3: Clasificación, Detalle de Hechos, Pedido Concreto y Aviso de Privacidad -->
                    <div v-show="pasoActual === 3" class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-xs space-y-6">
                        <div class="border-b border-slate-100 pb-4">
                            <h3 class="text-lg font-bold text-slate-900">
                                3. Detalle de la Reclamación y Pedido Concreto
                            </h3>
                            <p class="text-xs text-slate-500 mt-1">
                                Define la naturaleza de tu disconformidad, describe los hechos con exactitud y expresa tu pedido concreto.
                            </p>
                        </div>

                        <!-- Clasificación Formal: Reclamo vs Queja con explicación accesible -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-2">
                                Tipo de Presentación *
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <label
                                    class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition"
                                    :class="form.tipo === 'reclamo' ? 'border-blue-500 bg-blue-50/50 ring-1 ring-blue-500' : 'border-slate-200 bg-white hover:bg-slate-50'"
                                >
                                    <input
                                        type="radio"
                                        v-model="form.tipo"
                                        value="reclamo"
                                        class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500"
                                    />
                                    <div>
                                        <div class="flex items-center gap-2 font-bold text-slate-900 text-sm">
                                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-100 text-xs text-blue-700 font-extrabold">R</span>
                                            <span>Reclamo</span>
                                        </div>
                                        <p class="mt-1.5 text-xs text-slate-600 leading-relaxed">
                                            Disconformidad directamente relacionada con los productos o servicios adquiridos o contratados.
                                        </p>
                                    </div>
                                </label>

                                <label
                                    class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition"
                                    :class="form.tipo === 'queja' ? 'border-blue-500 bg-blue-50/50 ring-1 ring-blue-500' : 'border-slate-200 bg-white hover:bg-slate-50'"
                                >
                                    <input
                                        type="radio"
                                        v-model="form.tipo"
                                        value="queja"
                                        class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500"
                                    />
                                    <div>
                                        <div class="flex items-center gap-2 font-bold text-slate-900 text-sm">
                                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-amber-100 text-xs text-amber-700 font-extrabold">Q</span>
                                            <span>Queja</span>
                                        </div>
                                        <p class="mt-1.5 text-xs text-slate-600 leading-relaxed">
                                            Malestar o descontento respecto a la atención al público brindada por el personal o aspectos no relacionados directamente con el bien.
                                        </p>
                                    </div>
                                </label>
                            </div>
                            <p v-if="form.errors.tipo" class="mt-1 text-xs text-rose-600">
                                {{ form.errors.tipo }}
                            </p>
                        </div>

                        <!-- Campo: Detalle de los Hechos / Descripción del Problema con límites visibles -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-bold text-slate-700">
                                    Detalle del Problema y Hechos Ocurridos *
                                </label>
                                <span class="text-xs font-mono text-slate-400">
                                    {{ form.detalle_problema.length }} / 3000 caracteres
                                </span>
                            </div>
                            <textarea
                                v-model="form.detalle_problema"
                                rows="4"
                                maxlength="3000"
                                placeholder="Describe de forma cronológica y detallada los hechos que sustentan tu reclamación o queja..."
                                class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                :class="{ 'border-rose-400 focus:border-rose-500 focus:ring-rose-500': form.errors.detalle_problema }"
                            ></textarea>
                            <p v-if="form.errors.detalle_problema" class="mt-1 text-xs text-rose-600">
                                {{ form.errors.detalle_problema }}
                            </p>
                        </div>

                        <!-- Campo: Pedido Concreto del Consumidor con límites visibles -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-bold text-slate-700">
                                    Pedido Concreto del Consumidor *
                                </label>
                                <span class="text-xs font-mono text-slate-400">
                                    {{ form.pedido_consumidor.length }} / 1000 caracteres
                                </span>
                            </div>
                            <textarea
                                v-model="form.pedido_consumidor"
                                rows="3"
                                maxlength="1000"
                                placeholder="Indica concretamente lo que solicitas para solucionar tu reclamación (ej. devolución de dinero, cambio de producto, anulación de cobro, etc.)..."
                                class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                :class="{ 'border-rose-400 focus:border-rose-500 focus:ring-rose-500': form.errors.pedido_consumidor }"
                            ></textarea>
                            <p v-if="form.errors.pedido_consumidor" class="mt-1 text-xs text-rose-600">
                                {{ form.errors.pedido_consumidor }}
                            </p>
                        </div>

                        <!-- Anexión Opcional de Evidencias y Documentos de Sustento -->
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 sm:p-5 space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-slate-700">
                                    Documentos o Evidencias de Sustento (Opcional)
                                </label>
                                <span class="text-[11px] text-slate-400 font-medium">Máx. 3 archivos de 5MB c/u</span>
                            </div>
                            <input
                                type="file"
                                multiple
                                accept=".pdf,.jpg,.jpeg,.png,.webp"
                                @change="(e: any) => form.adjuntos = Array.from(e.target.files || [])"
                                class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition cursor-pointer"
                            />
                            <p class="text-[11px] text-slate-400">
                                Formatos admitidos: PDF, JPG, PNG o WEBP. Puedes adjuntar fotos del producto, contratos, recibos o capturas.
                            </p>
                            <p v-if="form.errors.adjuntos" class="mt-1 text-xs text-rose-600">
                                {{ form.errors.adjuntos }}
                            </p>
                        </div>

                        <!-- Marco Legal y Aviso de Privacidad y Tratamiento de Datos (Ley N° 29733) -->
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 sm:p-5 space-y-3">
                            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">
                                        Aviso de Privacidad y Protección de Datos Personales
                                    </h4>
                                </div>
                                <span class="text-[11px] font-mono text-slate-500 bg-white border border-slate-200 px-2 py-0.5 rounded-md">
                                    Versión {{ form.aviso_privacidad_version }}
                                </span>
                            </div>

                            <p class="text-xs text-slate-600 leading-relaxed">
                                En cumplimiento de la <strong>Ley N° 29733 (Ley de Protección de Datos Personales)</strong> y su reglamento, se informa que <strong>{{ empresa.razon_social }}</strong> actúa como <em>Responsable del Tratamiento</em> de los datos recopilados, con la finalidad exclusiva de dar curso, investigar y resolver su reclamación en el marco legal del Código de Protección y Defensa del Consumidor. Asimismo, <strong>Datec</strong> actúa como <em>Encargado Tecnológico</em> de la plataforma y almacenamiento seguro. Sus datos no serán empleados con fines comerciales ni transferidos a terceros no autorizados.
                            </p>

                            <!-- Checkbox de toma de conocimiento del aviso legal -->
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
                                <p v-if="form.errors.aviso_privacidad_leido" class="mt-1 text-xs text-rose-600">
                                    {{ form.errors.aviso_privacidad_leido }}
                                </p>
                            </div>
                        </div>

                        <!-- Botones de Acción: Regresar y Registrar Reclamación Oficial -->
                        <div class="flex items-center justify-between pt-6 border-t border-slate-100">
                            <button
                                type="button"
                                @click="pasoActual = 2"
                                class="inline-flex items-center gap-1.5 rounded-full border border-slate-300 bg-white px-6 py-2.5 text-xs sm:text-sm font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                <span>Paso Anterior</span>
                            </button>

                            <button
                                type="button"
                                @click="enviarRegistro"
                                :disabled="form.processing"
                                class="inline-flex items-center gap-2 rounded-full px-8 py-3 text-sm font-bold text-white shadow-lg shadow-blue-500/25 hover:opacity-95 transition disabled:opacity-50 cursor-pointer"
                                :style="{ backgroundColor: empresa.color_primario }"
                            >
                                <span v-if="form.processing">Procesando envío...</span>
                                <span v-else>Presentar Reclamación / Queja</span>
                                <svg v-if="!form.processing" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <!-- Pie de Página Institucional del Comercio -->
        <footer class="border-t border-slate-200 bg-white py-6 mt-12 text-center text-xs text-slate-500">
            <div class="mx-auto max-w-5xl px-4 space-y-1">
                <p>
                    Plataforma provista por <strong>Datec Libro</strong> conforme al Código de Protección y Defensa del Consumidor (Ley N° 29571).
                </p>
                <p class="text-slate-400">
                    {{ empresa.razon_social }} • RUC {{ empresa.ruc }} • {{ empresa.direccion }}
                </p>
            </div>
        </footer>
    </div>
</template>
