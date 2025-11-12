<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    stats: Object, // El objeto con todos los KPIs
    filters: Object,
});

// --- Formulario de Filtros de Fecha ---
const filterForm = useForm({
    fecha_inicio: props.filters.fecha_inicio,
    fecha_fin: props.filters.fecha_fin,
});

// Función para enviar los filtros
const submitFilters = () => {
    filterForm.get(route('reportes.index'), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Helper para formatear moneda
const formatCurrency = (amount) => (parseFloat(amount) || 0).toFixed(2);
// Helper para formatear números
const formatNumber = (num) => (parseInt(num) || 0).toLocaleString('es-ES');

</script>

<template>
    <AppLayout title="Reportes y Estadísticas">
        <Head title="Reportes y Estadísticas" />

        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Reportes y Estadísticas
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <div class="p-4 bg-white dark:bg-gray-800 rounded shadow-sm">
                    <form @submit.prevent="submitFilters" class="flex flex-col md:flex-row items-center gap-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Filtrar por Fecha:</h3>
                        <div class="flex-1">
                            <label for="fecha_inicio" class="block text-sm font-medium ...">Desde</label>
                            <input id="fecha_inicio" type="date" v-model="filterForm.fecha_inicio" class="... w-full" />
                        </div>
                        <div class="flex-1">
                            <label for="fecha_fin" class="block text-sm font-medium ...">Hasta</label>
                            <input id="fecha_fin" type="date" v-model="filterForm.fecha_fin" class="... w-full" />
                        </div>
                        <button type="submit" :disabled="filterForm.processing" class="bg-blue-600 text-white px-4 py-2 rounded shadow ...">
                            Aplicar Filtro
                        </button>
                    </form>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">Total Recaudado</h4>
                        <p class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">
                            Bs {{ formatCurrency(stats.recaudado) }}
                        </p>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            en {{ formatNumber(stats.conteoPagos) }} pagos
                        </p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">Total Facturado</h4>
                        <p class="mt-2 text-3xl font-bold text-blue-600 dark:text-blue-400">
                            Bs {{ formatCurrency(stats.facturado) }}
                        </p>
                         <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            en {{ formatNumber(stats.conteoFacturas) }} facturas generadas
                        </p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">Nuevos Afiliados</h4>
                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                            + {{ formatNumber(stats.nuevosAfiliados) }}
                        </p>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            registrados en este período
                        </p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">Lecturas Registradas</h4>
                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                            {{ formatNumber(stats.lecturasRegistradas) }}
                        </p>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            registradas en este período
                        </p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">Reclamos Recibidos</h4>
                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                            {{ formatNumber(stats.reclamosRecibidos) }}
                        </p>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            (Resueltos: {{ formatNumber(stats.reclamosResueltos) }})
                        </p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 border-2 border-red-500">
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">Deuda Total (Global)</h4>
                        <p class="mt-2 text-3xl font-bold text-red-600 dark:text-red-400">
                            Bs {{ formatCurrency(stats.deudaGlobal) }}
                        </p>
                         <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            de {{ formatNumber(stats.conteoDeudores) }} afiliados
                        </p>
                    </div>
                </div>
                
                <div class="mt-6 p-4 bg-white dark:bg-gray-800 rounded shadow-sm">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Exportar Reportes Detallados</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Descargue los datos completos en PDF o Excel (basado en los filtros de fecha).</p>
                    <div class="flex flex-wrap gap-4">
                        <button class="bg-green-600 text-white px-4 py-2 rounded shadow hover:bg-green-700 transition">
                            Descargar Pagos (PDF)
                        </button>
                        <button class="bg-red-600 text-white px-4 py-2 rounded shadow hover:bg-red-700 transition">
                            Descargar Deudas (PDF)
                        </button>
                         <button class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 transition">
                            Descargar Afiliados (PDF)
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>