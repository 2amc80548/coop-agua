<!-- Resources/js/Pages/Lecturas/Edit.vue -->
<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    lectura: Object,
    conexiones: Array,
    errors: Object,
});

const form = useForm({
    conexion_id: props.lectura.conexion_id,
    fecha_lectura: props.lectura.fecha_lectura,
    lectura_anterior: props.lectura.lectura_anterior,
    lectura_actual: props.lectura.lectura_actual,
    observacion: props.lectura.observacion || '',
});

const submit = () => {
    form.put(route('lecturas.update', props.lectura.id));
};
</script>

<template>
    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Editar Lectura
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <form @submit.prevent="submit">
                        <!-- Conexión (solo lectura) -->
                        <div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-md">
                            <h3 class="text-sm font-medium text-gray-900">Conexión Asociada</h3>
                            <p class="mt-2">
                                <strong>Beneficiario:</strong>
                                {{ lectura.conexion?.beneficiario?.nombre_completo }}
                            </p>
                            <p>
                                <strong>Medidor:</strong>
                                {{ lectura.conexion?.codigo_medidor }}
                            </p>
                            <p>
                                <strong>Dirección:</strong>
                                {{ lectura.conexion?.direccion }}
                            </p>
                        </div>

                        <!-- Fecha de lectura -->
                        <div class="mb-4">
                            <label class="block font-medium text-sm text-gray-700">Fecha de Lectura</label>
                            <input
                                v-model="form.fecha_lectura"
                                type="date"
                                class="border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm block mt-1 w-full"
                                required
                            />
                            <div v-if="errors.fecha_lectura" class="text-red-600 text-sm mt-1">{{ errors.fecha_lectura }}</div>
                        </div>

                        <!-- Lectura anterior -->
                        <div class="mb-4">
                            <label class="block font-medium text-sm text-gray-700">Lectura Anterior (m³)</label>
                            <input
                                v-model.number="form.lectura_anterior"
                                type="number"
                                step="0.01"
                                class="border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm block mt-1 w-full"
                                required
                            />
                            <div v-if="errors.lectura_anterior" class="text-red-600 text-sm mt-1">{{ errors.lectura_anterior }}</div>
                        </div>

                        <!-- Lectura actual -->
                        <div class="mb-4">
                            <label class="block font-medium text-sm text-gray-700">Lectura Actual (m³)</label>
                            <input
                                v-model.number="form.lectura_actual"
                                type="number"
                                step="0.01"
                                :min="form.lectura_anterior"
                                class="border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm block mt-1 w-full"
                                required
                            />
                            <div v-if="errors.lectura_actual" class="text-red-600 text-sm mt-1">{{ errors.lectura_actual }}</div>
                        </div>

                        <!-- Consumo calculado -->
                        <div v-if="form.lectura_actual" class="mb-4 p-3 bg-green-50 rounded-md">
                            <label class="block font-medium text-sm text-gray-700">Consumo Registrado</label>
                            <p class="text-xl font-bold text-green-700">
                                {{ (form.lectura_actual - form.lectura_anterior).toFixed(2) }} m³
                            </p>
                        </div>

                        <!-- Observación -->
                        <div class="mb-4">
                            <label class="block font-medium text-sm text-gray-700">Observación</label>
                            <textarea
                                v-model="form.observacion"
                                class="border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm block mt-1 w-full"
                                rows="3"
                            ></textarea>
                        </div>

                        <!-- Botones -->
                        <div class="flex justify-end gap-3">
                            <Link :href="route('lecturas.index')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Cancelar
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded"
                            >
                                {{ form.processing ? 'Guardando...' : 'Actualizar Lectura' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>