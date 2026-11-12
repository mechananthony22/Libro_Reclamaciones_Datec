<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface Configuracion {
    emails_notificaciones: string[];
    notificar_nuevo_reclamo: boolean;
    notificar_vencimiento_preventivo: boolean;
    notificar_vencido: boolean;
    dias_anticipacion_alerta: number;
    email_oficial: string | null;
    representante_email: string | null;
}

interface Props {
    configuracion: Configuracion;
}

const props = defineProps<Props>();

const nuevoEmailInput = ref('');
const errorEmail = ref('');

const form = useForm({
    emails_notificaciones: [...(props.configuracion.emails_notificaciones || [])],
    notificar_nuevo_reclamo: props.configuracion.notificar_nuevo_reclamo,
    notificar_vencimiento_preventivo: props.configuracion.notificar_vencimiento_preventivo,
    dias_anticipacion_alerta: props.configuracion.dias_anticipacion_alerta || 3,
});

function agregarEmail() {
    errorEmail.value = '';
    const email = nuevoEmailInput.value.trim().toLowerCase();

    if (!email) return;

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        errorEmail.value = 'Ingrese una dirección de correo electrónico válida.';
        return;
    }

    if (form.emails_notificaciones.includes(email)) {
        errorEmail.value = 'Este correo electrónico ya está registrado en la lista.';
        return;
    }

    form.emails_notificaciones.push(email);
    nuevoEmailInput.value = '';
}

function removerEmail(index: number) {
    form.emails_notificaciones.splice(index, 1);
}

function guardarConfiguracion() {
    form.put('/configuracion/notificaciones', {
        preserveScroll: true,
    });
}
</script>

<template>
    <AdminLayout>
        <Head title="Destinatarios y Alertas - Datec Libro" />

        <div class="mx-auto max-w-4xl space-y-6">
            <!-- Cabecera de Página (Estilo Admin/Empresas) -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex items-start gap-4">
                    <div
                        class="hidden sm:flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-md shadow-blue-500/25"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            Configuración de Empresa · Alertas y Destinatarios
                        </div>
                        <h1 class="mt-0.5 text-2xl font-extrabold tracking-tight text-slate-900">
                            Destinatarios y Alertas Automáticas
                        </h1>
                    </div>
                </div>
            </div>

            <!-- Formulario de Configuración -->
            <form @submit.prevent="guardarConfiguracion" class="space-y-6">
                <!-- Destinatarios Internos de Notificación -->
                <div class="space-y-4 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-2xs">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">
                                Destinatarios de Alertas y Notificaciones
                            </h2>
                            <p class="text-xs text-slate-500">
                                Los avisos automáticos se enviarán a estos correos institucionales además del correo legal principal.
                            </p>
                        </div>
                    </div>

                    <!-- Correos Base de la Empresa -->
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4 space-y-2">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Correos Principales Registrados
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-blue-600 shrink-0"></span>
                                <span class="text-slate-500">Oficial:</span>
                                <span class="font-bold text-slate-800 truncate">{{ configuracion.email_oficial || 'No configurado' }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-emerald-600 shrink-0"></span>
                                <span class="text-slate-500">Representante:</span>
                                <span class="font-bold text-slate-800 truncate">{{ configuracion.representante_email || 'No configurado' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Agregar Correos Adicionales -->
                    <div class="space-y-3 pt-2">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            Agregar Correo Adicional (Atención al Cliente, Legal, Gerencia)
                        </label>

                        <div class="flex gap-2">
                            <div class="flex-1">
                                <input
                                    v-model="nuevoEmailInput"
                                    type="email"
                                    @keydown.enter.prevent="agregarEmail"
                                    placeholder="ejemplo: atencion@suempresa.com"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                />
                            </div>
                            <button
                                type="button"
                                @click="agregarEmail"
                                class="cursor-pointer rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white transition hover:bg-slate-800 shadow-2xs"
                            >
                                Agregar Correo
                            </button>
                        </div>
                        <p v-if="errorEmail" class="text-rose-600 text-xs font-semibold">{{ errorEmail }}</p>

                        <!-- Lista de Emails en Chips/Tags -->
                        <div v-if="form.emails_notificaciones.length > 0" class="flex flex-wrap gap-2 pt-2">
                            <div
                                v-for="(email, idx) in form.emails_notificaciones"
                                :key="idx"
                                class="inline-flex items-center gap-2 rounded-full bg-blue-50 border border-blue-200/80 px-3 py-1 text-xs font-semibold text-blue-900 shadow-2xs"
                            >
                                <svg class="h-3.5 w-3.5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span>{{ email }}</span>
                                <button
                                    type="button"
                                    @click="removerEmail(idx)"
                                    class="cursor-pointer text-blue-400 hover:text-rose-600 transition"
                                    title="Quitar destinatario"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div v-else class="text-xs text-slate-400 italic">
                            No hay correos adicionales agregados. Las notificaciones se enviarán a los correos principales registrados.
                        </div>
                    </div>
                </div>

                <!-- Preferencias y Reglas de Alerta -->
                <div class="space-y-4 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-2xs">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-purple-50 text-purple-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">
                                Preferencias de Alertas y Vencimientos
                            </h2>
                        </div>
                    </div>

                    <div class="divide-y divide-slate-100 text-xs">
                        <!-- Alerta por Nuevo Reclamo -->
                        <div class="py-4 flex items-center justify-between gap-4">
                            <div>
                                <div class="font-bold text-slate-900">Alerta de Nuevo Reclamo o Queja</div>
                                <div class="text-slate-500 mt-0.5">
                                    Notifica inmediatamente cada vez que un consumidor registra un reclamo en el libro virtual.
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer shrink-0">
                                <input
                                    v-model="form.notificar_nuevo_reclamo"
                                    type="checkbox"
                                    class="sr-only peer"
                                />
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <!-- Alerta Preventiva de Vencimiento -->
                        <div class="py-4 flex items-center justify-between gap-4">
                            <div>
                                <div class="font-bold text-slate-900">Alerta Preventiva de Próximo Vencimiento</div>
                                <div class="text-slate-500 mt-0.5">
                                    Avisa con anticipación antes de alcanzar el plazo legal máximo de 15 días hábiles establecido por INDECOPI.
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer shrink-0">
                                <input
                                    v-model="form.notificar_vencimiento_preventivo"
                                    type="checkbox"
                                    class="sr-only peer"
                                />
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <!-- Días de anticipación preventiva -->
                        <div v-if="form.notificar_vencimiento_preventivo" class="py-4 space-y-2">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                Días hábiles de anticipación para la alerta preventiva:
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                                <button
                                    v-for="dia in [1, 2, 3, 4, 5]"
                                    :key="dia"
                                    type="button"
                                    @click="form.dias_anticipacion_alerta = dia"
                                    :class="[
                                        'cursor-pointer rounded-xl py-2 px-3 text-xs font-bold border transition text-center',
                                        form.dias_anticipacion_alerta === dia
                                            ? 'border-blue-600 bg-blue-50 text-blue-700 shadow-2xs'
                                            : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                                    ]"
                                >
                                    {{ dia }} {{ dia === 1 ? 'día hábil' : 'días hábiles' }}
                                </button>
                            </div>
                        </div>

                        <!-- Aviso Crítico Normativo -->
                        <div class="py-4 flex items-center justify-between gap-4 rounded-xl border border-slate-100 bg-slate-50/70 p-4 mt-2">
                            <div class="flex items-start gap-3">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-slate-200 text-slate-700 shadow-2xs">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-slate-900">Alerta de Plazo Legal Vencido</span>
                                        <span class="rounded-full bg-rose-100 text-rose-800 px-2 py-0.5 text-[10px] font-bold">
                                            Aviso Crítico Obligatorio
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">
                                        Por normativa de INDECOPI (Ley N° 29571 / D.S. 011-2011-PCM), los avisos críticos de casos con plazo vencido no pueden desactivarse para prevenir sanciones administrativas.
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 font-bold text-xs select-none">
                                <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-emerald-700">Siempre Activo</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botón de Guardar -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="cursor-pointer rounded-xl bg-blue-600 px-6 py-2.5 text-xs font-bold text-white shadow-md shadow-blue-500/20 transition hover:bg-blue-700 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Guardando preferencias...' : 'Guardar Configuración' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
