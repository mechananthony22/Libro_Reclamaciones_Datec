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

const claseEtiqueta = 'mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500';
const claseCampo =
    'w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500';
</script>

<template>
    <AdminLayout>
        <Head title="Identidad Legal y Fiscal - Datec Libro" />

        <div class="mx-auto max-w-5xl space-y-6">
            <!-- Cabecera de Página (Estilo Admin/Empresas) -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex items-start gap-4">
                    <div
                        class="hidden sm:flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-md shadow-blue-500/25"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            Configuración de Empresa · Normativa INDECOPI
                        </div>
                        <h1 class="mt-0.5 text-2xl font-extrabold tracking-tight text-slate-900">
                            Identidad Legal y Fiscal
                        </h1>
                        <p class="mt-1 text-sm text-slate-500">
                            Registro oficial de Razón Social, RUC, domicilio legal y contactos requeridos por ley para la validez de tu Libro de Reclamaciones.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a
                        :href="urlPublica"
                        target="_blank"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50 hover:text-slate-900"
                    >
                        <span>Ver Libro Público</span>
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Pestañas de Navegación de Configuración -->
            <div class="flex gap-1 overflow-x-auto rounded-xl bg-slate-100/80 p-1 shadow-2xs">
                <Link
                    href="/configuracion/empresa"
                    class="rounded-lg bg-blue-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-md shadow-blue-500/20 transition"
                >
                    Identidad Legal
                </Link>
                <Link
                    href="/configuracion/personalizacion"
                    class="rounded-lg px-3.5 py-1.5 text-xs font-bold text-slate-600 hover:bg-white hover:text-slate-900 transition"
                >
                    Logo y Colores
                </Link>
                <Link
                    href="/configuracion/establecimientos"
                    class="rounded-lg px-3.5 py-1.5 text-xs font-bold text-slate-600 hover:bg-white hover:text-slate-900 transition"
                >
                    Establecimientos / Sedes
                </Link>
            </div>

            <!-- Checklist de Cumplimiento Legal -->
            <div
                :class="[
                    'space-y-4 rounded-2xl border p-6 shadow-2xs transition',
                    tieneMinimos
                        ? 'border-emerald-200 bg-emerald-50/40'
                        : 'border-amber-200 bg-amber-50/40'
                ]"
            >
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b pb-4" :class="tieneMinimos ? 'border-emerald-200' : 'border-amber-200'">
                    <div class="flex items-center gap-3">
                        <span
                            :class="[
                                'flex h-9 w-9 items-center justify-center rounded-xl font-bold text-sm shadow-2xs',
                                tieneMinimos ? 'bg-emerald-200 text-emerald-800' : 'bg-amber-200 text-amber-900'
                            ]"
                        >
                            {{ tieneMinimos ? '✓' : '!' }}
                        </span>
                        <div>
                            <h2 class="text-sm font-bold" :class="tieneMinimos ? 'text-emerald-950' : 'text-amber-950'">
                                {{ tieneMinimos ? 'Requisitos Legales Mínimos Completos' : 'Requisitos Legales Mínimos Pendientes' }}
                            </h2>
                        </div>
                    </div>

                    <span
                        :class="[
                            'inline-flex items-center gap-1.5 self-start sm:self-auto rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wider',
                            empresa.estado === 'activa' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700'
                        ]"
                    >
                        <span :class="['h-2 w-2 rounded-full', empresa.estado === 'activa' ? 'bg-emerald-500' : 'bg-slate-400']"></span>
                        <span>Estado: {{ empresa.estado }}</span>
                    </span>
                </div>

                <!-- Lista de verificación -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                    <div
                        v-for="(item, key) in checklist"
                        :key="key"
                        class="rounded-xl bg-white p-3.5 border shadow-2xs flex flex-col justify-between transition hover:shadow-xs"
                        :class="item.cumplido ? 'border-emerald-200' : 'border-amber-200'"
                    >
                        <div>
                            <div class="flex items-center gap-1.5 font-bold" :class="item.cumplido ? 'text-emerald-800' : 'text-amber-800'">
                                <svg v-if="item.cumplido" class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <svg v-else class="h-4 w-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span>{{ item.titulo }}</span>
                            </div>
                            <p class="mt-1 text-[11px] text-slate-500">
                                {{ item.detalle }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulario de Identidad Legal -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Sección 1: Datos Fiscales del Proveedor -->
                <div class="space-y-4 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-2xs">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Datos Fiscales y Domicilio Legal</h2>
                            <p class="text-xs text-slate-500">Información tributaria y de contacto visible al consumidor en la cabecera del libro.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label :class="claseEtiqueta">Nombre Comercial / Nombre de Fantasía *</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="Ej. Tienda ABC"
                                :class="claseCampo"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-[11px] text-rose-600 font-semibold">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label :class="claseEtiqueta">Razón Social Registrada *</label>
                            <input
                                v-model="form.razon_social"
                                type="text"
                                required
                                placeholder="Ej. ABC COMERCIALIZADORA S.A.C."
                                :class="claseCampo"
                            />
                            <p v-if="form.errors.razon_social" class="mt-1 text-[11px] text-rose-600 font-semibold">{{ form.errors.razon_social }}</p>
                        </div>

                        <div>
                            <label :class="claseEtiqueta">Número de RUC (11 dígitos) *</label>
                            <input
                                v-model="form.ruc"
                                type="text"
                                maxlength="11"
                                required
                                placeholder="20123456789"
                                :class="[claseCampo, 'font-mono']"
                            />
                            <p class="mt-1 text-[11px] text-slate-400">SUNAT: Debe iniciar con 10 o 20.</p>
                            <p v-if="form.errors.ruc" class="mt-1 text-[11px] text-rose-600 font-semibold">{{ form.errors.ruc }}</p>
                        </div>

                        <div>
                            <label :class="claseEtiqueta">Correo Electrónico para Reclamaciones *</label>
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                placeholder="libro.reclamaciones@tiendaabc.pe"
                                :class="claseCampo"
                            />
                            <p class="mt-1 text-[11px] text-slate-400">Recibirá copia de las constancias emitidas por cada reclamo.</p>
                            <p v-if="form.errors.email" class="mt-1 text-[11px] text-rose-600 font-semibold">{{ form.errors.email }}</p>
                        </div>

                        <div class="sm:col-span-2">
                            <label :class="claseEtiqueta">Domicilio Fiscal / Legal Completo *</label>
                            <input
                                v-model="form.direccion"
                                type="text"
                                required
                                placeholder="Av. República de Panamá 3456, San Isidro, Lima - Perú"
                                :class="claseCampo"
                            />
                            <p v-if="form.errors.direccion" class="mt-1 text-[11px] text-rose-600 font-semibold">{{ form.errors.direccion }}</p>
                        </div>

                        <div>
                            <label :class="claseEtiqueta">Teléfono Oficial de Contacto</label>
                            <input
                                v-model="form.telefono"
                                type="text"
                                placeholder="+51 987654321"
                                :class="claseCampo"
                            />
                        </div>
                    </div>
                </div>

                <!-- Sección 2: Representante Operativo -->
                <div class="space-y-4 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-2xs">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-purple-50 text-purple-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Representante Legal u Operativo</h2>
                            <p class="text-xs text-slate-500">Persona natural encargada de la atención o representación del libro ante la autoridad.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label :class="claseEtiqueta">Nombre del Responsable</label>
                            <input
                                v-model="form.representante_nombre"
                                type="text"
                                placeholder="María González"
                                :class="claseCampo"
                            />
                        </div>

                        <div>
                            <label :class="claseEtiqueta">Correo Electrónico</label>
                            <input
                                v-model="form.representante_email"
                                type="email"
                                placeholder="maria.gonzalez@tiendaabc.pe"
                                :class="claseCampo"
                            />
                        </div>

                        <div>
                            <label :class="claseEtiqueta">Teléfono Móvil</label>
                            <input
                                v-model="form.representante_telefono"
                                type="text"
                                placeholder="+51 912345678"
                                :class="claseCampo"
                            />
                        </div>
                    </div>
                </div>

                <!-- Sección 3: Activación Pública -->
                <div v-if="empresa.estado !== 'activa'" class="rounded-2xl border border-blue-200/80 bg-blue-50/50 p-5 shadow-2xs">
                    <div class="flex items-start gap-3">
                        <input
                            id="activar-check"
                            v-model="form.solicitar_activacion"
                            type="checkbox"
                            class="h-4 w-4 mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                        />
                        <div>
                            <label for="activar-check" class="text-xs font-bold text-blue-950 cursor-pointer">
                                Solicitar activación y publicación inmediata del Libro de Reclamaciones
                            </label>
                            <p class="text-[11px] text-blue-800 mt-0.5">
                                Al marcar esta opción, el sistema verificará que los datos legales mínimos estén completos y cambiará el estado de la empresa a <strong>Activa</strong> para permitir registros de consumidores en <code class="font-mono text-blue-900">/l/{{ empresa.slug }}</code>.
                            </p>
                            <p v-if="form.errors.solicitar_activacion" class="mt-1 text-xs text-rose-600 font-semibold">
                                {{ form.errors.solicitar_activacion }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="cursor-pointer rounded-xl bg-blue-600 px-6 py-2.5 text-xs font-bold text-white shadow-md shadow-blue-500/20 transition hover:bg-blue-700 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Guardando configuración...' : 'Guardar Identidad Legal' }}
                    </button>
                </div>
            </form>

            <!-- Sección 4: Enlace y Botón Oficial para Tienda Virtual -->
            <div class="space-y-4 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-2xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">
                                Enlace y Botón Oficial para tu Sitio Web o Tienda
                            </h2>
                            <p class="text-xs text-slate-500">
                                Coloca este código en el pie de página (footer) de tu sitio web según exige INDECOPI.
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="copiarCodigoHtml"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50"
                    >
                        <svg v-if="!copiadoHtml" class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <svg v-else class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ copiadoHtml ? '¡Copiado!' : 'Copiar Código' }}</span>
                    </button>
                </div>

                <div class="rounded-xl bg-slate-900 p-4 text-xs font-mono text-slate-200 overflow-x-auto shadow-inner">
                    <code>{{ codigoHtml }}</code>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
