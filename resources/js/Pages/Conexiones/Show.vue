<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue'; 
import ViewCounter from '@/Components/ViewCounter.vue';

const props = defineProps({
    conexion: Object,
});

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        date.setMinutes(date.getMinutes() + date.getTimezoneOffset()); 
        return date.toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' });
    } catch { return dateString; }
};

const estadoClass = (estado) => {
    switch (estado) {
        case 'activo': return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700';
        case 'suspendido': return 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 border border-amber-200 dark:border-amber-700';
        case 'eliminado': return 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300 border border-rose-200 dark:border-rose-700';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300';
    }
};
</script>

<template>
    <AppLayout :title="'Medidor ' + conexion.codigo_medidor">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight flex items-center gap-3">
                        <span class="p-2 bg-gradient-to-r from-cyan-500 to-blue-600 rounded-xl text-white shadow-md">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </span>
                        Detalle del Medidor / Conexión
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Información técnica, ubicación, afiliado titular e historial de lecturas.</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="route('conexiones.index')" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 font-medium text-sm transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Volver a Conexiones
                    </Link>
                    <Link :href="route('conexiones.edit', conexion.id)" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-700 hover:to-blue-700 text-white rounded-xl font-medium text-sm transition shadow-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Editar Conexión
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Tarjeta Principal del Medidor -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="bg-gradient-to-r from-slate-900 via-cyan-950 to-slate-900 p-6 md:p-8 text-white flex flex-col md:flex-row items-center justify-between gap-6">
                        <div>
                            <div class="text-xs uppercase tracking-widest text-cyan-400 font-bold mb-1">Código de Medidor de Agua</div>
                            <div class="text-3xl md:text-4xl font-mono font-black tracking-tight text-white flex items-center gap-3">
                                {{ conexion.codigo_medidor }}
                                <span :class="estadoClass(conexion.estado)" class="text-xs font-sans px-3 py-1 rounded-full font-bold uppercase tracking-wider">
                                    {{ conexion.estado }}
                                </span>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-3 text-sm">
                            <div class="bg-white/10 backdrop-blur-md px-4 py-2.5 rounded-xl border border-white/10 text-center">
                                <div class="text-xs text-gray-300 uppercase font-semibold">Tipo</div>
                                <div class="font-bold capitalize text-white">{{ conexion.tipo_conexion }}</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-md px-4 py-2.5 rounded-xl border border-white/10 text-center">
                                <div class="text-xs text-gray-300 uppercase font-semibold">Zona</div>
                                <div class="font-bold text-cyan-300">{{ conexion.zona?.nombre || 'N/A' }}</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-md px-4 py-2.5 rounded-xl border border-white/10 text-center">
                                <div class="text-xs text-gray-300 uppercase font-semibold">Instalado</div>
                                <div class="font-bold text-white">{{ formatDate(conexion.fecha_instalacion) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cuadrícula: Ficha Técnica + Afiliado Titular -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <!-- Ficha Técnica de Instalación -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-3 pb-4 mb-4 border-b border-gray-100 dark:border-gray-700">
                            <div class="p-2.5 bg-cyan-50 dark:bg-cyan-900/40 text-cyan-600 dark:text-cyan-400 rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white">Ubicación e Instalación</h3>
                        </div>
                        <dl class="space-y-3 text-sm">
                            <div class="flex justify-between py-1 border-b border-gray-50 dark:border-gray-700/50">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium">Dirección de Instalación:</dt>
                                <dd class="font-bold text-gray-900 dark:text-gray-100 text-right">{{ conexion.direccion }}</dd>
                            </div>
                            <div class="flex justify-between py-1 border-b border-gray-50 dark:border-gray-700/50">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium">Barrio / Zona:</dt>
                                <dd class="font-bold text-cyan-600 dark:text-cyan-400">{{ conexion.zona?.nombre || 'N/A' }}</dd>
                            </div>
                            <div class="flex justify-between py-1 border-b border-gray-50 dark:border-gray-700/50">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium">Tipo de Servicio:</dt>
                                <dd class="font-bold text-gray-900 dark:text-gray-100 capitalize">{{ conexion.tipo_conexion }}</dd>
                            </div>
                            <div class="flex justify-between py-1">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium">Fecha Instalación:</dt>
                                <dd class="font-bold text-gray-900 dark:text-gray-100">{{ formatDate(conexion.fecha_instalacion) }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Afiliado Titular -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-3 pb-4 mb-4 border-b border-gray-100 dark:border-gray-700">
                            <div class="p-2.5 bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white">Afiliado Titular</h3>
                        </div>

                        <div v-if="conexion.afiliado" class="space-y-4">
                            <div class="flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600">
                                <div class="w-12 h-12 rounded-full bg-cyan-600 text-white font-bold text-lg flex items-center justify-center">
                                    {{ conexion.afiliado.nombre_completo.substring(0,2).toUpperCase() }}
                                </div>
                                <div class="flex-1">
                                    <div class="font-bold text-gray-900 dark:text-white">{{ conexion.afiliado.nombre_completo }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">CI: {{ conexion.afiliado.ci }} | Código: {{ conexion.afiliado.codigo }}</div>
                                </div>
                            </div>

                            <dl class="space-y-2 text-sm">
                                <div class="flex justify-between py-1 border-b border-gray-50 dark:border-gray-700/50">
                                    <dt class="text-gray-500 dark:text-gray-400 font-medium">Celular:</dt>
                                    <dd class="font-bold text-gray-900 dark:text-gray-100">{{ conexion.afiliado.celular || '—' }}</dd>
                                </div>
                                <div class="flex justify-between py-1">
                                    <dt class="text-gray-500 dark:text-gray-400 font-medium">Dirección Afiliado:</dt>
                                    <dd class="font-bold text-gray-900 dark:text-gray-100 text-right">{{ conexion.afiliado.direccion || '—' }}</dd>
                                </div>
                            </dl>

                            <Link :href="route('afiliados.show', conexion.afiliado.id)" class="block text-center py-2 bg-cyan-50 dark:bg-cyan-900/30 hover:bg-cyan-100 text-cyan-700 dark:text-cyan-300 font-bold rounded-xl text-xs transition">
                                Ver Perfil Completo del Afiliado &rarr;
                            </Link>
                        </div>
                        <p v-else class="text-sm text-gray-400 italic">No hay ningún afiliado asociado a esta conexión.</p>
                    </div>

                </div>

                <!-- Historial de Últimas Lecturas -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-lg text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            Últimas Lecturas Registradas
                        </h3>
                        <Link :href="route('lecturas.create')" class="px-3 py-1.5 bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 hover:bg-blue-100 rounded-xl text-xs font-bold transition flex items-center gap-1">
                            + Nueva Lectura
                        </Link>
                    </div>

                    <div v-if="conexion.lecturas && conexion.lecturas.length > 0" class="space-y-3">
                        <div v-for="lec in conexion.lecturas" :key="lec.id" class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                            <div>
                                <div class="font-bold text-gray-900 dark:text-white text-base">Período: {{ lec.periodo }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Fecha de lectura: {{ formatDate(lec.fecha_lectura) }}</div>
                            </div>
                            
                            <div class="flex items-center gap-4 text-sm">
                                <div>
                                    <span class="text-xs text-gray-400 block">Lecturas</span>
                                    <span class="font-mono text-gray-700 dark:text-gray-300">{{ lec.lectura_anterior }} m³ &rarr; {{ lec.lectura_actual }} m³</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs text-gray-400 block">Consumo</span>
                                    <span class="font-bold font-mono text-lg text-cyan-600 dark:text-cyan-400">{{ (lec.lectura_actual - lec.lectura_anterior).toFixed(2) }} m³</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <span :class="lec.estado === 'pendiente' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300'" class="px-2.5 py-1 text-xs font-bold rounded-lg uppercase">
                                    {{ lec.estado }}
                                </span>
                                <Link :href="route('lecturas.show', lec.id)" class="px-3 py-1.5 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-xs font-semibold hover:bg-gray-300 transition">
                                    Ver Lectura &rarr;
                                </Link>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400 italic">No existen lecturas registradas para esta conexión.</p>
                </div>

            </div>
        </div>

        <ViewCounter />
    </AppLayout>
</template>