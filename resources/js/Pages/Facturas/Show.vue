<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ViewCounter from '@/Components/ViewCounter.vue';

const props = defineProps({
    factura: Object,
    totalPagado: Number,
    saldoPendiente: Number,
    errors: Object,
});

const page = usePage();

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
        case 'impaga': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300';
        case 'pagado': return 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300';
        case 'anulada': return 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
};// --- Acciones de Anulación ---
const showAnularModal = ref(false);
const motivoAnulacion = ref('');
const errorAnulacion = ref('');
const anulando = ref(false);

const abrirModalAnular = () => {
    motivoAnulacion.value = '';
    errorAnulacion.value = '';
    showAnularModal.value = true;
};

const cerrarModalAnular = () => {
    showAnularModal.value = false;
    motivoAnulacion.value = '';
    errorAnulacion.value = '';
};

const confirmarAnularFactura = () => {
    if (!motivoAnulacion.value || motivoAnulacion.value.trim().length < 5) {
        errorAnulacion.value = 'Debe ingresar un motivo de al menos 5 caracteres.';
        return;
    }

    anulando.value = true;
    router.post(route('facturas.anular', props.factura.id), {
        motivo_anulacion: motivoAnulacion.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            anulando.value = false;
            cerrarModalAnular();
        },
        onError: (errs) => {
            anulando.value = false;
            errorAnulacion.value = errs.motivo_anulacion || errs.error_general || 'Error al anular la factura.';
        }
    });
};

const abrirImpresionFactura = () => {
    window.open(route('facturas.imprimir', props.factura.id), '_blank');
};
</script>

<template>
    <AppLayout :title="'Factura F-' + factura.id.toString().padStart(6, '0')">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Detalle de Factura: F-{{ factura.id.toString().padStart(6, '0') }}
                 <span :class="estadoClass(factura.estado)" class="ml-4 px-3 py-1 rounded-full text-sm font-medium uppercase">
                     {{ factura.estado }}
                 </span>
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Mensajes Flash -->
                <div v-if="page.props.flash.success" class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-4 shadow-sm dark:bg-green-900/40 dark:border-green-700 dark:text-green-100" role="alert">
                    <p class="font-bold">Éxito</p> <p>{{ page.props.flash.success }}</p>
                </div>
                <div v-if="page.props.flash.error || Object.keys(page.props.errors).length > 0" class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4 shadow-sm dark:bg-red-900/40 dark:border-red-700 dark:text-red-100" role="alert">
                   <p class="font-bold">Error</p>
                   <p>{{ page.props.flash.error || (errors ? errors.error_general : 'Ocurrió un error.') }}</p>
                   <ul class="list-disc ml-5 text-sm"><li v-for="(error, key) in page.props.errors" :key="key">{{ error }}</li></ul>
                </div>
                 <div v-if="page.props.flash.info" class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 px-4 py-3 rounded mb-4 shadow-sm dark:bg-blue-900/40 dark:border-blue-700 dark:text-blue-100" role="alert">
                     <p class="font-bold">Información</p> <p>{{ page.props.flash.info }}</p>
                 </div>

                <!-- Banner de Factura Anulada -->
                <div v-if="factura.estado === 'anulada'" class="mb-6 p-4 rounded-xl bg-red-100 border-l-4 border-red-600 dark:bg-red-950/60 dark:border-red-500 text-red-900 dark:text-red-200">
                    <h4 class="font-bold text-base flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                        Esta Factura fue ANULADA
                    </h4>
                    <p class="text-sm mt-1"><strong>Motivo:</strong> {{ factura.motivo_anulacion || 'No especificado' }}</p>
                    <p class="text-xs text-red-700 dark:text-red-400 mt-1">
                        Anulada el {{ formatDate(factura.fecha_anulacion) }} por {{ factura.usuario_anulacion?.name || 'Sistema' }}
                    </p>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 md:p-8">
                    
                    <!-- Resumen Financiero -->
                    <div class="mb-6 p-4 rounded-lg border" :class="{ 
                        'bg-red-50 dark:bg-red-900/40 border-red-200 dark:border-red-700': saldoPendiente > 0.01 && factura.estado !== 'anulada', 
                        'bg-green-50 dark:bg-green-900/40 border-green-200 dark:border-green-700': saldoPendiente <= 0.01 && factura.estado !== 'anulada',
                        'bg-gray-100 dark:bg-gray-700/50 border-gray-300 dark:border-gray-600': factura.estado === 'anulada'
                    }">
                         <h3 class="text-lg font-semibold mb-3" :class="{
                            'text-red-800 dark:text-red-200': saldoPendiente > 0.01 && factura.estado !== 'anulada', 
                            'text-green-800 dark:text-green-200': saldoPendiente <= 0.01 && factura.estado !== 'anulada',
                            'text-gray-700 dark:text-gray-300': factura.estado === 'anulada'
                         }">
                             Resumen Financiero
                         </h3>
                         <div class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                              <p class="flex justify-between"><span>Monto Total:</span> <span class="font-medium">Bs {{ formatCurrency(factura.monto_total) }}</span></p>
                              <p class="flex justify-between"><span>Total Pagado:</span> <span class="font-medium">Bs {{ formatCurrency(totalPagado) }}</span></p>
                              <hr class="my-1 border-gray-300 dark:border-gray-600">
                              <p class="flex justify-between text-base">
                                  <strong :class="{'text-red-700 dark:text-red-400': saldoPendiente > 0.01 && factura.estado !== 'anulada', 'text-green-700 dark:text-green-400': saldoPendiente <= 0.01 || factura.estado === 'anulada'}">Saldo Pendiente:</strong>
                                  <strong class="text-lg" :class="{'text-red-700 dark:text-red-400': saldoPendiente > 0.01 && factura.estado !== 'anulada', 'text-green-700 dark:text-green-400': saldoPendiente <= 0.01 || factura.estado === 'anulada'}">
                                      Bs {{ factura.estado === 'anulada' ? '0.00' : formatCurrency(saldoPendiente) }}
                                  </strong>
                              </p>
                         </div>
                      </div>

                    <!-- Datos del Afiliado y Conexión -->
                    <div class="mb-6 pb-4 border-b border-gray-200 dark:border-gray-700">
                         <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">Datos del Afiliado y Conexión</h3>
                         <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2 text-sm text-gray-700 dark:text-gray-300">
                             <p><strong class="text-gray-600 dark:text-gray-400">Afiliado:</strong> <span class="ml-1 font-medium">{{ factura.conexion?.afiliado?.nombre_completo || 'N/A' }}</span></p>
                             <p><strong class="text-gray-600 dark:text-gray-400">CI:</strong> <span class="ml-1">{{ factura.conexion?.afiliado?.ci || '—' }}</span></p>
                             <p><strong class="text-gray-600 dark:text-gray-400">Medidor:</strong> <span class="ml-1 font-medium">{{ factura.conexion?.codigo_medidor || 'N/A' }}</span></p>
                             <p><strong class="text-gray-600 dark:text-gray-400">Dirección:</strong> <span class="ml-1">{{ factura.conexion?.direccion || '—' }}</span></p>
                         </div>
                      </div>
                      
                    <!-- Detalles de Facturación -->
                    <div class="mb-6 pb-4 border-b border-gray-200 dark:border-gray-700">
                          <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">Detalles de Facturación</h3>
                          <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2 text-sm text-gray-700 dark:text-gray-300">
                              <p><strong class="text-gray-600 dark:text-gray-400">N° Factura:</strong> <span class="ml-1 font-medium">F-{{ factura.id.toString().padStart(6, '0') }}</span></p>
                              <p><strong class="text-gray-600 dark:text-gray-400">Período:</strong> <span class="ml-1">{{ factura.periodo }}</span></p>
                              <p><strong class="text-gray-600 dark:text-gray-400">Fecha Emisión:</strong> <span class="ml-1">{{ formatDate(factura.fecha_emision) }}</span></p>
                              <p><strong class="text-gray-600 dark:text-gray-400">Fecha Vencimiento:</strong> <span class="ml-1">{{ formatDate(factura.fecha_vencimiento) || 'N/A' }}</span></p>
                              <p><strong class="text-gray-600 dark:text-gray-400">Consumo:</strong> <span class="ml-1">{{ formatCurrency(factura.consumo_m3) }} m³</span></p>
                              <p><strong class="text-gray-600 dark:text-gray-400">Monto Total:</strong> <span class="ml-1 font-bold text-blue-700 dark:text-blue-400">Bs {{ formatCurrency(factura.monto_total) }}</span></p>
                          </div>
                          <div v-if="factura.justificacion_modificacion" class="mt-3 text-sm p-2 bg-yellow-50 border border-yellow-200 rounded dark:bg-yellow-900/40 dark:border-yellow-700">
                              <strong class="text-yellow-800 dark:text-yellow-200">Modificación Manual:</strong> <span class="text-gray-700 dark:text-gray-300 ml-1">{{ factura.justificacion_modificacion }}</span>
                          </div>
                      </div>
                      
                    <!-- Lectura Asociada -->
                    <div v-if="factura.lectura" class="mb-6 pb-4 border-b border-gray-200 dark:border-gray-700">
                           <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">Lectura Asociada</h3>
                           <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2 text-sm text-gray-700 dark:text-gray-300">
                               <p><strong class="text-gray-600 dark:text-gray-400">Fecha Lectura:</strong> <span class="ml-1">{{ formatDate(factura.lectura.fecha_lectura) }}</span></p>
                               <p><strong class="text-gray-600 dark:text-gray-400">Registró:</strong> <span class="ml-1">{{ factura.lectura.usuarioRegistrado?.name || 'N/A' }}</span></p>
                               <p><strong class="text-gray-600 dark:text-gray-400">Lectura Anterior:</strong> <span class="ml-1">{{ factura.lectura.lectura_anterior }} m³</span></p>
                               <p><strong class="text-gray-600 dark:text-gray-400">Lectura Actual:</strong> <span class="ml-1">{{ factura.lectura.lectura_actual }} m³</span></p>
                           </div>
                      </div>
                      
                    <!-- Historial de Pagos -->
                    <div class="mb-6">
                           <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">Historial de Pagos</h3>
                           <div v-if="!factura.pagos || factura.pagos.length === 0" class="text-sm text-gray-500 dark:text-gray-400 italic">
                               No hay pagos registrados para esta factura.
                           </div>
                           <ul v-else class="space-y-2">
                               <li v-for="pago in factura.pagos" :key="pago.id" class="text-sm border-b border-gray-200 dark:border-gray-700 pb-2 text-gray-700 dark:text-gray-300">
                                   <div class="flex justify-between">
                                       <span>{{ formatDate(pago.fecha_pago) }} - {{ pago.forma_pago }}</span>
                                       <span class="font-medium">Bs {{ formatCurrency(pago.monto_pagado) }}</span>
                                   </div>
                                   <div v-if="pago.referencia" class="text-xs text-gray-500 dark:text-gray-400">Ref: {{ pago.referencia }}</div>
                                   <div class="text-xs text-gray-400 dark:text-gray-500">Registró: {{ pago.usuarioRegistrado?.name || 'N/A' }}</div>
                               </li>
                           </ul>
                      </div>

                    <!-- Botones de Acción -->
                    <div class="flex flex-wrap justify-between items-center mt-8 pt-4 border-t border-gray-200 dark:border-gray-700 gap-3">
                        
                        <Link v-if="$page.props.auth.user.role_names.includes('Administrador') || $page.props.auth.user.role_names.includes('Secretaria')"
                              :href="route('facturas.index')" 
                              class="bg-gray-500 hover:bg-gray-600 dark:bg-gray-700 dark:hover:bg-gray-600 text-white font-bold py-2 px-4 rounded transition shadow">
                            Volver al Listado
                        </Link>

                        <Link v-else-if="$page.props.auth.user.role_names.includes('Usuario')"
                              :href="route('mi.cuenta')" class="bg-gray-500 hover:bg-gray-600 dark:bg-gray-700 dark:hover:bg-gray-600 text-white font-bold py-2 px-4 rounded transition shadow">
                            Volver a Mis Facturas
                        </Link>
                        
                        <div class="flex flex-wrap gap-3">
                            <button @click="abrirImpresionFactura" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded transition shadow">
                                🖨️ Imprimir Factura
                            </button>
                            
                            <button v-if="factura.estado === 'impaga' && ($page.props.auth.user.role_names.includes('Administrador') || $page.props.auth.user.role_names.includes('Secretaria'))" 
                                    @click="abrirModalAnular"
                                    class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition shadow">
                                Anular Factura
                            </button>

                            <Link v-if="factura.estado === 'impaga' && ($page.props.auth.user.role_names.includes('Administrador') || $page.props.auth.user.role_names.includes('Secretaria'))"
                                :href="route('pagos.create', { factura_id: factura.id })"
                                class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded transition shadow">
                                Registrar Pago
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODAL DE ANULACIÓN CONTROLADA -->
            <div v-if="showAnularModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-md w-full p-6 border dark:border-gray-700">
                    <div class="flex justify-between items-center border-b dark:border-gray-700 pb-3 mb-4">
                        <h3 class="text-lg font-bold text-red-600 dark:text-red-400 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            Anulación de Factura
                        </h3>
                        <button @click="cerrarModalAnular" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 font-bold">&times;</button>
                    </div>

                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">
                        Está por anular la factura <strong>F-{{ String(factura.id).padStart(6, '0') }}</strong> por un monto de <strong>Bs {{ formatCurrency(factura.monto_total) }}</strong>.
                    </p>
                    <p class="text-xs text-amber-600 dark:text-amber-400 font-semibold mb-4 bg-amber-50 dark:bg-amber-900/30 p-2 rounded border border-amber-200 dark:border-amber-800">
                        ⚠️ La lectura asociada volverá a estado "pendiente" para permitir una nueva facturación o corrección.
                    </p>

                    <div class="mb-4">
                        <label for="motivo_anulacion_show" class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1">
                            Motivo de Anulación *
                        </label>
                        <textarea
                            id="motivo_anulacion_show"
                            v-model="motivoAnulacion"
                            rows="3"
                            placeholder="Escriba el motivo detallado de la anulación (Ej: error en lectura, factura duplicada)..."
                            class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500"
                        ></textarea>
                        <p v-if="errorAnulacion" class="text-xs text-red-600 mt-1 font-semibold">{{ errorAnulacion }}</p>
                    </div>

                    <div class="flex justify-end gap-3 pt-2 border-t dark:border-gray-700">
                        <button
                            @click="cerrarModalAnular"
                            :disabled="anulando"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition"
                        >
                            Cancelar
                        </button>
                        <button
                            @click="confirmarAnularFactura"
                            :disabled="anulando"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg shadow transition flex items-center gap-2 disabled:opacity-50"
                        >
                            <span v-if="anulando" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
                            {{ anulando ? 'Anulando...' : 'Confirmar Anulación' }}
                        </button>
                    </div>
                </div>
            </div>

            <ViewCounter />
        </div>
    </AppLayout>
</template>