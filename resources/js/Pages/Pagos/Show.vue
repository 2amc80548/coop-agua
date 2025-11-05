<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    pago: Object, // El pago, con factura.conexion.afiliado y usuarioRegistrado
});

const formatDate = (dateString) => {
    if (!dateString) return '—'; 
    try { 
        const date = new Date(dateString); 
        date.setMinutes(date.getMinutes() + date.getTimezoneOffset()); 
        return date.toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' }); 
    } catch { return dateString; }
};
const formatCurrency = (amount) => { return (parseFloat(amount) || 0).toFixed(2); };

</script>

<template>
    <AppLayout :title="'Detalle Pago ID: ' + pago.id">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detalle del Pago #{{ pago.id }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 md:p-8">
                    
                    <div class="mb-6 pb-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">Detalles de la Transacción</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
                            <p><strong class="text-gray-600">ID de Pago:</strong> {{ pago.id }}</p>
                            <p><strong class="text-gray-600">Fecha de Pago:</strong> {{ formatDate(pago.fecha_pago) }}</p>
                            <p><strong class="text-gray-600">Forma de Pago:</strong> 
                                <span class="font-medium text-gray-900">{{ pago.forma_pago }}</span>
                            </p>
                            <p><strong class="text-gray-600">Monto Pagado:</strong> 
                                <span class="font-bold text-lg text-green-700">Bs {{ formatCurrency(pago.monto_pagado) }}</span>
                            </p>
                            <p v-if="pago.referencia" class="sm:col-span-2">
                                <strong class="text-gray-600">Referencia:</strong> {{ pago.referencia }}
                            </p>
                            <p class="sm:col-span-2"><strong class="text-gray-600">Registrado por (Cajero):</strong> 
                                {{ pago.usuarioRegistrado?.name || 'N/A' }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="mb-6 pb-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">Factura Asociada</h3>
                         <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
                             <p><strong class="text-gray-600">N° Factura:</strong> 
                                 <Link :href="route('facturas.show', pago.factura_id)" class="font-medium text-indigo-600 hover:underline">
                                    F-{{ pago.factura_id.toString().padStart(6, '0') }}
                                 </Link>
                             </p>
                             <p><strong class="text-gray-600">Período:</strong> {{ pago.factura?.periodo }}</p>
                             <p><strong class="text-gray-600">Afiliado:</strong> {{ pago.factura?.conexion?.afiliado?.nombre_completo }}</p>
                             <p><strong class="text-gray-600">Medidor:</strong> {{ pago.factura?.conexion?.codigo_medidor }}</p>
                         </div>
                    </div>

                    <div class="flex justify-between items-center mt-8">
                         <Link :href="route('pagos.index')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded transition">
                            Volver al Historial
                         </Link>
                         <button v-if="$page.props.auth.user.role_names.includes('Administrador')" 
                                 @click="anularPago(pago)" 
                                 class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded text-sm transition">
                             Anular este Pago
                         </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>