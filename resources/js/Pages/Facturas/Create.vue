<!-- Resources/js/Pages/Facturas/Create.vue -->
<script setup>
import { useForm } from '@inertiajs/vue3';

defineProps({
    conexiones: Array,
    errors: Object,
});

const form = useForm({
    conexion_id: '',
    fecha_emision: new Date().toISOString().split('T')[0],
    fecha_vencimiento: '',
    monto_total: '',
    estado: 'pendiente',
});

const submit = () => {
    form.post(route('facturas.store'));
};
</script>

<template>
    <Layout title="Crear Factura">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Crear Nueva Factura
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <form @submit.prevent="submit">
                        <!-- Conexión -->
                        <div class="mb-4">
                            <label class="block font-medium text-sm text-gray-700">Conexión</label>
                            <select
                                v-model="form.conexion_id"
                                class="border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm block mt-1 w-full"
                                required
                            >
                                <option value="">-- Selecciona una conexión --</option>
                                <option
                                    v-for="c in conexiones"
                                    :key="c.id"
                                    :value="c.id"
                                >
                                    {{ c.beneficiario?.nombre_completo }} - Medidor: {{ c.codigo_medidor }} ({{ c.direccion }})
                                </option>
                            </select>
                            <div v-if="errors.conexion_id" class="text-red-600 text-sm mt-1">{{ errors.conexion_id }}</div>
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
                            <div v-if="errors.fecha_emision" class="text-red-600 text-sm mt-1">{{ errors.fecha_emision }}</div>
                        </div>

                        <!-- Fecha de vencimiento -->
                        <div class="mb-4">
                            <label class="block font-medium text-sm text-gray-700">Fecha de Vencimiento</label>
                            <input
                                v-model="form.fecha_vencimiento"
                                type="date"
                                class="border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm block mt-1 w-full"
                                required
                            />
                            <div v-if="errors.fecha_vencimiento" class="text-red-600 text-sm mt-1">{{ errors.fecha_vencimiento }}</div>
                        </div>

                        <!-- Monto total -->
                        <div class="mb-4">
                            <label class="block font-medium text-sm text-gray-700">Monto Total (Bs)</label>
                            <input
                                v-model="form.monto_total"
                                type="number"
                                step="0.01"
                                min="0"
                                class="border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm block mt-1 w-full"
                                required
                            />
                            <div v-if="errors.monto_total" class="text-red-600 text-sm mt-1">{{ errors.monto_total }}</div>
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
                            <div v-if="errors.estado" class="text-red-600 text-sm mt-1">{{ errors.estado }}</div>
                        </div>

                        <!-- Botones -->
                        <div class="flex justify-end gap-3">
                            <Link :href="route('facturas.index')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Cancelar
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                            >
                                Guardar Factura
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </Layout>
</template>