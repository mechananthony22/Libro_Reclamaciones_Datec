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

const periodosOpciones = [
    { valor: '30_dias', label: '30 días' },
    { valor: 'este_mes', label: 'Este Mes' },
    { valor: '3_meses', label: '3 Meses' },
    { valor: 'este_anio', label: 'Año' },
    { valor: 'todos', label: 'Todos' },
    { valor: 'personalizado', label: 'Personalizado' },
];
</script>

<template>
    <AdminLayout>
        <Head title="Dashboard e Indicadores | DatecLibro" />

        <div class="space-y-6">
            <!-- Cabecera de Página (Estilo Admin/Empresas) -->
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="flex items-start gap-4">
                    <div
                        class="hidden sm:flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-md shadow-blue-500/25"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                            />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            Atención de Reclamos · Indicadores & Desempeño
                        </div>
                        <h1 class="mt-0.5 text-2xl font-extrabold tracking-tight text-slate-900">
                            Dashboard e Indicadores de Gestión
                        </h1>
                        <p class="mt-1 text-sm text-slate-500">
                            Control analítico de expedientes, plazos legales y desempeño de atención
                        </p>
                    </div>
                </div>

                <!-- Controles de Filtros de Periodo y Sede -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Selector de Sede -->
                    <div class="relative">
                        <select
                            v-model="formFiltros.establecimiento_id"
                            @change="aplicarFiltros"
                            class="appearance-none rounded-xl border border-slate-200 bg-white py-2 pl-3 pr-8 text-xs font-bold text-slate-700 shadow-2xs hover:border-slate-300 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
                        >
                            <option value="">Todas las Sedes</option>
                            <option v-for="sede in sedes" :key="sede.id" :value="sede.id">
                                {{ sede.nombre }} ({{ sede.codigo }})
                            </option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-400">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>

                    <!-- Botones de Rango de Periodo -->
                    <div class="flex gap-1 overflow-x-auto rounded-xl bg-slate-100/80 p-1 shadow-2xs">
                        <button
                            v-for="opc in periodosOpciones"
                            :key="opc.valor"
                            type="button"
                            @click="cambiarPeriodo(opc.valor)"
                            :class="[
                                'shrink-0 cursor-pointer rounded-lg px-2.5 py-1.5 text-xs font-bold transition',
                                formFiltros.periodo === opc.valor
                                    ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                    : 'text-slate-600 hover:bg-white hover:text-slate-900'
                            ]"
                        >
                            {{ opc.label }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Selector personalizado de fechas (visible si se selecciona "personalizado") -->
            <div
                v-if="formFiltros.periodo === 'personalizado'"
                class="flex flex-wrap items-center gap-3 rounded-2xl border border-blue-200/80 bg-blue-50/60 p-4 shadow-2xs"
            >
                <div class="flex items-center gap-2">
                    <label class="text-[11px] font-bold uppercase tracking-wider text-slate-600">Desde:</label>
                    <input
                        type="date"
                        v-model="formFiltros.fecha_desde"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-800 shadow-2xs focus:border-blue-500 focus:outline-none"
                    />
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-[11px] font-bold uppercase tracking-wider text-slate-600">Hasta:</label>
                    <input
                        type="date"
                        v-model="formFiltros.fecha_hasta"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-800 shadow-2xs focus:border-blue-500 focus:outline-none"
                    />
                </div>
                <button
                    type="button"
                    @click="aplicarFiltros"
                    class="rounded-xl bg-blue-600 px-4 py-1.5 text-xs font-bold text-white shadow-md shadow-blue-500/20 hover:bg-blue-700 transition cursor-pointer"
                >
                    Filtrar periodo
                </button>
            </div>

            <!-- Tarjetas de Métricas Clave (KPIs Principales - Colores suaves pasteles) -->
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
                <!-- Total Casos -->
                <div class="rounded-2xl border border-blue-200/70 bg-gradient-to-b from-blue-50/40 to-white p-5 shadow-2xs transition hover:shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-blue-700/80">Total Casos</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-100/70 text-blue-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-3xl font-black text-slate-900 tracking-tight">{{ kpis.total }}</p>
                    <p class="mt-1 text-[11px] font-medium text-slate-500">Registrados en periodo</p>
                </div>

                <!-- Pendientes -->
                <div class="rounded-2xl border border-amber-200/70 bg-gradient-to-b from-amber-50/40 to-white p-5 shadow-2xs transition hover:shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700/80">Pendientes</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-100/70 text-amber-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3 flex items-baseline justify-between">
                        <span class="text-3xl font-black text-slate-900 tracking-tight">{{ kpis.pendientes }}</span>
                        <span class="rounded-full bg-amber-100/80 px-2 py-0.5 text-[11px] font-bold text-amber-800">{{ porcentaje(kpis.pendientes, kpis.total) }}%</span>
                    </div>
                    <p class="mt-1 text-[11px] font-medium text-slate-500">Sin gestión iniciada</p>
                </div>

                <!-- En Trámite -->
                <div class="rounded-2xl border border-sky-200/70 bg-gradient-to-b from-sky-50/40 to-white p-5 shadow-2xs transition hover:shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-sky-700/80">En Trámite</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-sky-100/70 text-sky-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3 flex items-baseline justify-between">
                        <span class="text-3xl font-black text-slate-900 tracking-tight">{{ kpis.en_tramite }}</span>
                        <span class="rounded-full bg-sky-100/80 px-2 py-0.5 text-[11px] font-bold text-sky-800">{{ porcentaje(kpis.en_tramite, kpis.total) }}%</span>
                    </div>
                    <p class="mt-1 text-[11px] font-medium text-slate-500">En investigación activa</p>
                </div>

                <!-- Atendidas -->
                <div class="rounded-2xl border border-emerald-200/70 bg-gradient-to-b from-emerald-50/40 to-white p-5 shadow-2xs transition hover:shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-700/80">Atendidas</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-100/70 text-emerald-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3 flex items-baseline justify-between">
                        <span class="text-3xl font-black text-slate-900 tracking-tight">{{ kpis.atendidas }}</span>
                        <span class="rounded-full bg-emerald-100/80 px-2 py-0.5 text-[11px] font-bold text-emerald-800">{{ porcentaje(kpis.atendidas, kpis.total) }}%</span>
                    </div>
                    <p class="mt-1 text-[11px] font-medium text-slate-500">Respuesta notificada</p>
                </div>

                <!-- Tasa Resolución -->
                <div class="rounded-2xl border border-indigo-200/70 bg-gradient-to-b from-indigo-50/40 to-white p-5 shadow-2xs transition hover:shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-700/80">Resolución</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-100/70 text-indigo-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3 flex items-baseline justify-between">
                        <span class="text-3xl font-black text-slate-900 tracking-tight">{{ kpis.tasa_resolucion }}%</span>
                        <span class="rounded-full bg-indigo-100/80 px-2 py-0.5 text-[11px] font-bold text-indigo-800">{{ kpis.atendidas + kpis.cerradas }}/{{ kpis.total }}</span>
                    </div>
                    <p class="mt-1 text-[11px] font-medium text-slate-500">Casos concluidos</p>
                </div>

                <!-- Tiempo Respuesta -->
                <div class="rounded-2xl border border-purple-200/70 bg-gradient-to-b from-purple-50/40 to-white p-5 shadow-2xs transition hover:shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-purple-700/80">Tiempo Prom.</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-purple-100/70 text-purple-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3 flex items-baseline justify-between">
                        <span class="text-3xl font-black text-slate-900 tracking-tight">
                            {{ kpis.tiempo_promedio_dias !== null ? `${kpis.tiempo_promedio_dias}d` : '—' }}
                        </span>
                        <span v-if="kpis.tasa_cumplimiento_legal !== null" class="rounded-full bg-purple-100/80 px-2 py-0.5 text-[11px] font-bold text-purple-800">
                            {{ kpis.tasa_cumplimiento_legal }}% legal
                        </span>
                    </div>
                    <p class="mt-1 text-[11px] font-medium text-slate-500">Días promedio respuesta</p>
                </div>
            </div>

            <!-- Filas con Gráficos e Indicadores Semafóricos -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Tarjeta 1: Semáforo Legal de Vencimientos (Plazo 15 días hábiles Ley 29571) -->
                <div class="space-y-4 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-2xs">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-slate-900">Cumplimiento del Plazo Legal</h2>
                                <p class="text-xs text-slate-500">Expedientes en trámite según plazo de 15 días hábiles (Ley N° 29571).</p>
                            </div>
                        </div>
                        <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">
                            {{ semaforo.en_plazo + semaforo.proximo + semaforo.vence_hoy + semaforo.vencidos }} en curso
                        </span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/30 p-3.5 text-center transition hover:bg-emerald-50/50">
                            <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">En Plazo</div>
                            <div class="mt-1 text-2xl font-black text-emerald-900">{{ semaforo.en_plazo }}</div>
                            <div class="mt-0.5 text-[10px] font-semibold text-emerald-600">> 3 días hábiles</div>
                        </div>

                        <div class="rounded-xl border border-amber-200/80 bg-amber-50/30 p-3.5 text-center transition hover:bg-amber-50/50">
                            <div class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Próximos</div>
                            <div class="mt-1 text-2xl font-black text-amber-900">{{ semaforo.proximo }}</div>
                            <div class="mt-0.5 text-[10px] font-semibold text-amber-600">1 a 3 días hábiles</div>
                        </div>

                        <div class="rounded-xl border border-orange-200/80 bg-orange-50/30 p-3.5 text-center transition hover:bg-orange-50/50">
                            <div class="text-[11px] font-bold uppercase tracking-wider text-orange-700">Vence Hoy</div>
                            <div class="mt-1 text-2xl font-black text-orange-900">{{ semaforo.vence_hoy }}</div>
                            <div class="mt-0.5 text-[10px] font-semibold text-orange-600">Último día hábil</div>
                        </div>

                        <div class="rounded-xl border border-rose-200/80 bg-rose-50/30 p-3.5 text-center transition hover:bg-rose-50/50">
                            <div class="text-[11px] font-bold uppercase tracking-wider text-rose-700">Vencidos</div>
                            <div class="mt-1 text-2xl font-black text-rose-900">{{ semaforo.vencidos }}</div>
                            <div class="mt-0.5 text-[10px] font-semibold text-rose-600">Fuera de plazo</div>
                        </div>
                    </div>

                    <!-- Barra de progreso visual del semáforo -->
                    <div class="space-y-2 pt-1">
                        <div class="flex justify-between text-xs font-semibold text-slate-600">
                            <span>Distribución del estado de vencimiento</span>
                            <span class="font-bold text-slate-700">
                                {{ porcentaje(semaforo.en_plazo, semaforo.en_plazo + semaforo.proximo + semaforo.vence_hoy + semaforo.vencidos) }}% a tiempo
                            </span>
                        </div>
                        <div class="flex h-2 w-full overflow-hidden rounded-full bg-slate-100">
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
                <div class="space-y-4 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-2xs">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-purple-50 text-purple-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-slate-900">Antigüedad de Casos Abiertos</h2>
                                <p class="text-xs text-slate-500">Días transcurridos desde que el consumidor registró su presentación.</p>
                            </div>
                        </div>
                    </div>

                    <div class="divide-y divide-slate-100 pt-1 text-xs">
                        <div class="flex items-center justify-between py-2.5">
                            <span class="font-medium text-slate-700">0 a 5 días <span class="text-slate-400 text-[11px] font-normal">(Muy reciente)</span></span>
                            <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 font-bold text-emerald-700 border border-emerald-100/80">{{ antiguedad.de_0_a_5 }} casos</span>
                        </div>

                        <div class="flex items-center justify-between py-2.5">
                            <span class="font-medium text-slate-700">6 a 10 días <span class="text-slate-400 text-[11px] font-normal">(En trámite regular)</span></span>
                            <span class="rounded-full bg-sky-50 px-2.5 py-0.5 font-bold text-sky-700 border border-sky-100/80">{{ antiguedad.de_6_a_10 }} casos</span>
                        </div>

                        <div class="flex items-center justify-between py-2.5">
                            <span class="font-medium text-slate-700">11 a 15 días <span class="text-slate-400 text-[11px] font-normal">(Respuesta próxima)</span></span>
                            <span class="rounded-full bg-amber-50 px-2.5 py-0.5 font-bold text-amber-700 border border-amber-100/80">{{ antiguedad.de_11_a_15 }} casos</span>
                        </div>

                        <div class="flex items-center justify-between py-2.5">
                            <span class="font-medium text-slate-700">Más de 15 días <span class="text-slate-400 text-[11px] font-normal">(Plazo de ley superado)</span></span>
                            <span class="rounded-full bg-rose-50 px-2.5 py-0.5 font-bold text-rose-700 border border-rose-100/80">{{ antiguedad.mas_de_15 }} casos</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Distribuciones Tipológicas (Tipo de Reclamación y Bien) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Reclamos vs Quejas -->
                <div class="space-y-4 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-2xs">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Clasificación por Tipo de Caso</h2>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="rounded-xl border border-sky-200/70 bg-sky-50/25 p-4 transition hover:bg-sky-50/40">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-sky-700">Reclamos</span>
                            <div class="text-2xl font-black text-slate-900 mt-1">{{ distribucion_tipo.reclamos }}</div>
                            <div class="text-[11px] font-semibold text-sky-700 mt-0.5">{{ porcentaje(distribucion_tipo.reclamos, kpis.total) }}% del volumen</div>
                            <p class="text-[11px] text-slate-500 mt-2">Disconformidad sobre producto o servicio.</p>
                        </div>

                        <div class="rounded-xl border border-purple-200/70 bg-purple-50/25 p-4 transition hover:bg-purple-50/40">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-purple-700">Quejas</span>
                            <div class="text-2xl font-black text-slate-900 mt-1">{{ distribucion_tipo.quejas }}</div>
                            <div class="text-[11px] font-semibold text-purple-700 mt-0.5">{{ porcentaje(distribucion_tipo.quejas, kpis.total) }}% del volumen</div>
                            <p class="text-[11px] text-slate-500 mt-2">Malestar en atención o trato al cliente.</p>
                        </div>
                    </div>
                </div>

                <!-- Productos vs Servicios -->
                <div class="space-y-4 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-2xs">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Clasificación por Bien Contratado</h2>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="rounded-xl border border-indigo-200/70 bg-indigo-50/25 p-4 transition hover:bg-indigo-50/40">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-700">Productos</span>
                            <div class="text-2xl font-black text-slate-900 mt-1">{{ distribucion_bien.productos }}</div>
                            <div class="text-[11px] font-semibold text-indigo-700 mt-0.5">{{ porcentaje(distribucion_bien.productos, kpis.total) }}% del volumen</div>
                            <p class="text-[11px] text-slate-500 mt-2">Bienes materiales comercializados.</p>
                        </div>

                        <div class="rounded-xl border border-teal-200/70 bg-teal-50/25 p-4 transition hover:bg-teal-50/40">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-teal-700">Servicios</span>
                            <div class="text-2xl font-black text-slate-900 mt-1">{{ distribucion_bien.servicios }}</div>
                            <div class="text-[11px] font-semibold text-teal-700 mt-0.5">{{ porcentaje(distribucion_bien.servicios, kpis.total) }}% del volumen</div>
                            <p class="text-[11px] text-slate-500 mt-2">Prestaciones de servicios ejecutadas.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Expedientes Prioritarios de Atención Inmediata (Estilo Admin/Empresas) -->
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xs">
                <div class="flex items-center justify-between border-b border-slate-100 p-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Expedientes Prioritarios de Atención Inmediata</h2>
                            <p class="text-xs text-slate-500">Casos más urgentes según cómputo de días hábiles restantes de ley.</p>
                        </div>
                    </div>

                    <Link
                        href="/expedientes"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-bold text-blue-600 shadow-2xs transition hover:bg-blue-50"
                    >
                        <span>Ver todos en bandeja</span>
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="py-3 px-5">N° Hoja</th>
                                <th class="py-3 px-5">Fecha / Hora</th>
                                <th class="py-3 px-5">Consumidor</th>
                                <th class="py-3 px-5">Estado</th>
                                <th class="py-3 px-5">Vencimiento Legal</th>
                                <th class="py-3 px-5 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="exp in prioritarios" :key="exp.id" class="transition hover:bg-slate-50/60">
                                <td class="py-3.5 px-5 font-mono font-bold text-slate-900">
                                    {{ exp.numero_hoja }}
                                </td>
                                <td class="whitespace-nowrap py-3.5 px-5 font-medium text-slate-600">
                                    {{ exp.fecha_hora }}
                                </td>
                                <td class="py-3.5 px-5 font-semibold text-slate-800">
                                    {{ exp.consumidor_nombre || 'No especificado' }}
                                </td>
                                <td class="whitespace-nowrap py-3.5 px-5">
                                    <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-0.5 text-[10px] font-bold text-amber-700 ring-1 ring-amber-600/20">
                                        {{ exp.estado }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap py-3.5 px-5">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-bold ring-1',
                                            exp.vencido
                                                ? 'bg-rose-50 text-rose-700 ring-rose-600/20'
                                                : (exp.dias_restantes <= 3 ? 'bg-amber-50 text-amber-700 ring-amber-600/20' : 'bg-emerald-50 text-emerald-700 ring-emerald-600/20')
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'h-1.5 w-1.5 rounded-full',
                                                exp.vencido ? 'bg-rose-500' : (exp.dias_restantes <= 3 ? 'bg-amber-500' : 'bg-emerald-500')
                                            ]"
                                        ></span>
                                        {{ exp.semaforo?.etiqueta || (exp.vencido ? 'Vencido' : `${exp.dias_restantes} días`) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap py-3.5 px-5 text-right">
                                    <Link
                                        :href="`/expedientes/${exp.id}`"
                                        class="inline-flex items-center gap-1 rounded-xl bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700 transition hover:bg-blue-100"
                                    >
                                        <span>Gestionar</span>
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="prioritarios.length === 0">
                                <td colspan="6" class="py-12 text-center text-slate-400">
                                    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <p class="mt-3 text-xs font-semibold text-slate-600">Sin casos urgentes en este periodo</p>
                                    <p class="mt-1 text-[11px] text-slate-400">No hay expedientes pendientes con plazo crítico de vencimiento.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Desglose por Sedes / Establecimientos (Estilo Admin/Empresas) -->
            <div v-if="desglose_sedes && desglose_sedes.length > 0" class="space-y-4 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-2xs">
                <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Expedientes por Sede o Sucursal</h2>
                        <p class="text-xs text-slate-500">Distribución de reclamaciones recibidas por cada establecimiento comercial.</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                    <div
                        v-for="sede in desglose_sedes"
                        :key="sede.id"
                        class="rounded-xl border border-slate-100 bg-slate-50/70 p-4 transition hover:bg-slate-50"
                    >
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Código {{ sede.codigo }}</div>
                        <div class="text-xs font-bold text-slate-800 truncate mt-1">{{ sede.nombre }}</div>
                        <div class="mt-2 text-xl font-black text-slate-900">{{ sede.total }} expedientes</div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
