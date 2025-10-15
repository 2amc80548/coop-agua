<script setup>
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  facturas: {
    type: Array,
    default: () => []
  }
});

const confirmDelete = (id) => {
  if (confirm('¿Estás seguro de eliminar esta factura? Esta acción no se puede deshacer.')) {
    axios.delete(`/facturas/${id}`)
      .then(() => {
        window.location.href = '/facturas';
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
      <h1 class="text-2xl font-bold mb-6">Gestión de Facturas</h1>

      <!-- Mensaje flash -->
      <div v-if="$page.props.flash.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        {{ $page.props.flash.success }}
      </div>

      <!-- Tabla -->
      <table class="min-w-full bg-white border border-gray-300 rounded">
        <thead class="bg-gray-50">
          <tr>
            <th class="py-3 px-4 text-left text-sm font-semibold">N° Factura</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Beneficiario</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Medidor</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Emisión</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Vencimiento</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Monto</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Estado</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <!-- Si no hay facturas -->
          <tr v-if="facturas.length === 0">
            <td colspan="8" class="py-4 text-center text-gray-500">No hay facturas registradas.</td>
          </tr>

          <tr v-for="f in facturas" :key="f.id" class="border-t">
            <td class="py-3 px-4">F-{{ f.id.toString().padStart(6, '0') }}</td>
            <td class="py-3 px-4">{{ f.conexion?.beneficiario?.nombre_completo }}</td>
            <td class="py-3 px-4">{{ f.conexion?.codigo_medidor }}</td>
            <td class="py-3 px-4">{{ new Date(f.fecha_emision).toLocaleDateString() }}</td>
            <td class="py-3 px-4">{{ new Date(f.fecha_vencimiento).toLocaleDateString() }}</td>
            <td class="py-3 px-4 font-medium">Bs {{ f.monto_total.toFixed(2) }}</td>
            <td class="py-3 px-4">
              <span
                :class="{
                  'bg-yellow-100 text-yellow-800': f.estado === 'pendiente',
                  'bg-green-100 text-green-800': f.estado === 'pagada',
                  'bg-red-100 text-red-800': f.estado === 'anulada',
                }"
                class="px-2 py-1 rounded text-xs font-medium"
              >
                {{ f.estado }}
              </span>
            </td>
            <td class="py-3 px-4 space-x-2">
              <Link :href="route('facturas.show', f.id)" class="text-blue-600 hover:underline text-sm">Ver</Link>
              <Link :href="route('facturas.edit', f.id)" class="text-yellow-600 hover:underline text-sm">Editar</Link>
              <button @click="confirmDelete(f.id)" class="text-red-600 hover:underline text-sm">Eliminar</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppLayout>
</template>