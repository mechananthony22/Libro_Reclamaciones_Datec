<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const flashSuccess = computed(() => (page.props.flash as any)?.success || null);

const showPassword = ref(false);
const showDemoAccesos = ref(false);
const modalRecuperar = ref(false);
const pasoRecuperar = ref(1);
const showNuevaPassword = ref(false);
const showConfirmarPassword = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const formEnviarCodigo = useForm({
    email: '',
});

const formRestablecer = useForm({
    email: '',
    codigo: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}

function usarDemo(email: string) {
    form.email = email;
    form.password = 'password123';
}

function abrirModalRecuperar() {
    formEnviarCodigo.email = form.email || '';
    pasoRecuperar.value = 1;
    formEnviarCodigo.clearErrors();
    formRestablecer.clearErrors();
    modalRecuperar.value = true;
}

function cerrarModalRecuperar() {
    modalRecuperar.value = false;
    pasoRecuperar.value = 1;
    formEnviarCodigo.clearErrors();
    formRestablecer.clearErrors();
}

function solicitarCodigo() {
    formEnviarCodigo.post('/password/enviar-codigo', {
        preserveScroll: true,
        onSuccess: () => {
            formRestablecer.email = formEnviarCodigo.email;
            formRestablecer.codigo = '';
            formRestablecer.password = '';
            formRestablecer.password_confirmation = '';
            formRestablecer.clearErrors();
            pasoRecuperar.value = 2;
        },
    });
}

function ejecutarRestablecimiento() {
    formRestablecer.post('/password/restablecer', {
        preserveScroll: true,
        onSuccess: () => {
            modalRecuperar.value = false;
            pasoRecuperar.value = 1;
            form.email = formRestablecer.email;
            form.password = '';
            formEnviarCodigo.reset();
            formRestablecer.reset();
        },
    });
}

function volverAPaso1() {
    pasoRecuperar.value = 1;
    formRestablecer.clearErrors();
    formEnviarCodigo.clearErrors();
}
</script>

<template>
    <Head title="Iniciar Sesión | DatecLibro" />

    <div class="min-h-screen bg-gradient-to-br from-[#fbf8f3] via-[#f6f1e8] to-[#ede5d8] flex items-center justify-center p-4 sm:p-6 lg:p-8 font-sans selection:bg-blue-100 selection:text-blue-900 relative overflow-hidden">
        <!-- Tarjeta Principal Flotante -->
        <div class="relative w-full max-w-4xl bg-white rounded-3xl sm:rounded-[2.25rem] shadow-2xl shadow-stone-900/10 border border-stone-200/60 overflow-hidden flex flex-col md:flex-row min-h-[540px]">
            <!-- Columna Izquierda: Gráficos 3D y Bienvenida -->
            <div class="relative w-full md:w-5/12 bg-gradient-to-br from-[#035bd8] via-[#014bc0] to-[#00389c] p-8 sm:p-10 flex flex-col justify-between overflow-hidden text-white min-h-[260px] md:min-h-auto">
                <!-- Esfera 1 (Superior Derecha) -->
                <div class="absolute -top-12 -right-12 w-64 h-64 rounded-full bg-gradient-to-br from-blue-400 via-blue-600 to-blue-950 pointer-events-none opacity-85 shadow-2xl"></div>

                <!-- Esfera 2 (Inferior Izquierda) -->
                <div class="absolute -bottom-16 -left-16 w-60 h-60 rounded-full bg-gradient-to-tr from-blue-950 via-blue-700 to-blue-400 pointer-events-none opacity-90 shadow-2xl"></div>

                <!-- Esfera 3 (Flotante Centro Inferior) -->
                <div class="absolute bottom-6 right-6 md:left-24 w-40 h-40 rounded-full bg-gradient-to-br from-[#2b88ff] via-[#0256d0] to-[#002a7a] pointer-events-none shadow-2xl ring-1 ring-white/10"></div>

                <!-- Contenido Superior: Logotipo Oficial -->
                <div class="relative z-10">
                    <Link href="/" class="inline-flex items-center group">
                        <div class="bg-white/95 rounded-2xl p-2.5 sm:p-3 shadow-lg shadow-black/15 ring-1 ring-white/30 backdrop-blur-md">
                            <img
                                src="/logoDLR.png"
                                alt="DatecLibro"
                                class="h-16 sm:h-20 w-auto max-w-[260px] object-contain"
                                onerror="this.style.display='none'; if(this.parentElement.nextElementSibling) this.parentElement.nextElementSibling.style.display='flex';"
                            />
                        </div>
                        <div class="hidden items-center gap-2.5">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/15 backdrop-blur-md text-white shadow-sm ring-1 ring-white/20">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/>
                                    <path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/>
                                </svg>
                            </div>
                            <span class="text-2xl font-extrabold tracking-tight text-white">
                                Datec<span class="text-blue-200">Libro</span>
                            </span>
                        </div>
                    </Link>
                </div>

                <!-- Contenido Central: Mensaje de Bienvenida -->
                <div class="relative z-10 my-auto py-6">
                    <h1 class="text-3xl sm:text-4xl font-black tracking-wide text-white uppercase">
                        Bienvenido
                    </h1>
                    <div class="text-xs font-bold uppercase tracking-widest text-blue-200 mt-1">
                        Portal de Gestión
                    </div>
                    <p class="text-xs text-blue-100/90 leading-relaxed mt-3 max-w-xs font-medium">
                        Sistema integral para la atención de reclamaciones, trazabilidad legal y cumplimiento normativo INDECOPI.
                    </p>
                </div>

                <!-- Pie Lateral -->
                <div class="relative z-10 text-[11px] text-blue-200/80">
                    &copy; {{ new Date().getFullYear() }} Datec Consulting. Todos los derechos reservados.
                </div>
            </div>

            <!-- Columna Derecha: Formulario de Login -->
            <div class="relative flex-1 bg-white p-8 sm:p-12 flex flex-col justify-center overflow-hidden">
                <!-- Esfera Decorativa Inferior Derecha -->
                <div class="absolute -bottom-14 -right-14 w-40 h-40 rounded-full bg-gradient-to-br from-[#0264ec] to-[#0141a8] pointer-events-none opacity-90 shadow-xl"></div>

                <div class="relative z-10 max-w-sm w-full mx-auto space-y-6">
                    <!-- Título del Formulario -->
                    <div>
                        <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                            Iniciar Sesión
                        </h2>
                        <p class="text-xs text-slate-400 mt-1">
                            Ingresa con tu correo institucional y contraseña
                        </p>
                    </div>

                    <!-- Mensaje Flash -->
                    <div v-if="flashSuccess" class="rounded-xl border border-emerald-200 bg-emerald-50/90 p-3 text-xs font-semibold text-emerald-800 flex items-center gap-2">
                        <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ flashSuccess }}</span>
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">
                        <!-- Campo: Usuario / Email -->
                        <div>
                            <div class="relative flex items-center bg-slate-100/90 rounded-xl px-4 py-3 border border-transparent focus-within:border-blue-500 focus-within:bg-white transition shadow-2xs">
                                <svg class="h-5 w-5 text-slate-500 shrink-0 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    autocomplete="email"
                                    required
                                    placeholder="Usuario o correo electrónico"
                                    class="w-full bg-transparent text-xs font-medium text-slate-800 placeholder:text-slate-400 focus:outline-none"
                                />
                            </div>
                            <p v-if="form.errors.email" class="mt-1 text-[11px] text-rose-600 font-semibold pl-1">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <!-- Campo: Contraseña -->
                        <div>
                            <div class="relative flex items-center bg-slate-100/90 rounded-xl px-4 py-3 border border-transparent focus-within:border-blue-500 focus-within:bg-white transition shadow-2xs">
                                <svg class="h-5 w-5 text-slate-500 shrink-0 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                <input
                                    id="password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    autocomplete="current-password"
                                    required
                                    placeholder="Contraseña"
                                    class="w-full bg-transparent text-xs font-medium text-slate-800 placeholder:text-slate-400 focus:outline-none"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 hover:text-slate-800 transition cursor-pointer select-none ml-2 shrink-0"
                                >
                                    {{ showPassword ? 'OCULTAR' : 'MOSTRAR' }}
                                </button>
                            </div>
                            <p v-if="form.errors.password" class="mt-1 text-[11px] text-rose-600 font-semibold pl-1">
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <!-- Recordar Sesión / Olvidaste Contraseña -->
                        <div class="flex items-center justify-between text-xs pt-1">
                            <label class="flex items-center gap-2 cursor-pointer select-none text-slate-600 font-medium">
                                <input
                                    v-model="form.remember"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                />
                                <span>Recordarme</span>
                            </label>

                            <button
                                type="button"
                                @click="abrirModalRecuperar"
                                class="text-slate-600 hover:text-blue-700 transition font-medium text-xs cursor-pointer"
                            >
                                ¿Olvidaste tu contraseña?
                            </button>
                        </div>

                        <!-- Botón Principal: Iniciar Sesión -->
                        <div class="pt-2">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full rounded-xl bg-[#0c2e59] hover:bg-[#071f3e] text-white font-bold py-3 px-6 text-sm shadow-md transition disabled:opacity-50 cursor-pointer flex items-center justify-center gap-2"
                            >
                                <span v-if="form.processing">Iniciando sesión...</span>
                                <span v-else>Iniciar Sesión</span>
                            </button>
                        </div>
                    </form>

                    <!-- Divisor "o" -->
                    <div class="relative flex items-center justify-center pt-1">
                        <div class="border-t border-slate-200 w-full"></div>
                        <span class="bg-white px-3 text-xs text-slate-400 font-medium absolute">o</span>
                    </div>

                    <!-- Enlace inferior -->
                    <div class="text-center pt-2">
                        <span class="text-xs text-slate-500">¿No tienes una cuenta? </span>
                        <a href="https://datec.pe" target="_blank" class="text-xs font-bold text-blue-600 hover:text-blue-800 transition">
                            Solicitar información
                        </a>
                    </div>

                    <!-- Accesos Rápidos de Prueba (Colapsables) -->
                    <div class="pt-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showDemoAccesos = !showDemoAccesos"
                            class="w-full text-center text-[11px] font-semibold text-slate-400 hover:text-slate-600 transition flex items-center justify-center gap-1 cursor-pointer"
                        >
                            <span>{{ showDemoAccesos ? 'Ocultar accesos rápidos de prueba' : 'Ver accesos rápidos de prueba (Demo)' }}</span>
                            <svg class="h-3 w-3" :class="{ 'rotate-180': showDemoAccesos }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div v-if="showDemoAccesos" class="mt-2.5 space-y-1.5 text-xs animate-fade-in">
                            <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 border border-slate-200/80">
                                <span class="font-mono text-[11px] text-slate-600">datecsuperadmin@gmail.com</span>
                                <button
                                    type="button"
                                    @click="usarDemo('datecsuperadmin@gmail.com')"
                                    class="text-[10px] font-bold text-blue-600 hover:text-blue-800 bg-white border border-slate-200 px-2 py-0.5 rounded cursor-pointer"
                                >
                                    Superadmin
                                </button>
                            </div>
                            <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 border border-slate-200/80">
                                <span class="font-mono text-[11px] text-slate-600">admin@datec.pe</span>
                                <button
                                    type="button"
                                    @click="usarDemo('admin@datec.pe')"
                                    class="text-[10px] font-bold text-blue-600 hover:text-blue-800 bg-white border border-slate-200 px-2 py-0.5 rounded cursor-pointer"
                                >
                                    Admin Datec
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Interactivo: Recuperación de Contraseña mediante Código por Correo -->
        <Teleport to="body">
            <Transition
                enter-active-class="transform transition ease-out duration-300"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transform transition ease-in duration-200"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-if="modalRecuperar"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
                    @click.self="cerrarModalRecuperar"
                >
                    <div class="w-full max-w-md overflow-hidden rounded-3xl bg-white p-6 sm:p-7 shadow-2xl border border-slate-100 relative animate-fade-in text-left">
                        <!-- Botón Cerrar (X) -->
                        <button
                            type="button"
                            @click="cerrarModalRecuperar"
                            class="absolute top-4 right-4 rounded-xl p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition cursor-pointer"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <!-- PASO 1: Solicitar Código -->
                        <div v-if="pasoRecuperar === 1" class="space-y-4">
                            <div class="text-center">
                                <div class="mx-auto flex h-13 w-13 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 ring-8 ring-blue-50/50 mb-3">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-extrabold text-slate-900">
                                    ¿Olvidaste tu contraseña?
                                </h3>
                                <p class="mt-1 text-xs text-slate-500 leading-relaxed">
                                    Ingresa tu correo registrado y te enviaremos un código de seguridad de 6 dígitos para restablecerla al instante.
                                </p>
                            </div>

                            <form @submit.prevent="solicitarCodigo" class="space-y-3.5 mt-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Correo Electrónico *
                                    </label>
                                    <div class="relative">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <input
                                            v-model="formEnviarCodigo.email"
                                            type="email"
                                            required
                                            placeholder="ejemplo@empresa.com"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 pl-10 pr-4 py-2.5 text-xs sm:text-sm text-slate-800 focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-100 transition outline-none"
                                            :class="{ 'border-rose-400 bg-rose-50/30': formEnviarCodigo.errors.email }"
                                        />
                                    </div>
                                    <p v-if="formEnviarCodigo.errors.email" class="mt-1 text-xs text-rose-600 font-medium">
                                        {{ formEnviarCodigo.errors.email }}
                                    </p>
                                </div>

                                <div class="pt-2">
                                    <button
                                        type="submit"
                                        :disabled="formEnviarCodigo.processing"
                                        class="w-full rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 text-xs sm:text-sm shadow-md shadow-blue-500/25 transition disabled:opacity-50 cursor-pointer flex items-center justify-center gap-2"
                                    >
                                        <span v-if="formEnviarCodigo.processing">Enviando código...</span>
                                        <span v-else>Enviar código a mi correo</span>
                                    </button>
                                </div>

                                <div class="text-center pt-1">
                                    <button
                                        type="button"
                                        @click="cerrarModalRecuperar"
                                        class="text-xs font-semibold text-slate-500 hover:text-slate-800 transition cursor-pointer"
                                    >
                                        Cancelar y volver al inicio
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- PASO 2: Ingresar Código y Nueva Contraseña -->
                        <div v-else class="space-y-4">
                            <div class="text-center">
                                <div class="mx-auto flex h-13 w-13 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 ring-8 ring-emerald-50/50 mb-3">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-extrabold text-slate-900">
                                    Ingresa el código recibido
                                </h3>
                                <p class="mt-1 text-xs text-slate-500 leading-relaxed">
                                    Enviamos un código de 6 dígitos a <strong class="text-slate-800">{{ formRestablecer.email }}</strong>. Ingrésalo para definir tu nueva contraseña.
                                </p>
                            </div>

                            <form @submit.prevent="ejecutarRestablecimiento" class="space-y-3 mt-3">
                                <!-- Campo Código de 6 Dígitos -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                        Código de 6 Dígitos *
                                    </label>
                                    <input
                                        v-model="formRestablecer.codigo"
                                        type="text"
                                        required
                                        maxlength="6"
                                        autocomplete="one-time-code"
                                        placeholder="123456"
                                        class="w-full text-center font-mono text-xl sm:text-2xl font-black tracking-[8px] rounded-xl border border-blue-300 bg-blue-50/40 py-2.5 text-blue-900 focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-100 transition outline-none"
                                        :class="{ 'border-rose-400 bg-rose-50/30 text-rose-900': formRestablecer.errors.codigo }"
                                    />
                                    <p v-if="formRestablecer.errors.codigo" class="mt-1 text-xs text-rose-600 font-medium text-center">
                                        {{ formRestablecer.errors.codigo }}
                                    </p>
                                </div>

                                <!-- Campo Nueva Contraseña -->
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                            Nueva Contraseña *
                                        </label>
                                        <button
                                            type="button"
                                            @click="showNuevaPassword = !showNuevaPassword"
                                            class="text-[10px] font-bold text-blue-600 hover:text-blue-800 transition uppercase tracking-wider cursor-pointer"
                                        >
                                            {{ showNuevaPassword ? 'Ocultar' : 'Mostrar' }}
                                        </button>
                                    </div>
                                    <input
                                        v-model="formRestablecer.password"
                                        :type="showNuevaPassword ? 'text' : 'password'"
                                        required
                                        placeholder="Mínimo 8 caracteres"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2 text-xs sm:text-sm text-slate-800 focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-100 transition outline-none"
                                        :class="{ 'border-rose-400 bg-rose-50/30': formRestablecer.errors.password }"
                                    />
                                    <p v-if="formRestablecer.errors.password" class="mt-1 text-xs text-rose-600 font-medium">
                                        {{ formRestablecer.errors.password }}
                                    </p>
                                </div>

                                <!-- Campo Confirmar Contraseña -->
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                            Confirmar Contraseña *
                                        </label>
                                        <button
                                            type="button"
                                            @click="showConfirmarPassword = !showConfirmarPassword"
                                            class="text-[10px] font-bold text-blue-600 hover:text-blue-800 transition uppercase tracking-wider cursor-pointer"
                                        >
                                            {{ showConfirmarPassword ? 'Ocultar' : 'Mostrar' }}
                                        </button>
                                    </div>
                                    <input
                                        v-model="formRestablecer.password_confirmation"
                                        :type="showConfirmarPassword ? 'text' : 'password'"
                                        required
                                        placeholder="Repite tu nueva contraseña"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2 text-xs sm:text-sm text-slate-800 focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-100 transition outline-none"
                                    />
                                </div>

                                <div class="pt-2">
                                    <button
                                        type="submit"
                                        :disabled="formRestablecer.processing"
                                        class="w-full rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 text-xs sm:text-sm shadow-md shadow-blue-500/25 transition disabled:opacity-50 cursor-pointer flex items-center justify-center gap-2"
                                    >
                                        <span v-if="formRestablecer.processing">Restableciendo contraseña...</span>
                                        <span v-else>Restablecer contraseña y acceder</span>
                                    </button>
                                </div>

                                <div class="flex items-center justify-between pt-1 text-xs">
                                    <button
                                        type="button"
                                        @click="volverAPaso1"
                                        class="font-semibold text-blue-600 hover:text-blue-800 transition cursor-pointer"
                                    >
                                        &larr; Solicitar otro código
                                    </button>
                                    <button
                                        type="button"
                                        @click="cerrarModalRecuperar"
                                        class="font-semibold text-slate-400 hover:text-slate-600 transition cursor-pointer"
                                    >
                                        Cancelar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
