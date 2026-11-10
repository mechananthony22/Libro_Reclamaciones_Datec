<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

interface Props {
    show: boolean;
    estados: Array<{ value: string; label: string }>;
    planes: Array<{ value: string; label: string }>;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'close'): void;
}>();

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
    limite_reclamaciones_mes: '' as string | number,
});

watch(
    () => props.show,
    (abierto) => {
        if (abierto) {
            form.reset();
            form.clearErrors();
            form.vigencia_desde = new Date().toISOString().split('T')[0];
        }
    }
);

function cerrar() {
    emit('close');
}

function submit() {
    form.post('/admin/empresas', {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
            form.reset();
        },
    });
}

const claseEtiqueta = 'mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500';
const claseCampo =
    'w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500';
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 sm:p-6 backdrop-blur-xs overflow-y-auto animate-fade-in"
    >
        <div
            class="relative flex max-h-[90vh] w-full max-w-3xl flex-col rounded-2xl border border-slate-200 bg-white shadow-2xl overflow-hidden"
            @click.stop
        >
            <!-- Cabecera del Modal -->
            <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-md shadow-blue-500/25">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">Registrar Nueva Empresa</h2>
                        <p class="text-xs text-slate-500">Crea una organización cliente con sus datos legales, plan y configuración inicial.</p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="cerrar"
                    class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-200 hover:text-slate-700 cursor-pointer"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Cuerpo Scrolleable del Formulario -->
            <form @submit.prevent="submit" class="flex flex-col overflow-hidden flex-1">
                <div class="flex-1 overflow-y-auto p-6 space-y-6">
                    <!-- Sección 1: Identificación Legal y Comercial -->
                    <div class="space-y-4 rounded-xl border border-slate-200/80 bg-slate-50/40 p-4 sm:p-5">
                        <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">1</span>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Identificación Legal y Comercial</h3>
                        </div>

                        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label :class="claseEtiqueta">Nombre Comercial / Nombre de Fantasía *</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="Ej. Tienda ABC"
                                    :class="claseCampo"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-[11px] text-rose-600">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label :class="claseEtiqueta">Razón Social *</label>
                                <input
                                    v-model="form.razon_social"
                                    type="text"
                                    required
                                    placeholder="Ej. ABC RETAIL S.A.C."
                                    :class="claseCampo"
                                />
                                <p v-if="form.errors.razon_social" class="mt-1 text-[11px] text-rose-600">{{ form.errors.razon_social }}</p>
                            </div>

                            <div>
                                <label :class="claseEtiqueta">RUC (11 dígitos) *</label>
                                <input
                                    v-model="form.ruc"
                                    type="text"
                                    maxlength="11"
                                    required
                                    placeholder="20123456789"
                                    :class="[claseCampo, 'font-mono']"
                                />
                                <p v-if="form.errors.ruc" class="mt-1 text-[11px] text-rose-600">{{ form.errors.ruc }}</p>
                            </div>

                            <div class="sm:col-span-2">
                                <label :class="claseEtiqueta">Dirección Web Personalizada (Enlace del Libro - Opcional)</label>
                                <div class="flex">
                                    <span class="inline-flex items-center rounded-l-xl border border-r-0 border-slate-200 bg-slate-50 px-3 font-mono text-[11px] font-semibold text-slate-500">
                                        https://libro.datec.pe/l/
                                    </span>
                                    <input
                                        v-model="form.slug"
                                        type="text"
                                        placeholder="tienda-abc (se genera automáticamente si se deja vacío)"
                                        :class="[claseCampo, 'rounded-l-none font-mono']"
                                    />
                                </div>
                                <p v-if="form.errors.slug" class="mt-1 text-[11px] text-rose-600">{{ form.errors.slug }}</p>
                            </div>

                            <div class="sm:col-span-2">
                                <label :class="claseEtiqueta">Domicilio Legal / Dirección *</label>
                                <input
                                    v-model="form.direccion"
                                    type="text"
                                    required
                                    placeholder="Av. Javier Prado Este 1234, San Isidro, Lima"
                                    :class="claseCampo"
                                />
                                <p v-if="form.errors.direccion" class="mt-1 text-[11px] text-rose-600">{{ form.errors.direccion }}</p>
                            </div>

                            <div>
                                <label :class="claseEtiqueta">Correo de Contacto</label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    placeholder="contacto@tiendaabc.pe"
                                    :class="claseCampo"
                                />
                            </div>

                            <div>
                                <label :class="claseEtiqueta">Teléfono de Contacto</label>
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
                    <div class="space-y-4 rounded-xl border border-slate-200/80 bg-slate-50/40 p-4 sm:p-5">
                        <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700">2</span>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Representante Operativo de la Empresa</h3>
                        </div>

                        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-3">
                            <div>
                                <label :class="claseEtiqueta">Nombre</label>
                                <input
                                    v-model="form.representante_nombre"
                                    type="text"
                                    placeholder="Carlos Mendoza"
                                    :class="claseCampo"
                                />
                            </div>
                            <div>
                                <label :class="claseEtiqueta">Correo</label>
                                <input
                                    v-model="form.representante_email"
                                    type="email"
                                    placeholder="carlos@tiendaabc.pe"
                                    :class="claseCampo"
                                />
                            </div>
                            <div>
                                <label :class="claseEtiqueta">Teléfono</label>
                                <input
                                    v-model="form.representante_telefono"
                                    type="text"
                                    placeholder="+51 912345678"
                                    :class="claseCampo"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Sección 3: Plan de Suscripción y Estado -->
                    <div class="space-y-4 rounded-xl border border-slate-200/80 bg-slate-50/40 p-4 sm:p-5">
                        <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-amber-100 text-xs font-bold text-amber-700">3</span>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Plan de Suscripción y Estado del Servicio</h3>
                        </div>

                        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                            <div>
                                <label :class="claseEtiqueta">Plan Asignado *</label>
                                <select v-model="form.plan" :class="[claseCampo, 'cursor-pointer font-semibold']">
                                    <option v-for="p in planes" :key="p.value" :value="p.value">{{ p.label }}</option>
                                </select>
                            </div>

                            <div>
                                <label :class="claseEtiqueta">Estado Inicial *</label>
                                <select v-model="form.estado" :class="[claseCampo, 'cursor-pointer font-semibold']">
                                    <option v-for="e in estados" :key="e.value" :value="e.value">{{ e.label }}</option>
                                </select>
                            </div>

                            <div>
                                <label :class="claseEtiqueta">Vigencia Desde</label>
                                <input v-model="form.vigencia_desde" type="date" :class="claseCampo" />
                            </div>

                            <div>
                                <label :class="claseEtiqueta">Vigencia Hasta (Opcional)</label>
                                <input v-model="form.vigencia_hasta" type="date" :class="claseCampo" />
                            </div>

                            <div>
                                <label :class="claseEtiqueta">Límite Reclamaciones/Mes</label>
                                <input
                                    v-model="form.limite_reclamaciones_mes"
                                    type="number"
                                    min="1"
                                    placeholder="Ilimitado si se deja vacío"
                                    :class="claseCampo"
                                />
                            </div>

                            <div>
                                <label :class="claseEtiqueta">Color Primario de Marca</label>
                                <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-1.5 pr-3">
                                    <input
                                        v-model="form.color_primario"
                                        type="color"
                                        class="h-8 w-12 cursor-pointer rounded-lg border border-slate-200 bg-white p-0.5"
                                    />
                                    <span class="rounded-lg bg-slate-100 px-2 py-1 font-mono text-[11px] font-semibold text-slate-600">{{ form.color_primario }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Barra de Acciones Fija en el Pie -->
                <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/70 px-6 py-4">
                    <button
                        type="button"
                        @click="cerrar"
                        class="rounded-xl px-4 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-200 cursor-pointer"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-bold text-white shadow-md shadow-blue-500/20 transition hover:bg-blue-700 disabled:opacity-50"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>{{ form.processing ? 'Registrando...' : 'Crear y Registrar Empresa' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

