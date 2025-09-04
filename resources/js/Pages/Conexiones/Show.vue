<!-- Resources/js/Pages/Conexiones/Show.vue -->
<script setup>
import { Link } from '@inertia/inertia-vue3';
import Layout from '@/Layouts/AppLayout.vue';

defineProps({
    conexion: Object,
});
</script>

<template>
    <Layout title="Detalles de Conexión">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detalles de la Conexión
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <!-- Información general -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Información de la Conexión</h3>
                            <dl class="space-y-2">
                                <div><dt class="text-sm font-medium text-gray-500">Código del Medidor</dt><dd>{{ conexion.codigo_medidor }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">Dirección</dt><dd>{{ conexion.direccion }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">Zona</dt><dd>{{ conexion.zona || '—' }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">Estado</dt><dd>
                                    <span
                                        :class="{
                                            'px-2 py-1 text-xs font-semibold rounded-full':
                                                true,
                                            'bg-green-100 text-green-800': conexion.estado === 'activo',
                                            'bg-yellow-100 text-yellow-800': conexion.estado === 'suspendido',
                                            'bg-red-100 text-red-800': conexion.estado === 'eliminado',
                                        }"
                                    >
                                        {{ conexion.estado }}
                                    </span>
                                </dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">Tipo de Conexión</dt><dd>{{ conexion.tipo_conexion }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">Fecha de Instalación</dt><dd>{{ new Date(conexion.fecha_instalacion).toLocaleDateString() }}</dd></div>
                            </dl>
                        </div>

                        <!-- Beneficiario -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Beneficiario</h3>
                            <dl class="space-y-2">
                                <div><dt class="text-sm font-medium text-gray-500">Nombre</dt><dd>{{ conexion.beneficiario?.nombre_completo || `${conexion.beneficiario?.nombre} ${conexion.beneficiario?.apellido}` }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">CI</dt><dd>{{ conexion.beneficiario?.ci || '—' }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">Teléfono</dt><dd>{{ conexion.beneficiario?.telefono || '—' }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">Email</dt><dd>{{ conexion.beneficiario?.email || '—' }}</dd></div>
                            </dl>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="flex justify-end gap-3">
                        <Link :href="route('conexiones.index')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            Volver
                        </Link>
                        <Link :href="route('conexiones.edit', conexion.id)" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Editar
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>