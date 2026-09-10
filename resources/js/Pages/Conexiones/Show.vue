<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue'; 
import ViewCounter from '@/Components/ViewCounter.vue';

const props = defineProps({
    conexion: Object, // Recibe la conexión con afiliado, zona y lecturas
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
        case 'activo': return 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300';
        case 'suspendido': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300';
        case 'eliminado': return 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
};
</script>

<template>
    <AppLayout :title="'Medidor ' + conexion.codigo_medidor">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Detalles de la Conexión / Medidor
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 md:p-8">

                    <!-- Información Conexión & Afiliado -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
                                Información de la Conexión
                            </h3>
                            <dl class="space-y-3 text-sm">
                                <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-700/50">
                                    <dt class="font-medium text-gray-500 dark:text-gray-400">Código del Medidor:</dt>
                                    <dd class="font-bold text-gray-900 dark:text-gray-100">{{ conexion.codigo_medidor }}</dd>
                                </div>
                                <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-700/50">
                                    <dt class="font-medium text-gray-500 dark:text-gray-400">Dirección:</dt>
                                    <dd class="text-gray-900 dark:text-gray-100 text-right">{{ conexion.direccion }}</dd>
                                </div>
                                <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-700/50">
                                    <dt class="font-medium text-gray-500 dark:text-gray-400">Zona:</dt>
                                    <dd class="text-gray-900 dark:text-gray-100">{{ conexion.zona?.nombre || 'N/A' }}</dd>
                                </div>
                                <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-700/50">
                                    <dt class="font-medium text-gray-500 dark:text-gray-400">Estado:</dt>
                                    <dd>
                                        <span :class="estadoClass(conexion.estado)" class="px-2.5 py-0.5 text-xs font-semibold rounded-full">
                                            {{ conexion.estado }}
                                        </span>
                                    </dd>
                                </div>
                                <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-700/50">
                                    <dt class="font-medium text-gray-500 dark:text-gray-400">Tipo de Conexión:</dt>
                                    <dd class="text-gray-900 dark:text-gray-100 capitalize">{{ conexion.tipo_conexion }}</dd>
                                </div>
                                <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-700/50">
                                    <dt class="font-medium text-gray-500 dark:text-gray-400">Lectura Inicial Instalación:</dt>
                                    <dd class="font-semibold text-blue-600 dark:text-blue-400">{{ conexion.lectura_inicial ?? 0 }} m³</dd>
                                </div>
                                <div class="flex justify-between py-1">
                                    <dt class="font-medium text-gray-500 dark:text-gray-400">Fecha de Instalación:</dt>
                                    <dd class="text-gray-900 dark:text-gray-100">{{ formatDate(conexion.fecha_instalacion) }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
                                Afiliado Asignado
                            </h3>
                            <dl v-if="conexion.afiliado" class="space-y-3 text-sm">
                                <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-700/50">
                                    <dt class="font-medium text-gray-500 dark:text-gray-400">Nombre:</dt>
                                    <dd class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                        <Link :href="route('afiliados.show', conexion.afiliado.id)">{{ conexion.afiliado.nombre_completo }}</Link>
                                    </dd>
                                </div>
                                <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-700/50">
                                    <dt class="font-medium text-gray-500 dark:text-gray-400">CI:</dt>
                                    <dd class="text-gray-900 dark:text-gray-100">{{ conexion.afiliado.ci || '—' }}</dd>
                                </div>
                                <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-700/50">
                                    <dt class="font-medium text-gray-500 dark:text-gray-400">Teléfono:</dt>
                                    <dd class="text-gray-900 dark:text-gray-100">{{ conexion.afiliado.celular || '—' }}</dd>
                                </div>
                                <div class="flex justify-between py-1">
                                    <dt class="font-medium text-gray-500 dark:text-gray-400">Dirección Afiliado:</dt>
                                    <dd class="text-gray-900 dark:text-gray-100 text-right">{{ conexion.afiliado.direccion || '—' }}</dd>
                                </div>
                            </dl>
                            <p v-else class="text-sm text-gray-500 dark:text-gray-400 italic">No hay afiliado asignado a este medidor.</p>
                        </div>
                    </div>
                    
                    <!-- Últimas Lecturas -->
                    <div class="mb-8">
                         <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">
                             Últimas Lecturas
                         </h4>
                         <ul v-if="conexion.lecturas && conexion.lecturas.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700 border rounded-md dark:border-gray-700">
                            <li v-for="lec in conexion.lecturas" :key="lec.id" class="p-3 flex justify-between items-center text-sm hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">Período: {{ lec.periodo }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Fecha: {{ formatDate(lec.fecha_lectura) }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-700 dark:text-gray-300">Ant: {{ lec.lectura_anterior }} | Act: {{ lec.lectura_actual }}</p>
                                    <p class="font-bold text-blue-700 dark:text-blue-400">Consumo: {{ (lec.lectura_actual - lec.lectura_anterior).toFixed(2) }} m³</p>
                                </div>
                                <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full" 
                                      :class="lec.estado === 'pendiente' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300' : 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'">
                                    {{ lec.estado }}
                                </span>
                            </li>
                         </ul>
                         <p v-else class="text-sm text-gray-500 dark:text-gray-400 italic">No hay lecturas registradas para esta conexión.</p>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="flex justify-between gap-3 mt-8 border-t border-gray-200 dark:border-gray-700 pt-6">
                        <Link :href="route('conexiones.index')" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded transition shadow">
                            Volver al Listado
                        </Link>
                        <Link :href="route('conexiones.edit', conexion.id)" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded transition shadow">
                            Editar Conexión
                        </Link>
                    </div>
                </div>
            </div>
            <ViewCounter />
        </div>
    </AppLayout>
</template>