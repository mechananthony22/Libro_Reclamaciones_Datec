<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const flash = computed(() => (page.props.flash as any) || {});
const auth = computed(() => (page.props.auth as any) || {});

const empresaActual = computed(() => auth.value.empresa_actual);
const empresasUsuario = computed(() => (auth.value.empresas_usuario as any[]) || []);

function cambiarTenant(event: Event) {
    const target = event.target as HTMLSelectElement;
    const empresaId = parseInt(target.value);
    if (empresaId && (!empresaActual.value || empresaId !== empresaActual.value.id)) {
        router.post('/cambiar-empresa', { empresa_id: empresaId }, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-800 antialiased">
        <!-- Top Navbar -->
        <header class="border-b border-slate-200 bg-white sticky top-0 z-30 shadow-xs">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-6">
                    <Link href="/admin/empresas" class="flex items-center gap-2 font-bold text-lg text-blue-600 hover:text-blue-700">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
                            <path d="M6 6h10"/>
                            <path d="M6 10h10"/>
                        </svg>
                        <span>Datec Libro</span>
                    </Link>

                    <nav class="hidden md:flex items-center gap-1">
                        <Link
                            href="/admin/empresas"
                            :class="[
                                'rounded-md px-3 py-2 text-xs font-semibold transition-colors',
                                page.url.startsWith('/admin/empresas')
                                    ? 'bg-blue-50 text-blue-700'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                            ]"
                        >
                            Empresas Datec
                        </Link>
                        <Link
                            href="/admin/monitoreo"
                            :class="[
                                'rounded-md px-3 py-2 text-xs font-semibold transition-colors',
                                page.url.startsWith('/admin/monitoreo')
                                    ? 'bg-blue-50 text-blue-700'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                            ]"
                        >
                            Monitoreo 
                        </Link>

                        <span class="text-slate-300 px-1">|</span>

                        <Link
                            href="/configuracion/empresa"
                            :class="[
                                'rounded-md px-3 py-2 text-xs font-semibold transition-colors',
                                page.url.startsWith('/configuracion') && !page.url.startsWith('/configuracion/usuarios')
                                    ? 'bg-blue-50 text-blue-700'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                            ]"
                        >
                            Configuración Empresa
                        </Link>
                        <Link
                            href="/configuracion/usuarios"
                            :class="[
                                'rounded-md px-3 py-2 text-xs font-semibold transition-colors',
                                page.url.startsWith('/configuracion/usuarios')
                                    ? 'bg-blue-50 text-blue-700'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                            ]"
                        >
                            Usuarios y Roles 
                        </Link>
                    </nav>
                </div>

                <div class="flex items-center gap-4">
                    <!-- SELECTOR DE EMPRESA MULTITENANT (RF-012) -->
                    <div v-if="empresasUsuario && empresasUsuario.length > 1" class="flex items-center gap-2">
                        <label class="hidden lg:block text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                            Empresa activa 
                        </label>
                        <select
                            :value="empresaActual?.id"
                            @change="cambiarTenant"
                            class="rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-800 shadow-2xs focus:border-blue-500 focus:outline-hidden"
                        >
                            <option
                                v-for="emp in empresasUsuario"
                                :key="emp.id"
                                :value="emp.id"
                            >
                                {{ emp.name }}
                            </option>
                        </select>
                    </div>
                    <div v-else-if="empresaActual" class="hidden sm:flex items-center gap-1.5 rounded-full bg-slate-100 border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">
                        <span
                            class="h-2 w-2 rounded-full"
                            :style="{ backgroundColor: empresaActual.color_primario || '#2563EB' }"
                        ></span>
                        <span>{{ empresaActual.name }}</span>
                    </div>

                    <Link
                        href="/"
                        class="text-xs text-slate-500 hover:text-slate-800 underline"
                    >
                        Portal
                    </Link>
                </div>
            </div>
        </header>

        <!-- Flash alerts -->
        <div class="mx-auto max-w-7xl px-4 pt-4 sm:px-6 lg:px-8" v-if="flash.success || flash.error || flash.invitacion_url">
            <div
                v-if="flash.success"
                class="mb-4 flex items-center justify-between rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800"
            >
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ flash.success }}</span>
                </div>
            </div>

            <div
                v-if="flash.error"
                class="mb-4 flex items-center justify-between rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800"
            >
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ flash.error }}</span>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <slot />
        </main>
    </div>
</template>
