<!-- Resources/js/Pages/Facturas/Edit.vue -->
<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    factura: Object,
    conexiones: Array,
    errors: Object,
});

const form = useForm({
    conexion_id: props.factura.conexion_id,
    consumo_m3: props.factura.consumo_m3,
    monto_total: props.factura.monto_total,
    estado: props.factura.estado,
    fecha_emision: props.factura.fecha_emision,
});

const submit = () => {
    form.put(route('facturas.update', props.factura.id));
};
</script>

<template>
    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Editar Factura F-{{ factura.id.toString().padStart(6, '0') }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <form @submit.prevent="submit">
                        <!-- Conexión (solo lectura) -->
                        <div class="mb-6 p-4 bg-gray-50 rounded-md">
                            <p><strong>Beneficiario:</strong> {{ factura.conexion?.beneficiario?.nombre_completo }}</p>
                            <p><strong>Medidor:</strong> {{ factura.conexion?.codigo_medidor }}</p>
                        </div>

                        <!-- Consumo -->
                        <div class="mb-4">
                            <label class="block font-medium text-sm text-gray-700">Consumo (m³)</label>
                            <input
                                v-model.number="form.consumo_m3"
                                type="number"
                                step="0.01"
                                class="border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm block mt-1 w-full"
                                required
                            />
                        </div>

                        <!-- Monto total -->
                        <div class="mb-4">
                            <label class="block font-medium text-sm text-gray-700">Monto Total (Bs)</label>
                            <input
                                v-model.number="form.monto_total"
                                type="number"
                                step="0.01"
                                class="border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm block mt-1 w-full"
                                required
                            />
                        </div>

                        <!-- Fecha de emisión -->
                        <div class="mb-4">
                            <label class="block font-medium text-sm text-gray-700">Fecha de Emisión</label>
                            <input
                                v-model="form.fecha_emision"
                                type="date"
                                class="border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm block mt-1 w-full"
                                required
                            />
                        </div>

                        <!-- Estado -->
                        <div class="mb-4">
                            <label class="block font-medium text-sm text-gray-700">Estado</label>
                            <select
                                v-model="form.estado"
                                class="border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm block mt-1 w-full"
                            >
                                <option value="pendiente">Pendiente</option>
                                <option value="pagada">Pagada</option>
                                <option value="anulada">Anulada</option>
                            </select>
                        </div>

                        <!-- Botones -->
                        <div class="flex justify-end gap-3">
                            <Link :href="route('facturas.index')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Cancelar
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded"
                            >
                                Actualizar Factura
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>