<!-- Resources/js/Pages/Lecturas/Show.vue -->
<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    lectura: Object,
});
</script>

<template>
    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detalle de Lectura
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <!-- Información de la conexión -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">
                            Datos de la Conexión
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <p><strong>Beneficiario:</strong> {{ lectura.conexion?.beneficiario?.nombre_completo }}</p>
                            <p><strong>Medidor:</strong> {{ lectura.conexion?.codigo_medidor }}</p>
                            <p><strong>Dirección:</strong> {{ lectura.conexion?.direccion }}</p>
                            <p>
                                <strong>Estado:</strong>
                                <span
                                    :class="{
                                        'px-2 py-1 text-xs rounded': true,
                                        'bg-green-100 text-green-800': lectura.conexion?.estado === 'activo',
                                        'bg-yellow-100 text-yellow-800': lectura.conexion?.estado === 'suspendido',
                                        'bg-red-100 text-red-800': lectura.conexion?.estado === 'eliminado',
                                    }"
                                >
                                    {{ lectura.conexion?.estado }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Datos de la lectura -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">
                            Detalles de la Lectura
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <p><strong>Fecha de Lectura:</strong> {{ new Date(lectura.fecha_lectura).toLocaleDateString() }}</p>
                            <p><strong>Lectura Anterior:</strong> {{ lectura.lectura_anterior }} m³</p>
                            <p><strong>Lectura Actual:</strong> {{ lectura.lectura_actual }} m³</p>
                            <p>
                                <strong>Consumo:</strong>
                                <span class="font-bold text-blue-700">
                                    {{ (lectura.lectura_actual - lectura.lectura_anterior).toFixed(2) }} m³
                                </span>
                            </p>
                            <p><strong>Registrado por:</strong> {{ lectura.usuarioRegistrado?.name }}</p>
                            <p v-if="lectura.observacion"><strong>Observación:</strong> {{ lectura.observacion }}</p>
                        </div>
                    </div>

                    <!-- Factura asociada -->
                    <div v-if="lectura.factura" class="mb-8 p-4 bg-green-50 border border-green-200 rounded-md">
                        <h3 class="text-lg font-medium text-green-900">Factura Generada</h3>
                        <p><strong>N° Factura:</strong> F-{{ lectura.factura.id.toString().padStart(6, '0') }}</p>
                        <p><strong>Monto Total:</strong> Bs {{ lectura.factura.monto_total.toFixed(2) }}</p>
                        <p><strong>Estado:</strong>
                            <span
                                :class="{
                                    'px-2 py-1 text-xs rounded': true,
                                    'bg-yellow-100 text-yellow-800': lectura.factura.estado === 'pendiente',
                                    'bg-green-100 text-green-800': lectura.factura.estado === 'pagada',
                                    'bg-red-100 text-red-800': lectura.factura.estado === 'anulada',
                                }"
                            >
                                {{ lectura.factura.estado }}
                            </span>
                        </p>
                        <p><strong>Fecha de Emisión:</strong> {{ new Date(lectura.factura.fecha_emision).toLocaleDateString() }}</p>
                        <div class="mt-3">
                            <Link
                                :href="route('facturas.show', lectura.factura.id)"
                                class="text-blue-600 hover:text-blue-900 font-medium"
                            >
                                Ver Factura
                            </Link>
                        </div>
                    </div>

                    <!-- Sin factura -->
                    <div v-else class="mb-8 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                        <p class="text-yellow-800">No se ha generado factura para esta lectura.</p>
                    </div>

                    <!-- Botón volver -->
                    <div class="flex justify-end">
                        <Link :href="route('lecturas.index')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            Volver al Listado
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>