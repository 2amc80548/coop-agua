<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Banner from '@/Components/Banner.vue';
import DangerButton from '@/Components/DangerButton.vue';

// Estado del sidebar y modo oscuro
const isSidebarHidden = ref(false);
const darkMode = ref(false);
const currentTime = ref('');

// Alternar sidebar
const toggleSidebar = () => {
    isSidebarHidden.value = !isSidebarHidden.value;
};

// Alternar modo oscuro
const toggleDarkMode = () => {
    darkMode.value = !darkMode.value;
    if (darkMode.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

// Actualizar hora en tiempo real
const updateTime = () => {
    const now = new Date();
    currentTime.value = now.toLocaleDateString('es-ES', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    }) + ' ' + now.toLocaleTimeString('es-ES');
};

// Inicialización
onMounted(() => {
    // Detectar tema guardado o preferencia del sistema
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    darkMode.value = savedTheme === 'dark' || (!savedTheme && prefersDark);
    if (darkMode.value) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }

    // Iniciar y actualizar hora
    updateTime();
    setInterval(updateTime, 1000);
});

// Cerrar sesión
const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div :class="{ 'dark': darkMode }" class="bg-gray-100 dark:bg-gray-900 transition-colors duration-300 min-h-screen">
        <Head :title="title" />
        <Banner />

        <div class="flex min-h-screen relative">
            <!-- Sidebar (izquierda) -->
            <aside
                :class="{ 'w-72': !isSidebarHidden, 'w-20': isSidebarHidden }"
                class="bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 hidden md:flex flex-col transition-all duration-300 fixed h-full overflow-y-auto z-50"
            >
                <!-- Encabezado del sidebar -->
                <div class="flex items-center justify-between p-6 font-bold text-lg text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700">
                    <!-- Foto y nombre del usuario -->
                    <div v-if="!isSidebarHidden" class="flex items-center">
                        <img
                            class="h-10 w-10 rounded-full object-cover mr-3"
                            :src="$page.props.auth.user.profile_photo_url"
                            :alt="$page.props.auth.user.name"
                        />
                        <span>{{ $page.props.auth.user.name }}</span>
                    </div>
                    <!-- Solo foto cuando está colapsado -->
                    <img
                        v-else
                        class="h-10 w-10 rounded-full object-cover"
                        :src="$page.props.auth.user.profile_photo_url"
                        :alt="$page.props.auth.user.name"
                    />

                    <!-- Botón de toggle: 3 líneas o flecha -->
                    <button
                        @click="toggleSidebar"
                        class="p-2 rounded-full text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200"
                    >
                        <!-- 3 líneas (☰) cuando está expandido -->
                        <svg v-if="!isSidebarHidden" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <!-- Flecha derecha (→) cuando está colapsado -->
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>

                <!-- Menú de navegación -->
                <nav class="p-4 space-y-2 flex-1">
                    <!-- Opciones según rol -->
                    <template v-if="$page.props.auth.user.role_names?.includes('Administrador')">
                        <Link href="/users" class="flex items-center text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-lg transition-colors duration-200">
                            <span class="mr-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 9a3 3 0 100-6 3 3 0 000 6zM9.002 18.005a7.995 7.995 0 01-.225-3.003c0-.124.01-.25-.015-.375c-.024-.226.046-.45.195-.626.149-.176.353-.28.572-.28h1.161c.219 0 .423.104.572.28.149.176.219.4.195.626-.025.125-.015.251-.015.375a7.995 7.995 0 01-.225 3.003h-1.148zm1.002-8a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span>
                            <span v-if="!isSidebarHidden">Usuarios</span>
                        </Link>
                        <Link href="/beneficiarios" class="flex items-center text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-lg transition-colors duration-200">
                            <span class="mr-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18H4v-3a4 4 0 018.88-2.31c.22.13.434.288.64.464l-.001.001c.212.183.398.397.587.644.133.176.27.355.405.539.096.13.19.263.28.401l-.001-.001c.135.207.288.423.454.646.126.166.257.336.395.511a4.002 4.002 0 01-1.39 1.488L16 18z"/></svg></span>
                            <span v-if="!isSidebarHidden">Beneficiarios</span>
                        </Link>
                        <Link href="/facturas" class="flex items-center text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-lg transition-colors duration-200">
                            <span class="mr-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 012.707 13H4a1 1 0 011 1v2a1 1 0 01-1 1H3a1 1 0 000 2h14a1 1 0 000-2h-1a1 1 0 01-1-1v-2a1 1 0 011-1h1.293a1 1 0 01.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 4a4 4 0 014 4v4H6z"/></svg></span>
                            <span v-if="!isSidebarHidden">Facturas</span>
                        </Link>
                        <Link href="/pagos" class="flex items-center text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-lg transition-colors duration-200">
                            <span class="mr-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M4 4a2 2 0 00-2 2v6a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-3V3a1 1 0 00-2 0v1H9V3a1 1 0 00-2 0v1H4zm0 2h12v6H4V6zm-1 8h14a1 1 0 011 1v2a1 1 0 01-1 1H3a1 1 0 01-1-1v-2a1 1 0 011-1z"/></svg></span>
                            <span v-if="!isSidebarHidden">Pagos</span>
                        </Link>
                    </template>

                    <template v-else-if="$page.props.auth.user.role_names?.includes('Secretaria')">
                        <Link href="/beneficiario" class="flex items-center text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-lg transition-colors duration-200">
                            <span class="mr-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18H4v-3a4 4 0 018.88-2.31c.22.13.434.288.64.464l-.001.001c.212.183.398.397.587.644.133.176.27.355.405.539.096.13.19.263.28.401l-.001-.001c.135.207.288.423.454.646.126.166.257.336.395.511a4.002 4.002 0 01-1.39 1.488L16 18z"/></svg></span>
                            <span v-if="!isSidebarHidden">Beneficiarios</span>
                        </Link>
                        <Link href="/pago" class="flex items-center text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-lg transition-colors duration-200">
                            <span class="mr-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M4 4a2 2 0 00-2 2v6a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-3V3a1 1 0 00-2 0v1H9V3a1 1 0 00-2 0v1H4zm0 2h12v6H4V6zm-1 8h14a1 1 0 011 1v2a1 1 0 01-1 1H3a1 1 0 01-1-1v-2a1 1 0 011-1z"/></svg></span>
                            <span v-if="!isSidebarHidden">Pagos</span>
                        </Link>
                    </template>

                    <template v-else-if="$page.props.auth.user.role_names?.includes('Tecnico')">
                        <Link href="/conexiones" class="flex items-center text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-lg transition-colors duration-200">
                            <span class="mr-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-8V5a1 1 0 012 0v5a1 1 0 01-2 0zm-1-2.5a.5.5 0 011 0v.5a.5.5 0 01-1 0v-.5zm4 0a.5.5 0 011 0v.5a.5.5 0 01-1 0v-.5z" clip-rule="evenodd"/></svg></span>
                            <span v-if="!isSidebarHidden">Conexiones</span>
                        </Link>
                        <Link href="/lecturas" class="flex items-center text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-lg transition-colors duration-200">
                            <span class="mr-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 0v12h8V4H6zm1 2h6v2H7V6zm0 3h6v2H7V9zm0 3h6v2H7v-2z" clip-rule="evenodd"/></svg></span>
                            <span v-if="!isSidebarHidden">Lecturas</span>
                        </Link>
                    </template>

                    <template v-else-if="$page.props.auth.user.role_names?.includes('Usuario')">
                        <Link href="/mi-cuenta" class="flex items-center text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-lg transition-colors duration-200">
                            <span class="mr-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg></span>
                            <span v-if="!isSidebarHidden">Mi cuenta</span>
                        </Link>
                        <Link href="/notificaciones" class="flex items-center text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-lg transition-colors duration-200">
                            <span class="mr-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 012.707 13H4a1 1 0 011 1v2a1 1 0 01-1 1H3a1 1 0 000 2h14a1 1 0 000-2h-1a1 1 0 01-1-1v-2a1 1 0 011-1h1.293a1 1 0 01.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 110-6 3 3 0 010 6z"/></svg></span>
                            <span v-if="!isSidebarHidden">Notificaciones</span>
                        </Link>
                    </template>
                </nav>

                <!-- Configuración y cierre de sesión -->
                <div class="p-4 border-t border-gray-200 dark:border-gray-700 space-y-2">
                    <details class="group">
                        <summary class="flex items-center justify-between w-full px-3 py-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer transition-colors duration-200">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37a1.724 1.724 0 002.572-1.065z"/></svg>
                                <span v-if="!isSidebarHidden">Configuración</span>
                            </div>
                            <span v-if="!isSidebarHidden" class="transition group-open:rotate-180">
                                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </span>
                        </summary>
                        <div v-if="!isSidebarHidden" class="ml-4 space-y-2">
                            <Link :href="route('profile.show')" class="flex items-center w-full px-3 py-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200">
                                <span class="mr-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></span>
                                Perfil
                            </Link>
                            <div class="flex items-center justify-between w-full px-3 py-2 rounded-lg text-gray-700 dark:text-gray-300 transition-colors duration-200">
                                <div class="flex items-center">
                                    <span class="mr-3">
                                        <!-- Icono del sol o luna según modo -->
                                        <svg v-if="!darkMode" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                                        </svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                                        </svg>
                                    </span>
                                    <span>{{ darkMode ? 'Modo Oscuro' : 'Modo Claro' }}</span>
                                </div>
                                <!-- Switch de modo oscuro -->
                                <label for="darkModeSwitch" class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="darkModeSwitch" :checked="darkMode" @change="toggleDarkMode" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                        </div>
                    </details>

                    <button @click="logout" class="w-full">
                        <DangerButton class="w-full flex items-center justify-start px-3 py-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            <span v-if="!isSidebarHidden">Cerrar Sesión</span>
                        </DangerButton>
                    </button>
                </div>
            </aside>

            <!-- Contenido principal -->
            <div class="flex-1 flex flex-col transition-all duration-300" :class="{ 'md:ml-72': !isSidebarHidden, 'md:ml-20': isSidebarHidden }">
                <!-- Barra superior -->
                <nav class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 shadow-sm transition-colors duration-300 fixed top-0 w-full z-40">
                    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
                        <div class="flex justify-between h-16 items-center">
                            <!-- Logo y nombre de la cooperativa -->
                            <div class="flex items-center">
                                <Link :href="route('dashboard')">
                                    <ApplicationMark class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                                </Link>
                                <div class="ml-4 hidden md:flex flex-col">
                                    <span class="font-semibold text-gray-800 dark:text-gray-200">CooperatiDAva de Agua</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ currentTime }}</span>
                                </div>
                            </div>

                            <!-- Barra de búsqueda -->
                            <div class="flex-1 flex justify-center px-2">
                                <div class="relative w-full max-w-md">
                                    <input
                                        type="text"
                                        placeholder="Buscar..."
                                        class="w-full pl-10 pr-4 py-2 border rounded-full text-sm focus:outline-none focus:border-blue-500 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200"
                                    />
                                    <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                            </div>

                            <!-- Espacio vacío para centrar (evita duplicado del logo) -->
                            <div class="hidden md:flex"></div>
                        </div>
                    </div>
                </nav>

                <!-- Header opcional -->
                <header v-if="$slots.header" class="bg-white dark:bg-gray-800 shadow-sm transition-colors duration-300 pt-16">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        <slot name="header" />
                    </div>
                </header>

                <!-- Contenido principal -->
                <main class="flex-1 p-6 overflow-y-auto mt-16">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>