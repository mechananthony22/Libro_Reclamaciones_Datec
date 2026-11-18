<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import SeccionBienvenidaLegal from './Partials/SeccionBienvenidaLegal.vue';
import Paso1Consumidor from './Partials/Paso1Consumidor.vue';
import Paso2BienContratado from './Partials/Paso2BienContratado.vue';
import Paso3DetalleReclamo from './Partials/Paso3DetalleReclamo.vue';

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
const logoError = ref(false);

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

// Retorno a la tienda/página de origen o cierre de pestaña
const regresarPaginaOrigen = () => {
    if (typeof window === 'undefined') return;

    const urlParams = new URLSearchParams(window.location.search);
    const returnUrl = urlParams.get('return_url') || urlParams.get('ref') || urlParams.get('origen');

    if (returnUrl) {
        window.location.href = returnUrl;
        return;
    }

    if (document.referrer && !document.referrer.includes(window.location.host)) {
        window.location.href = document.referrer;
        return;
    }

    if (window.history.length > 1) {
        window.history.back();
        return;
    }

    try {
        window.close();
    } catch {
        // Fallback si el navegador bloquea el cierre por script
    }
};
</script>

<template>
    <div class="min-h-screen bg-[#F8FAFC] text-slate-800 antialiased font-sans selection:bg-blue-100 selection:text-blue-700 flex flex-col justify-between">
        <Head :title="`Libro de Reclamaciones Virtual - ${empresa.name}`">
            <meta name="description" :content="`Libro de Reclamaciones Virtual de ${empresa.name} (${empresa.razon_social}). Registre su queja o reclamo conforme al Código de Protección y Defensa del Consumidor e INDECOPI.`" />
            <meta name="robots" content="index, follow" />
            <meta property="og:title" :content="`Libro de Reclamaciones Virtual - ${empresa.name}`" />
            <meta property="og:description" :content="`Hoja de Reclamación Digital oficial de ${empresa.name}. Atención al consumidor y resolución formal de reclamos y quejas.`" />
            <meta property="og:type" content="website" />
            <meta property="og:image" :content="empresa.logo_path || '/faviconDLR.png'" />
            <meta name="twitter:card" content="summary" />
            <meta name="twitter:title" :content="`Libro de Reclamaciones Virtual - ${empresa.name}`" />
            <meta name="twitter:description" :content="`Registre su reclamo o queja en línea en ${empresa.name}.`" />
            <meta name="twitter:image" :content="empresa.logo_path || '/faviconDLR.png'" />
        </Head>

        <!-- Cabecera Institucional del Proveedor Reclamado -->
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
                        {{ empresa.name.charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-sm font-extrabold text-slate-900 truncate">
                            {{ empresa.name }}
                        </h1>
                        <p class="text-[11px] text-slate-400 font-mono truncate">
                            RUC {{ empresa.ruc }}
                        </p>
                    </div>
                </div>

                <!-- Insignia Oficial y Enlace a Consulta -->
                <div class="flex items-center gap-2">
                    <Link
                        :href="`/l/${empresa.slug}/consultar`"
                        class="hidden sm:inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-100 hover:text-slate-900 transition"
                    >
                        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <span>Consultar Estado</span>
                    </Link>

                    <div class="flex items-center gap-1.5 rounded-full bg-blue-50/80 border border-blue-200/60 px-2.5 py-1 text-[11px] font-bold text-blue-700">
                        <span class="h-2 w-2 rounded-full bg-blue-600 animate-pulse"></span>
                        <span class="hidden xs:inline">Libro Oficial INDECOPI</span>
                        <span class="xs:hidden">Libro Oficial</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Mensaje de confirmación de registro exitoso -->
        <div v-if="flashSuccess" class="mx-auto max-w-6xl px-4 pt-6 sm:px-6 w-full">
            <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/80 p-4 shadow-2xs flex items-start gap-3 text-emerald-900">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="text-xs">
                    <p class="font-bold text-emerald-950 text-sm">Registro completado con éxito</p>
                    <p class="mt-0.5 text-emerald-800">{{ flashSuccess }}</p>
                </div>
            </div>
        </div>

        <!-- Contenido Principal -->
        <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 grow w-full">
            <div class="lg:grid lg:grid-cols-[200px_1fr] lg:gap-8 items-start">

                <!-- Acciones Laterales -->
                <aside class="mb-6 lg:mb-0 lg:sticky lg:top-20 z-20 w-full lg:w-48 xl:w-52 shrink-0">
                    <div class="flex flex-col gap-2.5">
                        <button
                            type="button"
                            @click="regresarPaginaOrigen"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 hover:text-slate-900 hover:border-slate-300 transition cursor-pointer group w-full"
                            title="Regresar a la página o tienda de origen"
                        >
                            <svg class="h-4 w-4 text-slate-400 group-hover:text-slate-600 group-hover:-translate-x-0.5 transition shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            <span class="truncate">Volver a la tienda</span>
                        </button>

                        <Link
                            href="/"
                            class="inline-flex items-center gap-2 rounded-xl border border-blue-200/80 bg-blue-50/70 px-3.5 py-2.5 text-xs font-bold text-blue-700 shadow-2xs hover:bg-blue-100 hover:text-blue-900 hover:border-blue-300 transition group w-full"
                            title="Conocer más sobre Datec Libro de Reclamaciones"
                        >
                            <svg class="h-4 w-4 text-blue-500 group-hover:text-blue-700 group-hover:-translate-x-0.5 transition shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            <span class="truncate">Conocer sobre DatecLR</span>
                        </Link>
                    </div>
                </aside>

                <!-- Columna Central de Contenido y Formulario -->
                <div class="space-y-6 w-full min-w-0">

                    <!-- Vista Inicial: Bienvenida, Base Legal y Datos del Proveedor -->
                    <SeccionBienvenidaLegal
                        v-if="!mostrarFormulario"
                        :empresa="empresa"
                        @iniciar-registro="iniciarRegistro"
                    />

                    <!-- Formulario Interactivo de Registro en 3 Pasos -->
                    <div v-else class="space-y-6">
                        <!-- Indicador de avance y pasos del formulario -->
                        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    @click="mostrarFormulario = false"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-100 hover:text-slate-900 transition cursor-pointer"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                    </svg>
                                    <span>Volver</span>
                                </button>
                                <span class="h-4 w-px bg-slate-200 hidden sm:block"></span>
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Registro de Reclamación</span>
                                    <h3 class="text-xs sm:text-sm font-extrabold text-slate-900">
                                        Paso {{ pasoActual }} de 3:
                                        <span v-if="pasoActual === 1" class="text-blue-600">Datos del Consumidor Reclamante</span>
                                        <span v-else-if="pasoActual === 2" class="text-blue-600">Identificación del Bien o Servicio</span>
                                        <span v-else class="text-blue-600">Detalle de los Hechos y Pedido</span>
                                    </h3>
                                </div>
                            </div>

                            <!-- Stepper Indicator -->
                            <div class="flex items-center gap-2">
                                <div class="flex items-center gap-1.5">
                                    <span
                                        class="flex h-7 w-7 items-center justify-center rounded-xl text-xs font-extrabold transition"
                                        :class="pasoActual === 1 ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : (pasoActual > 1 ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-400')"
                                    >
                                        <svg v-if="pasoActual > 1" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                        <span v-else>1</span>
                                    </span>
                                    <span class="text-[11px] font-bold hidden md:inline" :class="pasoActual === 1 ? 'text-blue-600' : 'text-slate-400'">Consumidor</span>
                                </div>
                                <span class="h-0.5 w-4 bg-slate-200"></span>
                                <div class="flex items-center gap-1.5">
                                    <span
                                        class="flex h-7 w-7 items-center justify-center rounded-xl text-xs font-extrabold transition"
                                        :class="pasoActual === 2 ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : (pasoActual > 2 ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-400')"
                                    >
                                        <svg v-if="pasoActual > 2" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                        <span v-else>2</span>
                                    </span>
                                    <span class="text-[11px] font-bold hidden md:inline" :class="pasoActual === 2 ? 'text-blue-600' : 'text-slate-400'">Bien / Servicio</span>
                                </div>
                                <span class="h-0.5 w-4 bg-slate-200"></span>
                                <div class="flex items-center gap-1.5">
                                    <span
                                        class="flex h-7 w-7 items-center justify-center rounded-xl text-xs font-extrabold transition"
                                        :class="pasoActual === 3 ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'bg-slate-100 text-slate-400'"
                                    >
                                        3
                                    </span>
                                    <span class="text-[11px] font-bold hidden md:inline" :class="pasoActual === 3 ? 'text-blue-600' : 'text-slate-400'">Detalle</span>
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
                        <Paso1Consumidor
                            v-show="pasoActual === 1"
                            :form="form"
                            :tipos-documento="tipos_documento"
                            :longitud-maxima-doc="longitudMaximaDoc"
                            :placeholder-doc="placeholderDoc"
                            :color-primario="empresa.color_primario"
                            @continuar="validarPaso1"
                        />

                        <!-- Paso 2: Identificación del Bien o Servicio Contratado -->
                        <Paso2BienContratado
                            v-show="pasoActual === 2"
                            :form="form"
                            :establecimientos="establecimientos"
                            :color-primario="empresa.color_primario"
                            @continuar="validarPaso2"
                            @regresar="pasoActual = 1"
                        />

                        <!-- Paso 3: Detalle de la Reclamación y Pedido del Consumidor -->
                        <Paso3DetalleReclamo
                            v-show="pasoActual === 3"
                            :form="form"
                            :empresa="empresa"
                            :color-primario="empresa.color_primario"
                            @enviar="enviarRegistro"
                            @regresar="pasoActual = 2"
                        />
                    </div>
                </div>
            </div>
        </main>

        <!-- Pie de Página Institucional del Comercio -->
        <footer class="border-t border-slate-200/80 bg-white py-6 mt-12 text-center text-xs text-slate-500">
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
