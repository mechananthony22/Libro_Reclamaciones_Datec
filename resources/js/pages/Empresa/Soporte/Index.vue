<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface TicketItem {
    id: number;
    codigo_ticket: string;
    titulo: string;
    motivo: string;
    estado: string;
    is_activo: boolean;
    horas_vigencia: number;
    autorizado_en: string | null;
    valido_hasta: string | null;
    revocado_en: string | null;
    motivo_revocacion: string | null;
    solicitado_por: string;
    autorizado_por: string | null;
    created_at: string;
}

interface Props {
    tickets: TicketItem[];
    autorizacionVigente: {
        codigo_ticket: string;
        titulo: string;
        valido_hasta: string;
        horas_restantes: number;
    } | null;
}

const props = defineProps<Props>();

const modalNuevoTicket = ref(false);
const modalRevocar = ref(false);
const ticketSeleccionadoParaRevocar = ref<TicketItem | null>(null);

const formNuevoTicket = useForm({
    titulo: '',
    motivo: '',
    horas_vigencia: 24,
    conceder_acceso_inmediato: false,
});

const formAutorizar = useForm({
    horas_vigencia: 24,
});

const formRevocar = useForm({
    motivo_revocacion: '',
});

function enviarNuevoTicket() {
    formNuevoTicket.post('/configuracion/soporte', {
        onSuccess: () => {
            modalNuevoTicket.value = false;
            formNuevoTicket.reset();
        },
    });
}

function autorizarTicket(ticketId: number, horas: number = 24) {
    formAutorizar.horas_vigencia = horas;
    formAutorizar.patch(`/configuracion/soporte/${ticketId}/autorizar`, {
        preserveScroll: true,
    });
}

function abrirModalRevocar(ticket: TicketItem) {
    ticketSeleccionadoParaRevocar.value = ticket;
    formRevocar.motivo_revocacion = '';
    modalRevocar.value = true;
}

function confirmarRevocacion() {
    if (!ticketSeleccionadoParaRevocar.value) return;

    formRevocar.patch(`/configuracion/soporte/${ticketSeleccionadoParaRevocar.value.id}/revocar`, {
        onSuccess: () => {
            modalRevocar.value = false;
            ticketSeleccionadoParaRevocar.value = null;
        },
    });
}

function estadoBadge(ticket: TicketItem) {
    if (ticket.is_activo) {
        return {
            clase: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 font-bold',
            texto: 'Acceso Concedido',
        };
    }
    switch (ticket.estado) {
        case 'pendiente':
            return {
                clase: 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20 font-semibold',
                texto: 'Pendiente de Autorización',
            };
        case 'revocado':
            return {
                clase: 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20 font-semibold',
                texto: 'Revocado',
            };
        case 'expirado':
            return {
                clase: 'bg-slate-100 text-slate-600 ring-1 ring-slate-400/20 font-medium',
                texto: 'Expirado',
            };
        default:
            return {
                clase: 'bg-slate-100 text-slate-600 ring-1 ring-slate-400/20',
                texto: ticket.estado,
            };
    }
}

const claseEtiqueta = 'mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500';
const claseCampo = 'w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-800 placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500';
</script>

<template>
    <AdminLayout>
        <Head title="Acceso de Soporte y Privacidad | DatecLibro" />

        <div class="space-y-6">
            <!-- Cabecera de Página (Estilo Admin/Empresas) -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex items-start gap-4">
                    <div
                        class="hidden sm:flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-md shadow-blue-500/25"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            Configuración de Empresa · Privacidad y Control Delegado
                        </div>
                        <h1 class="mt-0.5 text-2xl font-extrabold tracking-tight text-slate-900">
                            Acceso de Soporte y Privacidad
                        </h1>
                        <p class="mt-1 text-sm text-slate-500">
                            Controle y audite de manera estricta cuándo el equipo de Datec puede acceder a sus datos técnicos.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="modalNuevoTicket = true"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm shadow-blue-500/20 hover:bg-blue-700 transition"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Crear Ticket de Soporte</span>
                    </button>
                </div>
            </div>

            <!-- Estado de Acceso Delegado Actual (Banner visual) -->
            <div
                v-if="autorizacionVigente"
                class="rounded-2xl border border-amber-200/80 bg-amber-50/70 p-5 shadow-2xs"
            >
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white shadow-sm">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-bold text-amber-950">
                                    Acceso Delegado a Soporte ACTIVO
                                </h3>
                                <span class="rounded-full bg-amber-200 px-2.5 py-0.5 text-[10px] font-black text-amber-900">
                                    Ticket {{ autorizacionVigente.codigo_ticket }}
                                </span>
                            </div>
                            <p class="text-xs text-amber-900/80 mt-1 leading-relaxed">
                                El equipo de soporte técnico Datec tiene permiso para inspeccionar temporalmente incidencias. Expira en <strong>{{ autorizacionVigente.horas_restantes }} horas</strong> ({{ autorizacionVigente.valido_hasta }}).
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="abrirModalRevocar(tickets.find(t => t.codigo_ticket === autorizacionVigente?.codigo_ticket) || tickets[0])"
                        class="shrink-0 rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-rose-700 transition"
                    >
                        Revocar Acceso Inmediatamente
                    </button>
                </div>
            </div>

            <div
                v-else
                class="rounded-2xl border border-emerald-200/80 bg-emerald-50/70 p-5 shadow-2xs"
            >
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-emerald-950">
                                Privacidad y Blindaje Activo
                            </h3>
                            <p class="text-xs text-emerald-900/80 mt-0.5 leading-relaxed">
                                Ningún técnico de Datec puede acceder al contenido de sus reclamaciones ni a la información confidencial de sus clientes. El acceso solo se habilita si usted lo autoriza de forma expresa.
                            </p>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1.5 self-start sm:self-center rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-bold text-emerald-800">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Blindaje Activo
                    </span>
                </div>
            </div>

            <!-- Tabla de Historial de Tickets y Solicitudes -->
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xs">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 px-5 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-500/20">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Historial de Tickets y Accesos</h2>
                            <p class="text-xs text-slate-500">Registro histórico de solicitudes de soporte y autorizaciones concedidas.</p>
                        </div>
                    </div>

                    <span class="text-xs font-semibold text-slate-400">
                        {{ tickets.length }} tickets registrados
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-left text-xs">
                        <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-3.5">Ticket</th>
                                <th class="px-5 py-3.5">Asunto / Motivo</th>
                                <th class="px-5 py-3.5">Estado</th>
                                <th class="px-5 py-3.5">Vigencia</th>
                                <th class="px-5 py-3.5">Solicitado Por</th>
                                <th class="px-5 py-3.5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                            <tr v-if="tickets.length === 0">
                                <td colspan="6" class="px-5 py-10 text-center text-slate-400 italic">
                                    No hay tickets de soporte registrados en su empresa.
                                </td>
                            </tr>
                            <tr
                                v-for="ticket in tickets"
                                :key="ticket.id"
                                class="hover:bg-slate-50/70 transition"
                            >
                                <td class="px-5 py-3.5 font-mono font-bold text-slate-900 whitespace-nowrap">
                                    {{ ticket.codigo_ticket }}
                                </td>
                                <td class="px-5 py-3.5 max-w-sm">
                                    <div class="font-bold text-slate-900">{{ ticket.titulo }}</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5 line-clamp-2">{{ ticket.motivo }}</div>
                                    <div v-if="ticket.revocado_en" class="text-[10px] text-rose-600 mt-1 font-semibold">
                                        Revocado el {{ ticket.revocado_en }}: {{ ticket.motivo_revocacion || 'Sin motivo especificado' }}
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px]',
                                            estadoBadge(ticket).clase
                                        ]"
                                    >
                                        {{ estadoBadge(ticket).texto }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-[11px]">
                                    <div v-if="ticket.is_activo" class="text-emerald-700 font-bold">
                                        Hasta {{ ticket.valido_hasta }}
                                    </div>
                                    <div v-else-if="ticket.valido_hasta" class="text-slate-500">
                                        Venció {{ ticket.valido_hasta }}
                                    </div>
                                    <div v-else class="text-slate-400 italic">
                                        {{ ticket.horas_vigencia }}h requeridas
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-[11px] text-slate-500">
                                    <div class="font-semibold text-slate-700">{{ ticket.solicitado_por }}</div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">{{ ticket.created_at }}</div>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-right text-xs">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            v-if="ticket.is_activo"
                                            type="button"
                                            @click="abrirModalRevocar(ticket)"
                                            class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-[11px] font-bold text-rose-700 hover:bg-rose-100 transition shadow-2xs"
                                        >
                                            Revocar
                                        </button>
                                        <div v-else-if="ticket.estado === 'pendiente'" class="flex items-center gap-1.5">
                                            <button
                                                type="button"
                                                @click="autorizarTicket(ticket.id, 24)"
                                                class="rounded-xl bg-emerald-600 px-3 py-1.5 text-[11px] font-bold text-white shadow-2xs hover:bg-emerald-700 transition"
                                            >
                                                Autorizar 24h
                                            </button>
                                            <button
                                                type="button"
                                                @click="autorizarTicket(ticket.id, 48)"
                                                class="rounded-xl border border-slate-200 bg-slate-100 px-2.5 py-1.5 text-[11px] font-bold text-slate-700 hover:bg-slate-200 transition"
                                            >
                                                48h
                                            </button>
                                        </div>
                                        <span v-else class="text-slate-300 text-xs px-2">—</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal para Crear Nuevo Ticket (Estilo CreateModal.vue) -->
        <div
            v-if="modalNuevoTicket"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 sm:p-6 backdrop-blur-xs overflow-y-auto animate-fade-in"
            @click.self="modalNuevoTicket = false"
        >
            <div class="relative flex w-full max-w-lg flex-col rounded-2xl border border-slate-200 bg-white shadow-2xl overflow-hidden my-auto">
                <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-md shadow-blue-500/25">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-extrabold text-slate-900">
                                Crear Ticket de Asistencia
                            </h2>
                            <p class="text-xs text-slate-500">Solicitud técnica formal a la mesa de ayuda de Datec.</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="modalNuevoTicket = false"
                        class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-200 hover:text-slate-700 cursor-pointer"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="enviarNuevoTicket" class="p-6 space-y-4 text-xs">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Título del Requerimiento <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="formNuevoTicket.titulo"
                            type="text"
                            required
                            maxlength="150"
                            placeholder="Ej. Asistencia con configuración de certificado o constancia"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-800 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                        <p v-if="formNuevoTicket.errors.titulo" class="text-rose-600 text-[11px] mt-1 font-semibold">
                            {{ formNuevoTicket.errors.titulo }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Descripción Detallada <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            v-model="formNuevoTicket.motivo"
                            rows="4"
                            required
                            maxlength="1000"
                            placeholder="Explique el comportamiento observado, los pasos para reproducir o la asistencia que necesita..."
                            class="w-full rounded-xl border border-slate-200 bg-white p-3.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 leading-relaxed"
                        ></textarea>
                        <p v-if="formNuevoTicket.errors.motivo" class="text-rose-600 text-[11px] mt-1 font-semibold">
                            {{ formNuevoTicket.errors.motivo }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-blue-200/80 bg-blue-50/60 p-4 space-y-3">
                        <label class="flex items-center gap-2.5 cursor-pointer select-none">
                            <input
                                v-model="formNuevoTicket.conceder_acceso_inmediato"
                                type="checkbox"
                                class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                            />
                            <span class="font-bold text-slate-900 text-xs">
                                Conceder acceso delegado a Datec de forma inmediata
                            </span>
                        </label>
                        <p class="text-[11px] text-blue-900 leading-relaxed pl-6.5">
                            Permite a los ingenieros de soporte revisar su caso sin esperar una aprobación posterior. Todo queda auditado en la bitácora inmutable.
                        </p>

                        <div v-if="formNuevoTicket.conceder_acceso_inmediato" class="pt-2 pl-6.5">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-blue-900 mb-1.5">
                                Duración del acceso temporal:
                            </label>
                            <select
                                v-model="formNuevoTicket.horas_vigencia"
                                class="w-full sm:w-64 rounded-xl border border-blue-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-800 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
                            >
                                <option :value="12">12 horas</option>
                                <option :value="24">24 horas (Recomendado)</option>
                                <option :value="48">48 horas</option>
                                <option :value="72">72 horas</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="modalNuevoTicket = false"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 hover:text-slate-900 transition cursor-pointer"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="formNuevoTicket.processing"
                            class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-bold text-white shadow-md shadow-blue-500/20 hover:bg-blue-700 disabled:opacity-50 transition cursor-pointer"
                        >
                            <svg v-if="!formNuevoTicket.processing" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>{{ formNuevoTicket.processing ? 'Enviando...' : 'Registrar Ticket' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal para Revocar Acceso (Estilo CreateModal.vue) -->
        <div
            v-if="modalRevocar"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 sm:p-6 backdrop-blur-xs overflow-y-auto animate-fade-in"
            @click.self="modalRevocar = false"
        >
            <div class="relative flex w-full max-w-md flex-col rounded-2xl border border-slate-200 bg-white shadow-2xl overflow-hidden my-auto">
                <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-600 text-white shadow-md shadow-rose-500/25">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-extrabold text-slate-900">
                                Revocar Acceso de Soporte
                            </h2>
                            <p class="text-xs text-slate-500">Ticket: <strong class="font-mono text-slate-800">{{ ticketSeleccionadoParaRevocar?.codigo_ticket }}</strong></p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="modalRevocar = false"
                        class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-200 hover:text-slate-700 cursor-pointer"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-4 text-xs">
                    <div class="rounded-xl border border-rose-200/80 bg-rose-50/70 p-3.5 text-rose-900 leading-relaxed text-xs">
                        ¿Está seguro de revocar de inmediato el acceso de soporte técnico?
                        El personal de Datec perderá acceso instantáneamente a la cuenta de su empresa.
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Motivo de Revocación <span class="text-slate-400 font-normal">(Opcional)</span>
                        </label>
                        <input
                            v-model="formRevocar.motivo_revocacion"
                            type="text"
                            maxlength="255"
                            placeholder="Ej. Incidencia resuelta satisfactoriamente"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-800 transition focus:border-rose-500 focus:outline-none focus:ring-1 focus:ring-rose-500"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="modalRevocar = false"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 hover:text-slate-900 transition cursor-pointer"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            @click="confirmarRevocacion"
                            :disabled="formRevocar.processing"
                            class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-5 py-2.5 text-xs font-bold text-white shadow-md shadow-rose-500/20 hover:bg-rose-700 disabled:opacity-50 transition cursor-pointer"
                        >
                            <span>{{ formRevocar.processing ? 'Revocando...' : 'Revocar Acceso Ahora' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
