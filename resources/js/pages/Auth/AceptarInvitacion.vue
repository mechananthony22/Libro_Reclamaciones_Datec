<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

interface Props {
    token: string;
    email: string;
    nombreSugerido: string | null;
    rolLabel: string;
    empresa: {
        id: number;
        name: string;
        razon_social: string;
        ruc: string;
        slug: string;
    };
}

const props = defineProps<Props>();

const form = useForm({
    name: props.nombreSugerido || '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post(`/invitacion/${props.token}`);
}
</script>

<template>
    <div class="min-h-screen bg-slate-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8 text-slate-800">
        <Head title="Activar Cuenta de Administrador - Datec Libro" />

        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-md">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
                    <path d="M6 6h10"/>
                    <path d="M6 10h10"/>
                </svg>
            </div>
            <h2 class="mt-4 text-2xl font-bold tracking-tight text-slate-900">
                Bienvenido a Datec Libro
            </h2>
            <p class="mt-1 text-sm text-slate-600">
                Configura tu acceso para administrar el Libro de Reclamaciones de:
            </p>
            <div class="mt-2 inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-900">
                <span>{{ empresa.name }}</span>
                <span class="text-blue-400">•</span>
                <span class="font-mono">RUC {{ empresa.ruc }}</span>
            </div>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-6 shadow-md rounded-2xl border border-slate-200 sm:px-10">
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Correo Electrónico (Registrado)</label>
                        <input
                            type="email"
                            :value="email"
                            disabled
                            class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-100 p-2.5 text-sm font-medium text-slate-600 cursor-not-allowed"
                        />
                        <p class="mt-1 text-[11px] text-slate-500">Este correo será tu identificador de acceso.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Tu Nombre Completo *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="Ej. Juan Pérez"
                            class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-hidden"
                        />
                        <div v-if="form.errors.name" class="mt-1 text-xs text-rose-600">{{ form.errors.name }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Crear Contraseña *</label>
                        <input
                            v-model="form.password"
                            type="password"
                            required
                            placeholder="Mínimo 8 caracteres"
                            class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-hidden"
                        />
                        <div v-if="form.errors.password" class="mt-1 text-xs text-rose-600">{{ form.errors.password }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Confirmar Contraseña *</label>
                        <input
                            v-model="form.password_confirmation"
                            type="password"
                            required
                            placeholder="Repite tu contraseña"
                            class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-hidden"
                        />
                    </div>

                    <div class="rounded-lg bg-slate-50 p-3 text-xs text-slate-600 border border-slate-200">
                        <span class="font-semibold text-slate-800">Rol asignado:</span> {{ rolLabel }}.<br />
                        Tendrás acceso restringido y seguro exclusivamente al panel de tu empresa.
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full rounded-lg bg-blue-600 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-blue-700 disabled:opacity-50 transition"
                    >
                        {{ form.processing ? 'Activando cuenta...' : 'Activar mi cuenta y acceder' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

