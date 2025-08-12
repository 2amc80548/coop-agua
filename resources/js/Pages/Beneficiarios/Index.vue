<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-6">Beneficiarios</h1>

    <!-- Botón crear -->
    <Link href="/beneficiarios/create" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 mb-6 inline-block">
      + Nuevo Beneficiario
    </Link>

    <!-- Mensaje flash -->
    <div v-if="$page.props.flash.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
      {{ $page.props.flash.success }}
    </div>

    <!-- Tabla -->
    <table class="min-w-full bg-white border border-gray-300 rounded">
      <thead class="bg-gray-50">
        <tr>
          <th class="py-3 px-4 text-left text-sm font-semibold">Nombre</th>
          <th class="py-3 px-4 text-left text-sm font-semibold">CI</th>
          <th class="py-3 px-4 text-left text-sm font-semibold">Teléfono</th>
          <th class="py-3 px-4 text-left text-sm font-semibold">Dirección</th>
          <th class="py-3 px-4 text-left text-sm font-semibold">Tipo</th>
          <th class="py-3 px-4 text-left text-sm font-semibold">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <!-- Si no hay beneficiarios, muestra mensaje -->
        <tr v-if="beneficiarios.length === 0">
          <td colspan="6" class="py-4 text-center text-gray-500">No hay beneficiarios registrados.</td>
        </tr>

        <tr v-for="b in beneficiarios" :key="b.id" class="border-t">
          <td class="py-3 px-4">{{ b.nombre_completo }}</td>
          <td class="py-3 px-4">{{ b.ci }}</td>
          <td class="py-3 px-4">{{ b.telefono }}</td>
          <td class="py-3 px-4">{{ b.direccion }}</td>
          <td class="py-3 px-4">
            <span :class="{
              'bg-green-100 text-green-800': b.tipo === 'socio',
              'bg-blue-100 text-blue-800': b.tipo === 'usuario'
            }" class="px-2 py-1 rounded text-xs font-medium">
              {{ b.tipo }}
            </span>
          </td>
          <td class="py-3 px-4 space-x-2">
            <Link :href="`/beneficiarios/${b.id}/edit`" class="text-yellow-600 hover:underline text-sm">Editar</Link>
            <button @click="confirmDelete(b.id)" class="text-red-600 hover:underline text-sm">Eliminar</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import axios from 'axios'; // ← Importa axios

const props = defineProps({
  beneficiarios: {
    type: Array,
    default: () => []
  }
});

const confirmDelete = (id) => {
  if (confirm('¿Estás seguro de eliminar este beneficiario? Esta acción no se puede deshacer.')) {
    axios.delete(`/beneficiarios/${id}`)
      .then(() => {
        // Recarga la página para ver cambios
        window.location.href = '/beneficiarios';
      })
      .catch(err => {
        alert('Error al eliminar: ' + (err.response?.data?.message || 'Verifica la consola'));
        console.error(err);
      });
  }
};
</script>