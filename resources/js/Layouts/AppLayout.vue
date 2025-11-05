<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Banner from '@/Components/Banner.vue';
import DangerButton from '@/Components/DangerButton.vue';

/* --- INICIO DE ADICIONES (NECESARIAS PARA EL NUEVO DISEÑO) --- */
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';

defineProps({
    title: String,
});

// Nuevo estado para el menú móvil
const isMobileMenuOpen = ref(false);
/* --- FIN DE ADICIONES --- */


/* --- INICIO DE TU LÓGICA ORIGINAL (SIN CAMBIOS) --- */
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
/* --- FIN DE TU LÓGICA ORIGINAL --- */
</script>

<template>
    <div :class="{ 'dark': darkMode }" class="bg-gray-100 dark:bg-gray-900 transition-colors duration-300 min-h-screen">
        <Head :title="title" />
        <Banner />

        <div class="flex min-h-screen relative">
            
            <aside
                :class="{ 'w-72': !isSidebarHidden, 'w-20': isSidebarHidden }"
                class="bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 hidden md:flex flex-col transition-all duration-300 fixed h-full z-30"
            >
                <div class="flex items-center justify-between p-4 h-16 border-b border-gray-200 dark:border-gray-700">
                    <Link :href="route('dashboard')" v-if="!isSidebarHidden" class="flex items-center gap-2">
                        <ApplicationMark class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                        <span class="font-semibold text-lg text-gray-800 dark:text-gray-200">AGUA CABEZAS</span>
                    </Link>
                    
                    <button
                        @click="toggleSidebar"
                        class="p-2 rounded-full text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200"
                        :class="!isSidebarHidden ? 'ml-auto' : 'mx-auto'" >

                        <svg v-if="!isSidebarHidden" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>

                <nav class="p-4 space-y-4 flex-1 overflow-y-auto">
                    
                    <template v-if="$page.props.auth.user.role_names?.includes('Administrador')">
                        <span v-if="!isSidebarHidden" class="px-4 pt-4 text-xs font-semibold uppercase text-gray-400 dark:text-gray-500">Administración</span>
                        
                        <Link :href="route('dashboard')" :class="{'bg-gray-100 dark:bg-gray-700': route().current('dashboard')}" class="flex items-center text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2.5 rounded-lg transition-colors duration-200">
                            <span class="mr-3" title="Dashboard"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6-4a1 1 0 001-1v-1a1 1 0 10-2 0v1a1 1 0 001 1zm5-1a1 1 0 100-2 1 1 0 000 2z" /></svg></span>
                            <span v-if="!isSidebarHidden">Dashboard</span>
                        </Link>
                        
                        <Link href="/users" :class="{'bg-gray-100 dark:bg-gray-700': route().current('users.*')}" class="flex items-center text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2.5 rounded-lg transition-colors duration-200">
                            <span class="mr-3" title="Roles y Usuarios"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg></span>
                            <span v-if="!isSidebarHidden">Roles y Usuarios</span>
                        </Link>
                        
                        <span v-if="!isSidebarHidden" class="px-4 pt-4 text-xs font-semibold uppercase text-gray-400 dark:text-gray-500">Gestión</span>

                        <Link href="/afiliados" :class="{'bg-gray-100 dark:bg-gray-700': route().current('afiliados.*')}" class="flex items-center text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2.5 rounded-lg transition-colors duration-200">
                            <span class="mr-3" title="Afiliados"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.653-.124-1.282-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.653.124-1.282.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg></span>
                            <span v-if="!isSidebarHidden">Afiliados</span>
                        </Link>

                        <Link href="/conexiones" :class="{'bg-gray-100 dark:bg-gray-700': route().current('conexiones.*')}" class="flex items-center text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2.5 rounded-lg transition-colors duration-200">
                            <span class="mr-3" title="Conexiones"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg></span>
                            <span v-if="!isSidebarHidden">Conexiones</span>
                        </Link>

                        <Link href="/lecturas" :class="{'bg-gray-100 dark:bg-gray-700': route().current('lecturas.*')}" class="flex items-center text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2.5 rounded-lg transition-colors duration-200">
                            <span class="mr-3" title="Lecturas"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 0v6m0-6l-6 6m6-6v6m0-6l-6 6" /></svg></span>
                            <span v-if="!isSidebarHidden">Lecturas</span>
                        </Link>
                        
                        <span v-if="!isSidebarHidden" class="px-4 pt-4 text-xs font-semibold uppercase text-gray-400 dark:text-gray-500">Facturación</span>

                        <Link href="/facturacion/generar" :class="{'bg-gray-100 dark:bg-gray-700': route().current('facturacion.*')}" class="flex items-center text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2.5 rounded-lg transition-colors duration-200">
                            <span class="mr-3" title="Generar Facturas"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg></span>
                            <span v-if="!isSidebarHidden">Generar Facturas</span>
                        </Link>

                        <Link href="/tarifas" :class="{'bg-gray-100 dark:bg-gray-700': route().current('tarifas.*')}" class="flex items-center text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2.5 rounded-lg transition-colors duration-200">
                            <span class="mr-3" title="Tarifas"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0c-1.657 0-3-.895-3-2s1.343-2 3-2 3-.895 3-2-1.343-2-3-2m0 8c1.11 0 2.08-.402 2.599-1M12 8V7m0 1v8m0 0c-1.657 0-3-.895-3-2s1.343-2 3-2 3-.895 3-2-1.343-2-3-2m0 8c1.11 0 2.08-.402 2.599-1M12 8V7" /></svg></span>
                            <span v-if="!isSidebarHidden">Tarifas</span>
                        </Link>

                        <Link href="/facturas" :class="{'bg-gray-100 dark:bg-gray-700': route().current('facturas.*')}" class="flex items-center text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2.5 rounded-lg transition-colors duration-200">
                            <span class="mr-3" title="Facturas"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg></span>
                            <span v-if="!isSidebarHidden">Facturas</span>
                        </Link>
                        
                        <Link href="/pagos" :class="{'bg-gray-100 dark:bg-gray-700': route().current('pagos.*')}" class="flex items-center text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2.5 rounded-lg transition-colors duration-200">
                            <span class="mr-3" title="Pagos"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg></span>
                            <span v-if="!isSidebarHidden">Pagos</span>
                        </Link>
                        
                        <Link href="/reportes" :class="{'bg-gray-100 dark:bg-gray-700': route().current('reportes.*')}" class="flex items-center text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2.5 rounded-lg transition-colors duration-200">
                            <span class="mr-3" title="Reportes"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg></span>
                            <span v-if="!isSidebarHidden">Reportes</span>
                        </Link> 

                        <Link :href="route('reclamos.index')" :class="{'bg-gray-100 dark:bg-gray-700': route().current('reclamos.*')}" class="flex items-center text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2.5 rounded-lg transition-colors duration-200">
                            <span class="mr-3" title="Gestión Reclamos"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20" /></svg></span>
                            <span v-if="!isSidebarHidden">Gestión Reclamos</span>
                        </Link>
                    </template>

                    <template v-else-if="$page.props.auth.user.role_names?.includes('Secretaria')">
                        <span v-if="!isSidebarHidden" class="px-4 pt-4 text-xs font-semibold uppercase text-gray-400 dark:text-gray-500">Menú Secretaria</span>
                        
                        <Link href="/afiliados" :class="{'bg-gray-100 dark:bg-gray-700': route().current('afiliados.*')}" class="flex items-center ...">
                            <span class="mr-3" title="Afiliados"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.653-.124-1.282-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.653.124-1.282.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg></span>
                            <span v-if="!isSidebarHidden">Afiliados</span>
                        </Link>
                        <Link href="/conexiones" :class="{'bg-gray-100 dark:bg-gray-700': route().current('conexiones.*')}" class="flex items-center ...">
                            <span class="mr-3" title="Conexiones"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg></span>
                            <span v-if="!isSidebarHidden">Conexiones</span>
                        </Link> 
                        <Link href="/facturacion/generar" :class="{'bg-gray-100 dark:bg-gray-700': route().current('facturacion.*')}" class="flex items-center ...">
                            <span class="mr-3" title="Generar Facturas"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg></span>
                            <span v-if="!isSidebarHidden">Generar Facturas</span>
                        </Link>
                        <Link href="/facturas" :class="{'bg-gray-100 dark:bg-gray-700': route().current('facturas.*')}" class="flex items-center ...">
                            <span class="mr-3" title="Facturas"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg></span>
                            <span v-if="!isSidebarHidden">Facturas</span>
                        </Link>
                        <Link href="/pagos" :class="{'bg-gray-100 dark:bg-gray-700': route().current('pagos.*')}" class="flex items-center ...">
                            <span class="mr-3" title="Pagos"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg></span>
                            <span v-if="!isSidebarHidden">Pagos</span>
                        </Link>
                        <Link :href="route('lecturas.index')" :class="{'bg-gray-100 dark:bg-gray-700': route().current('lecturas.*')}" class="flex items-center ...">
                            <span class="mr-3" title="Ver Lecturas"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 0v6m0-6l-6 6m6-6v6m0-6l-6 6" /></svg></span>
                            <span v-if="!isSidebarHidden">Ver Lecturas</span>
                        </Link>
                        <Link :href="route('reclamos.index')" :class="{'bg-gray-100 dark:bg-gray-700': route().current('reclamos.*')}" class="flex items-center ...">
                            <span class="mr-3" title="Gestión Reclamos"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20" /></svg></span>
                            <span v-if="!isSidebarHidden">Gestión Reclamos</span>
                        </Link>
                    </template>

                    <template v-else-if="$page.props.auth.user.role_names?.includes('Tecnico')">
                        <span v-if="!isSidebarHidden" class="px-4 pt-4 text-xs font-semibold uppercase text-gray-400 dark:text-gray-500">Operaciones</span>
                        
                        <Link href="/lecturas" :class="{'bg-gray-100 dark:bg-gray-700': route().current('lecturas.*')}" class="flex items-center text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2.5 rounded-lg transition-colors duration-200">
                            <span class="mr-3" title="Lecturas"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 0v6m0-6l-6 6m6-6v6m0-6l-6 6" /></svg></span>
                            <span v-if="!isSidebarHidden">Lecturas</span>
                        </Link>
                    </template>

                    <template v-else-if="$page.props.auth.user.role_names?.includes('Usuario')">
                        <span v-if="!isSidebarHidden" class="px-4 pt-4 text-xs font-semibold uppercase text-gray-400 dark:text-gray-500">Mi Cuenta</span>
                        
                        <Link :href="route('usuario.dashboard')" :class="{'bg-gray-100 dark:bg-gray-700': route().current('usuario.dashboard')}" class="flex items-center text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2.5 rounded-lg transition-colors duration-200">
                            <span class="mr-3" title="Mi Resumen"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6-4a1 1 0 001-1v-1a1 1 0 10-2 0v1a1 1 0 001 1zm5-1a1 1 0 100-2 1 1 0 000 2z" /></svg></span>
                            <span v-if="!isSidebarHidden">Dashboard</span>
                        </Link>
                        <Link :href="route('mi.cuenta')" :class="{'bg-gray-100 dark:bg-gray-700': route().current('mi.cuenta')}" class="flex items-center text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2.5 rounded-lg transition-colors duration-200">
                            <span class="mr-3" title="Mis Facturas"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg></span>
                            <span v-if="!isSidebarHidden">Mis Facturas</span>
                        </Link>
                        <Link :href="route('pagos.mihistorial')" :class="{'bg-gray-100 dark:bg-gray-700': route().current('pagos.mihistorial')}" class="flex items-center text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2.5 rounded-lg transition-colors duration-200">
                            <span class="mr-3" title="Historial de Pagos"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg></span>
                            <span v-if="!isSidebarHidden">Historial de Pagos</span>
                        </Link>
                        <Link :href="route('reclamos.usuarioIndex')" :class="{'bg-gray-100 dark:bg-gray-700': route().current('reclamos.usuarioIndex*') || route().current('reclamos.create')}" class="flex items-center text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2.5 rounded-lg transition-colors duration-200">
                            <span class="mr-3" title="Mis Reclamos"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20" /></svg></span>
                            <span v-if="!isSidebarHidden">Mis Reclamos</span>
                        </Link>
                    </template>
                </nav>
            </aside>

            <div class="flex-1 flex flex-col transition-all duration-300" :class="{ 'md:ml-72': !isSidebarHidden, 'md:ml-20': isSidebarHidden }">
                
                <nav 
                    class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 shadow-sm transition-all duration-300 fixed top-0 right-0 z-20"
                    :class="{ 'md:left-72': !isSidebarHidden, 'md:left-20': isSidebarHidden, 'left-0': true }"
                >
                    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
                        <div class="flex justify-between h-16 items-center">
                            
                            <div class="flex items-center">
                                <button
                                    @click="isMobileMenuOpen = !isMobileMenuOpen"
                                    class="p-2 -ml-2 rounded-md text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 md:hidden"
                                >
                                    <svg v-if="!isMobileMenuOpen" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                                    </svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                                <div class="hidden md:flex w-8"></div>
                            </div>

                            <div class="flex-1 flex justify-center items-center">
                                <div class="ml-4 hidden md:flex flex-col items-center">
                                    <span class="font-semibold text-gray-800 dark:text-gray-200">Asociacion de Beneficarios de Agua </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ currentTime }}</span>
                                </div>
                                <!-- <div class="flex md:hidden font-semibold text-gray-800 dark:text-gray-200">
                                    AMC
                                </div> -->
                            </div>

                            <div class="flex items-center ml-6">
                                <Dropdown align="right" width="60">
                                    <template #trigger>
                                        <button class="flex items-center text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 focus:outline-none transition duration-150 ease-in-out">
                                            <img
                                                class="h-8 w-8 rounded-full object-cover mr-2"
                                                :src="$page.props.auth.user.profile_photo_url"
                                                :alt="$page.props.auth.user.name"
                                            />
                                            <span class="hidden md:inline">{{ $page.props.auth.user.name }}</span>
                                            <svg class="ml-1 -mr-0.5 h-4 w-4 hidden md:inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </template>

                                    <template #content>
                                        <div class="block px-4 py-2 text-xs text-gray-400">
                                            Configurar Cuenta
                                        </div>

                                        <DropdownLink :href="route('profile.show')">
                                            Mi Perfil
                                        </DropdownLink>

                                        <div class="border-t border-gray-100 dark:border-gray-600"></div>
                                        <div class="flex items-center justify-between w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300">
                                            <div class="flex items-center">
                                                <svg v-if="!darkMode" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                                                </svg>
                                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                                                </svg>
                                                <span>Modo Oscuro</span>
                                            </div>
                                            <label for="darkModeSwitchNav" class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" id="darkModeSwitchNav" :checked="darkMode" @change="toggleDarkMode" class="sr-only peer">
                                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                            </label>
                                        </div>

                                        <div class="border-t border-gray-100 dark:border-gray-600"></div>
                                        <form @submit.prevent="logout">
                                            <DropdownLink as="button" class="w-full text-left">
                                                <span class="text-red-600 dark:text-red-400">Cerrar Sesión</span>
                                            </DropdownLink>
                                        </form>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>
                    </div>

                    <div :class="{ 'block': isMobileMenuOpen, 'hidden': !isMobileMenuOpen }" class="md:hidden bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-lg">
                        
                        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
                            <div class="flex items-center px-4">
                                <img
                                    class="h-10 w-10 rounded-full object-cover"
                                    :src="$page.props.auth.user.profile_photo_url"
                                    :alt="$page.props.auth.user.name"
                                />
                                <div class="ml-3">
                                    <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ $page.props.auth.user.name }}</div>
                                    <div class="font-medium text-sm text-gray-500">{{ $page.props.auth.user.email }}</div>
                                </div>
                            </div>
                            <div class="mt-3 space-y-1">
                                <NavLink :href="route('profile.show')" :active="route().current('profile.show')">
                                    Mi Perfil
                                </NavLink>
                            </div>
                        </div>
                        
                        <div class="pt-2 pb-3 space-y-1 border-t border-gray-200 dark:border-gray-600">
                            <template v-if="$page.props.auth.user.role_names?.includes('Administrador')">
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')">Dashboard</NavLink>
                                <NavLink href="/users" :active="route().current('users.*')">Roles y Usuarios</NavLink>
                                <NavLink href="/afiliados" :active="route().current('afiliados.*')">Afiliados</NavLink>
                                <NavLink href="/conexiones" :active="route().current('conexiones.*')">Conexiones</NavLink>
                                <NavLink href="/lecturas" :active="route().current('lecturas.*')">Lecturas</NavLink>
                                <NavLink href="/facturacion/generar" :active="route().current('facturacion.*')">Generar Facturas</NavLink>
                                <NavLink href="/tarifas" :active="route().current('tarifas.*')">Tarifas</NavLink>
                                <NavLink href="/facturas" :active="route().current('facturas.*')">Facturas</NavLink>
                                <NavLink href="/pagos" :active="route().current('pagos.*')">Pagos</NavLink>
                                <NavLink href="/reportes" :active="route().current('reportes.*')">Reportes</NavLink>
                                <NavLink :href="route('reclamos.index')" :active="route().current('reclamos.*')">Gestión Reclamos</NavLink>
                            </template>
                            
                            <template v-else-if="$page.props.auth.user.role_names?.includes('Secretaria')">
                                <NavLink href="/afiliados" :active="route().current('afiliados.*')">Afiliados</NavLink>
                                <NavLink href="/conexiones" :active="route().current('conexiones.*')">Conexiones</NavLink>
                                <NavLink href="/facturacion/generar" :active="route().current('facturacion.*')">Generar Facturas</NavLink>
                                <NavLink href="/facturas" :active="route().current('facturas.*')">Facturas</NavLink>
                                <NavLink href="/pagos" :active="route().current('pagos.*')">Pagos</NavLink>
                                <NavLink :href="route('lecturas.index')" :active="route().current('lecturas.*')">Ver Lecturas</NavLink>
                                <NavLink :href="route('reclamos.index')" :active="route().current('reclamos.*')">Gestión Reclamos</NavLink>
                            </template>

                            <template v-else-if="$page.props.auth.user.role_names?.includes('Tecnico')">
                                <NavLink href="/lecturas" :active="route().current('lecturas.*')">Lecturas</NavLink>
                            </template>

                            <template v-else-if="$page.props.auth.user.role_names?.includes('Usuario')">
                                <NavLink :href="route('usuario.dashboard')" :active="route().current('usuario.dashboard')">Mi Resumen</NavLink>
                                <NavLink :href="route('mi.cuenta')" :active="route().current('mi.cuenta')">Mis Facturas</NavLink>
                                <NavLink :href="route('pagos.mihistorial')" :active="route().current('pagos.mihistorial')">Historial de Pagos</NavLink>
                                <NavLink :href="route('reclamos.usuarioIndex')" :active="route().current('reclamos.usuarioIndex*') || route().current('reclamos.create')">Mis Reclamos</NavLink>
                            </template>
                        </div>

                        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
                            <form @submit.prevent="logout">
                                <NavLink as="button" class="w-full text-left text-red-600 dark:text-red-400">
                                    Cerrar Sesión
                                </NavLink>
                            </form>
                        </div>
                    </div>
                </nav>

                <header v-if="$slots.header" class="bg-white dark:bg-gray-800 shadow-sm transition-colors duration-300 pt-16">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        <slot name="header" />
                    </div>
                </header>
                <div v-else class="pt-16"></div>

                <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto">
                    <slot />
                </main>

            </div>
         </div> 
          </div> 
  </template>