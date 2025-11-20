<script setup>
import { ref, onMounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ApplicationMark from '@/Components/ApplicationMark.vue'
import Banner from '@/Components/Banner.vue'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
import NavLink from '@/Components/NavLink.vue'

defineProps({ title: String })

const isMobileMenuOpen = ref(false)
const isSidebarHidden = ref(false)
const darkMode = ref(false)
const temaActual = ref('tema-jovenes')
const currentTime = ref('')
const isMobile = ref(false)

/* =======================
 * BUSCADOR
 * ======================= */
const textoBusqueda = ref('')
const resultados = ref([])
const buscadorCargando = ref(false)
const buscadorError = ref('')

let searchTimeout = null

const buscar = () => {
    const query = textoBusqueda.value.trim()

    if (searchTimeout) {
        clearTimeout(searchTimeout)
    }

    searchTimeout = setTimeout(async () => {
        const q = textoBusqueda.value.trim()

        if (q.length < 2) {
            resultados.value = []
            buscadorError.value = ''
            return
        }

        buscadorCargando.value = true
        buscadorError.value = ''

        try {
            const res = await fetch(`/buscar?q=${encodeURIComponent(q)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            })

            if (!res.ok) {
                buscadorError.value = `Error del servidor (${res.status})`
                resultados.value = []
                return
            }

            const data = await res.json()

            resultados.value = data

            if (!data.length) {
                buscadorError.value = 'Sin resultados'
            }
        } catch (error) {
            buscadorError.value = 'Error de conexión'
            resultados.value = []
        } finally {
            buscadorCargando.value = false
        }
    }, 300)
}

const irAlPrimero = () => {
    if (resultados.value.length > 0) {
        const url = resultados.value[0].url
        router.visit(url)
        textoBusqueda.value = ''
        resultados.value = []
        buscadorError.value = ''
    }
}

const irA = (url) => {
    if (!url) return
    router.visit(url)
    textoBusqueda.value = ''
    resultados.value = []
    buscadorError.value = ''
}

/* =======================
 * TEMAS, TIEMPO, ETC
 * ======================= */
const aplicarTema = () => {
    const tema = localStorage.getItem('tema') || 'tema-jovenes'
    const oscuro = localStorage.getItem('dark') === 'true'
    temaActual.value = tema
    darkMode.value = oscuro
    document.documentElement.className = oscuro ? `dark ${tema}` : tema
}

const cambiarTema = (nuevo) => {
    localStorage.setItem('tema', nuevo)
    aplicarTema()
}

const toggleDarkMode = () => {
    localStorage.setItem('dark', !darkMode.value)
    aplicarTema()
}

const updateTime = () => {
    const now = new Date()
    currentTime.value =
        now.toLocaleDateString('es-ES', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        }) +
        ' | ' +
        now.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })
}

const checkMobile = () => {
    if (typeof window !== 'undefined') {
        isMobile.value = window.innerWidth < 768
    }
}

const closeMobileMenu = () => {
    isMobileMenuOpen.value = false
}

onMounted(() => {
    aplicarTema()
    updateTime()
    setInterval(updateTime, 1000)
    checkMobile()
    window.addEventListener('resize', () => {
        checkMobile()
        if (window.innerWidth >= 768) closeMobileMenu()
    })
})

const logout = () => {
    router.post(route('logout'))
}
</script>

<template>
<!-- CLAVE: clases correctas en el div raíz -->
<div :class="[darkMode ? 'dark' : '', temaActual]" class="min-h-screen bg-gray-50 dark:bg-gray-900">

    <Head :title="title" />
    <Banner />

    <!-- CONTENEDOR PRINCIPAL -->
    <div class="flex h-screen overflow-hidden">

        <!-- OVERLAY MÓVIL -->
        <div
            v-if="isMobileMenuOpen"
            @click="closeMobileMenu"
            class="fixed inset-0 bg-black bg-opacity-60 z-40 md:hidden"
        ></div>

        <!-- SIDEBAR -->
        <aside :class="{
                'translate-x-0 w-full max-w-xs shadow-2xl': isMobileMenuOpen && isMobile,
                '-translate-x-full': !isMobileMenuOpen && isMobile,
                'w-64': !isSidebarHidden && !isMobile,
                'w-20': isSidebarHidden && !isMobile
            }"
            class="bg-white dark:bg-gray-800 border-r border-cyan-200 dark:border-gray-700 
                   flex flex-col fixed inset-y-0 left-0 z-50 md:relative md:z-30 md:translate-x-0
                   transition-all duration-300 ease-in-out"
        >
            <!-- Header Sidebar -->
            <div class="flex items-center justify-between p-4 md:p-5 h-16 border-b border-cyan-100 dark:border-gray-700 bg-gradient-to-r from-cyan-500 to-blue-600 flex-shrink-0">
                <Link v-if="!isSidebarHidden" :href="route('dashboard')" class="flex items-center gap-3 text-white">
                    <ApplicationMark class="h-10 w-10 md:h-12 md:w-12 fill-white" />
                    <span class="font-bold text-lg md:text-xl">AGUA CABEZAS</span>
                </Link>
                <button @click="isSidebarHidden = !isSidebarHidden" class="p-2 rounded-full text-white hover:bg-white/20 transition-colors" :class="!isSidebarHidden ? 'ml-auto' : 'mx-auto'">
                    <svg v-if="!isSidebarHidden" class="h-6 w-6 md:h-7 md:w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    <svg v-else class="h-6 w-6 md:h-7 md:w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>

            <!-- NAVEGACIÓN  -->
             <nav class="flex-1 overflow-y-auto p-4 md:p-5 space-y-3 md:space-y-4 scrollbar-thin scrollbar-thumb-cyan-400">
                <template v-if="$page.props.auth.user.role_names?.includes('Administrador')">
                        <span v-if="!isSidebarHidden" class="px-3 text-xs md:text-sm font-bold uppercase text-cyan-600 dark:text-cyan-400">Administración</span>

                        <NavLink :href="route('dashboard')" :active="route().current('dashboard')" class="group flex items-center text-gray-700 dark:text-gray-300 hover:bg-cyan-50 dark:hover:bg-gray-700 px-3 md:px-4 py-3 md:py-3.5 rounded-xl transition-all duration-200 text-sm md:text-base">
                            <span class="mr-3 md:mr-4 text-cyan-600 dark:text-cyan-400 group-hover:scale-110 transition-transform" title="Dashboard">
                                <svg class="h-5 w-5 md:h-6 md:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6-4a1 1 0 001-1v-1a1 1 0 10-2 0v1a1 1 0 001 1zm5-1a1 1 0 100-2 1 1 0 000 2z" /></svg>
                            </span>
                            <span v-if="!isSidebarHidden" class="font-medium">Dashboard</span>
                        </NavLink>

                        <NavLink href="/users" :active="route().current('users.*')" class="group flex items-center text-gray-700 dark:text-gray-300 hover:bg-cyan-50 dark:hover:bg-gray-700 px-3 md:px-4 py-3 md:py-3.5 rounded-xl transition-all duration-200 text-sm md:text-base">
                            <span class="mr-3 md:mr-4 text-cyan-600 dark:text-cyan-400 group-hover:scale-110 transition-transform" title="Roles y Usuarios">
                                <svg class="h-5 w-5 md:h-6 md:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                            </span>
                            <span v-if="!isSidebarHidden" class="font-medium">Roles y Usuarios</span>
                        </NavLink>

                        <span v-if="!isSidebarHidden" class="px-3 text-xs md:text-sm font-bold uppercase text-cyan-600 dark:text-cyan-400">Gestión</span>

                        <NavLink href="/afiliados" :active="route().current('afiliados.*')" class="group flex items-center text-gray-700 dark:text-gray-300 hover:bg-cyan-50 dark:hover:bg-gray-700 px-3 md:px-4 py-3 md:py-3.5 rounded-xl transition-all duration-200 text-sm md:text-base">
                            <span class="mr-3 md:mr-4 text-cyan-600 dark:text-cyan-400 group-hover:scale-110 transition-transform" title="Afiliados">
                                <svg class="h-5 w-5 md:h-6 md:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.653-.124-1.282-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.653.124-1.282.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                            </span>
                            <span v-if="!isSidebarHidden" class="font-medium">Afiliados</span>
                        </NavLink>

                        <NavLink href="/conexiones" :active="route().current('conexiones.*')" class="group flex items-center text-gray-700 dark:text-gray-300 hover:bg-cyan-50 dark:hover:bg-gray-700 px-3 md:px-4 py-3 md:py-3.5 rounded-xl transition-all duration-200 text-sm md:text-base">
                            <span class="mr-3 md:mr-4 text-cyan-600 dark:text-cyan-400 group-hover:scale-110 transition-transform" title="Conexiones">
                                <svg class="h-5 w-5 md:h-6 md:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            </span>
                            <span v-if="!isSidebarHidden" class="font-medium">Conexiones</span>
                        </NavLink>

                        <NavLink href="/lecturas" :active="route().current('lecturas.*')" class="group flex items-center text-gray-700 dark:text-gray-300 hover:bg-cyan-50 dark:hover:bg-gray-700 px-3 md:px-4 py-3 md:py-3.5 rounded-xl transition-all duration-200 text-sm md:text-base">
                            <span class="mr-3 md:mr-4 text-cyan-600 dark:text-cyan-400 group-hover:scale-110 transition-transform" title="Lecturas">
                                <svg class="h-5 w-5 md:h-6 md:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 0v6m0-6l-6 6m6-6v6m0-6l-6 6" /></svg>
                            </span>
                            <span v-if="!isSidebarHidden" class="font-medium">Lecturas</span>
                        </NavLink>

                        <span v-if="!isSidebarHidden" class="px-3 text-xs md:text-sm font-bold uppercase text-cyan-600 dark:text-cyan-400">Facturación</span>

                        <NavLink href="/facturacion/generar" :active="route().current('facturacion.*')" class="group flex items-center text-gray-700 dark:text-gray-300 hover:bg-cyan-50 dark:hover:bg-gray-700 px-3 md:px-4 py-3 md:py-3.5 rounded-xl transition-all duration-200 text-sm md:text-base">
                            <span class="mr-3 md:mr-4 text-cyan-600 dark:text-cyan-400 group-hover:scale-110 transition-transform" title="Generar Facturas">
                                <svg class="h-5 w-5 md:h-6 md:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                            </span>
                            <span v-if="!isSidebarHidden" class="font-medium">Generar Facturas</span>
                        </NavLink>

                        <NavLink href="/tarifas" :active="route().current('tarifas.*')" class="group flex items-center text-gray-700 dark:text-gray-300 hover:bg-cyan-50 dark:hover:bg-gray-700 px-3 md:px-4 py-3 md:py-3.5 rounded-xl transition-all duration-200 text-sm md:text-base">
                            <span class="mr-3 md:mr-4 text-cyan-600 dark:text-cyan-400 group-hover:scale-110 transition-transform" title="Tarifas">
                                <svg class="h-5 w-5 md:h-6 md:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0c-1.657 0-3-.895-3-2s1.343-2 3-2 3-.895 3-2-1.343-2-3-2m0 8c1.11 0 2.08-.402 2.599-1M12 8V7" /></svg>
                            </span>
                            <span v-if="!isSidebarHidden" class="font-medium">Tarifas</span>
                        </NavLink>

                        <NavLink href="/facturas" :active="route().current('facturas.*')" class="group flex items-center text-gray-700 dark:text-gray-300 hover:bg-cyan-50 dark:hover:bg-gray-700 px-3 md:px-4 py-3 md:py-3.5 rounded-xl transition-all duration-200 text-sm md:text-base">
                            <span class="mr-3 md:mr-4 text-cyan-600 dark:text-cyan-400 group-hover:scale-110 transition-transform" title="Facturas">
                                <svg class="h-5 w-5 md:h-6 md:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            </span>
                            <span v-if="!isSidebarHidden" class="font-medium">Facturas</span>
                        </NavLink>

                        <NavLink href="/pagos" :active="route().current('pagos.*')" class="group flex items-center text-gray-700 dark:text-gray-300 hover:bg-cyan-50 dark:hover:bg-gray-700 px-3 md:px-4 py-3 md:py-3.5 rounded-xl transition-all duration-200 text-sm md:text-base">
                            <span class="mr-3 md:mr-4 text-cyan-600 dark:text-cyan-400 group-hover:scale-110 transition-transform" title="Pagos">
                                <svg class="h-5 w-5 md:h-6 md:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                            </span>
                            <span v-if="!isSidebarHidden" class="font-medium">Pagos</span>
                        </NavLink>

                        <NavLink href="/reportes" :active="route().current('reportes.*')" class="group flex items-center text-gray-700 dark:text-gray-300 hover:bg-cyan-50 dark:hover:bg-gray-700 px-3 md:px-4 py-3 md:py-3.5 rounded-xl transition-all duration-200 text-sm md:text-base">
                            <span class="mr-3 md:mr-4 text-cyan-600 dark:text-cyan-400 group-hover:scale-110 transition-transform" title="Reportes">
                                <svg class="h-5 w-5 md:h-6 md:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                            </span>
                            <span v-if="!isSidebarHidden" class="font-medium">Reportes</span>
                        </NavLink>

                        <NavLink :href="route('reclamos.index')" :active="route().current('reclamos.*')" class="group flex items-center text-gray-700 dark:text-gray-300 hover:bg-cyan-50 dark:hover:bg-gray-700 px-3 md:px-4 py-3 md:py-3.5 rounded-xl transition-all duration-200 text-sm md:text-base">
                            <span class="mr-3 md:mr-4 text-cyan-600 dark:text-cyan-400 group-hover:scale-110 transition-transform" title="Gestión Reclamos">
                                <svg class="h-5 w-5 md:h-6 md:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20" /></svg>
                            </span>
                            <span v-if="!isSidebarHidden" class="font-medium">Gestión Reclamos</span>
                        </NavLink>
                    </template>

                    <!-- ====================== SECRETARIA ====================== -->
                    <template v-else-if="$page.props.auth.user.role_names?.includes('Secretaria')">
                        <span v-if="!isSidebarHidden" class="px-3 text-xs md:text-sm font-bold uppercase text-cyan-600 dark:text-cyan-400">Menú Secretaria</span>

                        <NavLink href="/afiliados" :active="route().current('afiliados.*')" class="group flex items-center text-gray-700 dark:text-gray-300 hover:bg-cyan-50 dark:hover:bg-gray-700 px-3 md:px-4 py-3 md:py-3.5 rounded-xl transition-all duration-200 text-sm md:text-base">
                            <span class="mr-3 md:mr-4 text-cyan-600 dark:text-cyan-400 group-hover:scale-110 transition-transform" title="Afiliados">
                                <svg class="h-5 w-5 md:h-6 md:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.653-.124-1.282-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.653.124-1.282.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                            </span>
                            <span v-if="!isSidebarHidden" class="font-medium">Afiliados</span>
                        </NavLink>

                        <NavLink href="/conexiones" :active="route().current('conexiones.*')" class="group flex items-center text-gray-700 dark:text-gray-300 hover:bg-cyan-50 dark:hover:bg-gray-700 px-3 md:px-4 py-3 md:py-3.5 rounded-xl transition-all duration-200 text-sm md:text-base">
                            <span class="mr-3 md:mr-4 text-cyan-600 dark:text-cyan-400 group-hover:scale-110 transition-transform" title="Conexiones">
                                <svg class="h-5 w-5 md:h-6 md:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            </span>
                            <span v-if="!isSidebarHidden" class="font-medium">Conexiones</span>
                        </NavLink>

                        <NavLink href="/facturacion/generar" :active="route().current('facturacion.*')" class="group flex items-center text-gray-700 dark:text-gray-300 hover:bg-cyan-50 dark:hover:bg-gray-700 px-3 md:px-4 py-3 md:py-3.5 rounded-xl transition-all duration-200 text-sm md:text-base">
                            <span class="mr-3 md:mr-4 text-cyan-600 dark:text-cyan-400 group-hover:scale-110 transition-transform" title="Generar Facturas">
                                <svg class="h-5 w-5 md:h-6 md:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                            </span>
                            <span v-if="!isSidebarHidden" class="font-medium">Generar Facturas</span>
                        </NavLink>

                        <NavLink href="/facturas" :active="route().current('facturas.*')" class="group flex items-center text-gray-700 dark:text-gray-300 hover:bg-cyan-50 dark:hover:bg-gray-700 px-3 md:px-4 py-3 md:py-3.5 rounded-xl transition-all duration-200 text-sm md:text-base">
                            <span class="mr-3 md:mr-4 text-cyan-600 dark:text-cyan-400 group-hover:scale-110 transition-transform" title="Facturas">
                                <svg class="h-5 w-5 md:h-6 md:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            </span>
                            <span v-if="!isSidebarHidden" class="font-medium">Facturas</span>
                        </NavLink>

                        <NavLink href="/pagos" :active="route().current('pagos.*')" class="group flex items-center text-gray-700 dark:text-gray-300 hover:bg-cyan-50 dark:hover:bg-gray-700 px-3 md:px-4 py-3 md:py-3.5 rounded-xl transition-all duration-200 text-sm md:text-base">
                            <span class="mr-3 md:mr-4 text-cyan-600 dark:text-cyan-400 group-hover:scale-110 transition-transform" title="Pagos">
                                <svg class="h-5 w-5 md:h-6 md:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                            </span>
                            <span v-if="!isSidebarHidden" class="font-medium">Pagos</span>
                        </NavLink>

                        <NavLink :href="route('lecturas.index')" :active="route().current('lecturas.*')" class="group flex items-center text-gray-700 dark:text-gray-300 hover:bg-cyan-50 dark:hover:bg-gray-700 px-3 md:px-4 py-3 md:py-3.5 rounded-xl transition-all duration-200 text-sm md:text-base">
                            <span class="mr-3 md:mr-4 text-cyan-600 dark:text-cyan-400 group-hover:scale-110 transition-transform" title="Ver Lecturas">
                                <svg class="h-5 w-5 md:h-6 md:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 0v6m0-6l-6 6m6-6v6m0-6l-6 6" /></svg>
                            </span>
                            <span v-if="!isSidebarHidden" class="font-medium">Ver Lecturas</span>
                        </NavLink>

                        <NavLink :href="route('reclamos.index')" :active="route().current('reclamos.*')" class="group flex items-center text-gray-700 dark:text-gray-300 hover:bg-cyan-50 dark:hover:bg-gray-700 px-3 md:px-4 py-3 md:py-3.5 rounded-xl transition-all duration-200 text-sm md:text-base">
                            <span class="mr-3 md:mr-4 text-cyan-600 dark:text-cyan-400 group-hover:scale-110 transition-transform" title="Gestión Reclamos">
                                <svg class="h-5 w-5 md:h-6 md:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20" /></svg>
                            </span>
                            <span v-if="!isSidebarHidden" class="font-medium">Gestión Reclamos</span>
                        </NavLink>
                    </template>

                    <!-- ====================== TÉCNICO ====================== -->
                    <template v-else-if="$page.props.auth.user.role_names?.includes('Tecnico')">
                        <span v-if="!isSidebarHidden" class="px-3 text-xs md:text-sm font-bold uppercase text-cyan-600 dark:text-cyan-400">Operaciones</span>

                        <NavLink href="/lecturas" :active="route().current('lecturas.*')" class="group flex items-center text-gray-700 dark:text-gray-300 hover:bg-cyan-50 dark:hover:bg-gray-700 px-3 md:px-4 py-3 md:py-3.5 rounded-xl transition-all duration-200 text-sm md:text-base">
                            <span class="mr-3 md:mr-4 text-cyan-600 dark:text-cyan-400 group-hover:scale-110 transition-transform" title="Lecturas">
                                <svg class="h-5 w-5 md:h-6 md:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 0v6m0-6l-6 6m6- a6v6m0-6l-6 6" /></svg>
                            </span>
                            <span v-if="!isSidebarHidden" class="font-medium">Lecturas</span>
                        </NavLink>
                    </template>

                    <!-- ====================== USUARIO ====================== -->
                    <template v-else-if="$page.props.auth.user.role_names?.includes('Usuario')">
                        <template v-if="$page.props.auth.user.afiliado_id">
                            <span v-if="!isSidebarHidden" class="px-3 text-xs md:text-sm font-bold uppercase text-cyan-600 dark:text-cyan-400">Mi Cuenta</span>

                            <NavLink :href="route('usuario.dashboard')" :active="route().current('usuario.dashboard')" class="group flex items-center text-gray-700 dark:text-gray-300 hover:bg-cyan-50 dark:hover:bg-gray-700 px-3 md:px-4 py-3 md:py-3.5 rounded-xl transition-all duration-200 text-sm md:text-base">
                                <span class="mr-3 md:mr-4 text-cyan-600 dark:text-cyan-400 group-hover:scale-110 transition-transform" title="Mi Resumen">
                                    <svg class="h-5 w-5 md:h-6 md:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6-4a1 1 0 001-1v-1a1 1 0 10-2 0v1a1 1 0 001 1zm5-1a1 1 0 100-2 1 1 0 000 2z" /></svg>
                                </span>
                                <span v-if="!isSidebarHidden" class="font-medium">Mi Resumen</span>
                            </NavLink>

                            <NavLink :href="route('mi.cuenta')" :active="route().current('mi.cuenta')" class="group flex items-center text-gray-700 dark:text-gray-300 hover:bg-cyan-50 dark:hover:bg-gray-700 px-3 md:px-4 py-3 md:py-3.5 rounded-xl transition-all duration-200 text-sm md:text-base">
                                <span class="mr-3 md:mr-4 text-cyan-600 dark:text-cyan-400 group-hover:scale-110 transition-transform" title="Mis Facturas">
                                    <svg class="h-5 w-5 md:h-6 md:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                </span>
                                <span v-if="!isSidebarHidden" class="font-medium">Mis Facturas</span>
                            </NavLink>

                            <NavLink :href="route('pagos.mihistorial')" :active="route().current('pagos.mihistorial')" class="group flex items-center text-gray-700 dark:text-gray-300 hover:bg-cyan-50 dark:hover:bg-gray-700 px-3 md:px-4 py-3 md:py-3.5 rounded-xl transition-all duration-200 text-sm md:text-base">
                                <span class="mr-3 md:mr-4 text-cyan-600 dark:text-cyan-400 group-hover:scale-110 transition-transform" title="Historial de Pagos">
                                    <svg class="h-5 w-5 md:h-6 md:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                                </span>
                                <span v-if="!isSidebarHidden" class="font-medium">Historial de Pagos</span>
                            </NavLink>

                            <NavLink :href="route('reclamos.usuarioIndex')" :active="route().current('reclamos.usuarioIndex*') || route().current('reclamos.create')" class="group flex items-center text-gray-700 dark:text-gray-300 hover:bg-cyan-50 dark:hover:bg-gray-700 px-3 md:px-4 py-3 md:py-3.5 rounded-xl transition-all duration-200 text-sm md:text-base">
                                <span class="mr-3 md:mr-4 text-cyan-600 dark:text-cyan-400 group-hover:scale-110 transition-transform" title="Mis Reclamos">
                                    <svg class="h-5 w-5 md:h-6 md:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20" /></svg>
                                </span>
                                <span v-if="!isSidebarHidden" class="font-medium">Mis Reclamos</span>
                            </NavLink>
                        </template>    
                    </template>          
            </nav>
        </aside>

        <!-- CONTENIDO PRINCIPAL -->
            <div class="flex-1 flex flex-col overflow-hidden">

                <!-- NAVBAR SUPERIOR -->
                <nav class="bg-white dark:bg-gray-800 border-b border-cyan-100 dark:border-gray-700 shadow-lg fixed top-0 inset-x-0 z-20 h-16 backdrop-blur-sm bg-opacity-90">
                    <div class="px-4 md:px-6 h-full flex items-center justify-between text-sm md:text-base">

                        <!-- BOTÓN MENÚ MÓVIL -->
                        <button @click="isMobileMenuOpen = !isMobileMenuOpen" class="p-2 md:p-3 rounded-xl text-cyan-600 dark:text-cyan-400 hover:bg-cyan-50 dark:hover:bg-gray-700 transition-all md:hidden">
                            <svg v-if="!isMobileMenuOpen" class="h-6 w-6 md:h-7 md:w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <svg v-else class="h-6 w-6 md:h-7 md:w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>


<div class="flex-1 flex items-center justify-between px-4 md:px-6 relative z-50">

    <div
        class="relative max-w-sm w-full transition-all duration-300"
        :class="{
            'ml-20 md:ml-64': !isSidebarHidden,
            'ml-20': isSidebarHidden
        }"
    >
        <input
            v-model="textoBusqueda"
            @input="buscar"
            @keydown.enter.prevent="irAlPrimero"
            type="text"
            placeholder="Buscar afiliado, medidor, factura..."
            class="w-full pl-10 pr-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-shadow shadow-sm backdrop-blur-sm"
        />

        <svg
            class="absolute left-3 top-2.5 h-5 w-5 text-gray-500 pointer-events-none"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
            />
        </svg>

        <!-- Dropdown -->
        <div
            v-if="textoBusqueda.length >= 2"
            class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-800 rounded-lg shadow-2xl border border-gray-200 dark:border-gray-700 z-50 max-h-64 overflow-y-auto"
        >
            <!-- Estados -->
            <div v-if="buscadorCargando" class="px-4 py-2 text-xs text-gray-500">
                Buscando...
            </div>

            <div
                v-else-if="buscadorError && !resultados.length"
                class="px-4 py-2 text-xs text-red-500"
            >
                {{ buscadorError }}
            </div>

            <!-- Resultados -->
            <template v-else>
                <button
                    v-for="(r, i) in resultados"
                    :key="i"
                    @click="irA(r.url)"
                    class="w-full text-left px-4 py-2.5 hover:bg-cyan-50 dark:hover:bg-gray-700 text-sm flex items-center gap-3 border-b border-gray-100 dark:border-gray-700 last:border-0"
                >
                    <span class="text-xl">{{ r.icon }}</span>
                    <div>
                        <div class="font-medium">{{ r.title }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ r.subtitle }}
                        </div>
                    </div>
                </button>
            </template>
        </div>
    </div>

    <div class="hidden md:block text-right pr-4">
        <div class="font-bold text-cyan-700 dark:text-cyan-300 text-sm">
            Asociación de Beneficiarios de Agua Cabezas
        </div>
        <div class="text-xs text-cyan-600 dark:text-cyan-400 mt-1">
            {{ currentTime }}
        </div>
    </div>
</div>



                                    <!-- DROPDOWN USUARIO -->
                    <Dropdown align="right" width="64">
                        <template #trigger>
                            <button class="flex items-center text-sm md:text-base font-medium text-gray-700 dark:text-gray-300 hover:text-cyan-600 dark:hover:text-cyan-400 transition">
                                <img class="h-9 w-9 md:h-10 md:w-10 rounded-full object-cover ring-2 ring-cyan-500 ring-offset-2" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name" />
                                <span class="ml-2 hidden md:inline">{{ $page.props.auth.user.name }}</span>
                                <svg class="ml-1 h-4 w-4 md:h-5 md:w-5 hidden md:inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </template>

                        <template #content>
                            <div class="px-4 py-2 text-xs md:text-sm font-semibold text-cyan-600 dark:text-cyan-400">Mi Cuenta</div>
                            <DropdownLink :href="route('profile.show')">Mi Perfil</DropdownLink>
                            <div class="border-t border-cyan-100 dark:border-gray-700"></div>

                            <!-- MODO OSCURO -->
                            <div class="flex items-center justify-between px-4 py-2 text-sm md:text-base">
                                <span class="text-gray-700 dark:text-gray-300">Modo Oscuro</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" :checked="darkMode" @change="toggleDarkMode" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-300 peer-focus:ring-4 peer-focus:ring-cyan-300 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-600"></div>
                                </label>
                            </div>

                            <!-- BOTONES DE TEMA (CHICOS Y SIMPLES) -->
                            <div class="border-t border-cyan-100 dark:border-gray-700 py-2">
                                <div class="px-4 text-xs font-bold text-cyan-600 dark:text-cyan-400">Estilo visual</div>
                                <div class="flex justify-center gap-4 px-4 py-3">
                                    <button @click="cambiarTema('tema-jovenes')" :class="temaActual === 'tema-jovenes' ? 'ring-4 ring-cyan-500' : ''" class="w-10 h-10 rounded-xl bg-cyan-500 hover:shadow-lg transition"></button>
                                    <button @click="cambiarTema('tema-ninos')" :class="temaActual === 'tema-ninos' ? 'ring-4 ring-orange-500' : ''" class="w-10 h-10 rounded-xl bg-gradient-to-br from-sky-400 to-orange-400 hover:shadow-lg transition"></button>
                                    <button @click="cambiarTema('tema-adultos')" :class="temaActual === 'tema-adultos' ? 'ring-4 ring-indigo-600' : ''" class="w-10 h-10 rounded-xl bg-indigo-700 hover:shadow-lg transition"></button>
                                </div>
                            </div>

                            <div class="border-t border-cyan-100 dark:border-gray-700"></div>
                            <form @submit.prevent="logout">
                                <DropdownLink as="button" class="w-full text-left text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                                    Cerrar Sesión
                                </DropdownLink>
                            </form>
                        </template>
                    </Dropdown>
                </div>
            </nav>

            <!-- Espacio para navbar fija -->
            <div class="h-16 md:h-18"></div>

            <!-- Header opcional -->
            <header v-if="$slots.header" class="bg-white dark:bg-gray-800 shadow-sm border-b border-cyan-100 dark:border-gray-700">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Main content -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-gradient-to-b from-transparent to-white dark:to-gray-900">
                <slot />
            </main>
        </div>
    </div>
</div>
</template>

<style scoped>
.scrollbar-thin::-webkit-scrollbar { width: 6px; }
.scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
.scrollbar-thin::-webkit-scrollbar-thumb { background-color: hsl(184, 100%, 50%, 0.6); border-radius: 3px; }
</style>