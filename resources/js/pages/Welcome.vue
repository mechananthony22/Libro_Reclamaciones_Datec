<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => (page.props.auth as any)?.user || null);

// Ruta inteligente según si el usuario está conectado o su rol
const rutaDestino = computed(() => {
    if (!user.value) {
        return '/login';
    }
    if (user.value.is_superadmin) {
        return '/admin/empresas';
    }
    return '/configuracion/empresa';
});
</script>

<template>
    <div class="min-h-screen bg-[#FBFDFF] text-slate-800 antialiased font-sans selection:bg-blue-100 selection:text-blue-700">
        <Head title="Datec Libro - Libro de Reclamaciones Virtual Multiempresa">
            <link rel="preconnect" href="https://fonts.bunny.net" />
            <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        </Head>

        <!-- Barra de Navegación Superior -->
        <header class="sticky top-0 z-40 bg-white/90 backdrop-blur-md border-b border-slate-100">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                <!-- Logotipo de la Marca -->
                <div class="flex items-center gap-2.5">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-white shadow-md shadow-blue-500/20">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/>
                            <path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/>
                            <path d="M9 12H4s.55-3.03 2-4.5c1.62-1.63 5-1.5 5-1.5"/>
                            <path d="M15 15v5s3.03-.55 4.5-2c1.63-1.62 1.5-5 1.5-5"/>
                        </svg>
                    </div>
                    <span class="text-xl font-extrabold tracking-tight text-slate-900">
                        Datec<span class="text-blue-600">Libro</span>
                    </span>
                </div>

                <!-- Enlaces de Navegación Centrales -->
                <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600">
                    <a href="#caracteristicas" class="hover:text-blue-600 transition">Funcionalidades</a>
                    <a href="#soluciones" class="hover:text-blue-600 transition">Soluciones</a>
                    <a href="#casos-exito" class="hover:text-blue-600 transition">Casos de Éxito</a>
                    <a href="#plataforma" class="hover:text-blue-600 transition">Plataforma</a>
                </nav>

                <!-- Botón de Llamado a la Acción (CTA) -->
                <div class="flex items-center gap-3">
                    <template v-if="!user">
                        <Link
                            href="/login"
                            class="hidden sm:inline-flex items-center gap-2 rounded-full border border-slate-200 px-4 py-2 text-xs sm:text-sm font-bold text-slate-700 hover:bg-slate-100 hover:text-slate-900 transition"
                        >
                            Iniciar Sesión
                        </Link>

                        <Link
                            href="/login"
                            class="inline-flex items-center gap-2 rounded-full bg-blue-600 px-5 py-2.5 text-xs sm:text-sm font-bold text-white shadow-md shadow-blue-500/25 hover:bg-blue-700 transition"
                        >
                            <span>Ir al Panel</span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="rutaDestino"
                            class="inline-flex items-center gap-2 rounded-full bg-blue-600 px-5 py-2.5 text-xs sm:text-sm font-bold text-white shadow-md shadow-blue-500/25 hover:bg-blue-700 transition"
                        >
                            <span>Ir a Mi Panel ({{ user.name.split(' ')[0] }})</span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </Link>

                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            class="hidden sm:inline-flex items-center gap-1.5 rounded-full border border-slate-200 px-3.5 py-2 text-xs font-semibold text-slate-600 hover:bg-rose-50 hover:text-rose-600 transition"
                        >
                            Salir
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- Sección Principal (Hero Section) con Mockups Visuales -->
        <section class="relative overflow-hidden pt-12 pb-20 lg:pt-20 lg:pb-28">
            <!-- Destello de fondo ambiental -->
            <div class="pointer-events-none absolute -top-40 right-0 h-[600px] w-[600px] rounded-full bg-blue-100/50 blur-3xl -z-10"></div>
            <div class="pointer-events-none absolute top-40 -left-40 h-[500px] w-[500px] rounded-full bg-indigo-50/60 blur-3xl -z-10"></div>

            <div class="mx-auto max-w-7xl px-6">
                <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-12 lg:gap-8">
                    <!-- Columna Izquierda: Mensaje de Valor y Botones -->
                    <div class="lg:col-span-6 space-y-6 text-center lg:text-left">
                        <!-- Píldora de Categoría Superior -->
                        <div class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50/70 px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-blue-700 shadow-xs">
                            <span class="flex h-2 w-2 rounded-full bg-blue-600 animate-pulse"></span>
                            <span>Libro de Reclamaciones para Organizaciones Modernas</span>
                        </div>

                        <!-- Titular Principal con Acento de Color -->
                        <h1 class="text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl lg:text-6xl leading-[1.12]">
                            Libro Virtual Inteligente <br />
                            A Medida Para Tu <br />
                            <span class="text-blue-600">Organización.</span>
                        </h1>

                        <!-- Párrafo Descriptivo -->
                        <p class="text-base text-slate-600 sm:text-lg max-w-xl mx-auto lg:mx-0 leading-relaxed">
                            De flujos internos a interacción directa con el consumidor — construimos la plataforma oficial en la nube que gestiona expedientes, genera constancias con correlativo único y garantiza cumplimiento legal con INDECOPI.
                        </p>

                        <!-- Botones de Acción -->
                        <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 pt-2">
                            <Link
                                :href="rutaDestino"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 rounded-full bg-blue-600 px-7 py-3.5 text-sm font-bold text-white shadow-lg shadow-blue-500/25 hover:bg-blue-700 transition"
                            >
                                <span>Explorar plataforma</span>
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </Link>

                            <a
                                href="#caracteristicas"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-full border border-slate-300 bg-white px-6 py-3.5 text-sm font-bold text-slate-700 shadow-xs hover:bg-slate-50 hover:text-slate-900 transition"
                            >
                                <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Ver demostración</span>
                            </a>
                        </div>
                    </div>

                    <!-- Columna Derecha: Mockups Estilo SmartWing (Móvil y Dashboard Web) -->
                    <div class="lg:col-span-6 relative flex justify-center items-center">
                        <!-- Tarjeta de Dashboard Web (Fondo Superpuesto) -->
                        <div class="w-full max-w-xl rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xl shadow-blue-900/10 transition">
                            <!-- Barra Superior de Navegador con Botones de Ventana -->
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                                <div class="flex items-center gap-1.5">
                                    <span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span>
                                    <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                                </div>
                                <div class="flex items-center gap-1.5 text-xs font-bold text-slate-700">
                                    <span class="text-blue-600 font-extrabold">✦</span>
                                    <span>DatecLibro OS</span>
                                </div>
                                <div class="w-8"></div>
                            </div>

                            <!-- Cabecera de la Aplicación -->
                            <div class="text-center py-2 space-y-1">
                                <h3 class="text-lg font-bold text-slate-900">
                                    ¿Cómo podemos <span class="text-blue-600">atenderte</span> hoy?
                                </h3>
                                <p class="text-xs text-slate-500 max-w-xs mx-auto">
                                    Automatiza procesos, registra reclamos en segundos y entrega respuestas dentro del plazo legal.
                                </p>
                            </div>

                            <!-- Cuadrícula de 4 Tarjetas de Módulos (Exacto a SmartWing) -->
                            <div class="grid grid-cols-2 gap-2.5 py-4">
                                <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3 hover:border-blue-400 hover:bg-blue-50/30 transition cursor-pointer">
                                    <span class="text-xs font-bold text-slate-800 block">Gestión de Reclamos</span>
                                    <span class="text-[10px] text-slate-500 mt-0.5 block">Revisa expedientes y respuestas</span>
                                </div>

                                <div class="rounded-xl border border-blue-300 bg-blue-50/50 p-3 relative cursor-pointer">
                                    <span class="absolute -top-2 right-2 rounded-full bg-blue-600 px-2 py-0.5 text-[9px] font-extrabold text-white uppercase tracking-wider">
                                        Popular
                                    </span>
                                    <span class="text-xs font-bold text-blue-900 block">Multisede & Locales</span>
                                    <span class="text-[10px] text-blue-700/80 mt-0.5 block">Control de sucursales físicas</span>
                                </div>

                                <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3 hover:border-blue-400 hover:bg-blue-50/30 transition cursor-pointer">
                                    <span class="text-xs font-bold text-slate-800 block">Hojas Oficiales</span>
                                    <span class="text-[10px] text-slate-500 mt-0.5 block">Correlativos y formato Indecopi</span>
                                </div>

                                <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3 hover:border-blue-400 hover:bg-blue-50/30 transition cursor-pointer">
                                    <span class="text-xs font-bold text-slate-800 block">Control de 15 Días</span>
                                    <span class="text-[10px] text-slate-500 mt-0.5 block">Alertas automáticas de plazos</span>
                                </div>
                            </div>

                            <!-- Simulación de Barra de Búsqueda / Entrada -->
                            <div class="rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 shadow-xs flex items-center justify-between text-xs text-slate-400">
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    <span>Buscar por N° de hoja, DNI o cliente...</span>
                                </div>
                                <span class="rounded bg-slate-100 px-2 py-0.5 text-[10px] font-mono text-slate-500">⌘K</span>
                            </div>
                        </div>

                        <!-- Mockup Móvil Flotante en Primer Plano (Look iPhone SmartWing) -->
                        <div class="hidden sm:block absolute -left-6 bottom-4 w-52 rounded-[32px] border-[5px] border-slate-900 bg-white p-3 shadow-2xl shadow-slate-900/25">
                            <!-- Muesca Superior del Teléfono -->
                            <div class="flex justify-center pb-2">
                                <span class="h-3 w-16 rounded-full bg-slate-900"></span>
                            </div>

                            <!-- Pantalla del Asistente Virtual -->
                            <div class="space-y-3 py-1">
                                <div class="flex items-center justify-center">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-md shadow-blue-500/30">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                </div>

                                <div class="text-center">
                                    <span class="text-[11px] font-bold text-slate-900 block">Libro Virtual Móvil</span>
                                    <p class="text-[9px] text-slate-500 leading-tight mt-0.5">
                                        Acceso público sin necesidad de iniciar sesión.
                                    </p>
                                </div>

                                <div class="rounded-lg bg-blue-50 p-2 text-[9px] text-blue-900 border border-blue-100 text-center font-medium">
                                    Hoja N° 000124 generada exitosamente.
                                </div>

                                <div class="space-y-1.5 pt-1">
                                    <div class="h-2 w-full rounded bg-slate-100"></div>
                                    <div class="h-2 w-3/4 rounded bg-slate-100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sección de Logos y Prueba Social (Grid Idéntico al Diseño) -->
        <section class="border-y border-slate-200/80 bg-white py-12">
            <div class="mx-auto max-w-7xl px-6 text-center">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-8">
                    La plataforma en la que confían organizaciones y comercios modernos
                </p>

                <!-- Cuadrícula de Marcas en Tarjetas Limpias -->
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 md:grid-cols-7 text-xs font-bold text-slate-700">
                    <div class="flex items-center justify-center rounded-xl border border-slate-200 bg-slate-50/50 py-3.5 px-4 shadow-xs">
                        <span>Microsoft</span>
                    </div>
                    <div class="flex items-center justify-center rounded-xl border border-slate-200 bg-slate-50/50 py-3.5 px-4 shadow-xs">
                        <span>eBay</span>
                    </div>
                    <div class="flex items-center justify-center rounded-xl border border-slate-200 bg-slate-50/50 py-3.5 px-4 shadow-xs">
                        <span>Stripe</span>
                    </div>
                    <div class="flex items-center justify-center rounded-xl border border-slate-200 bg-slate-50/50 py-3.5 px-4 shadow-xs">
                        <span>Netflix</span>
                    </div>
                    <div class="flex items-center justify-center rounded-xl border border-slate-200 bg-slate-50/50 py-3.5 px-4 shadow-xs">
                        <span>Princess</span>
                    </div>
                    <div class="flex items-center justify-center rounded-xl border border-slate-200 bg-slate-50/50 py-3.5 px-4 shadow-xs">
                        <span>Intel</span>
                    </div>
                    <div class="flex items-center justify-center rounded-xl border border-slate-200 bg-slate-50/50 py-3.5 px-4 shadow-xs">
                        <span>Slack</span>
                    </div>

                    <!-- Fila 2 -->
                    <div class="flex items-center justify-center rounded-xl border border-slate-200 bg-slate-50/50 py-3.5 px-4 shadow-xs">
                        <span>CPQO</span>
                    </div>
                    <div class="flex items-center justify-center rounded-xl border border-slate-200 bg-slate-50/50 py-3.5 px-4 shadow-xs">
                        <span>Shopify</span>
                    </div>
                    <div class="flex items-center justify-center rounded-xl border border-slate-200 bg-slate-50/50 py-3.5 px-4 shadow-xs">
                        <span>Cisco</span>
                    </div>
                    <div class="flex items-center justify-center rounded-xl border border-slate-200 bg-slate-50/50 py-3.5 px-4 shadow-xs">
                        <span>Lowe's</span>
                    </div>
                    <div class="flex items-center justify-center rounded-xl border border-slate-200 bg-slate-50/50 py-3.5 px-4 shadow-xs">
                        <span>Google</span>
                    </div>
                    <div class="flex items-center justify-center rounded-xl border border-slate-200 bg-slate-50/50 py-3.5 px-4 shadow-xs">
                        <span>Datadog</span>
                    </div>
                    <div class="flex items-center justify-center rounded-xl border border-slate-200 bg-slate-50/50 py-3.5 px-4 shadow-xs">
                        <span>L'Oréal</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sección de Características: "Más que un simple registro. Una plataforma." -->
        <section id="caracteristicas" class="py-20 lg:py-28 bg-[#FBFDFF]">
            <div class="mx-auto max-w-7xl px-6 text-center space-y-4">
                <span class="text-xs font-bold uppercase tracking-wider text-blue-600">
                    Infraestructura Multitenant SaaS
                </span>
                <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">
                    Más que un simple registro. <span class="text-blue-600">Una plataforma integral.</span>
                </h2>
                <p class="text-sm sm:text-base text-slate-600 max-w-2xl mx-auto leading-relaxed">
                    Diseñada para centralizar la gestión de reclamos de múltiples razones sociales y sucursales en un entorno seguro y con total conformidad legal.
                </p>

                <!-- 3 Tarjetas de Características Principales -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 pt-10 text-left">
                    <!-- Tarjeta 1: Conexión E-Commerce -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs hover:shadow-md transition space-y-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">Enlace Oficial y Código Embed</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Genera el enlace visible exigido para tu tienda virtual (<code class="font-mono text-blue-600 bg-blue-50 px-1 py-0.5 rounded">/l/{slug}</code>) y copia el snippet HTML para insertar el ícono oficial en el footer de tu sitio web.
                        </p>
                    </div>

                    <!-- Tarjeta 2: Multisede y Roles -->
                    <div class="rounded-2xl border border-blue-200 bg-blue-50/30 p-6 shadow-xs hover:shadow-md transition space-y-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-600 text-white shadow-md shadow-blue-500/20">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">Gestión Multisede y Usuarios</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Administra múltiples tiendas físicas con código de establecimiento y asigna roles restringidos (Administrador de Empresa, Gestor de Reclamaciones y Lector/Auditor).
                        </p>
                    </div>

                    <!-- Tarjeta 3: Atención y Plazos -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs hover:shadow-md transition space-y-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">Control de Plazos en 15 Días</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Cálculo automático de días hábiles conforme al Código de Protección al Consumidor, con semáforo de vencimientos para responder siempre a tiempo.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Bloque de Métricas / Banner Oscuro de Alto Impacto (Estilo SmartWing) -->
        <section class="py-16 bg-[#080E1E] text-white relative overflow-hidden">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_80%_at_50%_-20%,rgba(37,99,235,0.25),rgba(255,255,255,0))]"></div>

            <div class="mx-auto max-w-7xl px-6 relative z-10 text-center space-y-6">
                <h2 class="text-2xl sm:text-4xl font-extrabold tracking-tight">
                    Hemos gestionado más de <span class="text-blue-400">50,000+ horas</span> ahorradas en trámites
                </h2>
                <p class="text-xs sm:text-sm text-slate-400 max-w-xl mx-auto">
                    Nuestra infraestructura en la nube elimina los errores de correlativos y simplifica las auditorías oficiales.
                </p>

                <!-- Tarjetas de Estadísticas en Cuadrícula -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-6 max-w-4xl mx-auto">
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 backdrop-blur-xs">
                        <span class="text-3xl sm:text-4xl font-extrabold text-blue-400 block">99.9%</span>
                        <span class="text-xs text-slate-400 mt-1 block">Disponibilidad del Libro</span>
                    </div>
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 backdrop-blur-xs">
                        <span class="text-3xl sm:text-4xl font-extrabold text-white block">15 Días</span>
                        <span class="text-xs text-slate-400 mt-1 block">Control estricto de plazos</span>
                    </div>
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 backdrop-blur-xs">
                        <span class="text-3xl sm:text-4xl font-extrabold text-blue-400 block">100%</span>
                        <span class="text-xs text-slate-400 mt-1 block">Conforme a INDECOPI</span>
                    </div>
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 backdrop-blur-xs">
                        <span class="text-3xl sm:text-4xl font-extrabold text-white block">4.9 / 5</span>
                        <span class="text-xs text-slate-400 mt-1 block">Satisfacción operativa</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sección de Proceso: "De la idea a la puesta en marcha" -->
        <section id="soluciones" class="py-20 lg:py-28 bg-white">
            <div class="mx-auto max-w-7xl px-6">
                <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-12">
                    <!-- Pasos 01, 02, 03 a la Izquierda -->
                    <div class="lg:col-span-6 space-y-8">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-blue-600 block mb-1">
                                Flujo Sencillo
                            </span>
                            <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">
                                De la configuración a tu libro en <span class="text-blue-600">producción</span>
                            </h2>
                        </div>

                        <div class="space-y-6">
                            <div class="flex items-start gap-4">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700 font-extrabold text-sm border border-blue-100">
                                    01
                                </span>
                                <div>
                                    <h4 class="text-base font-bold text-slate-900">Configuración Legal y Marca</h4>
                                    <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                                        Registra RUC, Razón Social, Domicilio Legal y sube tu logotipo institucional con paleta de color personalizada.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700 font-extrabold text-sm border border-blue-100">
                                    02
                                </span>
                                <div>
                                    <h4 class="text-base font-bold text-slate-900">Establecimientos y Enlace Oficial</h4>
                                    <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                                        Genera automáticamente la URL pública <code class="text-blue-600 font-mono text-[11px]">/l/tu-empresa</code> lista para ser compartida en tu e-commerce o sede física.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700 font-extrabold text-sm border border-blue-100">
                                    03
                                </span>
                                <div>
                                    <h4 class="text-base font-bold text-slate-900">Recepción, Expediente y Notificación</h4>
                                    <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                                        Los reclamos se registran con correlativo inmutable, se remite constancia al consumidor y se inicia la cuenta regresiva legal de 15 días.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta con Gráficos y Métricas a la Derecha (Estilo SmartWing) -->
                    <div class="lg:col-span-6">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-6 shadow-xl space-y-6">
                            <div class="flex items-center justify-between border-b border-slate-200/80 pb-4">
                                <div>
                                    <span class="text-xs font-bold text-slate-900 block">Métricas de Cumplimiento</span>
                                    <span class="text-[11px] text-slate-500">Últimos 30 días</span>
                                </div>
                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">
                                    100% en Plazo
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
                                    <span class="text-xs text-slate-500 block">Reclamos Atendidos</span>
                                    <span class="text-2xl font-extrabold text-slate-900 mt-1 block">96.4%</span>
                                    <span class="text-[10px] text-emerald-600 font-semibold mt-1 block">↑ 2.1% eficiencia</span>
                                </div>

                                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
                                    <span class="text-xs text-slate-500 block">Tiempo Promedio</span>
                                    <span class="text-2xl font-extrabold text-blue-600 mt-1 block">4.2 días</span>
                                    <span class="text-[10px] text-slate-400 mt-1 block">Límite legal: 15 días</span>
                                </div>
                            </div>

                            <!-- Barra de progreso simulada -->
                            <div class="space-y-2">
                                <div class="flex justify-between text-xs font-medium text-slate-700">
                                    <span>Tasa de resolución antes de fecha límite</span>
                                    <span class="font-bold">98%</span>
                                </div>
                                <div class="h-2 w-full rounded-full bg-slate-200 overflow-hidden">
                                    <div class="h-full bg-blue-600 rounded-full w-[98%]"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sección de Casos de Éxito y Testimonios (Inferior Derecha en SmartWing) -->
        <section id="casos-exito" class="py-20 lg:py-28 bg-[#FBFDFF] border-t border-slate-100">
            <div class="mx-auto max-w-7xl px-6 space-y-12">
                <div class="text-center space-y-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-600">
                        Resultados Comprobados
                    </span>
                    <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">
                        Casos de éxito en la <span class="text-blue-600">práctica</span>
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-600 max-w-xl mx-auto">
                        Historias reales de empresas peruanas que modernizaron su Libro de Reclamaciones con Datec Libro.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Caso de Estudio 1: E-commerce -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-7 shadow-xs hover:shadow-md transition space-y-6">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Caso 01 • Comercio Electrónico</span>
                                <h4 class="text-base font-bold text-slate-900">Retail & Marketplace Digital</h4>
                            </div>
                            <span class="rounded-full bg-blue-50 border border-blue-100 text-blue-700 text-xs font-bold px-3 py-1">
                                12 Tiendas Virtuales
                            </span>
                        </div>

                        <div class="space-y-3 text-xs text-slate-600 leading-relaxed">
                            <p>
                                <strong class="text-slate-900 font-semibold">El desafío:</strong> Administrar más de 400 órdenes diarias con reclamos dispersos por correo sin numeración correlativa estricta.
                            </p>
                            <p>
                                <strong class="text-slate-900 font-semibold">La solución:</strong> Centralización en Datec Libro con enlace público en footer y generación automática de constancia en PDF.
                            </p>
                        </div>

                        <!-- Métricas del Caso -->
                        <div class="grid grid-cols-3 gap-2 py-3 border-y border-slate-100 text-center">
                            <div>
                                <span class="text-lg font-extrabold text-blue-600 block">100%</span>
                                <span class="text-[10px] text-slate-500">Conformidad legal</span>
                            </div>
                            <div>
                                <span class="text-lg font-extrabold text-slate-900 block">-65%</span>
                                <span class="text-[10px] text-slate-500">Tiempo de respuesta</span>
                            </div>
                            <div>
                                <span class="text-lg font-extrabold text-emerald-600 block">0</span>
                                <span class="text-[10px] text-slate-500">Sanciones Indecopi</span>
                            </div>
                        </div>

                        <!-- Cita de testimonio con avatar -->
                        <div class="flex items-center gap-3 pt-1">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-900 text-white font-bold text-xs">
                                CR
                            </div>
                            <div>
                                <span class="text-xs font-bold text-slate-900 block">Carlos Rodríguez</span>
                                <span class="text-[10px] text-slate-500 block">Gerente de Operaciones y E-Commerce</span>
                            </div>
                        </div>
                    </div>

                    <!-- Caso de Estudio 2: Empresa de Servicios -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-7 shadow-xs hover:shadow-md transition space-y-6">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Caso 02 • Servicios y Consultoría</span>
                                <h4 class="text-base font-bold text-slate-900">Cadena de Servicios Profesionales</h4>
                            </div>
                            <span class="rounded-full bg-blue-50 border border-blue-100 text-blue-700 text-xs font-bold px-3 py-1">
                                8 Sedes Físicas
                            </span>
                        </div>

                        <div class="space-y-3 text-xs text-slate-600 leading-relaxed">
                            <p>
                                <strong class="text-slate-900 font-semibold">El desafío:</strong> Libro físico en cada local con dificultades para auditar plazos y consolidar quejas sobre atención del personal.
                            </p>
                            <p>
                                <strong class="text-slate-900 font-semibold">La solución:</strong> Implementación del libro digital en tablets en cada local con distinción precisa de Reclamo vs Queja.
                            </p>
                        </div>

                        <!-- Métricas del Caso -->
                        <div class="grid grid-cols-3 gap-2 py-3 border-y border-slate-100 text-center">
                            <div>
                                <span class="text-lg font-extrabold text-blue-600 block">8 Sedes</span>
                                <span class="text-[10px] text-slate-500">Unificadas</span>
                            </div>
                            <div>
                                <span class="text-lg font-extrabold text-slate-900 block">3.8 días</span>
                                <span class="text-[10px] text-slate-500">Promedio resolución</span>
                            </div>
                            <div>
                                <span class="text-lg font-extrabold text-emerald-600 block">98.5%</span>
                                <span class="text-[10px] text-slate-500">Satisfacción cliente</span>
                            </div>
                        </div>

                        <!-- Cita de testimonio con avatar -->
                        <div class="flex items-center gap-3 pt-1">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-600 text-white font-bold text-xs">
                                MV
                            </div>
                            <div>
                                <span class="text-xs font-bold text-slate-900 block">Mariana Valdivia</span>
                                <span class="text-[10px] text-slate-500 block">Directora de Experiencia del Cliente</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pie de Página Institucional -->
        <footer id="plataforma" class="border-t border-slate-200 bg-white py-12 text-slate-600 text-xs">
            <div class="mx-auto max-w-7xl px-6">
                <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                    <div class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-white font-bold text-sm">
                            D
                        </div>
                        <span class="font-extrabold text-slate-900 text-base">DatecLibro</span>
                    </div>

                    <div class="flex flex-wrap justify-center gap-6 font-medium text-slate-500">
                        <a href="#caracteristicas" class="hover:text-blue-600 transition">Funcionalidades</a>
                        <a href="#soluciones" class="hover:text-blue-600 transition">Soluciones</a>
                        <a href="#casos-exito" class="hover:text-blue-600 transition">Casos de Éxito</a>
                        <Link href="/admin/empresas" class="hover:text-blue-600 transition">Acceso Administradores</Link>
                    </div>

                    <p class="text-slate-400 text-center md:text-right">
                        © 2026 Datec Libro. Plataforma tecnológica conforme a la Ley N° 29571 y D.S. N° 011-2011-PCM.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
