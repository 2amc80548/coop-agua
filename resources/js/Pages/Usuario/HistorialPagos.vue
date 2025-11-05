<script setup>
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    pagos: Object, // Paginado
    filters: Object,
    error: String,
});

// (Aquí puedes añadir filtros de fecha si los necesitas, igual que en el Index de Pagos del Admin)

const formatDate = (dateString) => {
    if (!dateString) return '—'; 
    try { 
        const date = new Date(dateString); 
        date.setMinutes(date.getMinutes() + date.getTimezoneOffset()); 
        return date.toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' }); 
    } catch { return dateString; }
};
const formatCurrency = (amount) => (parseFloat(amount) || 0).toFixed(2);
</script>

<template>
    <AppLayout title="Historial de Pagos">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Mi Historial de Pagos
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <div v-if="error" class="bg-red-100 ... mb-6" role="alert">
                    <p class="font-bold">Error</p><p>{{ error }}</p>
                </div>

                <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded shadow-md">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha de Pago</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Factura / Período</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Monto Pagado</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Forma de Pago</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cajero</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-if="!pagos.data.length"> <td colspan="5" class="py-4 px-4 text-center text-sm text-gray-500 dark:text-gray-300">No se encontraron pagos registrados.</td> </tr>
                        <tr v-for="p in pagos.data" :key="p.id" class="hover:bg-gray-50 dark:hover:bg-gray-700 text-sm">
                            <td class="px-4 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ formatDate(p.fecha_pago) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <Link :href="route('facturas.show', p.factura_id)" class="font-medium text-indigo-600 hover:underline">
                                    F-{{ p.factura_id.toString().padStart(6, '0') }}
                                </Link>
                                <span class="block text-xs text-gray-500">Per: {{ p.factura?.periodo }}</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right font-bold text-green-700 dark:text-green-400">Bs {{ formatCurrency(p.monto_pagado) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                    {{ p.forma_pago }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">{{ p.usuarioRegistrado?.name }}</td>
                        </tr>
                    </tbody>
                    </table>
                </div>

                <div class="mt-6 flex justify-between items-center text-sm" v-if="pagos.links.length > 3">
                    <span class="text-gray-700 dark:text-gray-300">Mostrando {{ pagos.from }} a {{ pagos.to }} de {{ pagos.total }} pagos</span>
                    <div class="flex flex-wrap gap-1">
                    <Link v-for="(link, index) in pagos.links" :key="index" :href="link.url ?? '#'" v-html="link.label"
                            class="px-3 py-1 border rounded ..."
                            :class="{ 'bg-blue-600 text-white': link.active, 'text-gray-400 ...': !link.url, 'hover:bg-gray-100 ...': link.url }"
                            preserve-scroll preserve-state :disabled="!link.url"/>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>