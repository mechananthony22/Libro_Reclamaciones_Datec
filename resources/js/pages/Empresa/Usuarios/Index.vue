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
    rol: 'gestor_reclamaciones',
});

function abrirModalInvitar() {
    formInvitar.reset();
    formInvitar.rol = 'gestor_reclamaciones';
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
    router.patch(`/configuracion/usuarios/${u.id}/toggle`, {}, {
        preserveScroll: true,
    });
}

function desvincular(u: UsuarioItem) {
    if (confirm(`¿Estás seguro de desvincular a "${u.name}" de ${props.empresa.name}? Ya no tendrá acceso a este Libro.`)) {
        router.delete(`/configuracion/usuarios/${u.id}`, {
            preserveScroll: true,
        });
    }
}

function reenviarInvitacion(inv: InvitacionItem) {
    router.post(`/configuracion/usuarios/invitaciones/${inv.id}/reenviar`, {}, {
        preserveScroll: true,
    });
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
            return 'bg-purple-100 text-purple-800 border-purple-200';
        case 'gestor_reclamaciones':
            return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'lector_empresa':
            return 'bg-slate-100 text-slate-800 border-slate-200';
        default:
            return 'bg-slate-100 text-slate-700 border-slate-200';
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Usuarios y Roles (RF-011) - Datec Libro" />

        <div class="mx-auto max-w-5xl space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-md bg-blue-100 px-2.5 py-0.5 text-xs font-bold text-blue-800">RF-011</span>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                            Usuarios y Roles de la Empresa
                        </h1>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">
                        Gestiona el equipo de trabajo de <strong class="text-slate-800">{{ empresa.name }}</strong>, asigna roles de atención y administra invitaciones.
                    </p>
                </div>

                <div>
                    <button
                        type="button"
                        @click="abrirModalInvitar"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-blue-700 transition"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        <span>Invitar Usuario al Equipo</span>
                    </button>
                </div>
            </div>

            <!-- Banner con enlace de invitación recién generado -->
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
                            Enlace de activación generado exitosamente
                        </div>
                        <p class="text-xs text-blue-700 mt-0.5">
                            Comparte este enlace de activación con el usuario para que defina su contraseña y acceda al panel:
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
                            {{ copiadoExitoso ? '¡Copiado!' : 'Copiar enlace' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- REGLA DE AISLAMIENTO (CRITERIO DE ACEPTACIÓN RF-011) -->
            <div class="rounded-xl border border-purple-200 bg-purple-50/60 p-4 shadow-xs">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 text-purple-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <div class="text-xs text-purple-900 leading-relaxed">
                        <strong>Principio de Privilegio Mínimo (RF-011):</strong> Solo puedes asignar roles autorizados para tu empresa (Administrador, Gestor o Lector). No está permitido conceder roles globales ni visualizar miembros de otros tenants.
                    </div>
                </div>
            </div>

            <!-- TABLA 1: USUARIOS ACTIVOS DE LA EMPRESA -->
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs space-y-0">
                <div class="border-b border-slate-200 bg-slate-50/70 px-6 py-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            Miembros del Equipo ({{ usuarios.length }})
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Usuarios con acceso autorizado a la gestión de reclamaciones de esta empresa.
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200">
                            <tr>
                                <th scope="col" class="px-6 py-3.5">Usuario</th>
                                <th scope="col" class="px-6 py-3.5">Correo Electrónico</th>
                                <th scope="col" class="px-6 py-3.5">Rol en Empresa (RF-011)</th>
                                <th scope="col" class="px-6 py-3.5 text-center">Estado</th>
                                <th scope="col" class="px-6 py-3.5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr v-for="u in usuarios" :key="u.id" class="hover:bg-slate-50/70 transition-colors">
                                <!-- Nombre -->
                                <td class="px-6 py-3.5">
                                    <div class="font-semibold text-slate-900 flex items-center gap-2">
                                        <span>{{ u.name }}</span>
                                        <span v-if="u.id === usuarioActualId" class="rounded bg-slate-100 text-[10px] font-bold text-slate-600 px-1.5 py-0.5">
                                            Tú
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-slate-400">Vinculado el {{ u.vinculado_el }}</div>
                                </td>

                                <!-- Correo -->
                                <td class="px-6 py-3.5 font-mono text-xs text-slate-700">
                                    {{ u.email }}
                                </td>

                                <!-- Rol -->
                                <td class="px-6 py-3.5">
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold',
                                            getBadgeRol(u.rol)
                                        ]"
                                    >
                                        {{ u.rol_label }}
                                    </span>
                                </td>

                                <!-- Estado Toggle -->
                                <td class="px-6 py-3.5 text-center">
                                    <button
                                        type="button"
                                        @click="toggleActivo(u)"
                                        :disabled="u.id === usuarioActualId"
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold border transition disabled:opacity-60 disabled:cursor-not-allowed"
                                        :class="u.activo ? 'bg-emerald-50 text-emerald-800 border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200'"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="u.activo ? 'bg-emerald-600' : 'bg-slate-400'"></span>
                                        {{ u.activo ? 'Activo' : 'Desactivado' }}
                                    </button>
                                </td>

                                <!-- Acciones -->
                                <td class="px-6 py-3.5 text-right whitespace-nowrap space-x-2">
                                    <button
                                        type="button"
                                        @click="abrirModalRol(u)"
                                        class="rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                    >
                                        Cambiar rol
                                    </button>
                                    <button
                                        v-if="u.id !== usuarioActualId"
                                        type="button"
                                        @click="desvincular(u)"
                                        class="rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50"
                                    >
                                        Desvincular
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TABLA 2: INVITACIONES PENDIENTES -->
            <div v-if="invitaciones.length > 0" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
                <div class="border-b border-slate-200 bg-slate-50/70 px-6 py-4">
                    <h2 class="text-base font-bold text-slate-900">
                        Invitaciones Pendientes de Aceptación ({{ invitaciones.length }})
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Usuarios invitados que aún no han configurado su contraseña de acceso.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200">
                            <tr>
                                <th scope="col" class="px-6 py-3">Correo Destinatario</th>
                                <th scope="col" class="px-6 py-3">Rol a Asignar</th>
                                <th scope="col" class="px-6 py-3">Vencimiento del Enlace</th>
                                <th scope="col" class="px-6 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr v-for="inv in invitaciones" :key="inv.id" class="hover:bg-slate-50">
                                <td class="px-6 py-3 font-mono text-xs text-slate-800">
                                    {{ inv.email }}
                                    <span v-if="inv.nombre" class="text-slate-400 font-sans block text-[11px]">{{ inv.nombre }}</span>
                                </td>

                                <td class="px-6 py-3">
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold',
                                            getBadgeRol(inv.rol)
                                        ]"
                                    >
                                        {{ inv.rol_label }}
                                    </span>
                                </td>

                                <td class="px-6 py-3 text-xs">
                                    <span :class="inv.expirada ? 'text-rose-600 font-semibold' : 'text-slate-500'">
                                        {{ inv.expirada ? 'Expiró el ' + inv.expira_el : 'Expira el ' + inv.expira_el }}
                                    </span>
                                </td>

                                <td class="px-6 py-3 text-right whitespace-nowrap space-x-2">
                                    <button
                                        type="button"
                                        @click="copiarEnlace(inv.url)"
                                        class="rounded-md bg-blue-50 border border-blue-200 px-2.5 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100"
                                    >
                                        Copiar enlace
                                    </button>
                                    <button
                                        type="button"
                                        @click="reenviarInvitacion(inv)"
                                        class="rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                    >
                                        Renovar (+72h)
                                    </button>
                                    <button
                                        type="button"
                                        @click="cancelarInvitacion(inv)"
                                        class="rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50"
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

        <!-- MODAL INVITAR NUEVO USUARIO (RF-011) -->
        <div v-if="showModalInvitar" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl border border-slate-200 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-lg font-bold text-slate-900">
                        Invitar Usuario al Equipo (RF-011)
                    </h3>
                    <button
                        type="button"
                        @click="showModalInvitar = false"
                        class="text-slate-400 hover:text-slate-600"
                    >
                        ✕
                    </button>
                </div>

                <form @submit.prevent="submitInvitar" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Correo Electrónico *</label>
                        <input
                            v-model="formInvitar.email"
                            type="email"
                            required
                            placeholder="gestor@miempresa.com"
                            class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-hidden"
                        />
                        <div v-if="formInvitar.errors.email" class="text-xs text-rose-600 mt-1">{{ formInvitar.errors.email }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Nombre Completo (Opcional)</label>
                        <input
                            v-model="formInvitar.nombre"
                            type="text"
                            placeholder="Ana Morales"
                            class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-hidden"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-2">Rol a Asignar en la Empresa *</label>
                        <div class="space-y-2">
                            <label
                                v-for="r in roles"
                                :key="r.value"
                                class="flex items-start gap-3 rounded-xl border p-3 cursor-pointer transition"
                                :class="formInvitar.rol === r.value ? 'border-blue-600 bg-blue-50/50' : 'border-slate-200 bg-white'"
                            >
                                <input
                                    type="radio"
                                    v-model="formInvitar.rol"
                                    :value="r.value"
                                    class="h-4 w-4 mt-0.5 text-blue-600 border-slate-300 focus:ring-blue-500"
                                />
                                <div>
                                    <div class="text-xs font-bold text-slate-900">{{ r.label }}</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">{{ r.descripcion }}</div>
                                </div>
                            </label>
                        </div>
                        <div v-if="formInvitar.errors.rol" class="text-xs text-rose-600 mt-1">{{ formInvitar.errors.rol }}</div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showModalInvitar = false"
                            class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="formInvitar.processing"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ formInvitar.processing ? 'Generando...' : 'Generar Invitación' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL CAMBIAR ROL (RF-011) -->
        <div v-if="showModalRol" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-slate-200 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-lg font-bold text-slate-900">
                        Cambiar Rol de Usuario
                    </h3>
                    <button
                        type="button"
                        @click="showModalRol = false"
                        class="text-slate-400 hover:text-slate-600"
                    >
                        ✕
                    </button>
                </div>

                <p class="text-xs text-slate-500">
                    Modificando permisos de: <strong class="text-slate-900">{{ usuarioSeleccionadoRol?.name }}</strong>
                </p>

                <form @submit.prevent="submitCambiarRol" class="space-y-4">
                    <div class="space-y-2">
                        <label
                            v-for="r in roles"
                            :key="r.value"
                            class="flex items-start gap-3 rounded-xl border p-3 cursor-pointer transition"
                            :class="formRol.rol === r.value ? 'border-blue-600 bg-blue-50/50' : 'border-slate-200 bg-white'"
                        >
                            <input
                                type="radio"
                                v-model="formRol.rol"
                                :value="r.value"
                                class="h-4 w-4 mt-0.5 text-blue-600 border-slate-300 focus:ring-blue-500"
                            />
                            <div>
                                <div class="text-xs font-bold text-slate-900">{{ r.label }}</div>
                                <div class="text-[11px] text-slate-500 mt-0.5">{{ r.descripcion }}</div>
                            </div>
                        </label>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showModalRol = false"
                            class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="formRol.processing"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ formRol.processing ? 'Guardando...' : 'Actualizar Rol' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

