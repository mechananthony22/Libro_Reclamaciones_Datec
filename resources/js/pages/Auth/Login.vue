<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const flashSuccess = computed(() => (page.props.flash as any)?.success || null);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Iniciar Sesión | DatecLibro" />

    <div class="min-h-screen bg-[#FBFDFF] flex flex-col justify-center py-12 sm:px-6 lg:px-8 relative overflow-hidden font-sans selection:bg-blue-100 selection:text-blue-700">
        <!-- Fondo Decorativo Sutil -->
        <div class="absolute -top-40 -right-40 w-96 h-96 rounded-full bg-blue-100/50 blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 rounded-full bg-indigo-100/40 blur-3xl pointer-events-none"></div>

        <div class="sm:mx-auto sm:w-full sm:max-w-md relative z-10">
            <!-- Logotipo y Marca -->
            <div class="flex justify-center">
                <Link href="/" class="flex items-center gap-2.5 group">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-500/25 group-hover:bg-blue-700 transition">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/>
                            <path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/>
                            <path d="M9 12H4s.55-3.03 2-4.5c1.62-1.63 5-1.5 5-1.5"/>
                            <path d="M15 15v5s3.03-.55 4.5-2c1.63-1.62 1.5-5 1.5-5"/>
                        </svg>
                    </div>
                    <span class="text-2xl font-extrabold tracking-tight text-slate-900">
                        Datec<span class="text-blue-600">Libro</span>
                    </span>
                </Link>
            </div>

            <h2 class="mt-6 text-center text-2xl font-extrabold tracking-tight text-slate-900">
                Iniciar Sesión en la Plataforma
            </h2>
            <p class="mt-2 text-center text-xs text-slate-500">
                Ingresa con tus credenciales de Superadministrador Datec o Administrador de Empresa
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0 relative z-10">
            <div class="rounded-3xl border border-slate-200/80 bg-white p-8 shadow-xl shadow-slate-200/50 backdrop-blur-xl">
                <!-- Mensaje de éxito flash -->
                <div v-if="flashSuccess" class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50/80 p-3.5 text-xs font-semibold text-emerald-800 flex items-center gap-2">
                    <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ flashSuccess }}</span>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Campo Correo Electrónico -->
                    <div>
                        <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Correo Electrónico
                        </label>
                        <div class="relative">
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                autocomplete="email"
                                required
                                placeholder="tu-correo@empresa.com"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 transition"
                                :class="{ 'border-rose-400 focus:border-rose-500 focus:ring-rose-500': form.errors.email }"
                            />
                        </div>
                        <p v-if="form.errors.email" class="mt-1 text-xs text-rose-600 font-medium">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Campo Contraseña -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                Contraseña
                            </label>
                        </div>
                        <div class="relative">
                            <input
                                id="password"
                                v-model="form.password"
                                type="password"
                                autocomplete="current-password"
                                required
                                placeholder="••••••••"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 transition"
                                :class="{ 'border-rose-400 focus:border-rose-500 focus:ring-rose-500': form.errors.password }"
                            />
                        </div>
                        <p v-if="form.errors.password" class="mt-1 text-xs text-rose-600 font-medium">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Recordar Sesión -->
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                            />
                            <span class="text-xs text-slate-600 font-medium select-none">
                                Recordar mi sesión
                            </span>
                        </label>
                    </div>

                    <!-- Botón de Envío -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full flex items-center justify-center gap-2 rounded-full bg-blue-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-blue-500/25 hover:bg-blue-700 transition disabled:opacity-50 cursor-pointer"
                        >
                            <span v-if="form.processing">Verificando...</span>
                            <span v-else>Entrar al Panel</span>
                            <svg v-if="!form.processing" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </div>
                </form>

                <!-- Tarjeta Informativa de Credenciales de Prueba -->
                <div class="mt-6 pt-5 border-t border-slate-100 space-y-3">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center">
                        Accesos Rápidos de Demostración
                    </p>
                    <div class="grid grid-cols-1 gap-2 text-xs">
                        <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-2.5 flex items-center justify-between">
                            <div>
                                <span class="font-bold text-slate-800 block">Superadministrador Datec:</span>
                                <span class="text-slate-500 font-mono text-[11px]">datecsuperadmin@gmail.com</span>
                            </div>
                            <button
                                type="button"
                                @click="form.email = 'datecsuperadmin@gmail.com'; form.password = 'password123'"
                                class="text-[11px] font-bold text-blue-600 hover:text-blue-800 px-2.5 py-1 bg-white border border-slate-200 rounded-lg shadow-2xs cursor-pointer"
                            >
                                Usar
                            </button>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-2.5 flex items-center justify-between">
                            <div>
                                <span class="font-bold text-slate-800 block">Admin Empresa Datec:</span>
                                <span class="text-slate-500 font-mono text-[11px]">admin@datec.pe</span>
                            </div>
                            <button
                                type="button"
                                @click="form.email = 'admin@datec.pe'; form.password = 'password123'"
                                class="text-[11px] font-bold text-blue-600 hover:text-blue-800 px-2.5 py-1 bg-white border border-slate-200 rounded-lg shadow-2xs cursor-pointer"
                            >
                                Usar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enlace Volver al Sitio -->
            <div class="text-center mt-6">
                <Link href="/" class="text-xs font-semibold text-slate-500 hover:text-slate-800 transition inline-flex items-center gap-1.5">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Regresar a la página de inicio</span>
                </Link>
            </div>
        </div>
    </div>
</template>

