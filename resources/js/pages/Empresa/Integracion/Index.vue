<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface SlugHistorialItem {
    id: number;
    slug_antiguo: string;
    url_antigua: string;
    motivo: string | null;
    cambiado_por: string;
    fecha: string;
}

interface Props {
    empresa: {
        id: number;
        name: string;
        razon_social: string;
        ruc: string;
        slug: string;
        color_primario: string;
    };
    urls: {
        libro: string;
        consulta: string;
    };
    snippets: {
        boton_estandar: string;
        enlace_texto: string;
        widget_flotante: string;
        enlace_consulta: string;
    };
    slugs_historicos: SlugHistorialItem[];
}

const props = defineProps<Props>();

// Gestión de copia al portapapeles con feedback temporal
const copiadoKey = ref<string | null>(null);

function copiarTexto(texto: string, clave: string) {
    navigator.clipboard.writeText(texto);
    copiadoKey.value = clave;
    setTimeout(() => {
        if (copiadoKey.value === clave) {
            copiadoKey.value = null;
        }
    }, 2500);
}

// Pestañas de fragmentos HTML
const tabSnippet = ref<'boton' | 'texto' | 'widget' | 'consulta'>('boton');
const snippetSeleccionado = computed(() => {
    switch (tabSnippet.value) {
        case 'boton': return props.snippets.boton_estandar;
        case 'texto': return props.snippets.enlace_texto;
        case 'widget': return props.snippets.widget_flotante;
        case 'consulta': return props.snippets.enlace_consulta;
    }
});

// Pestañas de guías de instalación
const tabGuia = ref<'shopify' | 'woocommerce' | 'html'>('shopify');

// Modal para Reconfiguración de Slug
const modalSlug = ref(false);
const formSlug = useForm({
    slug: '',
    motivo: '',
});

const validandoSlug = ref(false);
const slugDisponible = ref<boolean | null>(null);
const mensajeValidacion = ref('');

let debounceTimer: ReturnType<typeof setTimeout> | null = null;

function normalizarSlugInput(e: Event) {
    const target = e.target as HTMLInputElement;
    const limpio = target.value
        .toLowerCase()
        .replace(/[^a-z0-9-]/g, '-')
        .replace(/-+/g, '-');
    formSlug.slug = limpio;
}

watch(() => formSlug.slug, (val) => {
    if (debounceTimer) clearTimeout(debounceTimer);
    if (!val || val.length < 3) {
        slugDisponible.value = null;
        mensajeValidacion.value = val.length === 0 ? '' : 'Mínimo 3 caracteres alfanuméricos.';
        return;
    }

    if (val === props.empresa.slug) {
        slugDisponible.value = null;
        mensajeValidacion.value = 'Es tu dirección web actual.';
        return;
    }

    validandoSlug.value = true;
    debounceTimer = setTimeout(async () => {
        try {
            const res = await fetch('/configuracion/integracion/slug/verificar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                },
                body: JSON.stringify({ slug: val }),
            });
            const data = await res.json();
            slugDisponible.value = data.disponible;
            mensajeValidacion.value = data.mensaje;
        } catch {
            mensajeValidacion.value = 'No se pudo verificar la disponibilidad en este momento.';
        } finally {
            validandoSlug.value = false;
        }
    }, 400);
});

function abrirModalSlug() {
    formSlug.slug = '';
    formSlug.motivo = '';
    slugDisponible.value = null;
    mensajeValidacion.value = '';
    modalSlug.value = true;
}

function enviarCambioSlug() {
    formSlug.post('/configuracion/integracion/slug', {
        onSuccess: () => {
            modalSlug.value = false;
            formSlug.reset();
        },
    });
}

// Generador de QR SVG / Canvas URL rápida para mostradores físicos
const qrUrl = computed(() => {
    return `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(props.urls.libro)}`;
});
</script>

<template>
    <AdminLayout>
        <Head title="Botón Web y Enlace Oficial - Configuración" />

        <div class="space-y-6">
            <!-- Encabezado Oficial -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-blue-50 px-2 py-0.5 text-[11px] font-bold tracking-wide text-blue-700 border border-blue-200 uppercase">
                            Publicación en tu Tienda
                        </span>
                        <span class="text-xs text-slate-400">&bull;</span>
                        <span class="text-xs font-semibold text-slate-500">Ley N° 29571 &bull; Botón Visible Obligatorio</span>
                    </div>
                    <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">
                        Botón Web, Enlaces y Código QR
                    </h1>
                    <p class="mt-1 text-xs text-slate-500 max-w-2xl">
                        Herramientas para colocar el botón de tu Libro en el pie de página de tu tienda online o web, personalizar tu dirección web oficial y descargar el código QR para tus locales físicos.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <a
                        :href="urls.libro"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-blue-700 transition"
                    >
                        <span>Probar enlace en vivo</span>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Tarjeta Central: URLs Públicas Oficiales -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <div class="h-8 w-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Direcciones Web Oficiales</h2>
                            <p class="text-xs text-slate-400">Acceso público asignado a {{ empresa.razon_social || empresa.name }}</p>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="abrirModalSlug"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-100 transition"
                    >
                        <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        <span>Personalizar Dirección Web</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <!-- URL del Formulario Público -->
                    <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-700 uppercase tracking-wider text-[11px]">Enlace Público del Libro</span>
                            <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                Principal
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <input
                                type="text"
                                readonly
                                :value="urls.libro"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-mono text-slate-800 select-all"
                            />
                            <button
                                type="button"
                                @click="copiarTexto(urls.libro, 'url_libro')"
                                class="shrink-0 rounded-lg bg-blue-600 px-3 py-2 text-xs font-bold text-white hover:bg-blue-700 transition"
                            >
                                {{ copiadoKey === 'url_libro' ? '¡Copiado!' : 'Copiar' }}
                            </button>
                        </div>
                        <p class="text-[11px] text-slate-500">
                            Coloca este enlace en el pie de página de tu tienda online para cumplir con la exigencia legal de Indecopi.
                        </p>
                    </div>

                    <!-- URL de Consulta de Reclamos -->
                    <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-700 uppercase tracking-wider text-[11px]">Enlace de Seguimiento de Reclamos</span>
                            <span class="text-[10px] font-semibold text-blue-700 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-200">
                                Consulta
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <input
                                type="text"
                                readonly
                                :value="urls.consulta"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-mono text-slate-800 select-all"
                            />
                            <button
                                type="button"
                                @click="copiarTexto(urls.consulta, 'url_consulta')"
                                class="shrink-0 rounded-lg bg-slate-700 px-3 py-2 text-xs font-bold text-white hover:bg-slate-800 transition"
                            >
                                {{ copiadoKey === 'url_consulta' ? '¡Copiado!' : 'Copiar' }}
                            </button>
                        </div>
                        <p class="text-[11px] text-slate-500">
                            Permite a tus clientes consultar de forma autónoma el estado y respuesta de sus expedientes registrados.
                        </p>
                    </div>
                </div>

                <!-- Historial de Direcciones Web Redirigidas si existen -->
                <div v-if="slugs_historicos.length > 0" class="pt-3 border-t border-slate-100">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2 flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Direcciones Web Anteriores (Redirección Automática Segura)</span>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="w-full text-left text-[11px]">
                            <thead class="bg-slate-50 text-slate-500 font-bold uppercase">
                                <tr>
                                    <th class="px-3 py-2">Dirección Anterior</th>
                                    <th class="px-3 py-2">Redirige a</th>
                                    <th class="px-3 py-2">Fecha del Cambio</th>
                                    <th class="px-3 py-2">Motivo</th>
                                    <th class="px-3 py-2">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr v-for="h in slugs_historicos" :key="h.id">
                                    <td class="px-3 py-2 font-mono text-slate-700 font-bold">/l/{{ h.slug_antiguo }}</td>
                                    <td class="px-3 py-2 font-mono text-blue-600">/l/{{ empresa.slug }}</td>
                                    <td class="px-3 py-2 text-slate-500">{{ h.fecha }}</td>
                                    <td class="px-3 py-2 text-slate-600 max-w-xs truncate">{{ h.motivo || 'Actualización de dirección' }}</td>
                                    <td class="px-3 py-2">
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 border border-emerald-200">
                                            Activa y Segura
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Sección de Fragmentos HTML con Previsualización en Vivo -->
            <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-2xs">
                <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Botón Oficial para tu Tienda Online o Página Web</h2>
                        <p class="text-xs text-slate-400">Elige la presentación visual que prefieras y cópiala en un solo clic.</p>
                    </div>

                    <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl text-xs font-bold">
                        <button
                            type="button"
                            @click="tabSnippet = 'boton'"
                            :class="['px-3 py-1.5 rounded-lg transition', tabSnippet === 'boton' ? 'bg-white text-blue-600 shadow-2xs' : 'text-slate-600 hover:text-slate-900']"
                        >
                            Botón Oficial
                        </button>
                        <button
                            type="button"
                            @click="tabSnippet = 'texto'"
                            :class="['px-3 py-1.5 rounded-lg transition', tabSnippet === 'texto' ? 'bg-white text-blue-600 shadow-2xs' : 'text-slate-600 hover:text-slate-900']"
                        >
                            Enlace de Texto
                        </button>
                        <button
                            type="button"
                            @click="tabSnippet = 'widget'"
                            :class="['px-3 py-1.5 rounded-lg transition', tabSnippet === 'widget' ? 'bg-white text-blue-600 shadow-2xs' : 'text-slate-600 hover:text-slate-900']"
                        >
                            Botón Flotante
                        </button>
                        <button
                            type="button"
                            @click="tabSnippet = 'consulta'"
                            :class="['px-3 py-1.5 rounded-lg transition', tabSnippet === 'consulta' ? 'bg-white text-blue-600 shadow-2xs' : 'text-slate-600 hover:text-slate-900']"
                        >
                            Enlace de Consulta
                        </button>
                    </div>
                </div>

                <div class="p-5 grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Previsualización Visual en Vivo -->
                    <div class="space-y-3">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            Vista Previa en Vivo
                        </div>
                        <div class="min-h-[160px] rounded-2xl border border-slate-200 bg-slate-50/70 p-6 flex flex-col items-center justify-center text-center">
                            <div v-if="tabSnippet === 'boton'">
                                <a
                                    :href="urls.libro"
                                    target="_blank"
                                    class="inline-flex items-center gap-2.5 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-800 shadow-2xs hover:bg-slate-50 transition"
                                >
                                    <svg class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                    <span>Libro de Reclamaciones</span>
                                </a>
                                <p class="text-[11px] text-slate-400 mt-2">Recomendado para el pie de página (footer) corporativo.</p>
                            </div>

                            <div v-else-if="tabSnippet === 'texto'">
                                <a
                                    :href="urls.libro"
                                    target="_blank"
                                    class="text-xs font-bold text-blue-600 underline hover:text-blue-800 transition"
                                >
                                    Libro de Reclamaciones
                                </a>
                                <p class="text-[11px] text-slate-400 mt-2">Integrable en listas de enlaces del menú footer.</p>
                            </div>

                            <div v-else-if="tabSnippet === 'widget'">
                                <div
                                    class="inline-flex items-center gap-2 rounded-full px-4 py-2.5 text-xs font-bold text-white shadow-md"
                                    :style="{ backgroundColor: empresa.color_primario || '#2563EB' }"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                    <span>Libro de Reclamaciones</span>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-2">Aparece fijo en la esquina inferior de tu web sin alterar el layout.</p>
                            </div>

                            <div v-else-if="tabSnippet === 'consulta'">
                                <a
                                    :href="urls.consulta"
                                    target="_blank"
                                    class="text-xs font-bold text-slate-700 underline hover:text-blue-600 transition"
                                >
                                    Consultar estado de mi reclamo o queja
                                </a>
                                <p class="text-[11px] text-slate-400 mt-2">Enlace directo para seguimiento de reclamaciones previas.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Código Listo para Copiar -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Código Listo para Copiar</span>
                            <button
                                type="button"
                                @click="copiarTexto(snippetSeleccionado, 'snippet')"
                                class="inline-flex items-center gap-1.5 text-xs font-bold text-blue-600 hover:text-blue-700 transition cursor-pointer"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                </svg>
                                <span>{{ copiadoKey === 'snippet' ? '¡Código Copiado!' : 'Copiar Código' }}</span>
                            </button>
                        </div>

                        <div class="relative">
                            <textarea
                                readonly
                                rows="6"
                                :value="snippetSeleccionado"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-900 p-4 font-mono text-xs text-emerald-400 focus:outline-none select-all"
                            ></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Guías de Instalación en Plataformas E-commerce & Código QR Físico -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Guías Paso a Paso (2 columnas) -->
                <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">¿Cómo agregarlo a tu tienda o página web?</h2>
                            <p class="text-xs text-slate-400">Instrucciones sencillas para publicar el botón en pocos minutos.</p>
                        </div>

                        <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl text-xs font-semibold">
                            <button
                                type="button"
                                @click="tabGuia = 'shopify'"
                                :class="['px-3 py-1 rounded-lg transition font-bold', tabGuia === 'shopify' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600']"
                            >
                                Shopify
                            </button>
                            <button
                                type="button"
                                @click="tabGuia = 'woocommerce'"
                                :class="['px-3 py-1 rounded-lg transition font-bold', tabGuia === 'woocommerce' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600']"
                            >
                                WooCommerce
                            </button>
                            <button
                                type="button"
                                @click="tabGuia = 'html'"
                                :class="['px-3 py-1 rounded-lg transition font-bold', tabGuia === 'html' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600']"
                            >
                                Cualquier otra Web (HTML)
                            </button>
                        </div>
                    </div>

                    <!-- Guía Shopify -->
                    <div v-if="tabGuia === 'shopify'" class="space-y-3 text-xs text-slate-600">
                        <div class="flex items-start gap-3">
                            <span class="flex h-6 w-6 rounded-full bg-blue-100 text-blue-700 font-bold items-center justify-center shrink-0">1</span>
                            <div>
                                <strong class="text-slate-800">Menú de Navegación del Footer:</strong>
                                Ingresa a tu panel de Shopify, ve a <em>Tienda online &gt; Navegación &gt; Menú del pie de página (Footer menu)</em>.
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="flex h-6 w-6 rounded-full bg-blue-100 text-blue-700 font-bold items-center justify-center shrink-0">2</span>
                            <div>
                                <strong class="text-slate-800">Agregar elemento de menú:</strong>
                                Haz clic en <em>Agregar elemento de menú</em>, coloca de nombre <code>Libro de Reclamaciones</code> y en el campo de enlace pega tu URL:
                                <code class="block bg-slate-100 p-1.5 rounded-lg text-slate-800 font-mono mt-1 text-[11px]">{{ urls.libro }}</code>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="flex h-6 w-6 rounded-full bg-blue-100 text-blue-700 font-bold items-center justify-center shrink-0">3</span>
                            <div>
                                <strong class="text-slate-800">Opción con Botón HTML:</strong>
                                Si prefieres el botón con icono, ve a <em>Tienda online &gt; Temas &gt; Personalizar &gt; Pie de página &gt; Agregar bloque &gt; HTML personalizado</em> y pega el código copiado arriba.
                            </div>
                        </div>
                    </div>

                    <!-- Guía WooCommerce / WordPress -->
                    <div v-else-if="tabGuia === 'woocommerce'" class="space-y-3 text-xs text-slate-600">
                        <div class="flex items-start gap-3">
                            <span class="flex h-6 w-6 rounded-full bg-purple-100 text-purple-700 font-bold items-center justify-center shrink-0">1</span>
                            <div>
                                <strong class="text-slate-800">Ingresa a Widgets o Editor de Bloques:</strong>
                                En el panel de WordPress, ve a <em>Apariencia &gt; Widgets</em> (o <em>Editor &gt; Pie de página</em> en temas FSE).
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="flex h-6 w-6 rounded-full bg-purple-100 text-purple-700 font-bold items-center justify-center shrink-0">2</span>
                            <div>
                                <strong class="text-slate-800">Insertar Bloque HTML Personalizado:</strong>
                                Añade un bloque de tipo <strong>HTML Personalizado</strong> en la sección del Footer deseada.
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="flex h-6 w-6 rounded-full bg-purple-100 text-purple-700 font-bold items-center justify-center shrink-0">3</span>
                            <div>
                                <strong class="text-slate-800">Pegar y Guardar:</strong>
                                Pega el fragmento HTML seleccionado y haz clic en <em>Actualizar</em>. El libro quedará accesible en todas las páginas de tu tienda.
                            </div>
                        </div>
                    </div>

                    <!-- Guía HTML / Genérico -->
                    <div v-else-if="tabGuia === 'html'" class="space-y-3 text-xs text-slate-600">
                        <div class="flex items-start gap-3">
                            <span class="flex h-6 w-6 rounded-full bg-slate-200 text-slate-700 font-bold items-center justify-center shrink-0">1</span>
                            <div>
                                <strong class="text-slate-800">Ubicar la plantilla del Footer:</strong>
                                En tu proyecto web (React, Vue, Laravel, PHP nativo o HTML plano), abre el componente o archivo de plantilla del pie de página (ej. <code>footer.blade.php</code>, <code>Footer.tsx</code> o <code>footer.html</code>).
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="flex h-6 w-6 rounded-full bg-slate-200 text-slate-700 font-bold items-center justify-center shrink-0">2</span>
                            <div>
                                <strong class="text-slate-800">Pegar el fragmento:</strong>
                                Coloca el código HTML dentro de la etiqueta <code>&lt;footer&gt;</code>.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Código QR para Establecimientos Físicos o Empaques -->
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <div class="h-8 w-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Código QR Oficial</h3>
                                <p class="text-[11px] text-slate-400">Para mostradores, cajas o empaques</p>
                            </div>
                        </div>

                        <div class="flex justify-center p-3 bg-slate-50 rounded-xl border border-slate-100 mb-3">
                            <img :src="qrUrl" alt="Código QR Libro de Reclamaciones" class="h-36 w-36 object-contain rounded-lg shadow-2xs" />
                        </div>

                        <p class="text-[11px] text-slate-500 text-center leading-relaxed">
                            Apunta directamente a tu libro virtual. Puedes imprimirlo para cumplir con la exhibición física obligatoria.
                        </p>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex gap-2">
                        <a
                            :href="qrUrl"
                            target="_blank"
                            download="qr_libro_reclamaciones.png"
                            class="w-full text-center rounded-xl bg-slate-900 py-2 text-xs font-bold text-white hover:bg-slate-800 transition"
                        >
                            Descargar QR PNG
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Reconfigurar Slug -->
        <div v-if="modalSlug" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl border border-slate-200">
                <div class="flex items-center gap-3 text-blue-600 mb-4">
                    <div class="h-10 w-10 rounded-xl bg-blue-100 flex items-center justify-center shrink-0">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Personalizar Dirección Web de tu Empresa</h3>
                        <p class="text-xs text-slate-500">Cambia el enlace público de tu Libro con redirección automática garantizada</p>
                    </div>
                </div>

                <form @submit.prevent="enviarCambioSlug" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Dirección Web Actual</label>
                        <div class="font-mono text-slate-600 bg-slate-100 px-3 py-2 rounded-xl text-xs flex items-center justify-between">
                            <span>.../l/<strong>{{ empresa.slug }}</strong></span>
                            <span class="text-[10px] font-bold text-slate-500 uppercase bg-white px-2 py-0.5 rounded border border-slate-200">Activo</span>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nueva Dirección Deseada *</label>
                        <div class="relative">
                            <input
                                type="text"
                                :value="formSlug.slug"
                                @input="normalizarSlugInput"
                                required
                                placeholder="ej. mi-tienda-oficial"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-mono focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            />
                            <span v-if="validandoSlug" class="absolute right-3 top-2.5 text-[11px] text-slate-400">
                                Verificando...
                            </span>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-400">
                            Tus clientes entrarán a: <strong class="text-slate-600 font-mono">.../l/{{ formSlug.slug || 'tu-tienda' }}</strong>
                        </p>

                        <!-- Indicador de Disponibilidad -->
                        <div v-if="mensajeValidacion" class="mt-1.5 flex items-center gap-1.5 text-[11px]">
                            <span
                                :class="[
                                    'h-2 w-2 rounded-full',
                                    slugDisponible === true ? 'bg-emerald-500' : (slugDisponible === false ? 'bg-rose-500' : 'bg-slate-400')
                                ]"
                            ></span>
                            <span :class="slugDisponible === true ? 'text-emerald-700 font-semibold' : (slugDisponible === false ? 'text-rose-600 font-semibold' : 'text-slate-500')">
                                {{ mensajeValidacion }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Motivo del Cambio (opcional)</label>
                        <textarea
                            v-model="formSlug.motivo"
                            rows="2"
                            placeholder="ej. Cambio de nombre comercial, renovación de marca..."
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs"
                        ></textarea>
                    </div>

                    <div class="rounded-xl bg-blue-50/80 p-3 text-[11px] text-blue-900 border border-blue-200 space-y-1">
                        <div class="font-bold">Garantía de continuidad y seguridad para tus clientes:</div>
                        <ul class="list-disc pl-4 space-y-0.5 text-blue-800">
                            <li>Tu dirección anterior seguirá funcionando y redirigirá de inmediato al nuevo enlace sin que los clientes lo noten.</li>
                            <li>Los códigos QR o enlaces que ya tengas impresos o en tus redes sociales nunca se romperán.</li>
                            <li>Tu enlace personalizado queda protegido y ninguna otra empresa podrá utilizarlo.</li>
                        </ul>
                    </div>

                    <div class="mt-5 flex justify-end gap-2 pt-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="modalSlug = false"
                            class="rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="formSlug.processing || slugDisponible === false || !formSlug.slug || formSlug.slug === empresa.slug"
                            class="rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white hover:bg-blue-700 disabled:opacity-50"
                        >
                            Guardar Nueva Dirección Web
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

