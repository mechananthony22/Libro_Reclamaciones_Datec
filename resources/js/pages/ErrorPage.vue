<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    status: number;
    message?: string | null;
}

const props = defineProps<Props>();

const titulo = computed(() => {
    switch (props.status) {
        case 403:
            return 'Acceso Restringido o Inactivo';
        case 404:
            return 'Página No Encontrada';
        case 500:
            return 'Error Interno del Servidor';
        case 503:
            return 'Servicio en Mantenimiento';
        default:
            return `Error ${props.status}`;
    }
});

const descripcion = computed(() => {
    if (props.message) {
        return props.message;
    }

    switch (props.status) {
        case 403:
            return 'El servicio de tu empresa se encuentra temporalmente suspendido o inactivo.';
        case 404:
            return 'La dirección o recurso que buscas no existe o ha sido reubicado.';
        case 500:
            return 'Ocurrió una eventualidad imprevista. Nuestro equipo técnico ha sido notificado.';
        case 503:
            return 'Estamos realizando mejoras técnicas programadas. Por favor regresa en breve.';
        default:
            return 'Ha ocurrido un error inesperado al procesar tu solicitud.';
    }
});

function regresar() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = '/login';
    }
}
</script>

<template>
    <Head :title="`${status} - ${titulo}`" />

    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 sm:p-6 backdrop-blur-xs overflow-y-auto animate-fade-in">
        <div class="relative flex w-full max-w-md flex-col rounded-2xl border border-slate-200 bg-white shadow-2xl overflow-hidden my-auto">
            <!-- Cabecera del Modal -->
            <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-6 py-4">
                <div class="flex items-center gap-3">
                    <div
                        :class="[
                            'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-white shadow-md',
                            status === 403 ? 'bg-amber-500 shadow-amber-500/25' : (status === 404 ? 'bg-blue-600 shadow-blue-500/25' : 'bg-rose-600 shadow-rose-500/25')
                        ]"
                    >
                        <svg v-if="status === 403" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <svg v-else-if="status === 404" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-amber-600">
                            Estado {{ status }} · Seguridad
                        </div>
                        <h2 class="text-base font-extrabold text-slate-900">
                            {{ titulo }}
                        </h2>
                    </div>
                </div>
            </div>

            <!-- Cuerpo del Modal -->
            <div class="p-6 space-y-4 text-xs">
                <div class="rounded-xl border border-amber-200/80 bg-amber-50/70 p-3.5 text-amber-900 flex items-start gap-2.5">
                    <svg class="h-4 w-4 shrink-0 text-amber-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div class="font-bold text-xs leading-relaxed text-amber-950">
                        {{ descripcion }}
                    </div>
                </div>

                <p class="text-slate-600 leading-relaxed text-[11px]">
                    El acceso a este módulo o panel ha sido restringido por la política de servicio de la organización o estado administrativo. Comunícate con soporte de Datec o con tu administrador si requieres habilitar el acceso.
                </p>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                    <button
                        type="button"
                        @click="regresar"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 hover:text-slate-900 transition cursor-pointer"
                    >
                        Regresar
                    </button>
                    <Link
                        href="/login"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-bold text-white shadow-md shadow-blue-500/20 hover:bg-blue-700 transition cursor-pointer"
                    >
                        Iniciar Sesión
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

