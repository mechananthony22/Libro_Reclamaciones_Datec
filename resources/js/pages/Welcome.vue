<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Estamos desarrollando backend - Datec Libro">
        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    </Head>

    <div class="flex min-h-screen items-center justify-center bg-[#131b2e] p-4 font-sans text-slate-300">
        <div class="w-full max-w-lg rounded-2xl border border-slate-800 bg-[#1e293b] p-8 shadow-2xl">
            <!-- Logos: Laravel + Vite -->
            <div class="flex items-center justify-center gap-4 pb-6">
                <!-- Laravel Logo -->
                <svg class="h-12 w-12 text-[#FF2D20]" viewBox="0 0 62 65" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M61.8548 14.6253C61.8778 14.7102 61.8895 14.7979 61.8897 14.8858V28.5615C61.8897 29.8607 61.2144 31.0608 60.1118 31.7226L39.7004 43.9695C38.644 44.6033 37.3195 44.6033 36.2631 43.9695L24.8885 37.1448L20.4857 39.7865L36.2631 49.2529C37.3195 49.8867 38.644 49.8867 39.7004 49.2529L60.1118 37.006C61.2144 36.3442 61.8897 35.1441 61.8897 33.8449V47.5206C61.8897 48.8198 61.2144 50.0199 60.1118 50.6817L39.7004 62.9286C38.644 63.5624 37.3195 63.5624 36.2631 62.9286L1.77792 42.2374C0.675336 41.5756 0 40.3755 0 39.0763V11.725C0 10.4258 0.675336 9.2257 1.77792 8.56387L22.1893 2.01699C23.2457 1.38321 24.5702 1.38321 25.6266 2.01699L37.0012 8.84171L41.404 6.20003L25.6266 3.26639C24.5702 2.63261 23.2457 2.63261 22.1893 3.26639L1.77792 15.5133C0.675336 16.1751 0 17.3752 0 18.6744V32.3501C0 33.6493 0.675336 34.8494 1.77792 35.5112L22.1893 47.7581C23.2457 48.3919 24.5702 48.3919 25.6266 47.7581L60.1118 27.0669C61.2144 26.4051 61.8897 25.205 61.8897 23.9058V10.2301C61.8897 9.87612 61.8085 9.53036 61.6562 9.22019L61.8548 14.6253Z" fill="currentColor"/>
                </svg>

                <span class="text-xl font-light text-slate-500">+</span>

                <!-- Vite Logo -->
                <svg class="h-12 w-12" viewBox="0 0 410 404" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M399.78 59.38L215.68 388.94c-4.14 7.42-14.77 7.4-18.9-.03L8.5 59.38c-4.66-8.34 2.22-18.25 11.58-16.79l187.82 29.27a11.2 11.2 0 003.48 0l176.82-29.27c9.36-1.46 16.24 8.45 11.58 16.79z" fill="url(#vite-a)"/>
                    <path d="M285.8 4.25L144.3 29.13a11.2 11.2 0 00-7.85 5.5L3.43 277.94a11.2 11.2 0 0013.12 15.69l75.46-21.73a11.2 11.2 0 0110.87 3.32l52.68 53.64c6.2 6.32 16.82 3.03 18.52-5.74L216.5 108.5a11.2 11.2 0 0110.86-9.06l141.6-1.92c9.35-.13 15.11-9.98 10.37-18.06L299.7 7.37a11.2 11.2 0 00-13.9-3.12z" fill="url(#vite-b)"/>
                    <path d="M288.72 4.4L150.3 29.07a11.2 11.2 0 00-7.85 5.5L3.43 277.94a11.2 11.2 0 0013.12 15.69l75.46-21.73a11.2 11.2 0 0110.87 3.32l52.68 53.64c6.2 6.32 16.82 3.03 18.52-5.74L216.5 108.5a11.2 11.2 0 0110.86-9.06l141.6-1.92c9.35-.13 15.11-9.98 10.37-18.06L299.7 7.37a11.2 11.2 0 00-10.98-2.97z" fill="#FFD800"/>
                    <path d="M172.5 106.63l-28.53 146.2a11.2 11.2 0 0015.02 12.63l39.95-17.65a11.2 11.2 0 0114.7 6.43l13.13 35.15c3.34 8.94 15.54 10.23 20.67 2.19l127.34-199.77c5.23-8.2-1.2-18.84-10.94-18.03l-45.7 3.8a11.2 11.2 0 01-11.83-8.91l-7.79-40.23c-1.8-9.3-13.06-13.09-20.08-6.75L172.5 106.63z" fill="#FFA800"/>
                    <defs>
                        <linearGradient id="vite-a" x1="8.5" y1="42.59" x2="284.28" y2="397.77" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#41D1FF"/>
                            <stop offset="1" stop-color="#BD34FE"/>
                        </linearGradient>
                        <linearGradient id="vite-b" x1="285.8" y1="4.25" x2="80.64" y2="307.72" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#FFEA83"/>
                            <stop offset=".08" stop-color="#FFDD35"/>
                            <stop offset="1" stop-color="#FFA800"/>
                        </linearGradient>
                    </defs>
                </svg>
            </div>

            <!-- Contenido principal -->
            <div class="space-y-5">
                <div class="text-center">
                    <h1 class="text-xl font-bold tracking-tight text-white">
                        estamos desarrollando baken
                    </h1>
                    <p class="mt-2 text-sm text-slate-400">
                        Plataforma SaaS de Libro de Reclamaciones Virtual Multiempresa para Datec.
                    </p>
                </div>

                <!-- Sección Módulo Superadmin -->
                <div class="rounded-xl border border-slate-700/60 bg-[#162032] p-4">
                    <h2 class="text-sm font-semibold text-[#FF2D20]">
                        Panel de Administración (RF-001 al RF-004)
                    </h2>
                    <p class="mt-1 text-xs text-slate-400">
                        El módulo backend para alta de empresas, gestión de estados, planes de suscripción e invitación de administradores está activo.
                    </p>
                    <div class="mt-3">
                        <Link
                            href="/admin/empresas"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#FF2D20] hover:text-[#ff5247] hover:underline"
                        >
                            <span>Abrir panel de empresas</span>
                            <span aria-hidden="true">&rarr;</span>
                        </Link>
                    </div>
                </div>

                <!-- Sección Libro Público -->
                <div class="rounded-xl border border-slate-700/60 bg-[#162032] p-4">
                    <h2 class="text-sm font-semibold text-[#41D1FF]">
                        Resolución Multitenant por URL
                    </h2>
                    <p class="mt-1 text-xs text-slate-400">
                        Cada empresa cuenta con su enlace público aislado bajo la ruta <code class="text-slate-300 font-mono">/l/{slug}</code>, resolviendo el contexto tenant automáticamente.
                    </p>
                </div>

                <hr class="border-slate-800" />

                <!-- Footer info con APP_URL -->
                <div class="space-y-2 text-xs text-slate-400">
                    <div>
                        <span>Servidor local configurado: </span>
                        <a
                            href="http://localhost:8000"
                            class="font-mono text-[#FF2D20] hover:underline"
                        >
                            http://localhost:8000
                        </a>
                    </div>
                    <div>
                        <span>Datec Libro • Laravel 13 + Inertia Vue 3 + PostgreSQL</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
