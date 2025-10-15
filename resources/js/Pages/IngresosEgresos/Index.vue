<script setup>
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  registros: {
    type: Array,
    default: () => []
  },
  filters: {
    type: Object,
    default: () => ({})
  }
});
</script>

<template>
  <AppLayout>
    <div class="p-6">
      <h1 class="text-2xl font-bold mb-6">Registro de Ingresos y Egresos</h1>

      <!-- Botón crear -->
      <Link href="/IngresosEgresos/create" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 mb-6 inline-block">
        + Nuevo Registro
      </Link>

      <!-- Mensaje flash -->
      <div v-if="$page.props.flash.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        {{ $page.props.flash.success }}
      </div>

      <!-- Tabla -->
      <table class="min-w-full bg-white border border-gray-300 rounded">
        <thead class="bg-gray-50">
          <tr>
            <th class="py-3 px-4 text-left text-sm font-semibold">Tipo</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Categoría</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Descripción</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Monto</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Fecha</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Registrado por</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <!-- Si no hay registros -->
          <tr v-if="registros.length === 0">
            <td colspan="7" class="py-4 text-center text-gray-500">No hay registros de ingresos o egresos.</td>
          </tr>

          <tr v-for="r in registros" :key="r.id" class="border-t">
            <td class="py-3 px-4">
              <span
                :class="{
                  'bg-green-100 text-green-800': r.tipo === 'ingreso',
                  'bg-red-100 text-red-800': r.tipo === 'egreso'
                }"
                class="px-2 py-1 rounded text-xs font-medium"
              >
                {{ r.tipo === 'ingreso' ? 'Ingreso' : 'Egreso' }}
              </span>
            </td>
            <td class="py-3 px-4">{{ r.categoria }}</td>
            <td class="py-3 px-4">{{ r.descripcion || '—' }}</td>
            <td class="py-3 px-4 font-medium" :class="r.tipo === 'ingreso' ? 'text-green-700' : 'text-red-700'">
              Bs {{ Number(r.monto).toFixed(2) }}
            </td>
            <td class="py-3 px-4">{{ new Date(r.fecha).toLocaleDateString() }}</td>
            <td class="py-3 px-4">{{ r.usuarioRegistrado?.name || '—' }}</td>
            <td class="py-3 px-4 space-x-2">
              <Link :href="`/IngresosEgresos/${r.id}`" class="text-blue-600 hover:underline text-sm">Ver</Link>
              <Link :href="`/IngresosEgresos/${r.id}/edit`" class="text-yellow-600 hover:underline text-sm">Editar</Link>
              <button @click="confirmDelete(r.id)" class="text-red-600 hover:underline text-sm">Eliminar</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppLayout>
</template>

<script>
export default {
  methods: {
    confirmDelete(id) {
      if (confirm('¿Estás seguro de eliminar este registro? Esta acción no se puede deshacer.')) {
        axios.delete(`/ingreso-egreso/${id}`)
          .then(() => {
            window.location.href = '/ingreso-egreso';
          })
          .catch(err => {
            alert('Error al eliminar: ' + (err.response?.data?.message || 'Verifica la consola'));
            console.error(err);
          });
      }
    }
  }
}
</script>