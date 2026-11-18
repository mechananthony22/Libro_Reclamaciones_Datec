<script setup lang="ts">
defineProps<{
    form: any;
    tiposDocumento: Array<{ value: string; label: string }>;
    longitudMaximaDoc: number;
    placeholderDoc: string;
    colorPrimario?: string;
}>();

const emit = defineEmits<{
    (e: 'continuar'): void;
}>();
</script>

<template>
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-2xs space-y-6">
        <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-900">
                    1. Identificación del Consumidor Reclamante
                </h3>
                <p class="text-xs text-slate-500">
                    Ingresa la información solicitada conforme al formato oficial del Libro de Reclamaciones.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <!-- Selector del Tipo de Documento de Identidad -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                    Tipo de Documento *
                </label>
                <select
                    v-model="form.tipo_documento"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-800 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
                >
                    <option v-for="tipo in tiposDocumento" :key="tipo.value" :value="tipo.value">
                        {{ tipo.label }}
                    </option>
                </select>
            </div>

            <!-- Campo para Número de Documento con máscara y longitud dinámica -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                    Número de Documento *
                </label>
                <input
                    v-model="form.numero_documento"
                    type="text"
                    :maxlength="longitudMaximaDoc"
                    :placeholder="placeholderDoc"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 font-mono text-xs font-bold text-slate-800 placeholder:text-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    :class="{ 'border-rose-400 focus:border-rose-500 focus:ring-rose-500': form.errors.numero_documento }"
                />
                <p v-if="form.errors.numero_documento" class="mt-1 text-[11px] font-semibold text-rose-600">
                    {{ form.errors.numero_documento }}
                </p>
            </div>

            <!-- Nombres o Razón Social según corresponda -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                    {{ form.tipo_documento === 'ruc' ? 'Razón Social o Nombre Comercial *' : 'Nombres Completos *' }}
                </label>
                <input
                    v-model="form.nombres"
                    type="text"
                    placeholder="Nombres según documento"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    :class="{ 'border-rose-400 focus:border-rose-500 focus:ring-rose-500': form.errors.nombres }"
                />
                <p v-if="form.errors.nombres" class="mt-1 text-[11px] font-semibold text-rose-600">
                    {{ form.errors.nombres }}
                </p>
            </div>

            <!-- Apellidos para personas naturales -->
            <div v-if="form.tipo_documento !== 'ruc'">
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                    Apellidos Completos *
                </label>
                <input
                    v-model="form.apellidos"
                    type="text"
                    placeholder="Apellidos según documento"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    :class="{ 'border-rose-400 focus:border-rose-500 focus:ring-rose-500': form.errors.apellidos }"
                />
                <p v-if="form.errors.apellidos" class="mt-1 text-[11px] font-semibold text-rose-600">
                    {{ form.errors.apellidos }}
                </p>
            </div>

            <!-- Correo Electrónico del Consumidor -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                    Correo Electrónico *
                </label>
                <input
                    v-model="form.email"
                    type="email"
                    placeholder="correo@ejemplo.com"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    :class="{ 'border-rose-400 focus:border-rose-500 focus:ring-rose-500': form.errors.email }"
                />
                <p class="mt-1 text-[11px] text-slate-400">
                    A esta dirección te enviaremos la constancia oficial de tu reclamación.
                </p>
                <p v-if="form.errors.email" class="mt-1 text-[11px] font-semibold text-rose-600">
                    {{ form.errors.email }}
                </p>
            </div>

            <!-- Teléfono o Celular de Contacto -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                    Teléfono / Celular de Contacto *
                </label>
                <input
                    v-model="form.telefono"
                    type="text"
                    placeholder="ej. 987654321"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    :class="{ 'border-rose-400 focus:border-rose-500 focus:ring-rose-500': form.errors.telefono }"
                />
                <p v-if="form.errors.telefono" class="mt-1 text-[11px] font-semibold text-rose-600">
                    {{ form.errors.telefono }}
                </p>
            </div>

            <!-- Domicilio o Dirección del Consumidor -->
            <div class="sm:col-span-2">
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                    Domicilio / Dirección Completa *
                </label>
                <input
                    v-model="form.direccion"
                    type="text"
                    placeholder="Av., Calle, N°, Urbanización o referencia"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    :class="{ 'border-rose-400 focus:border-rose-500 focus:ring-rose-500': form.errors.direccion }"
                />
                <p v-if="form.errors.direccion" class="mt-1 text-[11px] font-semibold text-rose-600">
                    {{ form.errors.direccion }}
                </p>
            </div>

            <!-- Ubicación Geográfica: Departamento, Provincia y Distrito -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                    Departamento
                </label>
                <input
                    v-model="form.departamento"
                    type="text"
                    placeholder="ej. Lima"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                />
            </div>

            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                    Provincia / Distrito
                </label>
                <div class="grid grid-cols-2 gap-2">
                    <input
                        v-model="form.provincia"
                        type="text"
                        placeholder="Provincia"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    />
                    <input
                        v-model="form.distrito"
                        type="text"
                        placeholder="Distrito"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    />
                </div>
            </div>
        </div>

        <!-- Bloque Condicional para Consumidor Menor de Edad -->
        <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 sm:p-5 space-y-4">
            <label class="flex items-center gap-3 cursor-pointer">
                <input
                    v-model="form.es_menor_edad"
                    type="checkbox"
                    class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                />
                <span class="text-xs font-bold text-slate-800">
                    El consumidor que presenta el reclamo es menor de edad
                </span>
            </label>

            <!-- Datos del Padre, Madre o Representante Legal -->
            <div v-if="form.es_menor_edad" class="pt-3 border-t border-slate-200 space-y-4">
                <p class="text-xs text-slate-600 font-medium">
                    Conforme a la normativa de INDECOPI, el reclamo de un menor de edad debe estar respaldado por uno de sus padres o representante legal:
                </p>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Nombres y Apellidos del Padre, Madre o Apoderado *
                        </label>
                        <input
                            v-model="form.apoderado_nombre"
                            type="text"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-medium text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            :class="{ 'border-rose-400': form.errors.apoderado_nombre }"
                        />
                        <p v-if="form.errors.apoderado_nombre" class="mt-1 text-[11px] font-semibold text-rose-600">
                            {{ form.errors.apoderado_nombre }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Tipo Documento Apoderado *
                        </label>
                        <select
                            v-model="form.apoderado_tipo_documento"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        >
                            <option value="dni">DNI</option>
                            <option value="ce">Carné de Extranjería</option>
                            <option value="pasaporte">Pasaporte</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            N° Documento Apoderado *
                        </label>
                        <input
                            v-model="form.apoderado_numero_documento"
                            type="text"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-mono font-bold text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            :class="{ 'border-rose-400': form.errors.apoderado_numero_documento }"
                        />
                        <p v-if="form.errors.apoderado_numero_documento" class="mt-1 text-[11px] font-semibold text-rose-600">
                            {{ form.errors.apoderado_numero_documento }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Teléfono Apoderado (Opcional)
                        </label>
                        <input
                            v-model="form.apoderado_telefono"
                            type="text"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-medium text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Correo Apoderado (Opcional)
                        </label>
                        <input
                            v-model="form.apoderado_email"
                            type="email"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-medium text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Botón de avance hacia el Paso 2 -->
        <div class="flex justify-end pt-4 border-t border-slate-100">
            <button
                type="button"
                @click="emit('continuar')"
                class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-xs font-bold text-white shadow-md shadow-blue-500/20 hover:opacity-95 transition cursor-pointer"
                :style="{ backgroundColor: colorPrimario || '#2563EB' }"
            >
                <span>Continuar al Paso 2</span>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </button>
        </div>
    </div>
</template>

