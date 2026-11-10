<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface EmpresaData {
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
    plan: string;
    estado: string;
    vigencia_desde: string | null;
    vigencia_hasta: string | null;
    limite_reclamaciones_mes: number | null;
}

interface Props {
    empresa: EmpresaData;
    estados: Array<{ value: string; label: string }>;
    planes: Array<{ value: string; label: string }>;
}

const props = defineProps<Props>();

const form = useForm({
    name: props.empresa.name,
    razon_social: props.empresa.razon_social,
    ruc: props.empresa.ruc,
    slug: props.empresa.slug,
    direccion: props.empresa.direccion,
    email: props.empresa.email || '',
    telefono: props.empresa.telefono || '',
    representante_nombre: props.empresa.representante_nombre || '',
    representante_email: props.empresa.representante_email || '',
    representante_telefono: props.empresa.representante_telefono || '',
    color_primario: props.empresa.color_primario || '#2563EB',
    plan: props.empresa.plan,
    estado: props.empresa.estado,
    vigencia_desde: props.empresa.vigencia_desde || '',
    vigencia_hasta: props.empresa.vigencia_hasta || '',
    limite_reclamaciones_mes: props.empresa.limite_reclamaciones_mes || '',
});

function submit() {
    form.put(`/admin/empresas/${props.empresa.id}`);
}
</script>

<template>
    <AdminLayout>
        <Head :title="`Editar ${empresa.name} - Datec Libro`" />

        <div class="mx-auto max-w-4xl space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Editar Empresa: {{ empresa.name }}</h1>
                    <p class="text-sm text-slate-500">
                        Modifica los datos comerciales, legales o suscripción de la empresa cliente.
                    </p>
                </div>
                <Link
                    href="/admin/empresas"
                    class="rounded-full border border-slate-300 bg-white px-5 py-2 text-xs font-bold text-slate-700 shadow-xs hover:bg-slate-50 transition"
                >
                    Volver al listado
                </Link>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Identificación legal y comercial -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-2 flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">1</span>
                        Identificación Legal y Comercial
                    </h2>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700">Nombre Comercial / Nombre de Fantasía *</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-hidden"
                            />
                            <div v-if="form.errors.name" class="mt-1 text-xs text-rose-600">{{ form.errors.name }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Razón Social *</label>
                            <input
                                v-model="form.razon_social"
                                type="text"
                                required
                                class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-hidden"
                            />
                            <div v-if="form.errors.razon_social" class="mt-1 text-xs text-rose-600">{{ form.errors.razon_social }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700">RUC (11 dígitos) *</label>
                            <input
                                v-model="form.ruc"
                                type="text"
                                maxlength="11"
                                required
                                class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm font-mono focus:border-blue-500 focus:outline-hidden"
                            />
                            <div v-if="form.errors.ruc" class="mt-1 text-xs text-rose-600">{{ form.errors.ruc }}</div>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700">Dirección Web Personalizada (Enlace del Libro) *</label>
                            <div class="mt-1 flex rounded-lg shadow-xs">
                                <span class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-300 bg-slate-50 px-3 text-xs text-slate-500 font-mono">
                                    https://libro.datec.pe/l/
                                </span>
                                <input
                                    v-model="form.slug"
                                    type="text"
                                    required
                                    class="w-full rounded-r-lg border border-slate-300 p-2.5 text-sm font-mono focus:border-blue-500 focus:outline-hidden"
                                />
                            </div>
                            <div v-if="form.errors.slug" class="mt-1 text-xs text-rose-600">{{ form.errors.slug }}</div>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700">Domicilio Legal / Dirección *</label>
                            <input
                                v-model="form.direccion"
                                type="text"
                                required
                                class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-hidden"
                            />
                            <div v-if="form.errors.direccion" class="mt-1 text-xs text-rose-600">{{ form.errors.direccion }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Correo de Contacto</label>
                            <input
                                v-model="form.email"
                                type="email"
                                class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-hidden"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Teléfono de Contacto</label>
                            <input
                                v-model="form.telefono"
                                type="text"
                                class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-hidden"
                            />
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 2: REPRESENTANTE OPERATIVO -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                    <h2 class="text-base font-semibold text-slate-900 border-b border-slate-100 pb-2 flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">2</span>
                        Representante Operativo de la Empresa
                    </h2>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Nombre del Representante</label>
                            <input
                                v-model="form.representante_nombre"
                                type="text"
                                class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-hidden"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Correo del Representante</label>
                            <input
                                v-model="form.representante_email"
                                type="email"
                                class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-hidden"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Teléfono</label>
                            <input
                                v-model="form.representante_telefono"
                                type="text"
                                class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-hidden"
                            />
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 3: PLAN, SUSCRIPCIÓN Y ESTADO -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                    <h2 class="text-base font-semibold text-slate-900 border-b border-slate-100 pb-2 flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">3</span>
                        Plan de Suscripción y Estado del Servicio
                    </h2>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-700">Plan Asignado</label>
                            <div class="mt-1 flex items-center justify-between rounded-xl border border-blue-200 bg-blue-50/60 p-2.5 text-xs font-bold text-blue-900 shadow-2xs">
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                    </svg>
                                    <span>Plan Corporativo / E-Commerce</span>
                                </div>
                                <span class="rounded-full bg-blue-600 px-2 py-0.5 text-[10px] font-extrabold uppercase text-white">
                                    Full Activo
                                </span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700">Estado del Servicio *</label>
                            <select
                                v-model="form.estado"
                                class="mt-1 w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-none"
                            >
                                <option v-for="e in estados" :key="e.value" :value="e.value">{{ e.label }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Vigencia Desde</label>
                            <input
                                v-model="form.vigencia_desde"
                                type="date"
                                class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-hidden"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Vigencia Hasta</label>
                            <input
                                v-model="form.vigencia_hasta"
                                type="date"
                                class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-hidden"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Límite Reclamaciones/Mes</label>
                            <input
                                v-model="form.limite_reclamaciones_mes"
                                type="number"
                                min="1"
                                class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-hidden"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Color Primario de Marca</label>
                            <div class="mt-1 flex items-center gap-3">
                                <input
                                    v-model="form.color_primario"
                                    type="color"
                                    class="h-10 w-16 cursor-pointer rounded-lg border border-slate-300 bg-white p-1"
                                />
                                <span class="font-mono text-xs text-slate-600">{{ form.color_primario }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4">
                    <Link
                        href="/admin/empresas"
                        class="rounded-full border border-slate-300 bg-white px-5 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition"
                    >
                        Cancelar
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-full bg-blue-600 px-7 py-2.5 text-xs sm:text-sm font-bold text-white shadow-md shadow-blue-500/25 hover:bg-blue-700 transition disabled:opacity-50 cursor-pointer"
                    >
                        {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

