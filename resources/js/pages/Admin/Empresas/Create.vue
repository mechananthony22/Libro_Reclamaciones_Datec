<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface Props {
    estados: Array<{ value: string; label: string }>;
    planes: Array<{ value: string; label: string }>;
}

const props = defineProps<Props>();

const form = useForm({
    name: '',
    razon_social: '',
    ruc: '',
    slug: '',
    direccion: '',
    email: '',
    telefono: '',
    representante_nombre: '',
    representante_email: '',
    representante_telefono: '',
    color_primario: '#2563EB',
    plan: 'basico',
    estado: 'borrador',
    vigencia_desde: new Date().toISOString().split('T')[0],
    vigencia_hasta: '',
    limite_reclamaciones_mes: '',
});

function submit() {
    form.post('/admin/empresas');
}
</script>

<template>
    <AdminLayout>
        <Head title="Nueva Empresa - Datec Libro" />

        <div class="mx-auto max-w-4xl space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Registrar Nueva Empresa</h1>
                    <p class="text-sm text-slate-500">
                        Crea una organización cliente con sus datos legales, plan y configuración inicial.
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
                                placeholder="Ej. Tienda ABC"
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
                                placeholder="Ej. ABC RETAIL S.A.C."
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
                                placeholder="20123456789"
                                class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm font-mono focus:border-blue-500 focus:outline-hidden"
                            />
                            <p class="mt-1 text-[11px] text-slate-500">Debe iniciar con 10 o 20 (normativa SUNAT).</p>
                            <div v-if="form.errors.ruc" class="mt-1 text-xs text-rose-600">{{ form.errors.ruc }}</div>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700">Enlace Personalizado / Slug (Opcional)</label>
                            <div class="mt-1 flex rounded-lg shadow-xs">
                                <span class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-300 bg-slate-50 px-3 text-xs text-slate-500 font-mono">
                                    https://libro.datec.pe/l/
                                </span>
                                <input
                                    v-model="form.slug"
                                    type="text"
                                    placeholder="tienda-abc (se genera automáticamente si se deja vacío)"
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
                                placeholder="Av. Javier Prado Este 1234, San Isidro, Lima"
                                class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-hidden"
                            />
                            <div v-if="form.errors.direccion" class="mt-1 text-xs text-rose-600">{{ form.errors.direccion }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Correo de Contacto</label>
                            <input
                                v-model="form.email"
                                type="email"
                                placeholder="contacto@tiendaabc.pe"
                                class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-hidden"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Teléfono de Contacto</label>
                            <input
                                v-model="form.telefono"
                                type="text"
                                placeholder="+51 987654321"
                                class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-hidden"
                            />
                        </div>
                    </div>
                </div>

                <!-- Representante operativo -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-2 flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">2</span>
                        Representante Operativo de la Empresa
                    </h2>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700">Nombre del Representante</label>
                            <input
                                v-model="form.representante_nombre"
                                type="text"
                                placeholder="Carlos Mendoza"
                                class="mt-1 w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-none"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700">Correo del Representante</label>
                            <input
                                v-model="form.representante_email"
                                type="email"
                                placeholder="carlos@tiendaabc.pe"
                                class="mt-1 w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-none"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700">Teléfono</label>
                            <input
                                v-model="form.representante_telefono"
                                type="text"
                                placeholder="+51 912345678"
                                class="mt-1 w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-none"
                            />
                        </div>
                    </div>
                </div>

                <!-- Plan, suscripción y estado -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-2 flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">3</span>
                        Plan de Suscripción y Estado del Servicio
                    </h2>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-700">Plan Asignado *</label>
                            <select
                                v-model="form.plan"
                                class="mt-1 w-full rounded-xl border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-none"
                            >
                                <option v-for="p in planes" :key="p.value" :value="p.value">{{ p.label }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700">Estado Inicial del Servicio *</label>
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
                            <label class="block text-xs font-semibold text-slate-700">Vigencia Hasta (Opcional)</label>
                            <input
                                v-model="form.vigencia_hasta"
                                type="date"
                                class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-hidden"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Límite Reclamaciones/Mes (Opcional)</label>
                            <input
                                v-model="form.limite_reclamaciones_mes"
                                type="number"
                                min="1"
                                placeholder="Ilimitado si se deja vacío"
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
                        {{ form.processing ? 'Registrando...' : 'Crear y Registrar Empresa' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

