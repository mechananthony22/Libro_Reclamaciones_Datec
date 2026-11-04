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

// Modal Cambiar Estado (RF-002)
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

// Modal Invitar Administrador (RF-004)
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

// Reenviar invitación existente
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

function getBadgeEstado(estado: string) {
    switch (estado) {
        case 'activa':
            return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'suspendida':
            return 'bg-amber-100 text-amber-800 border-amber-200';
        case 'baja_programada':
            return 'bg-rose-100 text-rose-800 border-rose-200';
        default:
            return 'bg-slate-100 text-slate-800 border-slate-200';
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Gestión de Empresas - Datec Libro" />

        <div class="space-y-6">
            <!-- Header section -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Empresas y Clientes</h1>
                    <p class="text-sm text-slate-500">
                        Administración centralizada de organizaciones, planes y cuentas de administradores iniciales.
                    </p>
                </div>
                <div>
                    <Link
                        href="/admin/empresas/create"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-blue-700 transition"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nueva Empresa
                    </Link>
                </div>
            </div>

            <!-- Banner con enlace de invitación recién generado (RF-004) -->
            <div
                v-if="flashInvitacionUrl"
                class="rounded-xl border border-blue-200 bg-blue-50 p-4 shadow-xs"
            >
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="flex items-center gap-2 font-semibold text-blue-900 text-sm">
                            <svg class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Enlace de activación de administrador generado
                        </div>
                        <p class="text-xs text-blue-700 mt-0.5">
                            Comparte este enlace de un solo uso con el administrador de la empresa para que configure su contraseña:
                        </p>
                        <code class="mt-1.5 block max-w-full overflow-x-auto rounded bg-white px-2 py-1 text-xs text-blue-950 border border-blue-200 font-mono">
                            {{ flashInvitacionUrl }}
                        </code>
                    </div>
                    <div class="shrink-0 pt-2 sm:pt-0">
                        <button
                            type="button"
                            @click="copiarEnlace(flashInvitacionUrl)"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3.5 py-2 text-xs font-medium text-white hover:bg-blue-700"
                        >
                            <svg v-if="!copiadoExitoso" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <svg v-else class="h-4 w-4 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ copiadoExitoso ? '¡Copiado!' : 'Copiar enlace' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Métricas resumen -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
                    <p class="text-xs font-medium text-slate-500">Total Empresas</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ metricas.total }}</p>
                </div>
                <div class="rounded-xl border border-emerald-100 bg-emerald-50/50 p-4 shadow-xs">
                    <p class="text-xs font-medium text-emerald-700">Activas</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-800">{{ metricas.activas }}</p>
                </div>
                <div class="rounded-xl border border-amber-100 bg-amber-50/50 p-4 shadow-xs">
                    <p class="text-xs font-medium text-amber-700">Suspendidas (RF-002)</p>
                    <p class="mt-1 text-2xl font-bold text-amber-800">{{ metricas.suspendidas }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 shadow-xs">
                    <p class="text-xs font-medium text-slate-600">Borrador</p>
                    <p class="mt-1 text-2xl font-bold text-slate-700">{{ metricas.borrador }}</p>
                </div>
            </div>

            <!-- Barra de Filtros y Búsqueda -->
            <div class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-xs sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative flex-1">
                        <input
                            v-model="buscar"
                            @keyup.enter="aplicarFiltros"
                            type="text"
                            placeholder="Buscar por nombre, razón social, RUC o slug..."
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm focus:border-blue-500 focus:outline-hidden focus:ring-1 focus:ring-blue-500"
                        />
                    </div>
                    <div class="w-full sm:w-44">
                        <select
                            v-model="filtroEstado"
                            @change="aplicarFiltros"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-hidden focus:ring-1 focus:ring-blue-500"
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
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-hidden focus:ring-1 focus:ring-blue-500"
                        >
                            <option value="">Todos los planes</option>
                            <option v-for="pl in planes" :key="pl.value" :value="pl.value">
                                {{ pl.label }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="aplicarFiltros"
                        class="rounded-lg bg-slate-900 px-3.5 py-2 text-sm font-medium text-white hover:bg-slate-800"
                    >
                        Filtrar
                    </button>
                    <button
                        type="button"
                        @click="limpiarFiltros"
                        class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
                    >
                        Limpiar
                    </button>
                </div>
            </div>

            <!-- Tabla de Empresas -->
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200">
                            <tr>
                                <th scope="col" class="px-4 py-3.5">Empresa / Razón Social</th>
                                <th scope="col" class="px-4 py-3.5">RUC</th>
                                <th scope="col" class="px-4 py-3.5">Enlace / Slug</th>
                                <th scope="col" class="px-4 py-3.5">Plan (RF-003)</th>
                                <th scope="col" class="px-4 py-3.5">Estado (RF-002)</th>
                                <th scope="col" class="px-4 py-3.5">Administrador (RF-004)</th>
                                <th scope="col" class="px-4 py-3.5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr v-for="emp in empresas.data" :key="emp.id" class="hover:bg-slate-50/70 transition-colors">
                                <!-- Nombre y Razón Social -->
                                <td class="px-4 py-3.5">
                                    <div class="font-semibold text-slate-900">{{ emp.name }}</div>
                                    <div class="text-xs text-slate-500 truncate max-w-xs">{{ emp.razon_social }}</div>
                                </td>

                                <!-- RUC -->
                                <td class="px-4 py-3.5 font-mono text-xs text-slate-800">
                                    {{ emp.ruc }}
                                </td>

                                <!-- Slug / URL pública -->
                                <td class="px-4 py-3.5">
                                    <a
                                        :href="`/l/${emp.slug}`"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 font-mono text-xs text-blue-600 hover:underline"
                                    >
                                        /l/{{ emp.slug }}
                                        <svg class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                </td>

                                <!-- Plan -->
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700 capitalize">
                                        {{ emp.plan }}
                                    </span>
                                </td>

                                <!-- Estado con Badge -->
                                <td class="px-4 py-3.5">
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold capitalize',
                                            getBadgeEstado(emp.estado)
                                        ]"
                                    >
                                        {{ emp.estado.replace('_', ' ') }}
                                    </span>
                                </td>

                                <!-- Admin / Invitación -->
                                <td class="px-4 py-3.5">
                                    <div v-if="emp.usuarios_count > 0" class="flex items-center gap-1.5 text-xs text-emerald-700 font-medium">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        {{ emp.usuarios_count }} cuenta(s) activa(s)
                                    </div>
                                    <div v-else-if="emp.invitaciones && emp.invitaciones.length > 0" class="text-xs">
                                        <div class="text-amber-700 font-medium">Invitación pendiente</div>
                                        <button
                                            type="button"
                                            @click="reenviarInvitacion(emp.id, emp.invitaciones[0].id)"
                                            class="text-blue-600 hover:underline text-[11px]"
                                        >
                                            Reenviar enlace
                                        </button>
                                    </div>
                                    <div v-else>
                                        <button
                                            type="button"
                                            @click="abrirModalInvitacion(emp)"
                                            class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 hover:text-blue-800"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            Invitar admin
                                        </button>
                                    </div>
                                </td>

                                <!-- Acciones -->
                                <td class="px-4 py-3.5 text-right whitespace-nowrap space-x-2">
                                    <button
                                        type="button"
                                        @click="abrirModalEstado(emp)"
                                        title="Cambiar estado de servicio (RF-002)"
                                        class="rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                    >
                                        Estado
                                    </button>
                                    <button
                                        type="button"
                                        @click="abrirModalInvitacion(emp)"
                                        title="Invitar usuario (RF-004)"
                                        class="rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                    >
                                        Invitar
                                    </button>
                                    <Link
                                        :href="`/admin/empresas/${emp.id}/edit`"
                                        class="rounded-md bg-slate-100 px-2.5 py-1.5 text-xs font-medium text-slate-800 hover:bg-slate-200"
                                    >
                                        Editar
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="empresas.data.length === 0">
                                <td colspan="7" class="px-4 py-8 text-center text-slate-500">
                                    No se encontraron empresas registradas con los filtros actuales.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div
                    v-if="empresas.last_page > 1"
                    class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-4 py-3 sm:px-6"
                >
                    <div class="text-xs text-slate-500">
                        Mostrando página {{ empresas.current_page }} de {{ empresas.last_page }} ({{ empresas.total }} empresas)
                    </div>
                    <div class="flex gap-1">
                        <Link
                            v-for="(link, i) in empresas.links"
                            :key="i"
                            :href="link.url || '#'"
                            :class="[
                                'rounded px-2.5 py-1 text-xs font-medium',
                                link.active ? 'bg-blue-600 text-white' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100',
                                !link.url && 'opacity-50 cursor-not-allowed'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL CAMBIAR ESTADO (RF-002) -->
        <div v-if="showModalEstado" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-slate-200">
                <h3 class="text-lg font-bold text-slate-900">
                    Cambiar estado de servicio
                </h3>
                <p class="text-xs text-slate-500 mt-1">
                    Empresa: <strong class="text-slate-800">{{ empresaSeleccionadaEstado?.name }}</strong>
                </p>

                <form @submit.prevent="submitCambiarEstado" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Nuevo Estado</label>
                        <select
                            v-model="formEstado.estado"
                            class="mt-1 w-full rounded-lg border border-slate-300 p-2 text-sm focus:border-blue-500 focus:outline-hidden"
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
                        <label class="block text-xs font-semibold text-slate-700">
                            Motivo del cambio (Obligatorio para auditoría RF-002)
                        </label>
                        <textarea
                            v-model="formEstado.motivo"
                            rows="3"
                            placeholder="Ej. Activación tras confirmación de suscripción, o suspensión por mora..."
                            class="mt-1 w-full rounded-lg border border-slate-300 p-2 text-sm focus:border-blue-500 focus:outline-hidden"
                            required
                        ></textarea>
                        <div v-if="formEstado.errors.motivo" class="text-xs text-rose-600 mt-1">
                            {{ formEstado.errors.motivo }}
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="showModalEstado = false"
                            class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="formEstado.processing"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ formEstado.processing ? 'Guardando...' : 'Guardar Cambio' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL INVITAR ADMINISTRADOR (RF-004) -->
        <div v-if="showModalInvitacion" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-slate-200">
                <h3 class="text-lg font-bold text-slate-900">
                    Invitar Administrador de Empresa
                </h3>
                <p class="text-xs text-slate-500 mt-1">
                    Se generará un enlace de activación seguro de un solo uso para <strong class="text-slate-800">{{ empresaSeleccionadaInvitacion?.name }}</strong>.
                </p>

                <form @submit.prevent="submitInvitar" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Correo Electrónico</label>
                        <input
                            v-model="formInvitacion.email"
                            type="email"
                            required
                            placeholder="admin@empresa.com"
                            class="mt-1 w-full rounded-lg border border-slate-300 p-2 text-sm focus:border-blue-500 focus:outline-hidden"
                        />
                        <div v-if="formInvitacion.errors.email" class="text-xs text-rose-600 mt-1">
                            {{ formInvitacion.errors.email }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Nombre del Administrador (Opcional)</label>
                        <input
                            v-model="formInvitacion.nombre"
                            type="text"
                            placeholder="Juan Pérez"
                            class="mt-1 w-full rounded-lg border border-slate-300 p-2 text-sm focus:border-blue-500 focus:outline-hidden"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Rol Asignado</label>
                        <select
                            v-model="formInvitacion.rol"
                            class="mt-1 w-full rounded-lg border border-slate-300 p-2 text-sm focus:border-blue-500 focus:outline-hidden"
                        >
                            <option v-for="r in roles" :key="r.value" :value="r.value">
                                {{ r.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Vigencia del enlace</label>
                        <select
                            v-model="formInvitacion.horas_expiracion"
                            class="mt-1 w-full rounded-lg border border-slate-300 p-2 text-sm focus:border-blue-500 focus:outline-hidden"
                        >
                            <option :value="24">24 horas (1 día)</option>
                            <option :value="72">72 horas (3 días - Recomendado)</option>
                            <option :value="168">168 horas (7 días)</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="showModalInvitacion = false"
                            class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="formInvitacion.processing"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ formInvitacion.processing ? 'Generando...' : 'Generar Invitación' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

