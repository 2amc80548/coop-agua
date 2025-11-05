<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

// --- 1. PROPS ---
const props = defineProps({
    summary: Object, // El objeto con todos los datos
    error: String,   // El mensaje de error
});

// --- 2. HELPERS VISUALES (Funciones de ayuda) ---
const formatCurrency = (amount) => {
    return (parseFloat(amount) || 0).toFixed(2);
};

const estadoServicioClass = computed(() => {
    if (!props.summary) return 'bg-gray-100 text-gray-800';
    switch (props.summary.estado_servicio) {
        case 'activo': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        case 'en_corte': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
        case 'cortado': return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
});

const estadoServicioTexto = computed(() => {
    if (!props.summary) return 'Desconocido';
    switch (props.summary.estado_servicio) {
        case 'activo': return 'Servicio Activo';
        case 'en_corte': return 'Servicio en Proceso de Corte';
        case 'cortado': return 'Servicio Cortado';
        default: return 'Desconocido';
    }
});
</script>

<template>
    <AppLayout title="Mi Resumen">
        <Head title="Mi Resumen" />

        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Bienvenido, {{ summary?.afiliado_nombre || 'Usuario' }}
            </h2>
        </template>

        <div class="py-6 md:py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div v-if="error" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p class="font-bold">Error de Vinculación</p>
                    <p>{{ error }}</p>
                </div>

                <div v-if="summary" class="space-y-6">
                    
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                        <div class="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Estado de su Servicio</h3>
                        </div>
                        <div class="p-6 text-center" :class="estadoServicioClass">
                            <div class="text-2xl md:text-4xl font-bold">
                                {{ estadoServicioTexto }}
                            </div>
                            <p class="text-sm mt-1 opacity-80">Código de Afiliado: {{ summary.afiliado_codigo }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Deuda Total</h4>
                            <p class="mt-2 text-3xl font-bold text-red-600 dark:text-red-400">
                                Bs {{ formatCurrency(summary.deuda_total) }}
                            </p>
                            <Link :href="route('mi.cuenta')" class="mt-4 inline-block text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                Ver Facturas Pendientes &rarr;
                            </Link>
                        </div>

                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Facturas Pendientes</h4>
                            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                                {{ summary.facturas_pendientes_count }}
                            </p>
                            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                                (Facturas con estado 'impaga')
                            </p>
                        </div>

                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Último Consumo</h4>
                            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                                {{ formatCurrency(summary.ultimo_consumo_m3) }} m³
                            </p>
                            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                                (Período: {{ summary.ultimo_periodo }})
                            </p>
                        </div>

                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                         <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Acciones Rápidas</h3>
                         <div class="flex flex-col sm:flex-row gap-4">
                             <Link :href="route('mi.cuenta')" class="w-full sm:w-auto text-center px-6 py-3 bg-blue-600 text-white font-bold rounded-md shadow hover:bg-blue-700 transition">
                                Ver Mis Facturas
                             </Link>
                             <Link :href="route('profile.show')" class="w-full sm:w-auto text-center px-6 py-3 bg-gray-500 text-white font-bold rounded-md shadow hover:bg-gray-600 transition">
                                Editar Mi Perfil
                             </Link>
                             
                             <Link :href="route('pagos.mihistorial')" class="w-full sm:w-auto text-center px-6 py-3 bg-gray-200 text-gray-800 font-bold rounded-md shadow hover:bg-gray-300 transition">
                                Ver Historial de Pagos
                             </Link>
                         </div>
                     </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>