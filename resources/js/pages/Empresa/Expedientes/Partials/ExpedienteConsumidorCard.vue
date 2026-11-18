<script setup lang="ts">
interface Consumidor {
    nombres: string;
    apellidos: string;
    tipo_documento: string;
    numero_documento: string;
    email: string;
    telefono?: string;
    direccion: string;
    distrito?: string;
    provincia?: string;
    departamento?: string;
}

interface Apoderado {
    nombre: string;
    tipo_documento: string;
    numero_documento: string;
    telefono?: string;
    email?: string;
}

defineProps<{
    consumidor: Consumidor;
    apoderado?: Apoderado | null;
}>();
</script>

<template>
    <div class="rounded-2xl border border-slate-200/80 bg-slate-50/40 p-5 transition hover:border-slate-300">
        <div class="flex items-center justify-between border-b border-slate-200/60 pb-3">
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span>1. Identificación del Consumidor Reclamante</span>
            </h3>
            <span class="text-[11px] font-bold text-slate-500 font-mono">
                {{ (consumidor.tipo_documento || 'DNI').toUpperCase() }}: {{ consumidor.numero_documento }}
            </span>
        </div>

        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div>
                <span class="text-slate-400 block text-[11px]">Nombres y Apellidos:</span>
                <span class="font-bold text-slate-900 text-sm">
                    {{ consumidor.nombres }} {{ consumidor.apellidos }}
                </span>
            </div>

            <div>
                <span class="text-slate-400 block text-[11px]">Documento de Identidad:</span>
                <span class="font-bold text-slate-900">
                    {{ (consumidor.tipo_documento || 'DNI').toUpperCase() }}: {{ consumidor.numero_documento }}
                </span>
            </div>

            <div>
                <span class="text-slate-400 block text-[11px]">Correo Electrónico:</span>
                <a :href="`mailto:${consumidor.email}`" class="font-semibold text-blue-600 hover:underline">
                    {{ consumidor.email }}
                </a>
            </div>

            <div>
                <span class="text-slate-400 block text-[11px]">Teléfono de Contacto:</span>
                <span class="font-semibold text-slate-800">
                    {{ consumidor.telefono || 'No especificado' }}
                </span>
            </div>

            <div class="sm:col-span-2">
                <span class="text-slate-400 block text-[11px]">Domicilio declarado:</span>
                <span class="font-medium text-slate-800">
                    {{ consumidor.direccion }}
                    <template v-if="consumidor.distrito">
                        ({{ consumidor.distrito }}, {{ consumidor.provincia }}, {{ consumidor.departamento }})
                    </template>
                </span>
            </div>
        </div>

        <!-- Bloque de Apoderado / Tutor si es menor de edad -->
        <div v-if="apoderado" class="mt-4 rounded-xl border border-blue-100 bg-blue-50/50 p-3.5 text-xs">
            <div class="font-bold text-blue-900 mb-1 flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full bg-blue-600"></span>
                Consumidor Menor de Edad - Datos del Padre, Madre o Tutor:
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-slate-700">
                <div><strong>Nombre:</strong> {{ apoderado.nombre }}</div>
                <div><strong>Documento:</strong> {{ (apoderado.tipo_documento || 'DNI').toUpperCase() }} {{ apoderado.numero_documento }}</div>
                <div><strong>Teléfono:</strong> {{ apoderado.telefono || '—' }}</div>
                <div><strong>Email:</strong> {{ apoderado.email || '—' }}</div>
            </div>
        </div>
    </div>
</template>

