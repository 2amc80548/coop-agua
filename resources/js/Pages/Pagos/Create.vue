<script setup>
import { useForm, Link, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue'; 
import ViewCounter from '@/Components/ViewCounter.vue';

const props = defineProps({
    factura: Object, 
    saldoPendiente: Number,
    errors: Object, 
});

const form = useForm({
    factura_id: props.factura.id,
    fecha_pago: new Date().toISOString().split('T')[0],
    forma_pago: 'Efectivo', 
    referencia: '',
});

const submit = () => {
    form.post(route('pagos.store'), {
         // No es necesario 'monto_pagado', el backend lo toma de la BD
    });
};

const formatCurrency = (amount) => { return (parseFloat(amount) || 0).toFixed(2); };
</script>

<template>
    <AppLayout :title="'Registrar Pago F-' + factura.id">
        <Head :title="'Registrar Pago F-' + factura.id" />
        
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Registrar Pago - Factura F-{{ factura.id.toString().padStart(6, '0') }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                
                <div v-if="form.errors.error_general || (errors && errors.error_general)" 
                       class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-6 shadow-sm dark:bg-red-900 dark:border-red-700 dark:text-red-100" role="alert">
                    <p class="font-bold">Error</p>
                    <p>{{ form.errors.error_general || (errors ? errors.error_general : 'No se pudo procesar.') }}</p>
                     <ul v-if="form.errors.factura_id" class="list-disc ml-5 text-sm mt-1">
                        <li>{{ form.errors.factura_id }}</li>
                    </ul>
                 </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 md:p-8">
                    
                    <div class="mb-6 pb-4 border-b border-gray-200 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-300">
                        <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Resumen de la Factura Impaga</h3>
                        <p><strong class="text-gray-600 dark:text-gray-400">Afiliado:</strong> {{ factura.conexion?.afiliado?.nombre_completo }}</p>
                        <p><strong class="text-gray-600 dark:text-gray-400">Medidor:</strong> {{ factura.conexion?.codigo_medidor }}</p>
                        <p><strong class="text-gray-600 dark:text-gray-400">Período:</strong> {{ factura.periodo }}</p>
                        <p class="mt-2 text-base"><strong class="text-red-700 dark:text-red-400">Monto a Pagar (Saldo):</strong> 
                            <span class="font-bold text-lg text-red-700 dark:text-red-400">Bs {{ formatCurrency(saldoPendiente) }}</span>
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">
                        
                        <div class="mb-4">
                            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">Monto a Pagar (Bs)</label>
                            <input
                                :value="formatCurrency(saldoPendiente)"
                                type="text"
                                class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm block mt-1 w-full bg-gray-100 dark:bg-gray-700/50 cursor-not-allowed"
                                disabled readonly
                            />
                            <div v-if="form.errors.monto_pagado" class="text-red-600 text-xs mt-1">{{ form.errors.monto_pagado }}</div>
                        </div>
                        
                        <div>
                            <label for="fecha_pago" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha de Pago</label>
                            <input id="fecha_pago" v-model="form.fecha_pago" type="date"
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                            <div v-if="form.errors.fecha_pago" class="text-red-600 text-xs mt-1">{{ form.errors.fecha_pago }}</div>
                        </div>
                        
                        <div>
                            <label for="forma_pago" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Forma de Pago</label>
                            <select id="forma_pago" v-model="form.forma_pago" 
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                                <option>Efectivo</option>
                                <option>QR</option>
                            </select>
                            <div v-if="form.errors.forma_pago" class="text-red-600 text-xs mt-1">{{ form.errors.forma_pago }}</div>
                        </div>
                        
                        <div>
                            <label for="referencia" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Referencia (Opcional)</label>
                            <input id="referencia" v-model="form.referencia" type="text" placeholder="N° transacción, N° cheque, etc."
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <div v-if="form.errors.referencia" class="text-red-600 text-xs mt-1">{{ form.errors.referencia }}</div>
                        </div>
                        
                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                             <Link :href="route('facturas.show', factura.id)" 
                                   class="bg-gray-500 hover:bg-gray-600 dark:bg-gray-700 dark:hover:bg-gray-600 text-white font-bold py-2 px-4 rounded shadow transition duration-150">
                                 Cancelar
                             </Link>
                             <button type="submit" :disabled="form.processing || saldoPendiente <= 0"
                                     class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded shadow disabled:opacity-50 transition duration-150">
                                 {{ form.processing ? 'Registrando...' : 'Confirmar Pago' }}
                             </button>
                         </div>
                    </form>
                </div>
            </div>
            <ViewCounter />
        </div>
    </AppLayout>
</template>