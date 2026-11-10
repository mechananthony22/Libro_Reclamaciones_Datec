<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface TicketItem {
    id: number;
    empresa_id: number;
    empresa_nombre: string;
    empresa_slug: string;
    codigo_ticket: string;
    titulo: string;
    motivo: string;
    estado: string;
    is_activo: boolean;
    horas_vigencia: number;
    autorizado_en: string | null;
    valido_hasta: string | null;
    solicitado_por: string;
    autorizado_por: string | null;
    created_at: string;
}

interface Props {
    tickets: {
        data: TicketItem[];
        current_page: number;
        last_page: number;
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    empresas: Array<{ id: number; name: string; slug: string }>;
    filtros: {
        empresa_id: string;
        estado: string;
    };
}

const props = defineProps<Props>();

const formFiltros = ref({
    empresa_id: props.filtros.empresa_id || '',
    estado: props.filtros.estado || '',
});

const hayFiltrosActivos = computed(() => Boolean(formFiltros.value.empresa_id || formFiltros.value.estado));

const modalSolicitudAbierto = ref(false);

const formSolicitud = useForm({
    empresa_id: '',
    titulo: '',
    motivo: '',
    horas_vigencia: 24,
});

function aplicarFiltros() {
    router.get('/admin/soporte', formFiltros.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function seleccionarEstado(valor: string) {
    formFiltros.value.estado = valor;
    aplicarFiltros();
}

function limpiarFiltros() {
    formFiltros.value = {
        empresa_id: '',
        estado: '',
    };
    aplicarFiltros();
}

function enviarSolicitud() {
    formSolicitud.post('/admin/soporte/solicitar', {
        onSuccess: () => {
            modalSolicitudAbierto.value = false;
            formSolicitud.reset();
        },
    });
}

function iniciales(nombre: string): string {
    return (nombre || 'E')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p.charAt(0).toUpperCase())
        .join('');
}

const pestanasEstado = [
    { valor: '', etiqueta: 'Todos' },
    { valor: 'pendiente', etiqueta: 'Pendientes' },
    { valor: 'autorizado', etiqueta: 'Autorizados' },
    { valor: 'revocado', etiqueta: 'Revocados' },
    { valor: 'expirado', etiqueta: 'Expirados' },
];

function estadoBadge(ticket: TicketItem) {
    if (ticket.is_activo) {
        return {
            clase: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            punto: 'bg-emerald-500',
            texto: 'Autorizado y Activo',
        };
    }
    switch (ticket.estado) {
        case 'pendiente':
            return {
                clase: 'bg-amber-50 text-amber-700 ring-amber-600/20',
                punto: 'bg-amber-500',
                texto: 'Pendiente de Aprobación',
            };
        case 'revocado':
            return {
                clase: 'bg-rose-50 text-rose-700 ring-rose-600/20',
                punto: 'bg-rose-500',
                texto: 'Acceso Revocado',
            };
        case 'expirado':
            return {
                clase: 'bg-slate-100 text-slate-600 ring-slate-400/20',
                punto: 'bg-slate-400',
                texto: 'Vigencia Expirada',
            };
        default:
            return {
                clase: 'bg-slate-100 text-slate-600 ring-slate-400/20',
                punto: 'bg-slate-400',
                texto: ticket.estado,
            };
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Soporte Técnico y Acceso Delegado - Datec Libro" />

        <div class="space-y-6">
            <!-- Cabecera de Página -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex items-start gap-4">
                    <div class="hidden sm:flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-md shadow-blue-500/25">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            Superadministrador · Asistencia Auditada
                        </div>
                        <h1 class="mt-0.5 text-2xl font-extrabold tracking-tight text-slate-900">
                            Soporte Técnico y Acceso Delegado
                        </h1>
                        <p class="mt-1 text-sm text-slate-500">
                            Control de tickets técnicos y autorizaciones temporales auditadas para asistencia a empresas clientes.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="modalSolicitudAbierto = true"
                    class="inline-flex shrink-0 cursor-pointer items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-blue-500/20 transition hover:bg-blue-700"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Solicitar Acceso Delegado</span>
                </button>
            </div>

            <!-- Aviso Normativo de Privacidad y Control de Acceso -->
            <div class="rounded-2xl border border-blue-200/80 bg-blue-50/70 p-4 shadow-2xs">
                <div class="flex gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <div class="text-xs leading-relaxed text-blue-900">
                        <p class="font-bold text-blue-950">Acceso a contenido restringido por defecto</p>
                        <p class="mt-0.5 text-[11px] text-blue-800">
                            El personal de Datec no puede consultar expedientes ni datos personales de los clientes salvo autorización expresa y temporal del cliente. Todo acceso durante una ventana concedida queda registrado en la bitácora de auditoría.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Contenedor de Listado: Filtros + Tabla + Paginación -->
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xs">
                <!-- Barra de Pestañas de Estado y Filtro por Empresa -->
                <div class="border-b border-slate-100 p-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <!-- Pestañas de estado -->
                        <div class="flex gap-1 overflow-x-auto rounded-xl bg-slate-100/80 p-1">
                            <button
                                v-for="tab in pestanasEstado"
                                :key="tab.valor"
                                type="button"
                                @click="seleccionarEstado(tab.valor)"
                                :class="[
                                    'flex shrink-0 cursor-pointer items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-bold transition',
                                    formFiltros.estado === tab.valor
                                        ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                        : 'text-slate-600 hover:bg-white hover:text-slate-900'
                                ]"
                            >
                                <span>{{ tab.etiqueta }}</span>
                            </button>
                        </div>

                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <select
                                v-model="formFiltros.empresa_id"
                                @change="aplicarFiltros"
                                class="cursor-pointer rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-700 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 sm:w-64"
                            >
                                <option value="">Todas las empresas clientes</option>
                                <option v-for="emp in empresas" :key="emp.id" :value="emp.id">
                                    {{ emp.name }}
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

                <!-- Tabla de Tickets -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            <tr>
                                <th scope="col" class="px-5 py-3">Ticket</th>
                                <th scope="col" class="px-5 py-3">Empresa</th>
                                <th scope="col" class="px-5 py-3">Asunto / Motivo</th>
                                <th scope="col" class="px-5 py-3">Estado</th>
                                <th scope="col" class="px-5 py-3">Vigencia</th>
                                <th scope="col" class="px-5 py-3">Autorizado Por</th>
                                <th scope="col" class="px-5 py-3">Fecha</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-600">
                            <tr
                                v-for="ticket in tickets.data"
                                :key="ticket.id"
                                class="transition-colors hover:bg-slate-50/70"
                            >
                                <td class="whitespace-nowrap px-5 py-3.5">
                                    <span class="rounded-lg bg-slate-100 px-2.5 py-1 font-mono text-[11px] font-bold text-slate-800">
                                        {{ ticket.codigo_ticket }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-[10px] font-black text-white">
                                            {{ iniciales(ticket.empresa_nombre) }}
                                        </div>
                                        <span class="font-bold text-slate-900">{{ ticket.empresa_nombre }}</span>
                                    </div>
                                </td>
                                <td class="max-w-xs px-5 py-3.5">
                                    <div class="truncate font-bold text-slate-900">{{ ticket.titulo }}</div>
                                    <div class="mt-0.5 line-clamp-2 text-[11px] text-slate-500">{{ ticket.motivo }}</div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold ring-1',
                                            estadoBadge(ticket).clase
                                        ]"
                                    >
                                        <span :class="['h-1.5 w-1.5 rounded-full', estadoBadge(ticket).punto]"></span>
                                        {{ estadoBadge(ticket).texto }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-[11px]">
                                    <div v-if="ticket.is_activo" class="font-bold text-emerald-700">
                                        Hasta {{ ticket.valido_hasta }}
                                    </div>
                                    <div v-else-if="ticket.valido_hasta" class="text-slate-500">
                                        Venció {{ ticket.valido_hasta }}
                                    </div>
                                    <div v-else class="italic text-slate-400">
                                        {{ ticket.horas_vigencia }}h solicitadas
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-[11px] font-semibold text-slate-700">
                                    {{ ticket.autorizado_por || '—' }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 font-mono text-[11px] text-slate-400">
                                    {{ ticket.created_at }}
                                </td>
                            </tr>

                            <tr v-if="tickets.data.length === 0">
                                <td colspan="7" class="px-5 py-14">
                                    <div class="flex flex-col items-center text-center">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                        </div>
                                        <p class="mt-3 text-sm font-bold text-slate-800">No se encontraron tickets de soporte</p>
                                        <p class="mt-1 text-xs text-slate-500">Ajusta los filtros o genera una nueva solicitud de acceso delegado.</p>
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

                <!-- Pie con Paginación -->
                <div class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50/50 px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-[11px] font-medium text-slate-500">
                        Total de registros: <span class="font-bold text-slate-700">{{ tickets.total }}</span>
                    </div>
                    <div v-if="tickets.links.length > 3" class="flex gap-1">
                        <template v-for="(link, idx) in tickets.links" :key="idx">
                            <span
                                v-if="!link.url"
                                class="min-w-8 rounded-lg px-2.5 py-1 text-center text-xs text-slate-300"
                                v-html="link.label"
                            ></span>
                            <button
                                v-else
                                type="button"
                                @click="router.get(link.url, {}, { preserveState: true, preserveScroll: true })"
                                :class="[
                                    'min-w-8 cursor-pointer rounded-lg px-2.5 py-1 text-center text-xs font-bold transition',
                                    link.active
                                        ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                        : 'text-slate-600 hover:bg-white hover:text-slate-900'
                                ]"
                                v-html="link.label"
                            ></button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para Solicitar Acceso Delegado (Estilo CreateModal.vue) -->
        <div
            v-if="modalSolicitudAbierto"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 sm:p-6 backdrop-blur-xs overflow-y-auto animate-fade-in"
            @click.self="modalSolicitudAbierto = false"
        >
            <div class="relative flex w-full max-w-lg flex-col rounded-2xl border border-slate-200 bg-white shadow-2xl overflow-hidden my-auto">
                <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-md shadow-blue-500/25">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-extrabold text-slate-900">Solicitar Acceso Delegado</h2>
                            <p class="text-xs text-slate-500">El cliente deberá autorizar la solicitud desde su panel.</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="modalSolicitudAbierto = false"
                        class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-200 hover:text-slate-700 cursor-pointer"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="enviarSolicitud" class="p-6 space-y-4 text-xs">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Empresa Cliente <span class="text-rose-500">*</span>
                        </label>
                        <select
                            v-model="formSolicitud.empresa_id"
                            required
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-800 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
                        >
                            <option value="" disabled>Seleccione una empresa</option>
                            <option v-for="emp in empresas" :key="emp.id" :value="emp.id">
                                {{ emp.name }}
                            </option>
                        </select>
                        <p v-if="formSolicitud.errors.empresa_id" class="mt-1 text-[11px] text-rose-600 font-semibold">
                            {{ formSolicitud.errors.empresa_id }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Título del Requerimiento <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="formSolicitud.titulo"
                            type="text"
                            required
                            maxlength="150"
                            placeholder="Ej. Revisión técnica de plantilla de constancia"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-800 placeholder:text-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                        <p v-if="formSolicitud.errors.titulo" class="mt-1 text-[11px] text-rose-600 font-semibold">
                            {{ formSolicitud.errors.titulo }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Motivo Técnico Justificado <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            v-model="formSolicitud.motivo"
                            rows="3"
                            required
                            maxlength="1000"
                            placeholder="Describa de manera detallada la incidencia técnica o motivo que requiere inspección..."
                            class="w-full rounded-xl border border-slate-200 bg-white p-3.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 leading-relaxed"
                        ></textarea>
                        <p v-if="formSolicitud.errors.motivo" class="mt-1 text-[11px] text-rose-600 font-semibold">
                            {{ formSolicitud.errors.motivo }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Horas de Vigencia Solicitadas <span class="text-rose-500">*</span>
                        </label>
                        <select
                            v-model="formSolicitud.horas_vigencia"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-800 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
                        >
                            <option :value="12">12 horas</option>
                            <option :value="24">24 horas (1 día)</option>
                            <option :value="48">48 horas (2 días)</option>
                            <option :value="72">72 horas (3 días)</option>
                            <option :value="168">168 horas (1 semana)</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="modalSolicitudAbierto = false"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 hover:text-slate-900 transition cursor-pointer"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="formSolicitud.processing"
                            class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-bold text-white shadow-md shadow-blue-500/20 hover:bg-blue-700 disabled:opacity-50 transition cursor-pointer"
                        >
                            <svg v-if="!formSolicitud.processing" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>{{ formSolicitud.processing ? 'Enviando...' : 'Crear Solicitud' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
