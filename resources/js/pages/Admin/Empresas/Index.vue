<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import CreateModal from './CreateModal.vue';
import EditModal from './EditModal.vue';

interface EmpresaItem {
    id: number;
    name: string;
    slug: string;
    razon_social: string;
    ruc: string;
    direccion?: string | null;
    email: string | null;
    telefono: string | null;
    representante_nombre?: string | null;
    representante_email?: string | null;
    representante_telefono?: string | null;
    color_primario?: string | null;
    estado: string;
    plan: string;
    activo: boolean;
    vigencia_desde?: string | null;
    vigencia_hasta?: string | null;
    limite_reclamaciones_mes?: number | string | null;
    usuarios_count: number;
    fecha_baja?: string | null;
    retencion_hasta?: string | null;
    paquete_salida_hash?: string | null;
    paquete_salida_generado_en?: string | null;
    invitaciones: Array<{
        id: number;
        email: string;
        token: string;
        expires_at: string;
        accepted_at: string | null;
    }>;
}

interface Props {
    empresas: {
        data: EmpresaItem[];
        current_page: number;
        last_page: number;
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filtros: {
        buscar: string;
        estado: string;
        plan: string;
    };
    metricas: {
        total: number;
        activas: number;
        suspendidas: number;
        borrador: number;
    };
    estados: Array<{ value: string; label: string }>;
    planes: Array<{ value: string; label: string }>;
    roles: Array<{ value: string; label: string }>;
}

const props = defineProps<Props>();
const page = usePage();

const buscar = ref(props.filtros.buscar || '');
const filtroEstado = ref(props.filtros.estado || '');
const filtroPlan = ref(props.filtros.plan || '');

const hayFiltrosActivos = computed(() => !!(buscar.value || filtroEstado.value || filtroPlan.value));

function aplicarFiltros() {
    router.get(
        '/admin/empresas',
        {
            buscar: buscar.value,
            estado: filtroEstado.value,
            plan: filtroPlan.value,
        },
        { preserveState: true, replace: true }
    );
}

function limpiarFiltros() {
    buscar.value = '';
    filtroEstado.value = '';
    filtroPlan.value = '';
    aplicarFiltros();
}

function seleccionarEstado(valor: string) {
    filtroEstado.value = valor;
    aplicarFiltros();
}

// Modales para Crear y Editar Empresa
const showModalCrear = ref(false);
const showModalEditar = ref(false);
const empresaSeleccionadaEditar = ref<EmpresaItem | null>(null);

function abrirModalCrear() {
    showModalCrear.value = true;
}

function abrirModalEditar(empresa: EmpresaItem) {
    empresaSeleccionadaEditar.value = empresa;
    showModalEditar.value = true;
}

// Modal para actualización de estado operativo de la empresa
const showModalEstado = ref(false);
const empresaSeleccionadaEstado = ref<EmpresaItem | null>(null);
const formEstado = useForm({
    estado: '',
    motivo: '',
});

function abrirModalEstado(empresa: EmpresaItem) {
    empresaSeleccionadaEstado.value = empresa;
    formEstado.estado = empresa.estado;
    formEstado.motivo = '';
    showModalEstado.value = true;
}

function submitCambiarEstado() {
    if (!empresaSeleccionadaEstado.value) return;

    formEstado.patch(`/admin/empresas/${empresaSeleccionadaEstado.value.id}/estado`, {
        onSuccess: () => {
            showModalEstado.value = false;
            formEstado.reset();
        },
    });
}

// Modal para procesar baja formal y custodia de pruebas del cliente
const showModalBaja = ref(false);
const empresaSeleccionadaBaja = ref<EmpresaItem | null>(null);
const formBaja = useForm({
    motivo: '',
    meses_retencion: 24,
});

function abrirModalBaja(empresa: EmpresaItem) {
    empresaSeleccionadaBaja.value = empresa;
    formBaja.motivo = '';
    formBaja.meses_retencion = 24;
    showModalBaja.value = true;
}

function submitBaja() {
    if (!empresaSeleccionadaBaja.value) return;

    formBaja.post(`/admin/empresas/${empresaSeleccionadaBaja.value.id}/baja`, {
        onSuccess: () => {
            showModalBaja.value = false;
            formBaja.reset();
        },
    });
}

// Modal para generar enlace de invitación de administrador inicial
const showModalInvitacion = ref(false);
const empresaSeleccionadaInvitacion = ref<EmpresaItem | null>(null);
const formInvitacion = useForm({
    email: '',
    nombre: '',
    rol: 'admin_empresa',
    horas_expiracion: 72,
});

function abrirModalInvitacion(empresa: EmpresaItem) {
    empresaSeleccionadaInvitacion.value = empresa;
    formInvitacion.email = empresa.email || '';
    formInvitacion.nombre = '';
    formInvitacion.rol = 'admin_empresa';
    formInvitacion.horas_expiracion = 72;
    showModalInvitacion.value = true;
}

function submitInvitar() {
    if (!empresaSeleccionadaInvitacion.value) return;

    formInvitacion.post(`/admin/empresas/${empresaSeleccionadaInvitacion.value.id}/invitaciones`, {
        onSuccess: () => {
            showModalInvitacion.value = false;
            formInvitacion.reset();
        },
    });
}

// Reenviar invitación existente a una empresa
function reenviarInvitacion(empresaId: number, invitacionId: number) {
    router.post(`/admin/empresas/${empresaId}/invitaciones/${invitacionId}/reenviar`, {}, {
        preserveScroll: true,
    });
}

const flashInvitacionUrl = computed(() => (page.props.flash as any)?.invitacion_url);
const copiadoExitoso = ref(false);

function copiarEnlace(enlace: string) {
    navigator.clipboard.writeText(enlace);
    copiadoExitoso.value = true;
    setTimeout(() => {
        copiadoExitoso.value = false;
    }, 3000);
}

// Etiquetas legibles para estados y planes a partir de los catálogos enviados por el servidor
function etiquetaEstado(valor: string): string {
    return props.estados.find((e) => e.value === valor)?.label ?? valor.replace('_', ' ');
}

function etiquetaPlan(valor: string): string {
    return props.planes.find((p) => p.value === valor)?.label ?? valor;
}

// Clases de estilo para distintivos de estado operativo
function getBadgeEstado(estado: string) {
    switch (estado) {
        case 'activa':
            return { badge: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20', punto: 'bg-emerald-500' };
        case 'suspendida':
            return { badge: 'bg-amber-50 text-amber-700 ring-amber-600/20', punto: 'bg-amber-500' };
        case 'baja_programada':
            return { badge: 'bg-rose-50 text-rose-700 ring-rose-600/20', punto: 'bg-rose-500' };
        default:
            return { badge: 'bg-slate-100 text-slate-600 ring-slate-400/20', punto: 'bg-slate-400' };
    }
}

function iniciales(nombre: string): string {
    return nombre
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p.charAt(0).toUpperCase())
        .join('');
}

// Pestañas rápidas de estado con sus contadores
const pestanasEstado = computed(() => [
    { valor: '', etiqueta: 'Todas', total: props.metricas.total },
    { valor: 'activa', etiqueta: 'Activas', total: props.metricas.activas },
    { valor: 'suspendida', etiqueta: 'Suspendidas', total: props.metricas.suspendidas },
    { valor: 'borrador', etiqueta: 'En configuración', total: props.metricas.borrador },
]);

const porcentajeActivas = computed(() =>
    props.metricas.total > 0 ? Math.round((props.metricas.activas / props.metricas.total) * 100) : 0
);
</script>

<template>
    <AdminLayout>
        <Head title="Gestión de Empresas - Datec Libro" />

        <div class="space-y-6">
            <!-- Cabecera de Página -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex items-start gap-4">
                    <div class="hidden sm:flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-md shadow-blue-500/25">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            Superadministrador · Plataforma Multiempresa
                        </div>
                        <h1 class="mt-0.5 text-2xl font-extrabold tracking-tight text-slate-900">
                            Empresas Clientes
                        </h1>
                        <p class="mt-1 text-sm text-slate-500">
                            Administra organizaciones, planes de suscripción y accesos iniciales de cada libro de reclamaciones.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="abrirModalCrear"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-blue-500/20 transition hover:bg-blue-700 cursor-pointer"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Nueva Empresa</span>
                </button>
            </div>

            <!-- Banner de Enlace de Activación Recién Generado -->
            <div
                v-if="flashInvitacionUrl"
                class="rounded-2xl border border-blue-200/80 bg-blue-50/70 p-4 shadow-2xs"
            >
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-blue-950">Enlace de activación de administrador generado</p>
                            <p class="mt-0.5 text-[11px] text-blue-800">
                                Compártelo con el administrador de la empresa para que configure su contraseña.
                            </p>
                            <code class="mt-2 block max-w-full overflow-x-auto rounded-lg border border-blue-200 bg-white px-3 py-1.5 font-mono text-[11px] text-blue-950">
                                {{ flashInvitacionUrl }}
                            </code>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="copiarEnlace(flashInvitacionUrl)"
                        class="inline-flex shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-blue-700"
                    >
                        <svg v-if="!copiadoExitoso" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <svg v-else class="h-4 w-4 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ copiadoExitoso ? '¡Copiado!' : 'Copiar enlace' }}</span>
                    </button>
                </div>
            </div>

            <!-- Tarjetas de Métricas -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Empresas</span>
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-3xl font-black text-slate-900">{{ metricas.total }}</p>
                    <p class="mt-1 text-[11px] font-medium text-slate-400">Organizaciones registradas</p>
                </div>

                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Activas</span>
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-3xl font-black text-slate-900">{{ metricas.activas }}</p>
                    <div class="mt-2 flex items-center gap-2">
                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-emerald-500 transition-all" :style="{ width: `${porcentajeActivas}%` }"></div>
                        </div>
                        <span class="text-[11px] font-bold text-emerald-700">{{ porcentajeActivas }}%</span>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Suspendidas</span>
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-3xl font-black text-slate-900">{{ metricas.suspendidas }}</p>
                    <p class="mt-1 text-[11px] font-medium text-slate-400">Servicio temporalmente en pausa</p>
                </div>

                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">En Configuración</span>
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-3xl font-black text-slate-900">{{ metricas.borrador }}</p>
                    <p class="mt-1 text-[11px] font-medium text-slate-400">Mínimos legales pendientes</p>
                </div>
            </div>

            <!-- Listado de Empresas -->
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xs">
                <!-- Pestañas de estado + búsqueda -->
                <div class="space-y-4 border-b border-slate-100 p-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <!-- Pestañas de estado al estilo del menú lateral -->
                        <div class="flex gap-1 overflow-x-auto rounded-xl bg-slate-100/80 p-1">
                            <button
                                v-for="tab in pestanasEstado"
                                :key="tab.valor"
                                type="button"
                                @click="seleccionarEstado(tab.valor)"
                                :class="[
                                    'flex shrink-0 cursor-pointer items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-bold transition',
                                    filtroEstado === tab.valor
                                        ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                        : 'text-slate-600 hover:bg-white hover:text-slate-900'
                                ]"
                            >
                                <span>{{ tab.etiqueta }}</span>
                                <span
                                    :class="[
                                        'rounded-md px-1.5 py-0.5 text-[10px] font-black',
                                        filtroEstado === tab.valor ? 'bg-white/20 text-white' : 'bg-white text-slate-500'
                                    ]"
                                >
                                    {{ tab.total }}
                                </span>
                            </button>
                        </div>

                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <div class="relative sm:w-72">
                                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <input
                                    v-model="buscar"
                                    @keyup.enter="aplicarFiltros"
                                    type="text"
                                    placeholder="Buscar nombre, RUC o enlace..."
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-xs font-medium text-slate-800 placeholder:text-slate-400 transition focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
                                />
                            </div>

                            <select
                                v-model="filtroPlan"
                                @change="aplicarFiltros"
                                class="cursor-pointer rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-700 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 sm:w-40"
                            >
                                <option value="">Todos los planes</option>
                                <option v-for="pl in planes" :key="pl.value" :value="pl.value">
                                    {{ pl.label }}
                                </option>
                            </select>

                            <button
                                v-if="hayFiltrosActivos"
                                type="button"
                                @click="limpiarFiltros"
                                class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-xl px-3 py-2 text-xs font-bold text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Limpiar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabla -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            <tr>
                                <th scope="col" class="px-5 py-3">Empresa</th>
                                <th scope="col" class="px-5 py-3">RUC</th>
                                <th scope="col" class="px-5 py-3">Libro Público</th>
                                <th scope="col" class="px-5 py-3">Plan</th>
                                <th scope="col" class="px-5 py-3">Estado</th>
                                <th scope="col" class="px-5 py-3">Administrador</th>
                                <th scope="col" class="px-5 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-600">
                            <tr
                                v-for="emp in empresas.data"
                                :key="emp.id"
                                class="group transition-colors hover:bg-slate-50/70"
                            >
                                <!-- Empresa con avatar de marca -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-[11px] font-black text-white shadow-2xs"
                                            :style="{ backgroundColor: emp.color_primario || '#2563EB' }"
                                        >
                                            {{ iniciales(emp.name) }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="truncate font-bold text-slate-900">{{ emp.name }}</div>
                                            <div class="max-w-[220px] truncate text-[11px] text-slate-400">{{ emp.razon_social }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- RUC -->
                                <td class="whitespace-nowrap px-5 py-3.5 font-mono text-[11px] font-semibold text-slate-700">
                                    {{ emp.ruc }}
                                </td>

                                <!-- Enlace público -->
                                <td class="whitespace-nowrap px-5 py-3.5">
                                    <a
                                        :href="`/l/${emp.slug}`"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-2 py-1 font-mono text-[11px] font-semibold text-slate-600 transition hover:bg-blue-50 hover:text-blue-700"
                                    >
                                        <span>/l/{{ emp.slug }}</span>
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                </td>

                                <!-- Plan -->
                                <td class="whitespace-nowrap px-5 py-3.5">
                                    <span class="inline-flex items-center gap-1 rounded-lg bg-blue-50 px-2 py-1 text-[11px] font-bold text-blue-700">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                        </svg>
                                        {{ etiquetaPlan(emp.plan) }}
                                    </span>
                                </td>

                                <!-- Estado -->
                                <td class="whitespace-nowrap px-5 py-3.5">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold ring-1',
                                            getBadgeEstado(emp.estado).badge
                                        ]"
                                    >
                                        <span :class="['h-1.5 w-1.5 rounded-full', getBadgeEstado(emp.estado).punto]"></span>
                                        {{ etiquetaEstado(emp.estado) }}
                                    </span>
                                </td>

                                <!-- Administrador e invitaciones -->
                                <td class="whitespace-nowrap px-5 py-3.5">
                                    <div v-if="emp.usuarios_count > 0" class="inline-flex items-center gap-1.5 text-[11px] font-bold text-emerald-700">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                        {{ emp.usuarios_count }} {{ emp.usuarios_count === 1 ? 'usuario activo' : 'usuarios activos' }}
                                    </div>
                                    <div v-else-if="emp.invitaciones && emp.invitaciones.length > 0">
                                        <span class="block text-[11px] font-bold text-amber-700">Invitación pendiente</span>
                                        <button
                                            type="button"
                                            @click="reenviarInvitacion(emp.id, emp.invitaciones[0].id)"
                                            class="mt-0.5 cursor-pointer text-[11px] font-semibold text-blue-600 hover:underline"
                                        >
                                            Reenviar enlace
                                        </button>
                                    </div>
                                    <span v-else class="text-[11px] italic text-slate-400">Sin administrador</span>
                                </td>

                                <!-- Acciones -->
                                <td class="whitespace-nowrap px-5 py-3.5">
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            type="button"
                                            @click="abrirModalEstado(emp)"
                                            title="Cambiar estado de servicio"
                                            class="cursor-pointer rounded-lg p-2 text-slate-400 transition hover:bg-amber-50 hover:text-amber-600"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z" />
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            @click="abrirModalInvitacion(emp)"
                                            title="Invitar administrador"
                                            class="cursor-pointer rounded-lg p-2 text-slate-400 transition hover:bg-blue-50 hover:text-blue-600"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                            </svg>
                                        </button>

                                        <!-- Descarga de Paquete de Custodia si está en Baja -->
                                        <a
                                            v-if="emp.estado === 'baja_programada'"
                                            :href="`/admin/empresas/${emp.id}/custodia/descargar`"
                                            title="Descargar paquete de custodia legal (ZIP)"
                                            class="cursor-pointer rounded-lg p-2 text-rose-500 transition hover:bg-rose-50 hover:text-rose-700"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                        </a>

                                        <!-- Botón de Baja Formal de Cliente -->
                                        <button
                                            v-else
                                            type="button"
                                            @click="abrirModalBaja(emp)"
                                            title="Procesar baja formal y custodia probatoria"
                                            class="cursor-pointer rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            @click="abrirModalEditar(emp)"
                                            class="ml-1 inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-3 py-1.5 text-[11px] font-bold text-blue-700 transition hover:bg-blue-600 hover:text-white cursor-pointer"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Editar
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Estado vacío -->
                            <tr v-if="empresas.data.length === 0">
                                <td colspan="7" class="px-5 py-14">
                                    <div class="flex flex-col items-center text-center">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                        </div>
                                        <p class="mt-3 text-sm font-bold text-slate-800">No se encontraron empresas</p>
                                        <p class="mt-1 text-xs text-slate-500">Ajusta los filtros o registra una nueva organización.</p>
                                        <button
                                            v-if="hayFiltrosActivos"
                                            type="button"
                                            @click="limpiarFiltros"
                                            class="mt-4 cursor-pointer rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50"
                                        >
                                            Limpiar filtros
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pie con paginación -->
                <div class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50/50 px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-[11px] font-medium text-slate-500">
                        Mostrando <span class="font-bold text-slate-700">{{ empresas.data.length }}</span> de
                        <span class="font-bold text-slate-700">{{ empresas.total }}</span> empresas
                    </div>
                    <div v-if="empresas.last_page > 1" class="flex gap-1">
                        <Link
                            v-for="(link, i) in empresas.links"
                            :key="i"
                            :href="link.url || '#'"
                            preserve-scroll
                            :class="[
                                'min-w-8 rounded-lg px-2.5 py-1 text-center text-xs font-bold transition',
                                link.active
                                    ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                    : 'text-slate-600 hover:bg-white hover:text-slate-900',
                                !link.url && 'pointer-events-none opacity-40'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para Modificar Estado Operativo -->
        <div v-if="showModalEstado" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs">
            <div class="w-full max-w-md space-y-4 rounded-2xl bg-white p-6 shadow-xl">
                <div class="flex items-start justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Cambiar estado de servicio</h3>
                            <p class="text-[11px] text-slate-500">{{ empresaSeleccionadaEstado?.name }}</p>
                        </div>
                    </div>
                    <button type="button" @click="showModalEstado = false" class="cursor-pointer rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitCambiarEstado" class="space-y-4 text-xs">
                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Nuevo estado operativo</label>
                        <select
                            v-model="formEstado.estado"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        >
                            <option v-for="est in estados" :key="est.value" :value="est.value">
                                {{ est.label }}
                            </option>
                        </select>
                        <p v-if="formEstado.errors.estado" class="mt-1 text-[11px] text-rose-600">{{ formEstado.errors.estado }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Motivo del cambio</label>
                        <textarea
                            v-model="formEstado.motivo"
                            rows="3"
                            required
                            placeholder="Justificación administrativa para el registro de auditoría..."
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        ></textarea>
                        <p v-if="formEstado.errors.motivo" class="mt-1 text-[11px] text-rose-600">{{ formEstado.errors.motivo }}</p>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-3">
                        <button
                            type="button"
                            @click="showModalEstado = false"
                            class="cursor-pointer rounded-xl px-4 py-2 font-bold text-slate-600 transition hover:bg-slate-100"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="formEstado.processing"
                            class="cursor-pointer rounded-xl bg-blue-600 px-4 py-2 font-bold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ formEstado.processing ? 'Guardando...' : 'Guardar cambio' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal para Generar Enlace de Invitación -->
        <div v-if="showModalInvitacion" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs">
            <div class="w-full max-w-md space-y-4 rounded-2xl bg-white p-6 shadow-xl">
                <div class="flex items-start justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Invitar administrador</h3>
                            <p class="text-[11px] text-slate-500">Enlace seguro de un solo uso para {{ empresaSeleccionadaInvitacion?.name }}</p>
                        </div>
                    </div>
                    <button type="button" @click="showModalInvitacion = false" class="cursor-pointer rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitInvitar" class="space-y-4 text-xs">
                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Correo electrónico *</label>
                        <input
                            v-model="formInvitacion.email"
                            type="email"
                            required
                            placeholder="admin@empresa.com"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                        <p v-if="formInvitacion.errors.email" class="mt-1 text-[11px] text-rose-600">{{ formInvitacion.errors.email }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Nombre (opcional)</label>
                        <input
                            v-model="formInvitacion.nombre"
                            type="text"
                            placeholder="Juan Pérez"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Rol</label>
                            <select
                                v-model="formInvitacion.rol"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            >
                                <option v-for="r in roles" :key="r.value" :value="r.value">
                                    {{ r.label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Vigencia</label>
                            <select
                                v-model="formInvitacion.horas_expiracion"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            >
                                <option :value="24">24 horas</option>
                                <option :value="72">72 horas (recomendado)</option>
                                <option :value="168">7 días</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-3">
                        <button
                            type="button"
                            @click="showModalInvitacion = false"
                            class="cursor-pointer rounded-xl px-4 py-2 font-bold text-slate-600 transition hover:bg-slate-100"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="formInvitacion.processing"
                            class="cursor-pointer rounded-xl bg-blue-600 px-4 py-2 font-bold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ formInvitacion.processing ? 'Generando...' : 'Generar invitación' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal para Procesar Baja Formal y Custodia Legal -->
        <div v-if="showModalBaja && empresaSeleccionadaBaja" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs">
            <div class="w-full max-w-lg space-y-4 rounded-2xl bg-white p-6 shadow-2xl border border-slate-200">
                <div class="flex items-start justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-600 shrink-0">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Baja Formal y Custodia Legal</h3>
                            <p class="text-[11px] text-slate-500">{{ empresaSeleccionadaBaja.razon_social || empresaSeleccionadaBaja.name }} &bull; RUC {{ empresaSeleccionadaBaja.ruc }}</p>
                        </div>
                    </div>
                    <button type="button" @click="showModalBaja = false" class="cursor-pointer rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitBaja" class="space-y-4 text-xs">
                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-700">Motivo de la baja o finalización de contrato *</label>
                        <textarea
                            v-model="formBaja.motivo"
                            rows="3"
                            required
                            placeholder="Indique la causa formal del cese de servicio..."
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 focus:border-rose-500 focus:outline-none focus:ring-1 focus:ring-rose-500"
                        ></textarea>
                    </div>

                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-700">Plazo de Retención Legal de Pruebas (Meses)</label>
                        <input
                            v-model.number="formBaja.meses_retencion"
                            type="number"
                            min="1"
                            max="120"
                            required
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 focus:border-rose-500 focus:outline-none focus:ring-1 focus:ring-rose-500"
                        />
                        <p class="mt-1 text-[11px] text-slate-400">Por defecto: 24 meses (2 años conforme al Código de Protección y Defensa del Consumidor INDECOPI).</p>
                    </div>

                    <div class="rounded-xl bg-rose-50/80 p-3.5 text-[11px] text-rose-900 border border-rose-200 space-y-1">
                        <div class="font-bold">Efectos inmediatos de esta operación:</div>
                        <ul class="list-disc pl-4 space-y-0.5 text-rose-800">
                            <li>Se revocarán los accesos de todos los usuarios vinculados a este cliente.</li>
                            <li>El formulario público quedará clausurado con aviso de retención legal.</li>
                            <li>Se compilará el paquete ZIP de custodia probatoria (CSVs + constancias) con hash criptográfico SHA-256.</li>
                        </ul>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-3">
                        <button
                            type="button"
                            @click="showModalBaja = false"
                            class="cursor-pointer rounded-xl px-4 py-2 font-bold text-slate-600 transition hover:bg-slate-100"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="formBaja.processing"
                            class="cursor-pointer rounded-xl bg-rose-600 px-4 py-2 font-bold text-white shadow-sm transition hover:bg-rose-700 disabled:opacity-50"
                        >
                            {{ formBaja.processing ? 'Procesando...' : 'Confirmar Baja y Custodia' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modales de Creación y Edición de Empresa -->
        <CreateModal
            :show="showModalCrear"
            :estados="estados"
            :planes="planes"
            @close="showModalCrear = false"
        />

        <EditModal
            :show="showModalEditar"
            :empresa="empresaSeleccionadaEditar"
            :estados="estados"
            :planes="planes"
            @close="showModalEditar = false"
        />
    </AdminLayout>
</template>
