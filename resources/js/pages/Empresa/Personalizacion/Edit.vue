<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface Props {
    empresa: {
        id: number;
        name: string;
        razon_social: string;
        ruc: string;
        slug: string;
        color_primario: string;
        logo_path: string | null;
    };
    paletaSegura: Array<{ hex: string; nombre: string }>;
    urlPublica: string;
}

const props = defineProps<Props>();

const form = useForm({
    color_primario: props.empresa.color_primario || '#2563EB',
    logo: null as File | null,
});

const previewLogoUrl = ref<string | null>(props.empresa.logo_path);
const fileInput = ref<HTMLInputElement | null>(null);

function onFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        form.logo = file;
        previewLogoUrl.value = URL.createObjectURL(file);
    }
}

function seleccionarColor(hex: string) {
    form.color_primario = hex;
}

function submit() {
    form.post('/configuracion/personalizacion', {
        forceFormData: true,
        onSuccess: () => {
            form.logo = null;
        },
    });
}

function eliminarLogo() {
    if (confirm('¿Deseas eliminar el logotipo actual? Se mostrará el nombre comercial en su lugar.')) {
        router.delete('/configuracion/personalizacion/logo', {
            onSuccess: () => {
                previewLogoUrl.value = null;
                form.logo = null;
                if (fileInput.value) fileInput.value.value = '';
            },
        });
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Personalización Visual (RF-009) - Datec Libro" />

        <div class="mx-auto max-w-5xl space-y-6">
            <!-- Header con pestañas de navegación de la empresa -->
            <div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-md bg-blue-100 px-2.5 py-0.5 text-xs font-bold text-blue-800">RF-009</span>
                            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                                Personalización Visual del Libro
                            </h1>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">
                            Personaliza el logotipo y color corporativo sin alterar el contenido legal obligatorio (RN-008).
                        </p>
                    </div>

                    <div>
                        <a
                            :href="urlPublica"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50"
                        >
                            <span>Ver Libro Público</span>
                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Tabs de configuración de empresa -->
                <nav class="mt-6 flex border-b border-slate-200 gap-6 text-sm font-medium">
                    <Link
                        href="/configuracion/empresa"
                        class="border-b-2 border-transparent pb-3 text-slate-500 hover:border-slate-300 hover:text-slate-700"
                    >
                        Identidad Legal 
                    </Link>
                    <Link
                        href="/configuracion/personalizacion"
                        class="border-b-2 border-blue-600 pb-3 text-blue-600 font-semibold"
                    >
                        Personalización Visual 
                    </Link>
                    <Link
                        href="/configuracion/establecimientos"
                        class="border-b-2 border-transparent pb-3 text-slate-500 hover:border-slate-300 hover:text-slate-700"
                    >
                        Establecimientos y Sedes 
                    </Link>
                </nav>
            </div>

            <!-- SIMULADOR / PREVISUALIZACIÓN EN VIVO (CRITERIO DE ACEPTACIÓN RF-009) -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        Previsualización en tiempo real del Libro Público
                    </h2>
                    <span class="text-[11px] text-slate-400 font-mono">/l/{{ empresa.slug }}</span>
                </div>

                <!-- Cabecera simulada -->
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex items-center justify-between bg-white rounded-lg p-3 border border-slate-200 shadow-2xs">
                        <div class="flex items-center gap-3">
                            <img
                                v-if="previewLogoUrl"
                                :src="previewLogoUrl"
                                alt="Logo"
                                class="h-10 max-w-[140px] object-contain rounded"
                            />
                            <div
                                v-else
                                class="flex h-10 w-10 items-center justify-center rounded-lg text-white font-bold text-sm shadow-xs transition-colors"
                                :style="{ backgroundColor: form.color_primario }"
                            >
                                {{ empresa.name.charAt(0).toUpperCase() }}
                            </div>

                            <div>
                                <h3 class="font-bold text-slate-900 text-sm leading-tight">{{ empresa.name }}</h3>
                                <p class="text-[11px] text-slate-500 font-mono">RUC: {{ empresa.ruc }}</p>
                            </div>
                        </div>

                        <div class="hidden sm:inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold text-white shadow-2xs"
                             :style="{ backgroundColor: form.color_primario }">
                            <span>Libro de Reclamaciones</span>
                        </div>
                    </div>
                </div>
                <p class="text-[11px] text-slate-500">
                    Si no se sube un logo, se mostrará el nombre comercial con la inicial de marca. La Razón Social y el RUC siempre permanecen inalterados conforme a ley.
                </p>
            </div>

            <!-- FORMULARIO DE PERSONALIZACIÓN -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- SECCIÓN 1: SUBIDA DE LOGOTIPO -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">1</span>
                            Logotipo de la Empresa (Opcional)
                        </h2>

                        <button
                            v-if="empresa.logo_path"
                            type="button"
                            @click="eliminarLogo"
                            class="text-xs font-semibold text-rose-600 hover:text-rose-800 hover:underline"
                        >
                            Quitar logotipo actual
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 items-center">
                        <!-- Vista previa actual -->
                        <div class="flex flex-col items-center justify-center border-2 border-dashed border-slate-200 rounded-xl p-6 bg-slate-50/50 text-center">
                            <div v-if="previewLogoUrl" class="space-y-2">
                                <img :src="previewLogoUrl" alt="Logo Previo" class="max-h-20 max-w-full mx-auto object-contain" />
                                <span class="block text-[11px] text-emerald-700 font-medium">Logotipo seleccionado</span>
                            </div>
                            <div v-else class="text-slate-400 space-y-1">
                                <svg class="h-10 w-10 mx-auto text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span class="block text-xs font-medium text-slate-500">Sin logotipo</span>
                                <span class="block text-[10px]">Se usará la inicial corporativa</span>
                            </div>
                        </div>

                        <!-- Selector de archivo -->
                        <div class="sm:col-span-2 space-y-2">
                            <label class="block text-xs font-semibold text-slate-700">Seleccionar archivo de imagen</label>
                            <input
                                ref="fileInput"
                                type="file"
                                accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                @change="onFileChange"
                                class="w-full text-xs text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"
                            />
                            <p class="text-[11px] text-slate-500">
                                Formatos admitidos: PNG, JPG, JPEG, WEBP o SVG transparente. Tamaño máximo: 2 MB.
                            </p>
                            <div v-if="form.errors.logo" class="text-xs text-rose-600">{{ form.errors.logo }}</div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 2: COLOR PRIMARIO ACCESIBLE -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-2 flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">2</span>
                        Color Primario de Marca (RF-009)
                    </h2>

                    <p class="text-xs text-slate-500">
                        Selecciona un color de nuestra paleta segura de alto contraste o ingresa tu código HEX institucional:
                    </p>

                    <!-- Paleta de colores predefinida -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <button
                            v-for="color in paletaSegura"
                            :key="color.hex"
                            type="button"
                            @click="seleccionarColor(color.hex)"
                            class="flex items-center gap-2.5 rounded-xl border p-2.5 text-left transition hover:shadow-2xs"
                            :class="form.color_primario.toUpperCase() === color.hex.toUpperCase() ? 'border-blue-600 bg-blue-50/50 ring-2 ring-blue-500/20' : 'border-slate-200 bg-white'"
                        >
                            <span
                                class="h-6 w-6 rounded-lg shrink-0 shadow-xs border border-black/10"
                                :style="{ backgroundColor: color.hex }"
                            ></span>
                            <div class="truncate">
                                <span class="block text-xs font-medium text-slate-800 truncate">{{ color.nombre }}</span>
                                <span class="block font-mono text-[10px] text-slate-400">{{ color.hex }}</span>
                            </div>
                        </button>
                    </div>

                    <!-- Selector personalizado -->
                    <div class="pt-2 flex items-center gap-3">
                        <label class="text-xs font-semibold text-slate-700">O elige un color personalizado:</label>
                        <input
                            v-model="form.color_primario"
                            type="color"
                            class="h-9 w-14 cursor-pointer rounded-lg border border-slate-300 bg-white p-1"
                        />
                        <input
                            v-model="form.color_primario"
                            type="text"
                            maxlength="7"
                            class="w-28 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-mono uppercase focus:border-blue-500 focus:outline-hidden"
                        />
                        <div v-if="form.errors.color_primario" class="text-xs text-rose-600">{{ form.errors.color_primario }}</div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-bold text-white shadow-xs hover:bg-blue-700 disabled:opacity-50 transition"
                    >
                        {{ form.processing ? 'Guardando cambios...' : 'Guardar Personalización' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

