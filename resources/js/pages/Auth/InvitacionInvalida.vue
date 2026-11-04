<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    motivo: string;
}>();
</script>

<template>
    <div class="min-h-screen bg-slate-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8 text-slate-800">
        <Head title="Enlace de Invitación No Válido - Datec Libro" />

        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <div class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 shadow-xs">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h2 class="mt-4 text-2xl font-bold tracking-tight text-slate-900">
                Invitación no disponible
            </h2>
            <p class="mt-2 text-sm text-slate-600 px-4">
                {{ motivo }}
            </p>

            <div class="mt-6 flex justify-center">
                <Link
                    href="/"
                    class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition"
                >
                    Ir a la página de inicio
                </Link>
            </div>
        </div>
    </div>
</template>

