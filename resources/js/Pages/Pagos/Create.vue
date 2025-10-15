<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
// ✅ 1. Define props
const props = defineProps({
    factura: Object,
    errors: Object,
    userId: Number, // ← nuevo
});



// ✅ 2. Verifica que factura exista antes de usarla
if (!props.factura) {
    console.error('Factura no recibida');
    // O redirige
    // router.visit(route('facturas.index'));
}

// ✅ 3. Inicializa el formulario
const form = useForm({
    factura_id: props.factura?.id || '',
    monto_pagado: props.factura?.monto_total || '',
    metodo_pago: 'efectivo',
    fecha_pago: new Date().toISOString().split('T')[0],
    registrado_por: props.userId,
});

// ✅ 4. Solo envía si hay factura
const submit = () => {
    if (!form.factura_id) {
        alert('No se especificó una factura');
        return;
    }
    form.post(route('pagos.store'));
};
</script>

<template>
    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Registrar Pago -Factura F-{{ props.factura.id.toString().padStart(6, '0') }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <!-- Detalles de la factura -->
                    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-md">
                        <h3 class="text-lg font-medium text-blue-900">Factura a Pagar</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                            <p><strong>Beneficiario:</strong> {{ factura.conexion?.beneficiario?.nombre_completo }}</p>
                            <p><strong>Medidor:</strong> {{ factura.conexion?.codigo_medidor }}</p>
                            <p><strong>Monto Total:</strong> Bs {{ Number(props.factura.monto_total).toFixed(2) }}</p>
                            <p><strong>Consumo:</strong> {{ Number(factura.consumo_m3).toFixed(2) }} m³</p>
                        </div>
                    </div>

                    <!-- Formulario de pago -->
                    <form @submit.prevent="submit">
                        <!-- Monto pagado -->
                        <div class="mb-4">
                            <label class="block font-medium text-sm text-gray-700">Monto Pagado (Bs)</label>
                            <input
                                v-model.number="form.monto_pagado"
                                type="number"
                                step="0.01"
                                min="0"
                                :max="factura.monto_total"
                                class="border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm block mt-1 w-full"
                                required
                            />
                            <div v-if="errors.monto_pagado" class="text-red-600 text-sm mt-1">{{ errors.monto_pagado }}</div>
                        </div>

                        <!-- Forma de pago -->
                        <div class="mb-4">
                            <label class="block font-medium text-sm text-gray-700">Forma de Pago</label>
                            <select
                                v-model="form.forma_pago"
                                class="border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm block mt-1 w-full"
                            >
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="tarjeta">Tarjeta</option>
                                <option value="cheque">Cheque</option>
                            </select>
                            <div v-if="errors.forma_pago" class="text-red-600 text-sm mt-1">{{ errors.forma_pago }}</div>
                        </div>

                        <!-- Fecha de pago -->
                        <div class="mb-4">
                            <label class="block font-medium text-sm text-gray-700">Fecha de Pago</label>
                            <input
                                v-model="form.fecha_pago"
                                type="date"
                                class="border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm block mt-1 w-full"
                                required
                            />
                            <div v-if="errors.fecha_pago" class="text-red-600 text-sm mt-1">{{ errors.fecha_pago }}</div>
                        </div>

                        <!-- Botones -->
                        <div class="flex justify-end gap-3">
                            <Link :href="route('facturas.show', factura.id)" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Cancelar
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                            >
                                {{ form.processing ? 'Guardando...' : 'Registrar Pago' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>