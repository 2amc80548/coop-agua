<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ViewCounter from '@/Components/ViewCounter.vue';
import { ref } from 'vue';

const props = defineProps({
    afiliado: Object,
});

const getPhotoUrl = (path) => {
    if (path) {
        const baseUrl = window.location.origin + window.location.pathname.split('/afiliados')[0];
        return `${baseUrl}/storage/${path}`;
    }
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(props.afiliado.nombre_completo)}&color=0284C7&background=E0F2FE&size=200`;
};

const estadoClass = (estado) => {
    switch (estado) {
        case 'activo': return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700';
        case 'suspendido': return 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 border border-amber-200 dark:border-amber-700';
        case 'baja': return 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300 border border-rose-200 dark:border-rose-700';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300';
    }
};

const estadoServicioClass = (estado) => {
    switch (estado) {
        case 'activo': return 'bg-teal-100 text-teal-800 dark:bg-teal-900/50 dark:text-teal-300 border border-teal-200 dark:border-teal-700';
        case 'en_corte': return 'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300 border border-orange-200 dark:border-orange-700';
        case 'cortado': return 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300 border border-red-200 dark:border-red-700';
        case 'Pendiente': return 'bg-sky-100 text-sky-800 dark:bg-sky-900/50 dark:text-sky-300 border border-sky-200 dark:border-sky-700';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300';
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '—';
    try {
        const date = new Date(dateString);
        date.setMinutes(date.getMinutes() + date.getTimezoneOffset());
        return date.toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' });
    } catch { return dateString; }
};

const showPhotoModal = ref(false);

const toggleModal = () => {
    showPhotoModal.value = !showPhotoModal.value;
};

const downloadImage = (url, name) => {
    const link = document.createElement('a');
    link.href = url;
    link.download = `foto-${name}.jpg`;
    link.click();
};
</script>

<template>
    <AppLayout :title="afiliado.nombre_completo">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight flex items-center gap-3">
                        <span class="p-2 bg-gradient-to-r from-cyan-500 to-blue-600 rounded-xl text-white shadow-md">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </span>
                        Perfil del Afiliado
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Detalles completos del afiliado, estado de cuenta y conexiones asignadas.</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="route('afiliados.index')" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 font-medium text-sm transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Volver al Listado
                    </Link>
                    <Link :href="route('afiliados.edit', afiliado.id)" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-700 hover:to-blue-700 text-white rounded-xl font-medium text-sm transition shadow-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Editar Afiliado
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Tarjeta de Cabecera Ejecutiva -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700 relative">
                    <div class="h-32 bg-gradient-to-r from-cyan-600 via-blue-600 to-indigo-700 opacity-90"></div>
                    <div class="px-6 md:px-8 pb-6 pt-0 relative flex flex-col md:flex-row items-center md:items-end justify-between gap-6 -mt-16">
                        
                        <div class="flex flex-col md:flex-row items-center md:items-end gap-6 text-center md:text-left">
                            <div class="relative group cursor-pointer" @click="showPhotoModal = true" title="Ver fotografía en tamaño completo">
                                <img 
                                    class="h-28 w-28 md:h-32 md:w-32 rounded-2xl object-cover ring-4 ring-white dark:ring-gray-800 shadow-2xl transition-transform duration-200 group-hover:scale-105" 
                                    :src="getPhotoUrl(afiliado.profile_photo_path)" 
                                    :alt="afiliado.nombre_completo"
                                >
                                <div class="absolute inset-0 flex items-center justify-center rounded-2xl bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                                </div>
                            </div>
                            <div>
                                <h1 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white tracking-tight">{{ afiliado.nombre_completo }}</h1>
                                <div class="flex flex-wrap items-center justify-center md:justify-start gap-2 mt-2">
                                    <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg text-xs font-mono font-bold">CI: {{ afiliado.ci }}</span>
                                    <span class="px-3 py-1 bg-cyan-50 dark:bg-cyan-900/40 text-cyan-700 dark:text-cyan-300 rounded-lg text-xs font-mono font-bold">CÓDIGO: {{ afiliado.codigo }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-center gap-2">
                            <span :class="estadoClass(afiliado.estado)" class="px-3 py-1.5 text-xs font-bold rounded-xl uppercase tracking-wider">
                                Afiliación: {{ afiliado.estado }}
                            </span>
                            <span :class="estadoServicioClass(afiliado.estado_servicio)" class="px-3 py-1.5 text-xs font-bold rounded-xl uppercase tracking-wider">
                                Servicio: {{ afiliado.estado_servicio }}
                            </span>
                            <span v-if="afiliado.adulto_mayor" class="px-3 py-1.5 text-xs font-bold rounded-xl bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300 border border-purple-200 dark:border-purple-700 uppercase tracking-wider flex items-center gap-1">
                                🌟 Adulto Mayor
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Cuadrícula de Información -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <!-- Ficha 1: Información de Contacto -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-3 pb-4 mb-4 border-b border-gray-100 dark:border-gray-700">
                            <div class="p-2.5 bg-cyan-50 dark:bg-cyan-900/40 text-cyan-600 dark:text-cyan-400 rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </div>
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white">Datos de Contacto y Ubicación</h3>
                        </div>
                        <dl class="space-y-3 text-sm">
                            <div class="flex justify-between py-1 border-b border-gray-50 dark:border-gray-700/50">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium">Celular / Teléfono:</dt>
                                <dd class="font-bold text-gray-900 dark:text-gray-100">{{ afiliado.celular || 'No registrado' }}</dd>
                            </div>
                            <div class="flex justify-between py-1 border-b border-gray-50 dark:border-gray-700/50">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium">Dirección / Calle:</dt>
                                <dd class="font-bold text-gray-900 dark:text-gray-100 text-right">{{ afiliado.direccion || '—' }}</dd>
                            </div>
                            <div class="flex justify-between py-1 border-b border-gray-50 dark:border-gray-700/50">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium">Barrio / Zona:</dt>
                                <dd class="font-bold text-cyan-600 dark:text-cyan-400">{{ afiliado.zona?.nombre || 'No asignada' }}</dd>
                            </div>
                            <div v-if="afiliado.user" class="flex justify-between py-1">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium">Cuenta de Usuario:</dt>
                                <dd class="font-bold text-gray-900 dark:text-gray-100">{{ afiliado.user.email }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Ficha 2: Datos de Afiliación -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-3 pb-4 mb-4 border-b border-gray-100 dark:border-gray-700">
                            <div class="p-2.5 bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white">Datos de Afiliación</h3>
                        </div>
                        <dl class="space-y-3 text-sm">
                            <div class="flex justify-between py-1 border-b border-gray-50 dark:border-gray-700/50">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium">Tipo de Afiliado:</dt>
                                <dd class="font-bold text-gray-900 dark:text-gray-100 capitalize">{{ afiliado.tipo }}</dd>
                            </div>
                            <div class="flex justify-between py-1 border-b border-gray-50 dark:border-gray-700/50">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium">Tenencia de Propiedad:</dt>
                                <dd class="font-bold text-gray-900 dark:text-gray-100 capitalize">{{ afiliado.tenencia }}</dd>
                            </div>
                            <div class="flex justify-between py-1 border-b border-gray-50 dark:border-gray-700/50">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium">Fecha de Registro:</dt>
                                <dd class="font-bold text-gray-900 dark:text-gray-100">{{ formatDate(afiliado.fecha_afiliacion) }}</dd>
                            </div>
                            <div v-if="afiliado.fecha_baja" class="flex justify-between py-1">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium">Fecha de Baja:</dt>
                                <dd class="font-bold text-red-500">{{ formatDate(afiliado.fecha_baja) }}</dd>
                            </div>
                        </dl>
                    </div>

                </div>

                <!-- Observaciones -->
                <div v-if="afiliado.observacion" class="bg-amber-50 dark:bg-amber-900/20 rounded-2xl p-6 border border-amber-200 dark:border-amber-700/40">
                    <h4 class="font-bold text-amber-900 dark:text-amber-300 text-sm uppercase tracking-wide flex items-center gap-2 mb-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Observaciones Internas
                    </h4>
                    <p class="text-sm text-amber-900/80 dark:text-amber-200 whitespace-pre-wrap font-medium">{{ afiliado.observacion }}</p>
                </div>

                <!-- Requisitos Presentados -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                    <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Requisitos Entregados
                    </h3>
                    <ul v-if="afiliado.requisitos && afiliado.requisitos.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                        <li v-for="req in afiliado.requisitos" :key="req.id" class="p-3 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/50 rounded-xl flex items-center justify-between">
                            <span class="font-semibold text-emerald-900 dark:text-emerald-300 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                {{ req.nombre }}
                            </span>
                            <span v-if="req.pivot.fecha_entrega" class="text-xs text-emerald-700 dark:text-emerald-400 font-mono">{{ formatDate(req.pivot.fecha_entrega) }}</span>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-gray-400 dark:text-gray-500 italic">No hay requisitos registrados para este afiliado.</p>
                </div>

                <!-- Conexiones (Medidores) -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-lg text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Conexiones de Agua (Medidores)
                        </h3>
                        <Link :href="route('conexiones.create')" class="px-3 py-1.5 bg-cyan-50 dark:bg-cyan-900/40 text-cyan-700 dark:text-cyan-300 hover:bg-cyan-100 rounded-xl text-xs font-bold transition flex items-center gap-1">
                            + Nueva Conexión
                        </Link>
                    </div>

                    <div v-if="afiliado.conexiones && afiliado.conexiones.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="con in afiliado.conexiones" :key="con.id" class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600 flex items-center justify-between shadow-sm hover:shadow-md transition">
                            <div>
                                <Link :href="route('conexiones.show', con.id)" class="font-mono font-bold text-cyan-600 dark:text-cyan-400 hover:underline text-base flex items-center gap-1">
                                    Medidor: {{ con.codigo_medidor }}
                                </Link>
                                <div class="text-xs text-gray-600 dark:text-gray-300 mt-1">{{ con.direccion }}</div>
                                <div class="text-xs text-gray-400 dark:text-gray-400 mt-0.5 font-medium">Zona: {{ con.zona?.nombre || 'N/A' }}</div>
                            </div>
                            <span :class="con.estado === 'activo' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300'" class="px-2.5 py-1 text-xs font-bold rounded-lg uppercase">
                                {{ con.estado }}
                            </span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400 dark:text-gray-500 italic">Este afiliado no posee conexiones asociadas.</p>
                </div>

            </div>
        </div>

        <!-- Modal de Fotografía -->
        <div v-if="showPhotoModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/90 backdrop-blur-md" @click.self="toggleModal">
            <div class="relative max-w-4xl w-full flex flex-col items-center">
                <div class="absolute -top-12 right-0 flex gap-3">
                    <button @click="downloadImage(getPhotoUrl(afiliado.profile_photo_path), afiliado.nombre_completo)" class="text-white hover:text-cyan-400 flex items-center gap-2 bg-gray-800/80 px-3 py-1.5 rounded-xl text-sm transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Descargar
                    </button>
                    <button @click="toggleModal" class="text-white hover:text-rose-400 transition">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <img :src="getPhotoUrl(afiliado.profile_photo_path)" class="max-h-[80vh] max-w-full rounded-2xl shadow-2xl object-contain border-4 border-white/10" :alt="afiliado.nombre_completo">
                <p class="text-white mt-4 font-bold text-lg">{{ afiliado.nombre_completo }}</p>
            </div>
        </div>

        <ViewCounter />
    </AppLayout>
</template>