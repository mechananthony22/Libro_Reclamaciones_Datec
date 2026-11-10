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
    router.patch(
        `/configuracion/establecimientos/${est.id}/toggle`,
        {},
        {
            preserveScroll: true,
        }
    );
}

function eliminar(est: EstablecimientoItem) {
    if (confirm(`¿Estás seguro de eliminar el establecimiento "${est.nombre}"?`)) {
        router.delete(`/configuracion/establecimientos/${est.id}`, {
            preserveScroll: true,
        });
    }
}

const claseEtiqueta = 'mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500';
const claseCampo =
    'w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500';
</script>

<template>
    <AdminLayout>
        <Head title="Establecimientos y Sedes - Datec Libro" />

        <div class="mx-auto max-w-5xl space-y-6">
            <!-- Cabecera de Página (Estilo Admin/Empresas) -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex items-start gap-4">
                    <div
                        class="hidden sm:flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-md shadow-blue-500/25"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            Configuración de Empresa · Locales y Puntos de Atención
                        </div>
                        <h1 class="mt-0.5 text-2xl font-extrabold tracking-tight text-slate-900">
                            Establecimientos y Sedes
                        </h1>
                        <p class="mt-1 text-sm text-slate-500">
                            Administración de locales, sucursales y puntos de atención de la empresa para la recepción de reclamaciones.
                        </p>
                    </div>
                </div>

                <div>
                    <button
                        type="button"
                        @click="abrirModalCrear"
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-blue-500/20 transition hover:bg-blue-700"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Nueva Sede / Local</span>
                    </button>
                </div>
            </div>

            <!-- Pestañas de Navegación de Configuración -->
            <div class="flex gap-1 overflow-x-auto rounded-xl bg-slate-100/80 p-1 shadow-2xs">
                <Link
                    href="/configuracion/empresa"
                    class="rounded-lg px-3.5 py-1.5 text-xs font-bold text-slate-600 hover:bg-white hover:text-slate-900 transition"
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
                    class="rounded-lg bg-blue-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-md shadow-blue-500/20 transition"
                >
                    Establecimientos / Sedes
                </Link>
            </div>

            <!-- Aviso Normativo -->
            <div class="rounded-2xl border border-blue-200/80 bg-blue-50/60 p-5 shadow-2xs">
                <div class="flex items-start gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="text-xs text-blue-900 leading-relaxed">
                        <strong>Criterio Normativo:</strong> Si tu empresa cuenta con <strong>más de una sede activa</strong> (actualmente tienes {{ totalActivos }} activas), el formulario público del Libro de Reclamaciones solicitará al consumidor seleccionar la sede o local donde realizó la transacción.
                    </div>
                </div>
            </div>

            <!-- Tabla de Establecimientos (Estilo Admin/Empresas) -->
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="py-3 px-5">Código</th>
                                <th class="py-3 px-5">Nombre de la Sede</th>
                                <th class="py-3 px-5">Dirección Física</th>
                                <th class="py-3 px-5 text-center">Tipo</th>
                                <th class="py-3 px-5 text-center">Estado</th>
                                <th class="py-3 px-5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="est in establecimientos" :key="est.id" class="transition hover:bg-slate-50/60">
                                <td class="whitespace-nowrap py-3.5 px-5 font-mono text-slate-700 font-semibold">
                                    {{ est.codigo || '—' }}
                                </td>

                                <td class="py-3.5 px-5 font-bold text-slate-900">
                                    {{ est.nombre }}
                                </td>

                                <td class="py-3.5 px-5 text-slate-600 max-w-xs truncate" :title="est.direccion">
                                    {{ est.direccion }}
                                </td>

                                <td class="whitespace-nowrap py-3.5 px-5 text-center">
                                    <span
                                        v-if="est.es_principal"
                                        class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-[10px] font-bold text-blue-700 ring-1 ring-blue-600/20"
                                    >
                                        Sede Principal
                                    </span>
                                    <span v-else class="text-[11px] text-slate-400 font-medium">Sucursal</span>
                                </td>

                                <td class="whitespace-nowrap py-3.5 px-5 text-center">
                                    <button
                                        type="button"
                                        @click="toggleActivo(est)"
                                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[10px] font-bold ring-1 transition"
                                        :class="est.activo ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 ring-slate-400/20 hover:bg-slate-200'"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="est.activo ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                        {{ est.activo ? 'Activo' : 'Inactivo' }}
                                    </button>
                                </td>

                                <td class="whitespace-nowrap py-3.5 px-5 text-right space-x-2">
                                    <button
                                        type="button"
                                        @click="abrirModalEditar(est)"
                                        class="cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50"
                                    >
                                        Editar
                                    </button>
                                    <button
                                        type="button"
                                        @click="eliminar(est)"
                                        class="cursor-pointer rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-700 shadow-2xs transition hover:bg-rose-100"
                                    >
                                        Eliminar
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="establecimientos.length === 0">
                                <td colspan="6" class="py-12 text-center text-slate-400">
                                    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                    <p class="mt-3 text-xs font-semibold text-slate-700">No hay establecimientos adicionales registrados.</p>
                                    <p class="mt-1 text-[11px] text-slate-400">Por defecto, el Libro de Reclamaciones utiliza el domicilio fiscal de la empresa.</p>
                                    <button
                                        type="button"
                                        @click="abrirModalCrear"
                                        class="mt-3 inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-md shadow-blue-500/20 hover:bg-blue-700 transition"
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

        <!-- Modal Crear / Editar Establecimiento (Estilo CreateModal.vue) -->
        <div
            v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 sm:p-6 backdrop-blur-xs overflow-y-auto animate-fade-in"
            @click.self="showModal = false"
        >
            <div class="relative flex w-full max-w-lg flex-col rounded-2xl border border-slate-200 bg-white shadow-2xl overflow-hidden my-auto">
                <!-- Cabecera del Modal -->
                <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-md shadow-blue-500/25">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-extrabold text-slate-900">
                                {{ modoEdicion ? 'Editar Establecimiento / Sede' : 'Nuevo Establecimiento / Sede' }}
                            </h2>
                            <p class="text-xs text-slate-500">Punto de atención comercial y físico de tu libro de reclamaciones.</p>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="showModal = false"
                        class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-200 hover:text-slate-700 cursor-pointer"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="p-6 space-y-4 text-xs">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Nombre de la Sede / Sucursal <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="form.nombre"
                            type="text"
                            required
                            maxlength="150"
                            placeholder="Ej. Tienda Miraflores, Almacén Central, Sede Principal"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-800 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                        <p v-if="form.errors.nombre" class="mt-1 text-[11px] text-rose-600 font-semibold">{{ form.errors.nombre }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                                Código Interno <span class="text-slate-400 font-normal">(Opcional)</span>
                            </label>
                            <input
                                v-model="form.codigo"
                                type="text"
                                maxlength="30"
                                placeholder="Ej. SEDE-01"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-mono font-semibold text-slate-800 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            />
                            <p v-if="form.errors.codigo" class="mt-1 text-[11px] text-rose-600 font-semibold">{{ form.errors.codigo }}</p>
                        </div>

                        <div class="flex items-center sm:pt-6">
                            <label class="flex items-center gap-2.5 cursor-pointer select-none">
                                <input
                                    id="es-principal-check"
                                    v-model="form.es_principal"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                />
                                <span class="text-xs font-bold text-slate-700">
                                    Sede Principal
                                </span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Dirección Física del Establecimiento <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            v-model="form.direccion"
                            rows="2"
                            required
                            maxlength="255"
                            placeholder="Ej. Av. Larco 789, Miraflores, Lima"
                            class="w-full rounded-xl border border-slate-200 bg-white p-3.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 leading-relaxed"
                        ></textarea>
                        <p v-if="form.errors.direccion" class="mt-1 text-[11px] text-rose-600 font-semibold">{{ form.errors.direccion }}</p>
                    </div>

                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-3">
                        <label class="flex items-center gap-2.5 cursor-pointer select-none">
                            <input
                                id="activo-check"
                                v-model="form.activo"
                                type="checkbox"
                                class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                            />
                            <span class="text-xs font-bold text-slate-700">
                                Establecimiento Habilitado / Activo
                            </span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 hover:text-slate-900 transition cursor-pointer"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-bold text-white shadow-md shadow-blue-500/20 hover:bg-blue-700 disabled:opacity-50 transition cursor-pointer"
                        >
                            <svg v-if="!form.processing" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>{{ form.processing ? 'Guardando...' : (modoEdicion ? 'Actualizar Sede' : 'Guardar Sede') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
