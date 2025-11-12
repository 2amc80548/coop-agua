<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    factura: Object,
    totalPagado: Number,
    saldoPendiente: Number,
    errors: Object,
});

const page = usePage(); // Para mensajes flash

// --- Funciones de Formato ---
const formatDate = (dateString) => {
    if (!dateString) return '—'; 
    try { 
        const date = new Date(dateString); 
        date.setMinutes(date.getMinutes() + date.getTimezoneOffset()); 
        return date.toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' }); 
    } catch { return dateString; }
};
const formatCurrency = (amount) => {
    return (parseFloat(amount) || 0).toFixed(2);
};
const estadoClass = (estado) => {
    switch (estado) {
        case 'impaga': return 'bg-yellow-100 text-yellow-800';
        case 'pagado': return 'bg-green-100 text-green-800';
        case 'anulada': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

// --- Acciones ---
const anularFactura = () => {
    if (confirm('¿Estás seguro de ANULAR esta factura? La lectura volverá a estar pendiente.')) {
        router.post(route('facturas.anular', props.factura.id), {}, {
            preserveScroll: true,
        });
    }
};

const abrirImpresionFactura = () => {
    // Usamos la nueva ruta 'facturas.imprimir' que crearemos
    window.open(route('facturas.imprimir', props.factura.id), '_blank');
};
</script>

<template>
    <AppLayout :title="'Factura F-' + factura.id.toString().padStart(6, '0')">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detalle de Factura: F-{{ factura.id.toString().padStart(6, '0') }}
                 <span :class="estadoClass(factura.estado)"
                       class="ml-4 px-3 py-1 rounded-full text-sm font-medium">
                     {{ factura.estado }}
                 </span>
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                
                <div v-if="page.props.flash.success" class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-4 shadow-sm" role="alert">
                    <p class="font-bold">Éxito</p> <p>{{ page.props.flash.success }}</p>
                </div>
                <div v-if="page.props.flash.error || Object.keys(page.props.errors).length > 0" class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4 shadow-sm" role="alert">
                   <p class="font-bold">Error</p>
                   <p>{{ page.props.flash.error || (errors ? errors.error_general : 'Ocurrió un error.') }}</p>
                   <ul class="list-disc ml-5 text-sm"><li v-for="(error, key) in page.props.errors" :key="key">{{ error }}</li></ul>
                </div>
                 <div v-if="page.props.flash.info" class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 px-4 py-3 rounded mb-4 shadow-sm" role="alert">
                    <p class="font-bold">Información</p> <p>{{ page.props.flash.info }}</p>
                 </div>

                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 md:p-8">
                    <div class="mb-6 p-4 rounded-lg" :class="{ 'bg-red-50 border-red-200': saldoPendiente > 0.01, 'bg-green-50 border-green-200': saldoPendiente <= 0.01, 'border':true }">
                         <h3 class="text-lg font-semibold mb-3" :class="{'text-red-800': saldoPendiente > 0.01, 'text-green-800': saldoPendiente <= 0.01}">Resumen Financiero</h3>
                         <div class="space-y-1 text-sm">
                             <p class="flex justify-between"><span>Monto Total:</span> <span class="font-medium">Bs {{ formatCurrency(factura.monto_total) }}</span></p>
                             <p class="flex justify-between"><span>Total Pagado:</span> <span class="font-medium">Bs {{ formatCurrency(totalPagado) }}</span></p>
                             <hr class="my-1 border-gray-300">
                             <p class="flex justify-between text-base">
                                 <strong :class="{'text-red-700': saldoPendiente > 0.01, 'text-green-700': saldoPendiente <= 0.01}">Saldo Pendiente:</strong>
                                 <strong class="text-lg" :class="{'text-red-700': saldoPendiente > 0.01, 'text-green-700': saldoPendiente <= 0.01}">Bs {{ formatCurrency(saldoPendiente) }}</strong>
                             </p>
                         </div>
                     </div>

                    <div class="mb-6 pb-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">Datos del Afiliado y Conexión</h3>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                            <p><strong class="text-gray-600">Afiliado:</strong> {{ factura.conexion?.afiliado?.nombre_completo }}</p>
                            <p><strong class="text-gray-600">CI:</strong> {{ factura.conexion?.afiliado?.ci }}</p>
                            <p><strong class="text-gray-600">Medidor:</strong> {{ factura.conexion?.codigo_medidor }}</p>
                            <p><strong class="text-gray-600">Dirección:</strong> {{ factura.conexion?.direccion }}</p>
                        </div>
                    </div>
                    <div class="mb-6 pb-4 border-b">
                         <h3 class="text-lg font-semibold text-gray-800 mb-3">Detalles de Facturación</h3>
                         <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                             <p><strong class="text-gray-600">N° Factura:</strong> F-{{ factura.id.toString().padStart(6, '0') }}</p>
                             <p><strong class="text-gray-600">Período:</strong> {{ factura.periodo }}</p>
                             <p><strong class="text-gray-600">Fecha Emisión:</strong> {{ formatDate(factura.fecha_emision) }}</p>
                             <p><strong class="text-gray-600">Fecha Vencimiento:</strong> {{ formatDate(factura.fecha_vencimiento) || 'N/A' }}</p>
                             <p><strong class="text-gray-600">Consumo:</strong> {{ formatCurrency(factura.consumo_m3) }} m³</p>
                              <p><strong class="text-gray-600">Monto Total:</strong> <span class="font-semibold">Bs {{ formatCurrency(factura.monto_total) }}</span></p>
                         </div>
                         <div v-if="factura.justificacion_modificacion" class="mt-3 text-sm p-2 bg-yellow-50 border border-yellow-200 rounded">
                             <strong class="text-yellow-800">Modificación Manual:</strong> {{ factura.justificacion_modificacion }}
                         </div>
                    </div>
                     <div v-if="factura.lectura" class="mb-6 pb-4 border-b">
                          <h3 class="text-lg font-semibold text-gray-800 mb-3">Lectura Asociada</h3>
                          <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                              <p><strong class="text-gray-600">Fecha Lectura:</strong> {{ formatDate(factura.lectura.fecha_lectura) }}</p>
                              <p><strong class="text-gray-600">Registró:</strong> {{ factura.lectura.usuarioRegistrado?.name }}</p>
                              <p><strong class="text-gray-600">Lectura Anterior:</strong> {{ factura.lectura.lectura_anterior }} m³</p>
                              <p><strong class="text-gray-600">Lectura Actual:</strong> {{ factura.lectura.lectura_actual }} m³</p>
                          </div>
                     </div>
                     
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">Historial de Pagos</h3>
                        <div v-if="!factura.pagos || factura.pagos.length === 0" class="text-sm text-gray-500 italic">
                            No hay pagos registrados para esta factura.
                        </div>
                        <ul v-else class="space-y-2">
                            <li v-for="pago in factura.pagos" :key="pago.id" class="text-sm border-b pb-2">
                                <div class="flex justify-between">
                                    <span>{{ formatDate(pago.fecha_pago) }} - {{ pago.forma_pago }}</span>
                                    <span class="font-medium">Bs {{ formatCurrency(pago.monto_pagado) }}</span>
                                </div>
                                <div v-if="pago.referencia" class="text-xs text-gray-500">Ref: {{ pago.referencia }}</div>
                                <div class="text-xs text-gray-400">Registró: {{ pago.usuarioRegistrado?.name || 'N/A' }}</div>
                            </li>
                        </ul>
                    </div>

                    <div class="flex flex-wrap justify-between items-center mt-8 pt-4 border-t gap-3">
                        
                        <Link v-if="$page.props.auth.user.role_names.includes('Administrador') || $page.props.auth.user.role_names.includes('Secretaria')"
                              :href="route('facturas.index')" 
                              class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded transition">
                            Volver al Listado
                        </Link>

                        <Link v-else-if="$page.props.auth.user.role_names.includes('Usuario')"
                              :href="route('mi.cuenta')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded transition">
                            Volver a Mis Facturas
                        </Link>
                        
                        <div class="flex gap-3">
                            <button @click="abrirImpresionFactura" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded transition">
                                🖨️ Imprimir Factura
                            </button>
                            
                        <button v-if="factura.estado === 'impaga' && ($page.props.auth.user.role_names.includes('Administrador') || $page.props.auth.user.role_names.includes('Secretaria'))" 
                                @click="anularFactura"
                                class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded transition">
                            Anular Factura
                        </button>

                        <Link v-if="factura.estado === 'impaga' && ($page.props.auth.user.role_names.includes('Administrador') || $page.props.auth.user.role_names.includes('Secretaria'))"
                            :href="route('pagos.create', { factura_id: factura.id })"
                            class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded transition">
                            Registrar Pago
                        </Link>


                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>