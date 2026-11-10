<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface UsuarioItem {
    id: number;
    name: string;
    email: string;
    rol: string;
    rol_label: string;
    activo: boolean;
    vinculado_el: string;
}

interface InvitacionItem {
    id: number;
    email: string;
    nombre: string | null;
    rol: string;
    rol_label: string;
    token: string;
    url: string;
    expira_el: string;
    expirada: boolean;
}

interface RolOption {
    value: string;
    label: string;
    descripcion: string;
}

interface Props {
    empresa: {
        id: number;
        name: string;
        slug: string;
    };
    usuarios: UsuarioItem[];
    invitaciones: InvitacionItem[];
    roles: RolOption[];
    usuarioActualId: number | null;
}

const props = defineProps<Props>();
const page = usePage();

// Modal de Invitación
const showModalInvitar = ref(false);
const formInvitar = useForm({
    email: '',
    nombre: '',
    rol: 'admin_empresa',
});

function abrirModalInvitar() {
    formInvitar.reset();
    formInvitar.rol = 'admin_empresa';
    showModalInvitar.value = true;
}

function submitInvitar() {
    formInvitar.post('/configuracion/usuarios/invitar', {
        onSuccess: () => {
            showModalInvitar.value = false;
            formInvitar.reset();
        },
    });
}

// Modal Cambiar Rol
const showModalRol = ref(false);
const usuarioSeleccionadoRol = ref<UsuarioItem | null>(null);
const formRol = useForm({
    rol: '',
});

function abrirModalRol(u: UsuarioItem) {
    usuarioSeleccionadoRol.value = u;
    formRol.rol = u.rol;
    showModalRol.value = true;
}

function submitCambiarRol() {
    if (!usuarioSeleccionadoRol.value) return;

    formRol.put(`/configuracion/usuarios/${usuarioSeleccionadoRol.value.id}/rol`, {
        onSuccess: () => {
            showModalRol.value = false;
        },
    });
}

function toggleActivo(u: UsuarioItem) {
    router.patch(
        `/configuracion/usuarios/${u.id}/toggle`,
        {},
        {
            preserveScroll: true,
        }
    );
}

function desvincular(u: UsuarioItem) {
    if (confirm(`¿Estás seguro de desvincular a "${u.name}" de ${props.empresa.name}? Ya no tendrá acceso a este Libro.`)) {
        router.delete(`/configuracion/usuarios/${u.id}`, {
            preserveScroll: true,
        });
    }
}

function reenviarInvitacion(inv: InvitacionItem) {
    router.post(
        `/configuracion/usuarios/invitaciones/${inv.id}/reenviar`,
        {},
        {
            preserveScroll: true,
        }
    );
}

function cancelarInvitacion(inv: InvitacionItem) {
    if (confirm(`¿Deseas cancelar la invitación para ${inv.email}?`)) {
        router.delete(`/configuracion/usuarios/invitaciones/${inv.id}`, {
            preserveScroll: true,
        });
    }
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

function getBadgeRol(rol: string) {
    switch (rol) {
        case 'admin_empresa':
            return 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20';
        default:
            return 'bg-slate-100 text-slate-700 ring-1 ring-slate-400/20';
    }
}

function iniciales(nombre: string): string {
    return (nombre || 'US')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p.charAt(0).toUpperCase())
        .join('');
}

const claseEtiqueta = 'mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500';
const claseCampo =
    'w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500';
</script>

<template>
    <AdminLayout>
        <Head title="Usuarios y Roles - Datec Libro" />

        <div class="mx-auto max-w-5xl space-y-6">
            <!-- Cabecera de Página (Estilo Admin/Empresas) -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex items-start gap-4">
                    <div
                        class="hidden sm:flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-md shadow-blue-500/25"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            Configuración de Empresa · Accesos y Equipo
                        </div>
                        <h1 class="mt-0.5 text-2xl font-extrabold tracking-tight text-slate-900">
                            Usuarios y Roles de la Empresa
                        </h1>
                        <p class="mt-1 text-sm text-slate-500">
                            Gestiona el equipo de trabajo de <strong class="text-slate-800">{{ empresa.name }}</strong> y asigna accesos de gestión.
                        </p>
                    </div>
                </div>

                <div>
                    <button
                        type="button"
                        @click="abrirModalInvitar"
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-blue-500/20 transition hover:bg-blue-700"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        <span>Invitar Usuario al Equipo</span>
                    </button>
                </div>
            </div>

            <!-- Banner con enlace de invitación recién generado -->
            <div
                v-if="flashInvitacionUrl"
                class="rounded-2xl border border-blue-200/80 bg-blue-50/70 p-4 shadow-2xs"
            >
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="flex items-center gap-2 font-bold text-blue-900 text-xs">
                            <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <span>Enlace de activación generado exitosamente</span>
                        </div>
                        <p class="text-[11px] text-blue-700 mt-0.5">
                            Comparte este enlace con el usuario para que defina su contraseña y acceda al panel:
                        </p>
                        <code class="mt-2 block max-w-full overflow-x-auto rounded-xl bg-white px-3 py-1.5 text-xs text-blue-950 border border-blue-200 font-mono shadow-2xs">
                            {{ flashInvitacionUrl }}
                        </code>
                    </div>
                    <div class="shrink-0 pt-2 sm:pt-0">
                        <button
                            type="button"
                            @click="copiarEnlace(flashInvitacionUrl)"
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white shadow-md shadow-blue-500/20 hover:bg-blue-700 transition"
                        >
                            {{ copiadoExitoso ? '¡Copiado!' : 'Copiar enlace' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabla 1: Miembros Activos del Equipo (Estilo Admin/Empresas) -->
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xs">
                <div class="flex items-center justify-between border-b border-slate-100 p-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">
                                Miembros del Equipo ({{ usuarios.length }})
                            </h2>
                            <p class="text-xs text-slate-500">Usuarios con acceso autorizado a la gestión de reclamaciones de esta empresa.</p>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="py-3 px-5">Usuario</th>
                                <th class="py-3 px-5">Correo Electrónico</th>
                                <th class="py-3 px-5">Rol Asignado</th>
                                <th class="py-3 px-5 text-center">Estado</th>
                                <th class="py-3 px-5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="u in usuarios" :key="u.id" class="transition hover:bg-slate-50/60">
                                <td class="py-3.5 px-5">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-xs font-black text-slate-700 shadow-2xs">
                                            {{ iniciales(u.name) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 flex items-center gap-1.5">
                                                <span>{{ u.name }}</span>
                                                <span v-if="u.id === usuarioActualId" class="rounded-full bg-slate-100 text-[10px] font-bold text-slate-600 px-2 py-0.5">
                                                    Tú
                                                </span>
                                            </div>
                                            <div class="text-[11px] text-slate-400">Vinculado el {{ u.vinculado_el }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td class="whitespace-nowrap py-3.5 px-5 font-mono text-slate-700 font-medium">
                                    {{ u.email }}
                                </td>

                                <td class="whitespace-nowrap py-3.5 px-5">
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-bold',
                                            getBadgeRol(u.rol)
                                        ]"
                                    >
                                        {{ u.rol_label }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap py-3.5 px-5 text-center">
                                    <button
                                        type="button"
                                        @click="toggleActivo(u)"
                                        :disabled="u.id === usuarioActualId"
                                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[10px] font-bold ring-1 transition disabled:opacity-60 disabled:cursor-not-allowed"
                                        :class="u.activo ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 ring-slate-400/20 hover:bg-slate-200'"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="u.activo ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                        {{ u.activo ? 'Activo' : 'Desactivado' }}
                                    </button>
                                </td>

                                <td class="whitespace-nowrap py-3.5 px-5 text-right space-x-2">
                                    <button
                                        type="button"
                                        @click="abrirModalRol(u)"
                                        class="cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50"
                                    >
                                        Cambiar rol
                                    </button>
                                    <button
                                        v-if="u.id !== usuarioActualId"
                                        type="button"
                                        @click="desvincular(u)"
                                        class="cursor-pointer rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-700 shadow-2xs transition hover:bg-rose-100"
                                    >
                                        Desvincular
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabla 2: Invitaciones Pendientes (Estilo Admin/Empresas) -->
            <div v-if="invitaciones.length > 0" class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xs">
                <div class="flex items-center justify-between border-b border-slate-100 p-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">
                                Invitaciones Pendientes de Aceptación ({{ invitaciones.length }})
                            </h2>
                            <p class="text-xs text-slate-500">Usuarios invitados que aún no han configurado su contraseña de acceso.</p>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="py-3 px-5">Correo Destinatario</th>
                                <th class="py-3 px-5">Rol a Asignar</th>
                                <th class="py-3 px-5">Vencimiento del Enlace</th>
                                <th class="py-3 px-5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="inv in invitaciones" :key="inv.id" class="transition hover:bg-slate-50/60">
                                <td class="py-3.5 px-5">
                                    <div class="font-mono text-slate-800 font-bold">{{ inv.email }}</div>
                                    <div v-if="inv.nombre" class="text-slate-400 text-[11px]">{{ inv.nombre }}</div>
                                </td>

                                <td class="whitespace-nowrap py-3.5 px-5">
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-bold',
                                            getBadgeRol(inv.rol)
                                        ]"
                                    >
                                        {{ inv.rol_label }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap py-3.5 px-5 text-slate-600">
                                    {{ inv.expira_el }}
                                </td>

                                <td class="whitespace-nowrap py-3.5 px-5 text-right space-x-2">
                                    <button
                                        type="button"
                                        @click="copiarEnlace(inv.url)"
                                        class="cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50"
                                    >
                                        Copiar enlace
                                    </button>
                                    <button
                                        type="button"
                                        @click="reenviarInvitacion(inv)"
                                        class="cursor-pointer rounded-xl border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700 shadow-2xs transition hover:bg-blue-100"
                                    >
                                        Reenviar
                                    </button>
                                    <button
                                        type="button"
                                        @click="cancelarInvitacion(inv)"
                                        class="cursor-pointer rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-700 shadow-2xs transition hover:bg-rose-100"
                                    >
                                        Cancelar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal 1: Invitar Usuario (Estilo CreateModal.vue) -->
        <div
            v-if="showModalInvitar"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 sm:p-6 backdrop-blur-xs overflow-y-auto animate-fade-in"
            @click.self="showModalInvitar = false"
        >
            <div class="relative flex w-full max-w-lg flex-col rounded-2xl border border-slate-200 bg-white shadow-2xl overflow-hidden my-auto">
                <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-md shadow-blue-500/25">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-extrabold text-slate-900">Invitar Miembro al Equipo</h2>
                            <p class="text-xs text-slate-500">Se generará un enlace seguro de activación con vigencia de 72 horas.</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showModalInvitar = false"
                        class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-200 hover:text-slate-700 cursor-pointer"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitInvitar" class="p-6 space-y-4 text-xs">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Correo Electrónico <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="formInvitar.email"
                            type="email"
                            required
                            placeholder="colaborador@empresa.pe"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-800 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                        <p v-if="formInvitar.errors.email" class="mt-1 text-[11px] text-rose-600 font-semibold">{{ formInvitar.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Nombre Completo <span class="text-slate-400 font-normal">(Opcional)</span>
                        </label>
                        <input
                            v-model="formInvitar.nombre"
                            type="text"
                            placeholder="Ej. Juan Pérez"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                        <p v-if="formInvitar.errors.nombre" class="mt-1 text-[11px] text-rose-600 font-semibold">{{ formInvitar.errors.nombre }}</p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Rol de Acceso <span class="text-rose-500">*</span>
                        </label>
                        <select
                            v-model="formInvitar.rol"
                            required
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-800 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
                        >
                            <option v-for="r in roles" :key="r.value" :value="r.value">
                                {{ r.label }} — {{ r.descripcion }}
                            </option>
                        </select>
                        <p v-if="formInvitar.errors.rol" class="mt-1 text-[11px] text-rose-600 font-semibold">{{ formInvitar.errors.rol }}</p>
                    </div>

                    <div class="rounded-xl border border-blue-200/80 bg-blue-50/60 p-3.5 flex items-start gap-3">
                        <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-blue-100 text-blue-700 mt-0.5">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="text-[11px] text-blue-900 leading-relaxed">
                            Al confirmar, obtendrás el enlace de invitación para compartirlo con el colaborador y que configure su contraseña de acceso.
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showModalInvitar = false"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 hover:text-slate-900 transition cursor-pointer"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="formInvitar.processing"
                            class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-bold text-white shadow-md shadow-blue-500/20 hover:bg-blue-700 disabled:opacity-50 transition cursor-pointer"
                        >
                            <svg v-if="!formInvitar.processing" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>{{ formInvitar.processing ? 'Generando...' : 'Generar Invitación' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal 2: Cambiar Rol de Usuario -->
        <div
            v-if="showModalRol && usuarioSeleccionadoRol"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 sm:p-6 backdrop-blur-xs overflow-y-auto animate-fade-in"
            @click.self="showModalRol = false"
        >
            <div class="relative flex w-full max-w-md flex-col rounded-2xl border border-slate-200 bg-white shadow-2xl overflow-hidden my-auto">
                <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-md shadow-indigo-500/25">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-extrabold text-slate-900">Modificar Rol</h2>
                            <p class="text-xs text-slate-500">{{ usuarioSeleccionadoRol.name }} · <span class="font-mono text-slate-700">{{ usuarioSeleccionadoRol.email }}</span></p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showModalRol = false"
                        class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-200 hover:text-slate-700 cursor-pointer"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitCambiarRol" class="p-6 space-y-4 text-xs">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Selecciona el nuevo rol <span class="text-rose-500">*</span>
                        </label>
                        <select
                            v-model="formRol.rol"
                            required
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-800 transition focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer"
                        >
                            <option v-for="r in roles" :key="r.value" :value="r.value">
                                {{ r.label }} — {{ r.descripcion }}
                            </option>
                        </select>
                        <p v-if="formRol.errors.rol" class="mt-1 text-[11px] text-rose-600 font-semibold">{{ formRol.errors.rol }}</p>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showModalRol = false"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 hover:text-slate-900 transition cursor-pointer"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="formRol.processing"
                            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-md shadow-indigo-500/20 hover:bg-indigo-700 disabled:opacity-50 transition cursor-pointer"
                        >
                            <span>{{ formRol.processing ? 'Actualizando...' : 'Actualizar Rol' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
