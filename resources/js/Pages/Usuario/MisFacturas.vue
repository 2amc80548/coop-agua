<script setup>
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { watch } from 'vue';

const props = defineProps({
    facturas: Object, // Paginado
    filters: Object,
    periodos: Array,
    error: String,
});

const filterForm = useForm({
    estado: props.filters.estado ?? 'impaga',
});

// Cuando el filtro cambia, recarga la página
watch(() => filterForm.estado, (newState) => {
    router.get(route('mi.cuenta'), { estado: newState }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
});

const formatDate = (dateString) => {
    if (!dateString) return '—'; 
    try { 
        const date = new Date(dateString); 
        date.setMinutes(date.getMinutes() + date.getTimezoneOffset()); 
        return date.toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' }); 
    } catch { return dateString; }
};
const formatCurrency = (amount) => (parseFloat(amount) || 0).toFixed(2);
const estadoClass = (estado) => {
    switch (estado) {
        case 'impaga': return 'bg-yellow-100 text-yellow-800';
        case 'pagado': return 'bg-green-100 text-green-800';
        case 'anulada': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

// Abrir factura para imprimir
const imprimirFactura = (facturaId) => {
    window.open(route('facturas.imprimir', facturaId), '_blank');
};

</script>

<template>
    <AppLayout title="Mis Facturas">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Mis Facturas
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div v-if="error" class="bg-red-100 ... mb-6" role="alert">
                    <p class="font-bold">Error</p><p>{{ error }}</p>
                </div>

                <div class="mb-4 flex justify-end">
                    <select v-model="filterForm.estado" class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm text-sm ...">
                        <option value="impaga">Mostrar Impagas</option>
                        <option value="pagado">Mostrar Pagadas</option>
                        <option value="todos">Mostrar Todas</option>
                    </select>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                        <li v-if="!facturas.data.length" class="p-6 text-center text-gray-500 dark:text-gray-400">
                            No se encontraron facturas con el filtro "{{ filterForm.estado }}".
                        </li>
                        
                        <li v-for="factura in facturas.data" :key="factura.id" class="p-4 sm:p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <div class="flex flex-col sm:flex-row justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-4">
                                        <span :class="estadoClass(factura.estado)" class="px-3 py-1 text-xs font-semibold rounded-full uppercase">
                                            {{ factura.estado }}
                                        </span>
                                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Período: {{ factura.periodo }}
                                        </span>
                                    </div>
                                    <h3 class="mt-2 text-lg font-bold text-gray-900 dark:text-white">
                                        Factura F-{{ factura.id.toString().padStart(6, '0') }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Medidor: {{ factura.conexion.codigo_medidor }}
                                    </p>
                                </div>
                                <div class="text-left sm:text-right flex-shrink-0">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Monto Total</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white">Bs {{ formatCurrency(factura.monto_total) }}</p>
                                    <p v-if="factura.estado === 'impaga'" class="text-sm font-bold text-red-600 dark:text-red-400">
                                        Deuda: Bs {{ formatCurrency(factura.deuda_pendiente) }}
                                    </p>
                                    <p v-if="factura.estado === 'pagado'" class="text-sm font-bold text-green-600 dark:text-green-400">
                                        Pagado
                                    </p>
                                </div>
                            </div>
                            <div class="mt-4 flex gap-3">
                                <Link :href="route('facturas.show', factura.id)" class="text-sm text-indigo-600 hover:underline">
                                    Ver Detalle
                                </Link>
                                <button @click="imprimirFactura(factura.id)" class="text-sm text-purple-600 hover:underline">
                                    🖨️ Imprimir Factura
                                </button>
                            </div>
                        </li>
                    </ul>
                </div>
                
                <div class="mt-6 flex justify-between items-center text-sm" v-if="facturas.links.length > 3">
                    <span class="text-gray-700 dark:text-gray-300">Mostrando {{ facturas.from }} a {{ facturas.to }} de {{ facturas.total }} facturas</span>
                    <div class="flex flex-wrap gap-1">
                      <Link v-for="(link, index) in facturas.links" :key="index" :href="link.url ?? '#'" v-html="link.label"
                            class="px-3 py-1 border rounded ..."
                            :class="{ 'bg-blue-600 text-white': link.active, 'text-gray-400 ...': !link.url, 'hover:bg-gray-100 ...': link.url }"
                            preserve-scroll preserve-state :disabled="!link.url"/>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>