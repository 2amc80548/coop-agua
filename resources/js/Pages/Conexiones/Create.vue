<!-- Resources/js/Pages/Conexiones/Create.vue -->
<script setup>
 import { useForm } from '@inertiajs/vue3';

defineProps({
    beneficiarios: Array,
    errors: Object,
});

const form = useForm({
    beneficiario_id: '',
    codigo_medidor: '',
    direccion: '',
    zona: '',
    estado: 'activo',
    fecha_instalacion: new Date().toISOString().split('T')[0], // Formato: "2025-04-05"
    tipo_conexion: 'domiciliaria',
});

const submit = () => {
    form.post(route('conexiones.store'));
};
</script>

<template>
    <Layout title="Crear Conexión">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Crear Nueva Conexión
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <form @submit.prevent="submit">
                        <!-- Beneficiario -->
                        <div class="mb-4">
                            <label class="block font-medium text-sm text-gray-700">Beneficiario</label>
                            <select
                                v-model="form.beneficiario_id"
                                class="border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm block mt-1 w-full"
                                required
                            >
                                <option value="">-- Selecciona un beneficiario --</option>
                                <option
                                    v-for="b in beneficiarios"
                                    :key="b.id"
                                    :value="b.id"
                                >
                                    {{ b.nombre_completo }} (CI: {{ b.ci }})
                                </option>
                            </select>
                            <div v-if="errors.beneficiario_id" class="text-red-600 text-sm mt-1">{{ errors.beneficiario_id }}</div>
                        </div>

                        <!-- Código del medidor -->
                        <div class="mb-4">
                            <label class="block font-medium text-sm text-gray-700">Código del Medidor</label>
                            <input
                                v-model="form.codigo_medidor"
                                type="text"
                                class="border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm block mt-1 w-full"
                                required
                            />
                            <div v-if="errors.codigo_medidor" class="text-red-600 text-sm mt-1">{{ errors.codigo_medidor }}</div>
                        </div>

                        <!-- Dirección -->
                        <div class="mb-4">
                            <label class="block font-medium text-sm text-gray-700">Dirección</label>
                            <input
                                v-model="form.direccion"
                                type="text"
                                class="border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm block mt-1 w-full"
                                required
                            />
                        </div>

                        <!-- Zona -->
                        <div class="mb-4">
                            <label class="block font-medium text-sm text-gray-700">Zona (opcional)</label>
                            <input
                                v-model="form.zona"
                                type="text"
                                class="border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm block mt-1 w-full"
                            />
                        </div>

                        <!-- Estado -->
                        <div class="mb-4">
                            <label class="block font-medium text-sm text-gray-700">Estado</label>
                            <select v-model="form.estado" class="border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm block mt-1 w-full">
                                <option value="activo">Activo</option>
                                <option value="suspendido">Suspendido</option>
                                <option value="eliminado">Eliminado</option>
                            </select>
                        </div>

                        <!-- Fecha de instalación -->
                        <div class="mb-4">
                            <label class="block font-medium text-sm text-gray-700">Fecha de Instalación</label>
                            <input
                                v-model="form.fecha_instalacion"
                                type="date"
                                class="border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm block mt-1 w-full"
                                required
                            />
                        </div>

                        <!-- Tipo de conexión -->
                        <div class="mb-4">
                            <label class="block font-medium text-sm text-gray-700">Tipo de Conexión</label>
                            <select v-model="form.tipo_conexion" class="border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm block mt-1 w-full">
                                <option value="domiciliaria">Domiciliaria</option>
                                <option value="comercial">Comercial</option>
                                <option value="institucional">Institucional</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>

                        <!-- Botones -->
                        <div class="flex justify-end gap-3">
                            <Link :href="route('conexiones.index')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Cancelar
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                            >
                                Guardar Conexión
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </Layout>
</template>