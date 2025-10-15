<!-- Resources/js/Pages/Facturas/Show.vue -->
<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    factura: Object,
});
</script>

<template>
    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Factura F-{{ factura.id.toString().padStart(6, '0') }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <!-- Información de la conexión -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">
                            Datos del Beneficiario
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <p><strong>Beneficiario:</strong> {{ factura.conexion?.beneficiario?.nombre_completo }}</p>
                            <p><strong>Medidor:</strong> {{ factura.conexion?.codigo_medidor }}</p>
                            <p><strong>Dirección:</strong> {{ factura.conexion?.direccion }}</p>
                            <p>
                                <strong>Estado:</strong>
                                <span
                                    :class="{
                                        'px-2 py-1 text-xs rounded': true,
                                        'bg-green-100 text-green-800': factura.conexion?.estado === 'activo',
                                        'bg-yellow-100 text-yellow-800': factura.conexion?.estado === 'suspendido',
                                    }"
                                >
                                    {{ factura.conexion?.estado }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Detalles de la factura -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">
                            Detalles de la Factura
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <p><strong>N° Factura:</strong> F-{{ factura.id.toString().padStart(6, '0') }}</p>
                            <p><strong>Fecha de Emisión:</strong> {{ new Date(factura.fecha_emision).toLocaleDateString() }}</p>
                            <p><strong>Consumo:</strong> {{ Number(factura.consumo_m3).toFixed(2) }} m³</p>
                            <p><strong>Monto Total:</strong> <span class="font-bold">Bs {{ Number(factura.monto_total).toFixed(2) }}</span></p>
                            <p>
                                <strong>Estado:</strong>
                                <span
                                    :class="{
                                        'px-2 py-1 text-xs rounded': true,
                                        'bg-yellow-100 text-yellow-800': factura.estado === 'pendiente',
                                        'bg-green-100 text-green-800': factura.estado === 'pagada',
                                        'bg-red-100 text-red-800': factura.estado === 'anulada',
                                    }"
                                >
                                    {{ factura.estado }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Lectura asociada -->
                    <div v-if="factura.lectura" class="mb-8 p-4 bg-blue-50 border border-blue-200 rounded-md">
                        <h3 class="text-lg font-medium text-blue-900">Lectura Asociada</h3>
                        <p><strong>Fecha de Lectura:</strong> {{ new Date(factura.lectura.fecha_lectura).toLocaleDateString() }}</p>
                        <p><strong>Lectura Anterior:</strong> {{ factura.lectura.lectura_anterior }} m³</p>
                        <p><strong>Lectura Actual:</strong> {{ factura.lectura.lectura_actual }} m³</p>
                        <p><strong>Registrada por:</strong> {{ factura.lectura.usuarioRegistrado?.name }}</p>
                    </div>

                    <!-- Pagos registrados -->
                    <div v-if="factura.pagos && factura.pagos.length > 0" class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900">Pagos Registrados</h3>
                        <table class="min-w-full divide-y divide-gray-200 mt-2">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Forma</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="pago in factura.pagos" :key="pago.id">
                                    <td class="px-4 py-2 text-sm">{{ new Date(pago.fecha_pago).toLocaleDateString() }}</td>
                                    <td class="px-4 py-2 text-sm">Bs {{ Number(pago.monto_pagado).toFixed(2) }}</td>
                                    <td class="px-4 py-2 text-sm">{{ pago.forma_pago }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Botones -->
                    <div class="flex justify-between">
                        <Link :href="route('facturas.index')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            Volver
                        </Link>
                        <Link
                            :href="route('pagos.create', { factura_id: factura.id })"
                            class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                        >
                            Registrar Pago
                        </Link>>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>