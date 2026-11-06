<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface EmpresaItem {
    id: number;
    name: string;
    slug: string;
    razon_social: string;
    ruc: string;
    email: string | null;
    telefono: string | null;
    estado: string;
    plan: string;
    activo: boolean;
    usuarios_count: number;
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

// Clases de estilo para distintivos de estado operativo
function getBadgeEstado(estado: string) {
    switch (estado) {
        case 'activa':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'suspendida':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'baja_programada':
            return 'bg-rose-50 text-rose-700 border-rose-200';
        default:
            return 'bg-slate-50 text-slate-700 border-slate-200';
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Gestión de Empresas - Datec Libro" />

        <div class="space-y-8">
            <!-- Cabecera de Página con Estilo SaaS Moderno -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50/70 px-3 py-1 text-xs font-bold uppercase tracking-wider text-blue-700 mb-2">
                        <span class="flex h-2 w-2 rounded-full bg-blue-600 animate-pulse"></span>
                        <span>Plataforma SaaS Multitenant</span>
                    </div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">
                        Empresas y Clientes
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Administración centralizada de organizaciones, suscripciones y credenciales de acceso inicial.
                    </p>
                </div>

                <div>
                    <Link
                        href="/admin/empresas/create"
                        class="inline-flex items-center gap-2 rounded-full bg-blue-600 px-6 py-3 text-sm font-bold text-white shadow-md shadow-blue-500/25 hover:bg-blue-700 transition"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Nueva Empresa</span>
                    </Link>
                </div>
            </div>

            <!-- Banner Informativo de Enlace de Activación Recién Generado -->
            <div
                v-if="flashInvitacionUrl"
                class="rounded-2xl border border-blue-200 bg-blue-50/80 p-5 shadow-xs"
            >
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="flex items-center gap-2 font-bold text-blue-950 text-sm">
                            <svg class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Enlace de activación de administrador generado
                        </div>
                        <p class="text-xs text-blue-800 mt-0.5">
                            Comparte este enlace seguro con el administrador de la empresa para que configure su contraseña:
                        </p>
                        <code class="mt-2 block max-w-full overflow-x-auto rounded-xl bg-white px-3 py-1.5 text-xs text-blue-950 border border-blue-200 font-mono shadow-2xs">
                            {{ flashInvitacionUrl }}
                        </code>
                    </div>

                    <div class="shrink-0 pt-2 sm:pt-0">
                        <button
                            type="button"
                            @click="copiarEnlace(flashInvitacionUrl)"
                            class="inline-flex items-center gap-1.5 rounded-full bg-blue-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-blue-700 transition cursor-pointer"
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
            </div>

            <!-- Métricas Resumen en Tarjetas Tipo SmartWing -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Empresas</span>
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-600 text-xs font-bold">🏢</span>
                    </div>
                    <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ metricas.total }}</p>
                    <span class="text-[11px] text-slate-400 mt-1 block">Organizaciones registradas</span>
                </div>

                <div class="rounded-2xl border border-emerald-200 bg-emerald-50/40 p-5 shadow-xs hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-emerald-800 uppercase tracking-wider">Activas</span>
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 text-xs font-bold">✓</span>
                    </div>
                    <p class="mt-2 text-3xl font-extrabold text-emerald-900">{{ metricas.activas }}</p>
                    <span class="text-[11px] text-emerald-700/80 mt-1 block">Libros en producción</span>
                </div>

                <div class="rounded-2xl border border-amber-200 bg-amber-50/40 p-5 shadow-xs hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-amber-800 uppercase tracking-wider">Suspendidas</span>
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-100 text-amber-700 text-xs font-bold">⏸</span>
                    </div>
                    <p class="mt-2 text-3xl font-extrabold text-amber-900">{{ metricas.suspendidas }}</p>
                    <span class="text-[11px] text-amber-700/80 mt-1 block">Servicio en pausa</span>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-5 shadow-xs hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-600 uppercase tracking-wider">En Configuración</span>
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-200 text-slate-700 text-xs font-bold">✎</span>
                    </div>
                    <p class="mt-2 text-3xl font-extrabold text-slate-800">{{ metricas.borrador }}</p>
                    <span class="text-[11px] text-slate-500 mt-1 block">Mínimos legales pendientes</span>
                </div>
            </div>

            <!-- Barra de Filtros y Búsqueda -->
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs space-y-3 sm:space-y-0 sm:flex sm:items-center sm:justify-between sm:gap-4">
                <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative flex-1">
                        <svg class="pointer-events-none absolute left-3.5 top-3 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            v-model="buscar"
                            @keyup.enter="aplicarFiltros"
                            type="text"
                            placeholder="Buscar por nombre, razón social, RUC o enlace..."
                            class="w-full rounded-xl border border-slate-300 bg-slate-50/50 pl-10 pr-4 py-2 text-xs sm:text-sm text-slate-900 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition"
                        />
                    </div>

                    <div class="w-full sm:w-44">
                        <select
                            v-model="filtroEstado"
                            @change="aplicarFiltros"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs sm:text-sm font-medium text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        >
                            <option value="">Todos los estados</option>
                            <option v-for="est in estados" :key="est.value" :value="est.value">
                                {{ est.label }}
                            </option>
                        </select>
                    </div>

                    <div class="w-full sm:w-44">
                        <select
                            v-model="filtroPlan"
                            @change="aplicarFiltros"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs sm:text-sm font-medium text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        >
                            <option value="">Todos los planes</option>
                            <option v-for="pl in planes" :key="pl.value" :value="pl.value">
                                {{ pl.label }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <button
                        type="button"
                        @click="aplicarFiltros"
                        class="rounded-full bg-blue-600 px-5 py-2 text-xs font-bold text-white shadow-xs hover:bg-blue-700 transition cursor-pointer"
                    >
                        Filtrar
                    </button>
                    <button
                        type="button"
                        @click="limpiarFiltros"
                        class="rounded-full border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer"
                    >
                        Limpiar
                    </button>
                </div>
            </div>

            <!-- Tabla de Empresas Estilo Card Moderno -->
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50/80 text-[11px] uppercase font-bold text-slate-500 border-b border-slate-200">
                            <tr>
                                <th scope="col" class="px-5 py-4">Empresa / Razón Social</th>
                                <th scope="col" class="px-5 py-4">RUC</th>
                                <th scope="col" class="px-5 py-4">Enlace Público</th>
                                <th scope="col" class="px-5 py-4">Plan</th>
                                <th scope="col" class="px-5 py-4">Estado</th>
                                <th scope="col" class="px-5 py-4">Administrador</th>
                                <th scope="col" class="px-5 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="emp in empresas.data" :key="emp.id" class="hover:bg-blue-50/20 transition-colors">
                                <!-- Nombre y Razón Social -->
                                <td class="px-5 py-4">
                                    <div class="font-bold text-slate-900">{{ emp.name }}</div>
                                    <div class="text-xs text-slate-500 truncate max-w-xs">{{ emp.razon_social }}</div>
                                </td>

                                <!-- RUC -->
                                <td class="px-5 py-4 font-mono text-xs font-semibold text-slate-800">
                                    {{ emp.ruc }}
                                </td>

                                <!-- Enlace / Slug -->
                                <td class="px-5 py-4">
                                    <a
                                        :href="`/l/${emp.slug}`"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 font-mono text-xs font-semibold text-blue-600 hover:text-blue-800 hover:underline"
                                    >
                                        <span>/l/{{ emp.slug }}</span>
                                        <svg class="h-3.5 w-3.5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                </td>

                                <!-- Plan -->
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center rounded-full bg-slate-100 border border-slate-200 px-2.5 py-0.5 text-xs font-bold text-slate-700 capitalize">
                                        {{ emp.plan }}
                                    </span>
                                </td>

                                <!-- Estado con Distintivo Píldora -->
                                <td class="px-5 py-4">
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-bold capitalize',
                                            getBadgeEstado(emp.estado)
                                        ]"
                                    >
                                        {{ emp.estado.replace('_', ' ') }}
                                    </span>
                                </td>

                                <!-- Administrador e Invitaciones -->
                                <td class="px-5 py-4">
                                    <div v-if="emp.usuarios_count > 0" class="flex items-center gap-1.5 text-xs text-emerald-700 font-bold">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                        {{ emp.usuarios_count }} activo(s)
                                    </div>
                                    <div v-else-if="emp.invitaciones && emp.invitaciones.length > 0" class="text-xs">
                                        <span class="text-amber-700 font-bold block">Invitación pendiente</span>
                                        <button
                                            type="button"
                                            @click="reenviarInvitacion(emp.id, emp.invitaciones[0].id)"
                                            class="text-blue-600 hover:underline text-[11px] font-semibold mt-0.5 cursor-pointer"
                                        >
                                            Reenviar enlace
                                        </button>
                                    </div>
                                    <div v-else>
                                        <button
                                            type="button"
                                            @click="abrirModalInvitacion(emp)"
                                            class="inline-flex items-center gap-1 text-xs font-bold text-blue-600 hover:text-blue-800 cursor-pointer"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            Invitar admin
                                        </button>
                                    </div>
                                </td>

                                <!-- Acciones -->
                                <td class="px-5 py-4 text-right whitespace-nowrap space-x-1.5">
                                    <button
                                        type="button"
                                        @click="abrirModalEstado(emp)"
                                        class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
                                    >
                                        Estado
                                    </button>
                                    <button
                                        type="button"
                                        @click="abrirModalInvitacion(emp)"
                                        class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
                                    >
                                        Invitar
                                    </button>
                                    <Link
                                        :href="`/admin/empresas/${emp.id}/edit`"
                                        class="rounded-full bg-blue-50 border border-blue-100 px-3.5 py-1.5 text-xs font-bold text-blue-700 hover:bg-blue-100 transition"
                                    >
                                        Editar
                                    </Link>
                                </td>
                            </tr>

                            <tr v-if="empresas.data.length === 0">
                                <td colspan="7" class="px-5 py-10 text-center text-slate-500">
                                    No se encontraron empresas registradas con los filtros seleccionados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div
                    v-if="empresas.last_page > 1"
                    class="flex items-center justify-between border-t border-slate-200 bg-slate-50/70 px-5 py-3 sm:px-6"
                >
                    <div class="text-xs font-medium text-slate-500">
                        Página {{ empresas.current_page }} de {{ empresas.last_page }} ({{ empresas.total }} empresas registradas)
                    </div>
                    <div class="flex gap-1">
                        <Link
                            v-for="(link, i) in empresas.links"
                            :key="i"
                            :href="link.url || '#'"
                            :class="[
                                'rounded-full px-3 py-1 text-xs font-bold transition',
                                link.active ? 'bg-blue-600 text-white' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100',
                                !link.url && 'opacity-50 cursor-not-allowed'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para Modificar Estado Operativo -->
        <div v-if="showModalEstado" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-slate-200">
                <h3 class="text-lg font-bold text-slate-900">
                    Cambiar estado de servicio
                </h3>
                <p class="text-xs text-slate-500 mt-1">
                    Empresa: <strong class="text-slate-800">{{ empresaSeleccionadaEstado?.name }}</strong>
                </p>

                <form @submit.prevent="submitCambiarEstado" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700">Nuevo Estado Operativo</label>
                        <select
                            v-model="formEstado.estado"
                            class="mt-1 w-full rounded-xl border border-slate-300 p-2.5 text-sm font-medium text-slate-900 focus:border-blue-500 focus:outline-none"
                        >
                            <option v-for="est in estados" :key="est.value" :value="est.value">
                                {{ est.label }}
                            </option>
                        </select>
                        <div v-if="formEstado.errors.estado" class="text-xs text-rose-600 mt-1">
                            {{ formEstado.errors.estado }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700">
                            Motivo del cambio de estado
                        </label>
                        <textarea
                            v-model="formEstado.motivo"
                            rows="3"
                            placeholder="Indica la justificación administrativa para el registro de auditoría..."
                            class="mt-1 w-full rounded-xl border border-slate-300 p-2.5 text-sm text-slate-900 focus:border-blue-500 focus:outline-none"
                            required
                        ></textarea>
                        <div v-if="formEstado.errors.motivo" class="text-xs text-rose-600 mt-1">
                            {{ formEstado.errors.motivo }}
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showModalEstado = false"
                            class="rounded-full border border-slate-300 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="formEstado.processing"
                            class="rounded-full bg-blue-600 px-5 py-2 text-xs font-bold text-white shadow-xs hover:bg-blue-700 transition disabled:opacity-50 cursor-pointer"
                        >
                            {{ formEstado.processing ? 'Guardando...' : 'Guardar Cambio' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal para Generar Enlace de Invitación -->
        <div v-if="showModalInvitacion" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-slate-200">
                <h3 class="text-lg font-bold text-slate-900">
                    Invitar Administrador de Empresa
                </h3>
                <p class="text-xs text-slate-500 mt-1">
                    Se generará un enlace de activación seguro de un solo uso para <strong class="text-slate-800">{{ empresaSeleccionadaInvitacion?.name }}</strong>.
                </p>

                <form @submit.prevent="submitInvitar" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700">Correo Electrónico *</label>
                        <input
                            v-model="formInvitacion.email"
                            type="email"
                            required
                            placeholder="admin@empresa.com"
                            class="mt-1 w-full rounded-xl border border-slate-300 p-2.5 text-sm text-slate-900 focus:border-blue-500 focus:outline-none"
                        />
                        <div v-if="formInvitacion.errors.email" class="text-xs text-rose-600 mt-1">
                            {{ formInvitacion.errors.email }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700">Nombre del Administrador (Opcional)</label>
                        <input
                            v-model="formInvitacion.nombre"
                            type="text"
                            placeholder="Juan Pérez"
                            class="mt-1 w-full rounded-xl border border-slate-300 p-2.5 text-sm text-slate-900 focus:border-blue-500 focus:outline-none"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700">Rol Asignado</label>
                        <select
                            v-model="formInvitacion.rol"
                            class="mt-1 w-full rounded-xl border border-slate-300 p-2.5 text-sm font-medium text-slate-900 focus:border-blue-500 focus:outline-none"
                        >
                            <option v-for="r in roles" :key="r.value" :value="r.value">
                                {{ r.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700">Vigencia del enlace</label>
                        <select
                            v-model="formInvitacion.horas_expiracion"
                            class="mt-1 w-full rounded-xl border border-slate-300 p-2.5 text-sm font-medium text-slate-900 focus:border-blue-500 focus:outline-none"
                        >
                            <option :value="24">24 horas (1 día)</option>
                            <option :value="72">72 horas (3 días - Recomendado)</option>
                            <option :value="168">168 horas (7 días)</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showModalInvitacion = false"
                            class="rounded-full border border-slate-300 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="formInvitacion.processing"
                            class="rounded-full bg-blue-600 px-5 py-2 text-xs font-bold text-white shadow-xs hover:bg-blue-700 transition disabled:opacity-50 cursor-pointer"
                        >
                            {{ formInvitacion.processing ? 'Generando...' : 'Generar Invitación' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
