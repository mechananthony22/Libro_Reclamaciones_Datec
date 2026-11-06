<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const flash = computed(() => (page.props.flash as any) || {});
const auth = computed(() => (page.props.auth as any) || {});
const usuario = computed(() => auth.value.user);
const esSuperAdmin = computed(() => !!usuario.value?.is_superadmin);

const empresaActual = computed(() => auth.value.empresa_actual);
const empresasUsuario = computed(() => (auth.value.empresas_usuario as any[]) || []);

// Control de apertura del sidebar en dispositivos móviles
const sidebarMovilAbierto = ref(false);

// Cambio dinámico de contexto multitenant para el usuario autenticado
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
    <div class="min-h-screen bg-[#F8FAFC] text-slate-800 antialiased font-sans selection:bg-blue-100 selection:text-blue-700 flex flex-col lg:flex-row">
        <!-- Overlay backdrop para móvil -->
        <div
            v-if="sidebarMovilAbierto"
            @click="sidebarMovilAbierto = false"
            class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-xs lg:hidden transition-opacity"
        ></div>

        <!-- Barra Superior Móvil (Header solo para pantallas pequeñas) -->
        <header class="lg:hidden sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 shadow-2xs">
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    @click="sidebarMovilAbierto = !sidebarMovilAbierto"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100 focus:outline-none"
                    aria-label="Alternar menú lateral"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <Link href="/" class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-white shadow-xs">
                        <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/>
                            <path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/>
                        </svg>
                    </div>
                    <span class="text-base font-extrabold tracking-tight text-slate-900">
                        Datec<span class="text-blue-600">Libro</span>
                    </span>
                </Link>
            </div>

            <div v-if="empresaActual" class="flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-700">
                <span class="h-1.5 w-1.5 rounded-full" :style="{ backgroundColor: empresaActual.color_primario || '#2563EB' }"></span>
                <span class="truncate max-w-[110px]">{{ empresaActual.name }}</span>
            </div>
        </header>

        <!-- SIDEBAR LATERAL (Fijo en desktop, desplegable en móvil) -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-slate-200/90 bg-white transition-transform duration-200 ease-in-out lg:static lg:translate-x-0 shrink-0 shadow-sm lg:shadow-none',
                sidebarMovilAbierto ? 'translate-x-0' : '-translate-x-full'
            ]"
        >
            <!-- Cabecera del Sidebar con Logotipo y Marca -->
            <div class="flex h-20 items-center justify-between px-6 border-b border-slate-100">
                <Link href="/" class="flex items-center gap-3 group">
                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-md shadow-blue-500/25 group-hover:bg-blue-700 transition">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/>
                            <path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/>
                            <path d="M9 12H4s.55-3.03 2-4.5c1.62-1.63 5-1.5 5-1.5"/>
                            <path d="M15 15v5s3.03-.55 4.5-2c1.63-1.62 1.5-5 1.5-5"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-lg font-extrabold tracking-tight text-slate-900 block leading-tight">
                            Datec<span class="text-blue-600">Libro</span>
                        </span>
                        <span class="text-[10px] font-semibold text-slate-400 tracking-wider uppercase">
                            {{ esSuperAdmin ? 'Panel Superadmin' : 'Panel de Gestión' }}
                        </span>
                    </div>
                </Link>

                <!-- Botón de cerrar sidebar en móvil -->
                <button
                    type="button"
                    @click="sidebarMovilAbierto = false"
                    class="lg:hidden rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Selector de Contexto Multitenant (Empresa Activa) -->
            <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60">
                <div class="text-[10px] font-bold tracking-wider text-slate-400 uppercase mb-2">
                    Empresa Activa
                </div>

                <div v-if="empresasUsuario && empresasUsuario.length > 1" class="relative">
                    <select
                        :value="empresaActual?.id"
                        @change="cambiarTenant"
                        class="w-full appearance-none rounded-xl border border-slate-200 bg-white py-2 pl-3 pr-8 text-xs font-bold text-slate-800 shadow-2xs hover:border-slate-300 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
                    >
                        <option
                            v-for="emp in empresasUsuario"
                            :key="emp.id"
                            :value="emp.id"
                        >
                            {{ emp.name }}
                        </option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-slate-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                        </svg>
                    </div>
                </div>

                <div v-else-if="empresaActual" class="flex items-center gap-2.5 rounded-xl border border-slate-200/80 bg-white p-2.5 shadow-2xs">
                    <span
                        class="h-3 w-3 rounded-full shrink-0 shadow-2xs"
                        :style="{ backgroundColor: empresaActual.color_primario || '#2563EB' }"
                    ></span>
                    <div class="min-w-0 flex-1">
                        <div class="text-xs font-bold text-slate-800 truncate">{{ empresaActual.name }}</div>
                        <div class="text-[10px] text-slate-400 truncate">{{ empresaActual.slug }}</div>
                    </div>
                    <Link
                        :href="`/l/${empresaActual.slug}`"
                        target="_blank"
                        title="Ver Libro Público de Reclamaciones"
                        class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-blue-600 transition"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </Link>
                </div>

                <div v-else class="text-xs text-slate-500 italic">
                    Sin empresa seleccionada
                </div>
            </div>

            <!-- Navegación del Menú Lateral -->
            <nav class="flex-1 space-y-6 overflow-y-auto px-4 py-5">
                <!-- Bloque Superadmin Datec (solo visible para superadministradores) -->
                <div v-if="esSuperAdmin" class="space-y-1">
                    <div class="px-3 pb-1 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        Superadministrador
                    </div>

                    <Link
                        href="/admin/empresas"
                        :class="[
                            'group flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-bold transition',
                            page.url.startsWith('/admin/empresas')
                                ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                        ]"
                    >
                        <svg class="h-4.5 w-4.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span>Empresas Clientes</span>
                    </Link>

                    <Link
                        href="/admin/monitoreo"
                        :class="[
                            'group flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-bold transition',
                            page.url.startsWith('/admin/monitoreo')
                                ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                        ]"
                    >
                        <svg class="h-4.5 w-4.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span>Monitoreo Global</span>
                    </Link>

                    <Link
                        href="/admin/auditoria"
                        :class="[
                            'group flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-bold transition',
                            page.url.startsWith('/admin/auditoria')
                                ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                        ]"
                    >
                        <svg class="h-4.5 w-4.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <span>Bitácora de Auditoría</span>
                    </Link>
                </div>

                <!-- Bloque Gestión de la Empresa Activa -->
                <div class="space-y-1">
                    <div class="px-3 pb-1 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        Atención de Reclamos
                    </div>

                    <Link
                        href="/dashboard"
                        :class="[
                            'group flex items-center justify-between rounded-xl px-3.5 py-2.5 text-xs font-bold transition',
                            page.url === '/dashboard' || page.url.startsWith('/dashboard?')
                                ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                        ]"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="h-4.5 w-4.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span>Dashboard e Indicadores</span>
                        </div>
                        <span
                            v-if="page.url === '/dashboard' || page.url.startsWith('/dashboard?')"
                            class="h-2 w-2 rounded-full bg-white animate-pulse"
                        ></span>
                    </Link>

                    <Link
                        href="/expedientes"
                        :class="[
                            'group flex items-center justify-between rounded-xl px-3.5 py-2.5 text-xs font-bold transition',
                            page.url.startsWith('/expedientes')
                                ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                        ]"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="h-4.5 w-4.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Bandeja de Reclamos</span>
                        </div>
                        <span
                            v-if="page.url.startsWith('/expedientes')"
                            class="h-2 w-2 rounded-full bg-white animate-pulse"
                        ></span>
                    </Link>
                </div>

                <!-- Bloque Configuración de Empresa -->
                <div class="space-y-1">
                    <div class="px-3 pb-1 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        Configuración de Empresa
                    </div>

                    <Link
                        href="/configuracion/empresa"
                        :class="[
                            'group flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-bold transition',
                            page.url === '/configuracion/empresa'
                                ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                        ]"
                    >
                        <svg class="h-4.5 w-4.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span>Identidad Legal</span>
                    </Link>

                    <Link
                        href="/configuracion/personalizacion"
                        :class="[
                            'group flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-bold transition',
                            page.url === '/configuracion/personalizacion'
                                ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                        ]"
                    >
                        <svg class="h-4.5 w-4.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                        </svg>
                        <span>Logo y Colores</span>
                    </Link>

                    <Link
                        href="/configuracion/establecimientos"
                        :class="[
                            'group flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-bold transition',
                            page.url === '/configuracion/establecimientos'
                                ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                        ]"
                    >
                        <svg class="h-4.5 w-4.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>Establecimientos / Sedes</span>
                    </Link>

                    <Link
                        href="/configuracion/usuarios"
                        :class="[
                            'group flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-bold transition',
                            page.url.startsWith('/configuracion/usuarios')
                                ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                        ]"
                    >
                        <svg class="h-4.5 w-4.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span>Usuarios y Roles</span>
                    </Link>

                    <Link
                        href="/configuracion/auditoria"
                        :class="[
                            'group flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-bold transition',
                            page.url.startsWith('/configuracion/auditoria')
                                ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                        ]"
                    >
                        <svg class="h-4.5 w-4.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Historial de Auditoría</span>
                    </Link>
                </div>

                <!-- Enlace al Libro de Reclamaciones Público (Vista Previa) -->
                <div v-if="empresaActual" class="space-y-1">
                    <div class="px-3 pb-1 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        Atención al Cliente
                    </div>

                    <Link
                        :href="`/l/${empresaActual.slug}`"
                        target="_blank"
                        class="group flex items-center justify-between rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-600 hover:bg-blue-50 hover:text-blue-700 transition"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="h-4.5 w-4.5 shrink-0 text-slate-400 group-hover:text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <span>Libro Público</span>
                        </div>
                        <svg class="h-3.5 w-3.5 text-slate-400 group-hover:text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </Link>

                    <Link
                        :href="`/l/${empresaActual.slug}/consultar`"
                        target="_blank"
                        class="group flex items-center justify-between rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-600 hover:bg-blue-50 hover:text-blue-700 transition"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="h-4.5 w-4.5 shrink-0 text-slate-400 group-hover:text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <span>Consultar Reclamo</span>
                        </div>
                        <svg class="h-3.5 w-3.5 text-slate-400 group-hover:text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </Link>
                </div>
            </nav>

            <!-- Pie del Sidebar: Perfil del Usuario y Cerrar Sesión -->
            <div class="border-t border-slate-100 p-4 bg-slate-50/50">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-200 text-slate-700 font-bold text-xs uppercase shadow-2xs">
                            {{ usuario?.name ? usuario.name.charAt(0) : 'U' }}
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-slate-900 truncate">
                                {{ usuario?.name }}
                            </div>
                            <div class="text-[11px] text-slate-400 truncate">
                                {{ usuario?.email }}
                            </div>
                        </div>
                    </div>

                    <!-- Cerrar Sesión -->
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="rounded-xl p-2 text-slate-400 hover:bg-rose-50 hover:text-rose-600 transition cursor-pointer"
                        title="Cerrar Sesión"
                    >
                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </Link>
                </div>

                <div class="mt-3 pt-3 border-t border-slate-200/60 flex items-center justify-between text-[11px] text-slate-500">
                    <Link href="/" class="hover:text-blue-600 font-semibold transition">
                        ← Sitio Web
                    </Link>
                    <span class="text-[10px] text-slate-400">DatecLibro v1.0</span>
                </div>
            </div>
        </aside>

        <!-- CONTENIDO PRINCIPAL (Lado derecho con scroll independiente) -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Barra Superior en Desktop -->
            <header class="hidden lg:flex h-16 items-center justify-between border-b border-slate-200/80 bg-white/80 backdrop-blur-md px-8 sticky top-0 z-30 shadow-2xs">
                <div class="flex items-center gap-3">
                    <span class="text-xs font-semibold text-slate-400">
                        {{ esSuperAdmin ? 'Centro de Control Datec' : 'Panel de Administración' }}
                    </span>
                    <span class="text-slate-300">/</span>
                    <span class="text-xs font-bold text-slate-800">
                        {{ empresaActual ? empresaActual.name : 'Plataforma Global' }}
                    </span>
                </div>

                <div class="flex items-center gap-4">
                    <Link
                        href="/"
                        class="rounded-full border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition shadow-2xs"
                    >
                        Página Principal
                    </Link>
                    <div class="h-4 w-px bg-slate-200"></div>
                    <div class="flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        <span class="text-xs font-semibold text-slate-600">Sistema Conforme INDECOPI</span>
                    </div>
                </div>
            </header>

            <!-- Mensajes de Notificación Flash -->
            <div class="px-4 sm:px-8 pt-6" v-if="flash.success || flash.error">
                <div
                    v-if="flash.success"
                    class="flex items-center justify-between rounded-2xl border border-emerald-200 bg-emerald-50/90 p-4 text-sm text-emerald-900 shadow-xs"
                >
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span class="font-medium">{{ flash.success }}</span>
                    </div>
                </div>

                <div
                    v-if="flash.error"
                    class="flex items-center justify-between rounded-2xl border border-rose-200 bg-rose-50/90 p-4 text-sm text-rose-900 shadow-xs"
                >
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-rose-100 text-rose-700">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span class="font-medium">{{ flash.error }}</span>
                    </div>
                </div>
            </div>

            <!-- Contenido dinámico inyectado por las vistas de Inertia -->
            <main class="flex-1 px-4 sm:px-8 py-6">
                <slot />
            </main>
        </div>
    </div>
</template>
