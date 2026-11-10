<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface SedeItem {
    id: number;
    codigo: string;
    nombre: string;
    total?: number;
}

interface PrioritarioItem {
    id: number;
    numero_hoja: string;
    fecha_hora: string;
    estado: string;
    consumidor_nombre: string;
    vence_en: string | null;
    dias_restantes: number;
    vencido: boolean;
    semaforo: {
        color: string;
        etiqueta: string;
    };
}

interface Props {
    filtros: {
        periodo: string;
        fecha_desde: string;
        fecha_hasta: string;
        establecimiento_id: number | null;
    };
    sedes: SedeItem[];
    kpis: {
        total: number;
        pendientes: number;
        en_tramite: number;
        atendidas: number;
        cerradas: number;
        tasa_resolucion: number;
        tiempo_promedio_dias: number | null;
        tasa_cumplimiento_legal: number | null;
    };
    semaforo: {
        en_plazo: number;
        proximo: number;
        vence_hoy: number;
        vencidos: number;
    };
    antiguedad: {
        de_0_a_5: number;
        de_6_a_10: number;
        de_11_a_15: number;
        mas_de_15: number;
    };
    distribucion_tipo: {
        reclamos: number;
        quejas: number;
    };
    distribucion_bien: {
        productos: number;
        servicios: number;
    };
    desglose_sedes: Array<{
        id: number;
        codigo: string;
        nombre: string;
        total: number;
    }>;
    prioritarios: PrioritarioItem[];
}

const props = defineProps<Props>();

const formFiltros = ref({
    periodo: props.filtros.periodo || '30_dias',
    fecha_desde: props.filtros.fecha_desde || '',
    fecha_hasta: props.filtros.fecha_hasta || '',
    establecimiento_id: props.filtros.establecimiento_id || '',
});

function cambiarPeriodo(nuevoPeriodo: string) {
    formFiltros.value.periodo = nuevoPeriodo;
    if (nuevoPeriodo !== 'personalizado') {
        aplicarFiltros();
    }
}

function aplicarFiltros() {
    router.get('/dashboard', formFiltros.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function porcentaje(parcial: number, total: number): number {
    if (!total || total <= 0) return 0;
    return Math.round((parcial / total) * 100);
}
</script>

<template>
    <AdminLayout>
        <Head title="Dashboard e Indicadores | DatecLibro" />

        <div class="space-y-6">
            <!-- Barra Superior del Dashboard con Filtros de Periodo y Sede -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 border-b border-slate-200/80 pb-5">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-slate-900">
                        Dashboard e Indicadores de Gestión
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Control analítico de expedientes, plazos legales y desempeño de atención al consumidor.
                    </p>
                </div>

                <!-- Controles de Filtros -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Selector de Sede -->
                    <div class="relative">
                        <select
                            v-model="formFiltros.establecimiento_id"
                            @change="aplicarFiltros"
                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-2xs focus:border-blue-500 focus:outline-none"
                        >
                            <option value="">Todas las Sedes</option>
                            <option v-for="sede in sedes" :key="sede.id" :value="sede.id">
                                {{ sede.nombre }} ({{ sede.codigo }})
                            </option>
                        </select>
                    </div>

                    <!-- Botones de Rango de Periodo -->
                    <div class="inline-flex rounded-xl border border-slate-200 bg-white p-1 shadow-2xs">
                        <button
                            type="button"
                            @click="cambiarPeriodo('30_dias')"
                            :class="[
                                'rounded-lg px-2.5 py-1.5 text-xs font-bold transition cursor-pointer',
                                formFiltros.periodo === '30_dias'
                                    ? 'bg-blue-600 text-white shadow-2xs'
                                    : 'text-slate-600 hover:text-slate-900'
                            ]"
                        >
                            30 días
                        </button>
                        <button
                            type="button"
                            @click="cambiarPeriodo('este_mes')"
                            :class="[
                                'rounded-lg px-2.5 py-1.5 text-xs font-bold transition cursor-pointer',
                                formFiltros.periodo === 'este_mes'
                                    ? 'bg-blue-600 text-white shadow-2xs'
                                    : 'text-slate-600 hover:text-slate-900'
                            ]"
                        >
                            Este Mes
                        </button>
                        <button
                            type="button"
                            @click="cambiarPeriodo('3_meses')"
                            :class="[
                                'rounded-lg px-2.5 py-1.5 text-xs font-bold transition cursor-pointer',
                                formFiltros.periodo === '3_meses'
                                    ? 'bg-blue-600 text-white shadow-2xs'
                                    : 'text-slate-600 hover:text-slate-900'
                            ]"
                        >
                            3 Meses
                        </button>
                        <button
                            type="button"
                            @click="cambiarPeriodo('este_anio')"
                            :class="[
                                'rounded-lg px-2.5 py-1.5 text-xs font-bold transition cursor-pointer',
                                formFiltros.periodo === 'este_anio'
                                    ? 'bg-blue-600 text-white shadow-2xs'
                                    : 'text-slate-600 hover:text-slate-900'
                            ]"
                        >
                            Año
                        </button>
                        <button
                            type="button"
                            @click="cambiarPeriodo('todos')"
                            :class="[
                                'rounded-lg px-2.5 py-1.5 text-xs font-bold transition cursor-pointer',
                                formFiltros.periodo === 'todos'
                                    ? 'bg-blue-600 text-white shadow-2xs'
                                    : 'text-slate-600 hover:text-slate-900'
                            ]"
                        >
                            Todos
                        </button>
                        <button
                            type="button"
                            @click="cambiarPeriodo('personalizado')"
                            :class="[
                                'rounded-lg px-2.5 py-1.5 text-xs font-bold transition cursor-pointer',
                                formFiltros.periodo === 'personalizado'
                                    ? 'bg-blue-600 text-white shadow-2xs'
                                    : 'text-slate-600 hover:text-slate-900'
                            ]"
                        >
                            Personalizado
                        </button>
                    </div>
                </div>
            </div>

            <!-- Selector personalizado de fechas (visible si se selecciona "personalizado") -->
            <div
                v-if="formFiltros.periodo === 'personalizado'"
                class="flex flex-wrap items-center gap-3 rounded-2xl border border-blue-200 bg-blue-50/60 p-4"
            >
                <div class="flex items-center gap-2">
                    <label class="text-xs font-bold text-slate-700">Desde:</label>
                    <input
                        type="date"
                        v-model="formFiltros.fecha_desde"
                        class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-800 shadow-2xs focus:border-blue-500 focus:outline-none"
                    />
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-xs font-bold text-slate-700">Hasta:</label>
                    <input
                        type="date"
                        v-model="formFiltros.fecha_hasta"
                        class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-800 shadow-2xs focus:border-blue-500 focus:outline-none"
                    />
                </div>
                <button
                    type="button"
                    @click="aplicarFiltros"
                    class="rounded-xl bg-blue-600 px-4 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-blue-700 transition cursor-pointer"
                >
                    Filtrar periodo
                </button>
            </div>

            <!-- Tarjetas de Métricas Clave (KPIs Principales) -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Expedientes</div>
                    <div class="mt-2 flex items-baseline justify-between">
                        <span class="text-3xl font-black text-slate-900">{{ kpis.total }}</span>
                        <span class="text-xs font-semibold text-slate-500">100%</span>
                    </div>
                    <div class="mt-2 text-[11px] text-slate-400">Registrados en periodo</div>
                </div>

                <div class="rounded-2xl border border-amber-200/80 bg-amber-50/50 p-4 shadow-2xs">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Pendientes</div>
                    <div class="mt-2 flex items-baseline justify-between">
                        <span class="text-3xl font-black text-amber-900">{{ kpis.pendientes }}</span>
                        <span class="text-xs font-bold text-amber-700">{{ porcentaje(kpis.pendientes, kpis.total) }}%</span>
                    </div>
                    <div class="mt-2 text-[11px] text-amber-600">Sin gestión iniciada</div>
                </div>

                <div class="rounded-2xl border border-blue-200/80 bg-blue-50/50 p-4 shadow-2xs">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-blue-700">En Trámite</div>
                    <div class="mt-2 flex items-baseline justify-between">
                        <span class="text-3xl font-black text-blue-900">{{ kpis.en_tramite }}</span>
                        <span class="text-xs font-bold text-blue-700">{{ porcentaje(kpis.en_tramite, kpis.total) }}%</span>
                    </div>
                    <div class="mt-2 text-[11px] text-blue-600">En revisión / asignados</div>
                </div>

                <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/50 p-4 shadow-2xs">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Atendidas</div>
                    <div class="mt-2 flex items-baseline justify-between">
                        <span class="text-3xl font-black text-emerald-900">{{ kpis.atendidas }}</span>
                        <span class="text-xs font-bold text-emerald-700">{{ porcentaje(kpis.atendidas, kpis.total) }}%</span>
                    </div>
                    <div class="mt-2 text-[11px] text-emerald-600">Respuesta notificada</div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 shadow-2xs">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Tasa Resolución</div>
                    <div class="mt-2 flex items-baseline justify-between">
                        <span class="text-3xl font-black text-slate-800">{{ kpis.tasa_resolucion }}%</span>
                        <span class="text-xs font-semibold text-slate-500">{{ kpis.atendidas + kpis.cerradas }}/{{ kpis.total }}</span>
                    </div>
                    <div class="mt-2 text-[11px] text-slate-500">Casos resueltos</div>
                </div>

                <div class="rounded-2xl border border-indigo-200/80 bg-indigo-50/50 p-4 shadow-2xs">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-indigo-700">Tiempo Respuesta</div>
                    <div class="mt-2 flex items-baseline justify-between">
                        <span class="text-3xl font-black text-indigo-900">
                            {{ kpis.tiempo_promedio_dias !== null ? `${kpis.tiempo_promedio_dias}d` : '-' }}
                        </span>
                        <span v-if="kpis.tasa_cumplimiento_legal !== null" class="text-xs font-bold text-indigo-700">
                            {{ kpis.tasa_cumplimiento_legal }}% legal
                        </span>
                    </div>
                    <div class="mt-2 text-[11px] text-indigo-600">Promedio de respuesta</div>
                </div>
            </div>

            <!-- Filas con Gráficos e Indicadores Semafóricos -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Tarjeta 1: Semáforo Legal de Vencimientos (Plazo 15 días hábiles Ley 29571) -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            <div>
                                <h2 class="text-sm font-bold text-slate-900">Cumplimiento del Plazo Legal</h2>
                                <p class="text-[11px] text-slate-500">Expedientes en trámite según plazo de 15 días hábiles (Ley N° 29571)</p>
                            </div>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600">
                            {{ semaforo.en_plazo + semaforo.proximo + semaforo.vence_hoy + semaforo.vencidos }} en curso
                        </span>
                    </div>

                    <div class="mt-5 grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-3 text-center">
                            <div class="text-[11px] font-bold text-emerald-800">En Plazo</div>
                            <div class="mt-1 text-2xl font-black text-emerald-900">{{ semaforo.en_plazo }}</div>
                            <div class="text-[10px] text-emerald-700">> 3 días hábiles</div>
                        </div>

                        <div class="rounded-xl border border-amber-200 bg-amber-50/70 p-3 text-center">
                            <div class="text-[11px] font-bold text-amber-800">Próximos</div>
                            <div class="mt-1 text-2xl font-black text-amber-900">{{ semaforo.proximo }}</div>
                            <div class="text-[10px] text-amber-700">1 a 3 días hábiles</div>
                        </div>

                        <div class="rounded-xl border border-orange-200 bg-orange-50/70 p-3 text-center">
                            <div class="text-[11px] font-bold text-orange-800">Vence Hoy</div>
                            <div class="mt-1 text-2xl font-black text-orange-900">{{ semaforo.vence_hoy }}</div>
                            <div class="text-[10px] text-orange-700">Último día hábil</div>
                        </div>

                        <div class="rounded-xl border border-rose-200 bg-rose-50/70 p-3 text-center">
                            <div class="text-[11px] font-bold text-rose-800">Vencidos</div>
                            <div class="mt-1 text-2xl font-black text-rose-900">{{ semaforo.vencidos }}</div>
                            <div class="text-[10px] text-rose-700">Fuera de plazo</div>
                        </div>
                    </div>

                    <!-- Barra de progreso visual del semáforo -->
                    <div class="mt-5 space-y-2">
                        <div class="text-xs font-semibold text-slate-600 flex justify-between">
                            <span>Distribución del estado de vencimiento</span>
                            <span>{{ porcentaje(semaforo.en_plazo, semaforo.en_plazo + semaforo.proximo + semaforo.vence_hoy + semaforo.vencidos) }}% a tiempo</span>
                        </div>
                        <div class="flex h-3 w-full overflow-hidden rounded-full bg-slate-100">
                            <div
                                :style="{ width: `${porcentaje(semaforo.en_plazo, semaforo.en_plazo + semaforo.proximo + semaforo.vence_hoy + semaforo.vencidos)}%` }"
                                class="bg-emerald-500 transition-all duration-300"
                                title="En plazo"
                            ></div>
                            <div
                                :style="{ width: `${porcentaje(semaforo.proximo, semaforo.en_plazo + semaforo.proximo + semaforo.vence_hoy + semaforo.vencidos)}%` }"
                                class="bg-amber-500 transition-all duration-300"
                                title="Próximos a vencer"
                            ></div>
                            <div
                                :style="{ width: `${porcentaje(semaforo.vence_hoy, semaforo.en_plazo + semaforo.proximo + semaforo.vence_hoy + semaforo.vencidos)}%` }"
                                class="bg-orange-500 transition-all duration-300"
                                title="Vence hoy"
                            ></div>
                            <div
                                :style="{ width: `${porcentaje(semaforo.vencidos, semaforo.en_plazo + semaforo.proximo + semaforo.vence_hoy + semaforo.vencidos)}%` }"
                                class="bg-rose-500 transition-all duration-300"
                                title="Vencidos"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta 2: Antigüedad de Expedientes Abiertos -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </span>
                            <div>
                                <h2 class="text-sm font-bold text-slate-900">Antigüedad de Casos Abiertos</h2>
                                <p class="text-[11px] text-slate-500">Días transcurridos desde que el consumidor registró su presentación</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 space-y-3">
                        <div>
                            <div class="flex justify-between text-xs font-semibold text-slate-700 mb-1">
                                <span>0 a 5 días (Muy reciente)</span>
                                <span>{{ antiguedad.de_0_a_5 }} casos</span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-slate-100 overflow-hidden">
                                <div
                                    class="h-full bg-blue-500 rounded-full"
                                    :style="{ width: `${porcentaje(antiguedad.de_0_a_5, kpis.pendientes + kpis.en_tramite)}%` }"
                                ></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between text-xs font-semibold text-slate-700 mb-1">
                                <span>6 a 10 días (En trámite medio)</span>
                                <span>{{ antiguedad.de_6_a_10 }} casos</span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-slate-100 overflow-hidden">
                                <div
                                    class="h-full bg-indigo-500 rounded-full"
                                    :style="{ width: `${porcentaje(antiguedad.de_6_a_10, kpis.pendientes + kpis.en_tramite)}%` }"
                                ></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between text-xs font-semibold text-slate-700 mb-1">
                                <span>11 a 15 días (Requiere respuesta urgente)</span>
                                <span>{{ antiguedad.de_11_a_15 }} casos</span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-slate-100 overflow-hidden">
                                <div
                                    class="h-full bg-amber-500 rounded-full"
                                    :style="{ width: `${porcentaje(antiguedad.de_11_a_15, kpis.pendientes + kpis.en_tramite)}%` }"
                                ></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between text-xs font-semibold text-slate-700 mb-1">
                                <span>Más de 15 días (Plazo de ley superado)</span>
                                <span class="font-bold text-rose-600">{{ antiguedad.mas_de_15 }} casos</span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-slate-100 overflow-hidden">
                                <div
                                    class="h-full bg-rose-500 rounded-full"
                                    :style="{ width: `${porcentaje(antiguedad.mas_de_15, kpis.pendientes + kpis.en_tramite)}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Distribuciones Tipológicas (Tipo de Reclamación y Bien) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Reclamos vs Quejas -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs">
                    <h2 class="text-sm font-bold text-slate-900 mb-4">Clasificación por Tipo de Caso</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                            <div class="text-xs font-bold text-slate-500">Reclamos</div>
                            <div class="text-2xl font-black text-slate-900 mt-1">{{ distribucion_tipo.reclamos }}</div>
                            <div class="text-[11px] text-slate-500 mt-1">{{ porcentaje(distribucion_tipo.reclamos, kpis.total) }}% del volumen</div>
                            <p class="text-[10px] text-slate-400 mt-2">Disconformidad sobre producto o servicio brindado</p>
                        </div>

                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                            <div class="text-xs font-bold text-slate-500">Quejas</div>
                            <div class="text-2xl font-black text-slate-900 mt-1">{{ distribucion_tipo.quejas }}</div>
                            <div class="text-[11px] text-slate-500 mt-1">{{ porcentaje(distribucion_tipo.quejas, kpis.total) }}% del volumen</div>
                            <p class="text-[10px] text-slate-400 mt-2">Malestar en atención o trato al cliente</p>
                        </div>
                    </div>
                </div>

                <!-- Productos vs Servicios -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs">
                    <h2 class="text-sm font-bold text-slate-900 mb-4">Clasificación por Bien Contratado</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                            <div class="text-xs font-bold text-slate-500">Productos</div>
                            <div class="text-2xl font-black text-slate-900 mt-1">{{ distribucion_bien.productos }}</div>
                            <div class="text-[11px] text-slate-500 mt-1">{{ porcentaje(distribucion_bien.productos, kpis.total) }}% del volumen</div>
                            <p class="text-[10px] text-slate-400 mt-2">Bienes tangibles comercializados</p>
                        </div>

                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                            <div class="text-xs font-bold text-slate-500">Servicios</div>
                            <div class="text-2xl font-black text-slate-900 mt-1">{{ distribucion_bien.servicios }}</div>
                            <div class="text-[11px] text-slate-500 mt-1">{{ porcentaje(distribucion_bien.servicios, kpis.total) }}% del volumen</div>
                            <p class="text-[10px] text-slate-400 mt-2">Prestaciones de servicios ejecutadas</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Expedientes Prioritarios de Atención Inmediata -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Expedientes Prioritarios de Atención Inmediata</h2>
                        <p class="text-[11px] text-slate-500">Casos más urgentes según cómputo de días hábiles restantes</p>
                    </div>
                    <Link
                        href="/expedientes"
                        class="text-xs font-bold text-blue-600 hover:text-blue-700 transition"
                    >
                        Ver todos en bandeja &rarr;
                    </Link>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                <th class="py-2.5 px-3">N° Hoja</th>
                                <th class="py-2.5 px-3">Fecha</th>
                                <th class="py-2.5 px-3">Consumidor</th>
                                <th class="py-2.5 px-3">Estado</th>
                                <th class="py-2.5 px-3">Vencimiento Legal</th>
                                <th class="py-2.5 px-3 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="exp in prioritarios" :key="exp.id" class="hover:bg-slate-50/80 transition">
                                <td class="py-3 px-3 font-mono font-bold text-slate-900">
                                    {{ exp.numero_hoja }}
                                </td>
                                <td class="py-3 px-3 text-slate-600">
                                    {{ exp.fecha_hora }}
                                </td>
                                <td class="py-3 px-3 font-semibold text-slate-800">
                                    {{ exp.consumidor_nombre || 'No especificado' }}
                                </td>
                                <td class="py-3 px-3">
                                    <span class="inline-flex rounded-md bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 border border-amber-200">
                                        {{ exp.estado }}
                                    </span>
                                </td>
                                <td class="py-3 px-3">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[10px] font-bold border',
                                            exp.vencido
                                                ? 'bg-rose-50 text-rose-700 border-rose-200'
                                                : (exp.dias_restantes <= 3 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200')
                                        ]"
                                    >
                                        {{ exp.semaforo?.etiqueta || (exp.vencido ? 'Vencido' : `${exp.dias_restantes} días`) }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-right">
                                    <Link
                                        :href="`/expedientes/${exp.id}`"
                                        class="inline-flex items-center rounded-lg bg-blue-50 px-2.5 py-1 text-[11px] font-bold text-blue-700 hover:bg-blue-100 transition"
                                    >
                                        Gestionar &rarr;
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="prioritarios.length === 0">
                                <td colspan="6" class="py-6 text-center text-slate-400">
                                    No hay expedientes pendientes con urgencia en el periodo seleccionado.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Desglose por Sedes / Establecimientos -->
            <div v-if="desglose_sedes && desglose_sedes.length > 0" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs">
                <h2 class="text-sm font-bold text-slate-900 mb-3">Expedientes por Sede o Sucursal</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                    <div
                        v-for="sede in desglose_sedes"
                        :key="sede.id"
                        class="rounded-xl border border-slate-100 bg-slate-50/70 p-3"
                    >
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Código {{ sede.codigo }}</div>
                        <div class="text-xs font-bold text-slate-800 truncate mt-0.5">{{ sede.nombre }}</div>
                        <div class="mt-2 text-lg font-black text-slate-900">{{ sede.total }} expedientes</div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

