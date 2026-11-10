<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface EstablecimientoItem {
    id: number;
    codigo: string | null;
    nombre: string;
    direccion: string;
    es_principal: boolean;
    activo: boolean;
    created_at: string;
}

interface Props {
    empresa: {
        id: number;
        name: string;
        slug: string;
        direccion: string;
    };
    establecimientos: EstablecimientoItem[];
    total: number;
    totalActivos: number;
}

const props = defineProps<Props>();

// Modal de Crear / Editar
const showModal = ref(false);
const modoEdicion = ref(false);
const establecimientoEditando = ref<EstablecimientoItem | null>(null);

const form = useForm({
    codigo: '',
    nombre: '',
    direccion: '',
    es_principal: false,
    activo: true,
});

function abrirModalCrear() {
    modoEdicion.value = false;
    establecimientoEditando.value = null;
    form.reset();
    form.codigo = '';
    form.nombre = '';
    form.direccion = props.empresa.direccion || '';
    form.es_principal = props.establecimientos.length === 0;
    form.activo = true;
    showModal.value = true;
}

function abrirModalEditar(est: EstablecimientoItem) {
    modoEdicion.value = true;
    establecimientoEditando.value = est;
    form.codigo = est.codigo || '';
    form.nombre = est.nombre;
    form.direccion = est.direccion;
    form.es_principal = est.es_principal;
    form.activo = est.activo;
    showModal.value = true;
}

function submitForm() {
    if (modoEdicion.value && establecimientoEditando.value) {
        form.put(`/configuracion/establecimientos/${establecimientoEditando.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post('/configuracion/establecimientos', {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
}

function toggleActivo(est: EstablecimientoItem) {
    router.patch(`/configuracion/establecimientos/${est.id}/toggle`, {}, {
        preserveScroll: true,
    });
}

function eliminar(est: EstablecimientoItem) {
    if (confirm(`¿Estás seguro de eliminar el establecimiento "${est.nombre}"?`)) {
        router.delete(`/configuracion/establecimientos/${est.id}`, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Gestión de Establecimientos (RF-010) - Datec Libro" />

        <div class="mx-auto max-w-5xl space-y-6">
            <!-- Header con pestañas -->
            <div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-md bg-blue-100 px-2.5 py-0.5 text-xs font-bold text-blue-800">RF-010</span>
                            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                                Establecimientos y Sedes
                            </h1>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">
                            Administración de locales, sucursales y puntos de atención de la empresa para la recepción de reclamaciones.
                        </p>
                    </div>

                    <div>
                        <button
                            type="button"
                            @click="abrirModalCrear"
                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-blue-700 transition"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Nueva Sede / Local</span>
                        </button>
                    </div>
                </div>

                <!-- Tabs de navegación -->
                <nav class="mt-6 flex border-b border-slate-200 gap-6 text-sm font-medium">
                    <Link
                        href="/configuracion/empresa"
                        class="border-b-2 border-transparent pb-3 text-slate-500 hover:border-slate-300 hover:text-slate-700"
                    >
                        Identidad Legal 
                    </Link>
                    <Link
                        href="/configuracion/personalizacion"
                        class="border-b-2 border-transparent pb-3 text-slate-500 hover:border-slate-300 hover:text-slate-700"
                    >
                        Personalización Visual 
                    </Link>
                    <Link
                        href="/configuracion/establecimientos"
                        class="border-b-2 border-blue-600 pb-3 text-blue-600 font-semibold"
                    >
                        Establecimientos y Sedes 
                    </Link>
                </nav>
            </div>

            <!-- AVISO DE REGLA NORMATIVA (CRITERIO DE ACEPTACIÓN ) -->
            <div class="rounded-2xl border border-blue-200 bg-blue-50/60 p-4 shadow-xs">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 text-blue-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="text-xs text-blue-900 leading-relaxed">
                        <strong>Criterio de aceptación RF-010:</strong> Si la empresa cuenta con <strong>más de una sede activa</strong> (actualmente tienes {{ totalActivos }} activas), el formulario público del Libro de Reclamaciones exigirá obligatoriamente al consumidor seleccionar el local o sucursal donde adquirió el producto o servicio.
                    </div>
                </div>
            </div>

            <!-- TABLA DE ESTABLECIMIENTOS -->
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200">
                            <tr>
                                <th scope="col" class="px-6 py-3.5">Código</th>
                                <th scope="col" class="px-6 py-3.5">Nombre de la Sede</th>
                                <th scope="col" class="px-6 py-3.5">Dirección Física</th>
                                <th scope="col" class="px-6 py-3.5 text-center">Tipo</th>
                                <th scope="col" class="px-6 py-3.5 text-center">Estado</th>
                                <th scope="col" class="px-6 py-3.5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr v-for="est in establecimientos" :key="est.id" class="hover:bg-slate-50/70 transition-colors">
                                <!-- Código -->
                                <td class="px-6 py-3.5 font-mono text-xs text-slate-700">
                                    {{ est.codigo || '-' }}
                                </td>

                                <!-- Nombre -->
                                <td class="px-6 py-3.5 font-semibold text-slate-900">
                                    {{ est.nombre }}
                                </td>

                                <!-- Dirección -->
                                <td class="px-6 py-3.5 text-xs text-slate-600 max-w-xs truncate">
                                    {{ est.direccion }}
                                </td>

                                <!-- Es principal -->
                                <td class="px-6 py-3.5 text-center">
                                    <span
                                        v-if="est.es_principal"
                                        class="inline-flex items-center rounded-full bg-blue-100 border border-blue-200 px-2.5 py-0.5 text-xs font-semibold text-blue-800"
                                    >
                                        Sede Principal
                                    </span>
                                    <span v-else class="text-xs text-slate-400 font-normal">Sucursal</span>
                                </td>

                                <!-- Estado Toggle -->
                                <td class="px-6 py-3.5 text-center">
                                    <button
                                        type="button"
                                        @click="toggleActivo(est)"
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold border transition"
                                        :class="est.activo ? 'bg-emerald-50 text-emerald-800 border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200'"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="est.activo ? 'bg-emerald-600' : 'bg-slate-400'"></span>
                                        {{ est.activo ? 'Activo' : 'Inactivo' }}
                                    </button>
                                </td>

                                <!-- Acciones -->
                                <td class="px-6 py-3.5 text-right whitespace-nowrap space-x-2">
                                    <button
                                        type="button"
                                        @click="abrirModalEditar(est)"
                                        class="rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                    >
                                        Editar
                                    </button>
                                    <button
                                        type="button"
                                        @click="eliminar(est)"
                                        class="rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50"
                                    >
                                        Eliminar
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="establecimientos.length === 0">
                                <td colspan="6" class="px-6 py-10 text-center text-slate-500">
                                    <p class="font-medium text-slate-700">No hay establecimientos adicionales registrados.</p>
                                    <p class="text-xs text-slate-400 mt-1">Por defecto, el Libro de Reclamaciones utiliza el domicilio fiscal de la empresa.</p>
                                    <button
                                        type="button"
                                        @click="abrirModalCrear"
                                        class="mt-3 inline-flex items-center gap-1 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700"
                                    >
                                        Registrar primera sede
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODAL CREAR / EDITAR ESTABLECIMIENTO (RF-010) -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl border border-slate-200 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-lg font-bold text-slate-900">
                        {{ modoEdicion ? 'Editar Establecimiento / Sede' : 'Nuevo Establecimiento / Sede' }}
                    </h3>
                    <button
                        type="button"
                        @click="showModal = false"
                        class="text-slate-400 hover:text-slate-600"
                    >
                        ✕
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Nombre de la Sede / Sucursal *</label>
                        <input
                            v-model="form.nombre"
                            type="text"
                            required
                            placeholder="Ej. Tienda Miraflores, Almacén Central, Sede Principal"
                            class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-hidden"
                        />
                        <div v-if="form.errors.nombre" class="mt-1 text-xs text-rose-600">{{ form.errors.nombre }}</div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Código Interno (Opcional)</label>
                            <input
                                v-model="form.codigo"
                                type="text"
                                placeholder="Ej. SEDE-01"
                                class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm font-mono focus:border-blue-500 focus:outline-hidden"
                            />
                        </div>

                        <div class="flex items-center pt-6 gap-2">
                            <input
                                id="es-principal-check"
                                v-model="form.es_principal"
                                type="checkbox"
                                class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                            />
                            <label for="es-principal-check" class="text-xs font-semibold text-slate-700 cursor-pointer">
                                Sede Principal
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Dirección Física del Establecimiento *</label>
                        <textarea
                            v-model="form.direccion"
                            rows="2"
                            required
                            placeholder="Ej. Av. Larco 789, Miraflores, Lima"
                            class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-blue-500 focus:outline-hidden"
                        ></textarea>
                        <div v-if="form.errors.direccion" class="mt-1 text-xs text-rose-600">{{ form.errors.direccion }}</div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input
                            id="activo-check"
                            v-model="form.activo"
                            type="checkbox"
                            class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        />
                        <label for="activo-check" class="text-xs font-semibold text-slate-700 cursor-pointer">
                            Establecimiento Habilitado / Activo
                        </label>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Guardando...' : (modoEdicion ? 'Actualizar Sede' : 'Guardar Sede') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

