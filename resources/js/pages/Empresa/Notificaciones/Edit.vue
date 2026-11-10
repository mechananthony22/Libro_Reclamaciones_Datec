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

    // Validación básica de formato de correo
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
        <Head title="Configurar Correos de Notificación y Alertas" />

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Encabezado -->
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900">
                    Notificaciones y Alertas Automáticas
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    Defina los destinatarios internos de su empresa y configure las alertas de vencimiento de reclamos.
                </p>
            </div>

            <!-- Formulario de Configuración -->
            <form @submit.prevent="guardarConfiguracion" class="space-y-6">
                <!-- Destinatarios Internos de Notificación -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs space-y-5">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            Destinatarios de Alertas y Notificaciones
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Los avisos automáticos se enviarán a estos correos institucionales además del correo legal principal.
                        </p>
                    </div>

                    <!-- Correos Base de la Empresa -->
                    <div class="rounded-xl border border-slate-200/70 bg-slate-50/80 p-4 space-y-2">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            Correos Principales de la Empresa
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
                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-slate-700">
                            Correos Adicionales para Alertas Operativas (Atención al Cliente, Legal, Gerencia)
                        </label>

                        <div class="flex gap-2">
                            <div class="flex-1">
                                <input
                                    v-model="nuevoEmailInput"
                                    type="email"
                                    @keydown.enter.prevent="agregarEmail"
                                    placeholder="ejemplo: atencion@suempresa.com"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs text-slate-800 focus:border-blue-500 focus:outline-none"
                                />
                            </div>
                            <button
                                type="button"
                                @click="agregarEmail"
                                class="rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white hover:bg-slate-800 transition"
                            >
                                Agregar Correo
                            </button>
                        </div>
                        <p v-if="errorEmail" class="text-rose-600 text-xs">{{ errorEmail }}</p>

                        <!-- Lista de Emails en Chips/Tags -->
                        <div v-if="form.emails_notificaciones.length > 0" class="flex flex-wrap gap-2 pt-2">
                            <div
                                v-for="(email, idx) in form.emails_notificaciones"
                                :key="idx"
                                class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 border border-blue-200/80 px-3 py-1 text-xs font-semibold text-blue-900 shadow-2xs"
                            >
                                <svg class="h-3.5 w-3.5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span>{{ email }}</span>
                                <button
                                    type="button"
                                    @click="removerEmail(idx)"
                                    class="text-blue-400 hover:text-rose-600 transition"
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
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs space-y-5">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            Preferencias de Alertas y Vencimientos
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Active o pause las notificaciones preventivas manteniendo siempre la seguridad legal.
                        </p>
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
                                    Avisa con anticipación antes de alcanzar el plazo legal máximo de 15 días hábiles establecido por Indecopi.
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
                            <label class="block font-bold text-slate-800">
                                Días hábiles de anticipación para la alerta preventiva:
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                                <button
                                    v-for="dia in [1, 2, 3, 4, 5]"
                                    :key="dia"
                                    type="button"
                                    @click="form.dias_anticipacion_alerta = dia"
                                    :class="[
                                        'rounded-xl py-2 px-3 text-xs font-bold border transition text-center',
                                        form.dias_anticipacion_alerta === dia
                                            ? 'border-blue-600 bg-blue-50 text-blue-700 shadow-2xs'
                                            : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                                    ]"
                                >
                                    {{ dia }} {{ dia === 1 ? 'día hábil' : 'días hábiles' }}
                                </button>
                            </div>
                        </div>

                        <!-- Aviso Crítico Normativo (Obligatorio e Inalterable) -->
                        <div class="py-4 flex items-center justify-between gap-4 bg-slate-50/70 -mx-6 px-6 rounded-b-2xl">
                            <div class="flex items-start gap-3">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-200 text-slate-600">
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
                                        Por normativa de Indecopi (Ley N° 29571 / D.S. 011-2011-PCM), los avisos críticos de casos con plazo vencido no pueden desactivarse para prevenir sanciones administrativas.
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 text-slate-400 font-bold text-xs select-none">
                                <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-emerald-700">Siempre Activo</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botón de Guardar -->
                <div class="flex items-center justify-end gap-3">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-xl bg-blue-600 px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-blue-700 disabled:opacity-50 transition"
                    >
                        {{ form.processing ? 'Guardando Preferencias...' : 'Guardar Configuración' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

