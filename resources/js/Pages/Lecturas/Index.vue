<script setup>
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  lecturas: {
    type: Array,
    default: () => []
  }
});

const confirmDelete = (id) => {
  if (confirm('¿Estás seguro de eliminar esta lectura? Esta acción no se puede deshacer.')) {
    axios.delete(`/lecturas/${id}`)
      .then(() => {
        window.location.href = '/lecturas';
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
      <h1 class="text-2xl font-bold mb-6">Gestión de Lecturas</h1>

      <!-- Botón crear -->
      <Link :href="route('lecturas.create')" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 mb-6 inline-block">
        + Nueva Lectura
      </Link>

      <!-- Mensaje flash -->
      <div v-if="$page.props.flash.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        {{ $page.props.flash.success }}
      </div>

      <!-- Tabla -->
      <table class="min-w-full bg-white border border-gray-300 rounded">
        <thead class="bg-gray-50">
          <tr>
            <th class="py-3 px-4 text-left text-sm font-semibold">Medidor</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Beneficiario</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Anterior</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Actual</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Consumo</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Fecha</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Registrado</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Factura</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <!-- Si no hay lecturas -->
          <tr v-if="lecturas.length === 0">
            <td colspan="9" class="py-4 text-center text-gray-500">No hay lecturas registradas.</td>
          </tr>

          <tr v-for="l in lecturas" :key="l.id" class="border-t">
            <td class="py-3 px-4">{{ l.conexion?.codigo_medidor }}</td>
            <td class="py-3 px-4">{{ l.conexion?.beneficiario?.nombre_completo }}</td>
            <td class="py-3 px-4">{{ l.lectura_anterior }}</td>
            <td class="py-3 px-4">{{ l.lectura_actual }}</td>
            <td class="py-3 px-4 font-medium text-green-700">
              {{ l.lectura_actual - l.lectura_anterior }} m³
            </td>
            <td class="py-3 px-4">
              {{ new Date(l.fecha_lectura).toLocaleDateString() }}
            </td>
            <td class="py-3 px-4">{{ l.usuarioRegistrado?.name }}</td>
            <td class="py-3 px-4">
              <span v-if="l.factura" class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                F-{{ l.factura.id.toString().padStart(6, '0') }}
              </span>
              <span v-else class="px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                Pendiente
              </span>
            </td>
            <td class="py-3 px-4 space-x-2">
              <Link :href="route('lecturas.show', l.id)" class="text-blue-600 hover:underline text-sm">Ver</Link>
              <!-- <Link :href="route('lecturas.edit', l.id)" class="text-yellow-600 hover:underline text-sm">Editar</Link> -->
              <button @click="confirmDelete(l.id)" class="text-red-600 hover:underline text-sm">Eliminar</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppLayout>
</template>