<script setup>
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  conexiones: {
    type: Array,
    default: () => []
  }
});

const confirmDelete = (id) => {
  if (confirm('¿Estás seguro de eliminar esta conexión? Esta acción no se puede deshacer.')) {
    axios.delete(`/conexiones/${id}`)
      .then(() => {
        window.location.href = '/conexiones';
      })
      .catch(err => {
        alert('Error al eliminar: ' + (err.response?.data?.message || 'Verifica la consola'));
        console.error(err);
      });
  }
};
</script>
<template>
    <AppLayout>
      <div class="p-6">
        <h1 class="text-2xl font-bold mb-6">Gestión de Conexiones</h1>
  
        <!-- Botón crear -->
        <Link :href="route('conexiones.create')" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 mb-6 inline-block">
          + Nueva Conexión
        </Link>
  
        <!-- Mensaje flash (opcional, si lo usas en el backend) -->
        <div v-if="$page.props.flash.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
          {{ $page.props.flash.success }}
        </div>
  
        <!-- Tabla -->
        <table class="min-w-full bg-white border border-gray-300 rounded">
          <thead class="bg-gray-50">
            <tr>
              <th class="py-3 px-4 text-left text-sm font-semibold">Medidor</th>
              <th class="py-3 px-4 text-left text-sm font-semibold">Beneficiario</th>
              <th class="py-3 px-4 text-left text-sm font-semibold">Dirección</th>
              <th class="py-3 px-4 text-left text-sm font-semibold">Estado</th>
              <th class="py-3 px-4 text-left text-sm font-semibold">Tipo</th>
              <th class="py-3 px-4 text-left text-sm font-semibold">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <!-- Si no hay conexiones -->
            <tr v-if="conexiones.length === 0">
              <td colspan="6" class="py-4 text-center text-gray-500">No hay conexiones registradas.</td>
            </tr>
  
            <tr v-for="c in conexiones" :key="c.id" class="border-t">
              <td class="py-3 px-4">{{ c.codigo_medidor }}</td>
              <td class="py-3 px-4">
                {{ c.beneficiario?.nombre_completo || `${c.beneficiario?.nombre || ''} ${c.beneficiario?.apellido || ''}` }}
              </td>
              <td class="py-3 px-4">{{ c.direccion }}</td>
              <td class="py-3 px-4">
                <span
                  :class="{
                    'bg-green-100 text-green-800': c.estado === 'activo',
                    'bg-yellow-100 text-yellow-800': c.estado === 'suspendido',
                    'bg-red-100 text-red-800': c.estado === 'eliminado',
                  }"
                  class="px-2 py-1 rounded text-xs font-medium"
                >
                  {{ c.estado }}
                </span>
              </td>
              <td class="py-3 px-4">{{ c.tipo_conexion }}</td>
              <td class="py-3 px-4 space-x-2">
                <Link :href="route('conexiones.show', c.id)" class="text-blue-600 hover:underline text-sm">Ver</Link>
                <Link :href="route('conexiones.edit', c.id)" class="text-yellow-600 hover:underline text-sm">Editar</Link>
                <button @click="confirmDelete(c.id)" class="text-red-600 hover:underline text-sm">Eliminar</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AppLayout>
  </template>
  
