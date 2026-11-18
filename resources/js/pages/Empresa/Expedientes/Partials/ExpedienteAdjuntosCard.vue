<script setup lang="ts">
interface Adjunto {
    id: number;
    nombre_original: string;
    mime_type: string;
    tamano_humano: string;
    hash_sha256?: string;
    es_imagen: boolean;
    url_descarga: string;
}

defineProps<{
    adjuntos: Adjunto[];
}>();
</script>

<template>
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                </svg>
                <span>Evidencias y Archivos Adjuntos ({{ adjuntos.length }})</span>
            </h2>
        </div>

        <div v-if="adjuntos.length > 0" class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div
                v-for="adj in adjuntos"
                :key="adj.id"
                class="flex items-center justify-between rounded-xl border border-slate-200 p-3 hover:bg-slate-50 transition"
            >
                <div class="min-w-0 flex items-center gap-2.5">
                    <svg class="h-5 w-5 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <div class="truncate">
                        <div class="text-xs font-bold text-slate-800 truncate" :title="adj.nombre_original">
                            {{ adj.nombre_original }}
                        </div>
                        <div class="text-[10px] text-slate-400">
                            {{ adj.tamano_humano }} • {{ adj.mime_type }}
                        </div>
                    </div>
                </div>

                <a
                    :href="adj.url_descarga"
                    target="_blank"
                    class="ml-2 inline-flex items-center gap-1 rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700 hover:bg-blue-100 transition shrink-0"
                >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span>Ver</span>
                </a>
            </div>
        </div>

        <div v-else class="mt-4 text-xs text-slate-400 italic">
            El consumidor no adjuntó archivos adicionales en este registro.
        </div>
    </div>
</template>

