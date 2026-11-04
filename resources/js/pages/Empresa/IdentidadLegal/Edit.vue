<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface ChecklistItem {
    titulo: string;
    cumplido: boolean;
    detalle: string;
}

interface Props {
    empresa: {
        id: number;
        name: string;
        slug: string;
        razon_social: string;
        ruc: string;
        direccion: string;
        email: string | null;
        telefono: string | null;
        representante_nombre: string | null;
        representante_email: string | null;
        representante_telefono: string | null;
        color_primario: string | null;
        estado: string;
        activo: boolean;
    };
    checklist: Record<string, ChecklistItem>;
    tieneMinimos: boolean;
    urlPublica: string;
}

const props = defineProps<Props>();
const page = usePage();

const form = useForm({
    name: props.empresa.name || '',
    razon_social: props.empresa.razon_social || '',
    ruc: props.empresa.ruc || '',
    direccion: props.empresa.direccion || '',
    email: props.empresa.email || '',
    telefono: props.empresa.telefono || '',
    representante_nombre: props.empresa.representante_nombre || '',
    representante_email: props.empresa.representante_email || '',
    representante_telefono: props.empresa.representante_telefono || '',
    solicitar_activacion: false,
});

function submit() {
    form.put('/configuracion/empresa');
}

const codigoHtml = computed(() => {
    return `<a href="${props.urlPublica}" target="_blank" rel="noopener noreferrer">
    <img src="https://libro.datec.pe/reclamaciones-badge.svg" alt="Libro de Reclamaciones Virtual - ${props.empresa.name}" />
</a>`;
});

const copiadoHtml = ref(false);
function copiarCodigoHtml() {
    navigator.clipboard.writeText(codigoHtml.value);
    copiadoHtml.value = true;
    setTimeout(() => {
        copiadoHtml.value = false;
    }, 2500);
}
</script>

<template>
    <AdminLayout>
        <Head title="Identidad Legal y Fiscal - Datec Libro" />

        <div class="mx-auto max-w-5xl space-y-6">
            <!-- Encabezado -->
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-md bg-blue-100 px-2.5 py-0.5 text-xs font-bold text-blue-800">RF-008</span>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                            Configurar Identidad Legal y Fiscal
                        </h1>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">
                        Registro oficial de Razón Social, RUC, domicilio legal y contactos requeridos por INDECOPI para la validez del Libro de Reclamaciones.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <a
                        :href="urlPublica"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition"
                    >
                        <span>Ver Libro Público</span>
                        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Tabs de configuración de empresa -->
            <nav class="flex border-b border-slate-200 gap-6 text-sm font-medium">
                <Link
                    href="/configuracion/empresa"
                    class="border-b-2 border-blue-600 pb-3 text-blue-600 font-semibold"
                >
                    Identidad Legal (RF-008)
                </Link>
                <Link
                    href="/configuracion/personalizacion"
                    class="border-b-2 border-transparent pb-3 text-slate-500 hover:border-slate-300 hover:text-slate-700"
                >
                    Personalización Visual (RF-009)
                </Link>
                <Link
                    href="/configuracion/establecimientos"
                    class="border-b-2 border-transparent pb-3 text-slate-500 hover:border-slate-300 hover:text-slate-700"
                >
                    Establecimientos y Sedes (RF-010)
                </Link>
            </nav>

            <!-- CHECKLIST DE CUMPLIMIENTO LEGAL (CRITERIO DE ACEPTACIÓN RF-008) -->
            <div
                class="rounded-2xl border p-5 shadow-xs transition"
                :class="tieneMinimos ? 'border-emerald-200 bg-emerald-50/40' : 'border-amber-200 bg-amber-50/40'"
            >
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b pb-3" :class="tieneMinimos ? 'border-emerald-200' : 'border-amber-200'">
                    <div class="flex items-center gap-2.5">
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-xl font-bold text-sm"
                            :class="tieneMinimos ? 'bg-emerald-200 text-emerald-800' : 'bg-amber-200 text-amber-900'"
                        >
                            {{ tieneMinimos ? '✓' : '!' }}
                        </span>
                        <div>
                            <h3 class="text-sm font-bold" :class="tieneMinimos ? 'text-emerald-900' : 'text-amber-950'">
                                {{ tieneMinimos ? 'Requisitos Legales Mínimos Completos' : 'Requisitos Legales Incompletos (RN-002)' }}
                            </h3>
                            <p class="text-xs" :class="tieneMinimos ? 'text-emerald-700' : 'text-amber-800'">
                                {{ tieneMinimos ? 'La empresa cuenta con los datos exigidos por ley para operar su Libro de Reclamaciones.' : 'Criterio de aceptación RF-008: Sin estos datos completos, no se podrá activar el formulario público.' }}
                            </p>
                        </div>
                    </div>

                    <span
                        class="self-start sm:self-auto rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wider border"
                        :class="empresa.estado === 'activa' ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-slate-100 text-slate-700 border-slate-300'"
                    >
                        Estado: {{ empresa.estado }}
                    </span>
                </div>

                <!-- Lista de verificación -->
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                    <div
                        v-for="(item, key) in checklist"
                        :key="key"
                        class="rounded-xl bg-white p-3 border shadow-2xs flex flex-col justify-between"
                        :class="item.cumplido ? 'border-emerald-200' : 'border-amber-200'"
                    >
                        <div>
                            <div class="flex items-center gap-1.5 font-semibold" :class="item.cumplido ? 'text-emerald-800' : 'text-amber-800'">
                                <svg v-if="item.cumplido" class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <svg v-else class="h-4 w-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span>{{ item.titulo }}</span>
                            </div>
                            <p class="mt-1 text-[11px] text-slate-500 line-clamp-1">
                                {{ item.detalle }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FORMULARIO DE IDENTIDAD LEGAL (RF-008) -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- SECCIÓN 1: DATOS FISCALES DEL PROVEEDOR -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-2 flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">1</span>
                        Datos Fiscales y Domicilio Legal
                    </h2>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Nombre Comercial / Fantasía *</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="Ej. Tienda ABC"
                                class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-hidden"
                            />
                            <div v-if="form.errors.name" class="mt-1 text-xs text-rose-600">{{ form.errors.name }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Razón Social Registrada *</label>
                            <input
                                v-model="form.razon_social"
                                type="text"
                                required
                                placeholder="Ej. ABC COMERCIALIZADORA S.A.C."
                                class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-hidden"
                            />
                            <div v-if="form.errors.razon_social" class="mt-1 text-xs text-rose-600">{{ form.errors.razon_social }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Número de RUC (11 dígitos) *</label>
                            <input
                                v-model="form.ruc"
                                type="text"
                                maxlength="11"
                                required
                                placeholder="20123456789"
                                class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm font-mono focus:border-blue-500 focus:outline-hidden"
                            />
                            <p class="mt-1 text-[11px] text-slate-500">SUNAT: Debe iniciar con 10 o 20.</p>
                            <div v-if="form.errors.ruc" class="mt-1 text-xs text-rose-600">{{ form.errors.ruc }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Correo Electrónico para Reclamaciones *</label>
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                placeholder="libro.reclamaciones@tiendaabc.pe"
                                class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-hidden"
                            />
                            <p class="mt-1 text-[11px] text-slate-500">Recibirá las constancias de cada nuevo registro presentado.</p>
                            <div v-if="form.errors.email" class="mt-1 text-xs text-rose-600">{{ form.errors.email }}</div>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700">Domicilio Fiscal / Legal Completo *</label>
                            <input
                                v-model="form.direccion"
                                type="text"
                                required
                                placeholder="Av. República de Panamá 3456, San Isidro, Lima - Perú"
                                class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-hidden"
                            />
                            <div v-if="form.errors.direccion" class="mt-1 text-xs text-rose-600">{{ form.errors.direccion }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Teléfono Oficial de Contacto</label>
                            <input
                                v-model="form.telefono"
                                type="text"
                                placeholder="+51 987654321"
                                class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-hidden"
                            />
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 2: REPRESENTANTE OPERATIVO -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-2 flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">2</span>
                        Representante Operativo del Libro
                    </h2>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Nombre del Responsable</label>
                            <input
                                v-model="form.representante_nombre"
                                type="text"
                                placeholder="María González"
                                class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-hidden"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Correo Electrónico</label>
                            <input
                                v-model="form.representante_email"
                                type="email"
                                placeholder="maria.gonzalez@tiendaabc.pe"
                                class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-hidden"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Teléfono Móvil</label>
                            <input
                                v-model="form.representante_telefono"
                                type="text"
                                placeholder="+51 912345678"
                                class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-hidden"
                            />
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 3: ACTIVACIÓN PÚBLICA (CRITERIO DE ACEPTACIÓN RF-008) -->
                <div v-if="empresa.estado !== 'activa'" class="rounded-2xl border border-blue-200 bg-blue-50/50 p-5 shadow-xs">
                    <div class="flex items-start gap-3">
                        <input
                            id="activar-check"
                            v-model="form.solicitar_activacion"
                            type="checkbox"
                            class="h-4 w-4 mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        />
                        <div>
                            <label for="activar-check" class="text-sm font-bold text-blue-950 cursor-pointer">
                                Solicitar activación y publicación inmediata del Libro de Reclamaciones
                            </label>
                            <p class="text-xs text-blue-800 mt-0.5">
                                Al marcar esta opción, el sistema verificará que los datos legales mínimos estén completos y cambiará el estado de la empresa a <strong>Activa</strong> para permitir registros de consumidores en <code class="font-mono text-blue-900">/l/{{ empresa.slug }}</code>.
                            </p>
                            <div v-if="form.errors.solicitar_activacion" class="mt-1 text-xs text-rose-600 font-semibold">
                                {{ form.errors.solicitar_activacion }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-bold text-white shadow-xs hover:bg-blue-700 disabled:opacity-50 transition"
                    >
                        {{ form.processing ? 'Guardando configuración...' : 'Guardar Identidad Legal' }}
                    </button>
                </div>
            </form>

            <!-- SECCIÓN 4: CÓDIGO DE INTEGRACIÓN PARA E-COMMERCE -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            Enlace y Botón Oficial para tu Tienda Virtual
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Copia este enlace o código HTML para colocarlo visible en el pie de página (footer) de tu sitio web según exige INDECOPI.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="copiarCodigoHtml"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100"
                    >
                        <svg v-if="!copiadoHtml" class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <svg v-else class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ copiadoHtml ? '¡Copiado!' : 'Copiar HTML' }}</span>
                    </button>
                </div>

                <div class="rounded-xl bg-slate-900 p-4 text-xs font-mono text-slate-200 overflow-x-auto">
                    <code>{{ codigoHtml }}</code>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

