<!-- Resources/js/Pages/Conexiones/Index.vue -->
<script setup>

import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';


defineProps({
    conexiones: Array,  
});
</script>

<template>
    <app-layout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Gestión de Conexiones
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <!-- Encabezado con botón -->
                    <div class="p-6 border-b border-gray-200">
                        <Link :href="route('conexiones.create')" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Nueva Conexión
                        </Link>
                    </div>

                    <!-- Tabla de conexiones -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medidor</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Beneficiario</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dirección</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="c in conexiones" :key="c.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ c.codigo_medidor }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ c.beneficiario?.nombre_completo || `${c.beneficiario?.nombre} ${c.beneficiario?.apellido}` }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ c.direccion }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            :class="{
                                                'px-2 inline-flex text-xs leading-5 font-semibold rounded-full':
                                                    true,
                                                'bg-green-100 text-green-800': c.estado === 'activo',
                                                'bg-yellow-100 text-yellow-800': c.estado === 'suspendido',
                                                'bg-red-100 text-red-800': c.estado === 'eliminado',
                                            }"
                                        >
                                            {{ c.estado }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ c.tipo_conexion }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <Link :href="route('conexiones.show', c.id)" class="text-blue-600 hover:text-blue-900 mr-3">Ver</Link>
                                        <Link :href="route('conexiones.edit', c.id)" class="text-yellow-600 hover:text-yellow-900 mr-3">Editar</Link>
                                        <Link
                                            :href="route('conexiones.destroy', c.id)"
                                            method="delete"
                                            as="button"
                                            type="button"
                                            class="text-red-600 hover:text-red-900"
                                            onclick="return confirm('¿Estás seguro de eliminar esta conexión?')"
                                        >
                                            Eliminar
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </app-layout>
</template>