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
const logoError = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

function onFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        form.logo = file;
        logoError.value = false;
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
        <Head title="Logo y Colores - Datec Libro" />

        <div class="mx-auto max-w-5xl space-y-6">
            <!-- Cabecera de Página (Estilo Admin/Empresas) -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex items-start gap-4">
                    <div
                        class="hidden sm:flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-md shadow-blue-500/25"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            Configuración de Empresa · Imagen Institucional
                        </div>
                        <h1 class="mt-0.5 text-2xl font-extrabold tracking-tight text-slate-900">
                            Logo y Colores del Libro
                        </h1>
                        <p class="mt-1 text-sm text-slate-500">
                            Personaliza el logotipo y color corporativo sin alterar el contenido legal obligatorio del libro.
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
                    class="rounded-lg px-3.5 py-1.5 text-xs font-bold text-slate-600 hover:bg-white hover:text-slate-900 transition"
                >
                    Identidad Legal
                </Link>
                <Link
                    href="/configuracion/personalizacion"
                    class="rounded-lg bg-blue-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-md shadow-blue-500/20 transition"
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

            <!-- Previsualización en Vivo de Cabecera -->
            <div class="space-y-4 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-2xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Previsualización en Tiempo Real</h2>
                            <p class="text-xs text-slate-500">Así verán tus clientes la cabecera del portal público en <span class="font-mono text-slate-700">/l/{{ empresa.slug }}</span>.</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex items-center justify-between rounded-xl border border-slate-200/80 bg-white p-4 shadow-2xs">
                        <div class="flex items-center gap-3.5">
                            <img
                                v-if="previewLogoUrl && !logoError"
                                :src="previewLogoUrl"
                                alt="Logo"
                                class="h-10 max-w-[140px] rounded-lg object-contain"
                                @error="logoError = true"
                            />
                            <div
                                v-else
                                class="flex h-11 w-11 items-center justify-center rounded-xl text-white font-extrabold text-sm shadow-2xs transition-colors"
                                :style="{ backgroundColor: form.color_primario }"
                            >
                                {{ empresa.name.charAt(0).toUpperCase() }}
                            </div>

                            <div>
                                <h3 class="text-sm font-extrabold text-slate-900 leading-tight">{{ empresa.name }}</h3>
                                <p class="text-[11px] font-mono text-slate-400">RUC: {{ empresa.ruc }}</p>
                            </div>
                        </div>

                        <div
                            class="hidden sm:inline-flex items-center gap-1.5 rounded-full px-3.5 py-1 text-xs font-bold text-white shadow-2xs"
                            :style="{ backgroundColor: form.color_primario }"
                        >
                            <span>Libro de Reclamaciones</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulario de Personalización -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Sección 1: Logotipo -->
                <div class="space-y-4 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-2xs">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-purple-50 text-purple-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-slate-900">Logotipo de la Empresa</h2>
                                <p class="text-xs text-slate-500">Formato horizontal recomendado con fondo transparente.</p>
                            </div>
                        </div>

                        <button
                            v-if="empresa.logo_path"
                            type="button"
                            @click="eliminarLogo"
                            class="cursor-pointer text-xs font-bold text-rose-600 hover:text-rose-700 hover:underline"
                        >
                            Quitar logotipo actual
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 items-center">
                        <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/60 p-6 text-center">
                            <div v-if="previewLogoUrl && !logoError" class="space-y-2">
                                <img
                                    :src="previewLogoUrl"
                                    alt="Logo Previo"
                                    class="max-h-20 max-w-full mx-auto object-contain"
                                    @error="logoError = true"
                                />
                                <span class="block text-[11px] font-bold text-emerald-700">Logotipo seleccionado</span>
                            </div>
                            <div v-else class="text-slate-400 space-y-1">
                                <svg class="h-10 w-10 mx-auto text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span class="block text-xs font-semibold text-slate-600">Sin logotipo</span>
                                <span class="block text-[10px] text-slate-400">Se usará la inicial corporativa</span>
                            </div>
                        </div>

                        <div class="sm:col-span-2 space-y-2">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500">Seleccionar imagen</label>
                            <input
                                ref="fileInput"
                                type="file"
                                accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                @change="onFileChange"
                                class="w-full text-xs text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"
                            />
                            <p class="text-[11px] text-slate-400">
                                Formatos permitidos: PNG, JPG, JPEG, WEBP o SVG transparente. Tamaño máximo: 2 MB.
                            </p>
                            <div v-if="form.errors.logo" class="text-xs text-rose-600 font-semibold">{{ form.errors.logo }}</div>
                        </div>
                    </div>
                </div>

                <!-- Sección 2: Color Primario -->
                <div class="space-y-4 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-2xs">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Color Primario de Marca</h2>
                            <p class="text-xs text-slate-500">Paleta segura de alto contraste para botones y cabeceras del portal.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <button
                            v-for="color in paletaSegura"
                            :key="color.hex"
                            type="button"
                            @click="seleccionarColor(color.hex)"
                            class="flex cursor-pointer items-center gap-2.5 rounded-xl border p-2.5 text-left transition hover:shadow-2xs"
                            :class="form.color_primario.toUpperCase() === color.hex.toUpperCase() ? 'border-blue-600 bg-blue-50/50 ring-2 ring-blue-500/20' : 'border-slate-200 bg-white hover:border-slate-300'"
                        >
                            <span
                                class="h-6 w-6 rounded-lg shrink-0 shadow-xs border border-black/10"
                                :style="{ backgroundColor: color.hex }"
                            ></span>
                            <div class="truncate">
                                <span class="block text-xs font-bold text-slate-800 truncate">{{ color.nombre }}</span>
                                <span class="block font-mono text-[10px] text-slate-400">{{ color.hex }}</span>
                            </div>
                        </button>
                    </div>

                    <!-- Selector personalizado -->
                    <div class="pt-2 flex items-center gap-3">
                        <label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">O ingresa un color personalizado:</label>
                        <input
                            v-model="form.color_primario"
                            type="color"
                            class="h-9 w-14 cursor-pointer rounded-xl border border-slate-200 bg-white p-1"
                        />
                        <input
                            v-model="form.color_primario"
                            type="text"
                            maxlength="7"
                            class="w-28 rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-mono uppercase focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                        <div v-if="form.errors.color_primario" class="text-xs text-rose-600 font-semibold">{{ form.errors.color_primario }}</div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="cursor-pointer rounded-xl bg-blue-600 px-6 py-2.5 text-xs font-bold text-white shadow-md shadow-blue-500/20 transition hover:bg-blue-700 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Guardando cambios...' : 'Guardar Personalización' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
