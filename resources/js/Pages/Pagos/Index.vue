<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  pagos: {
    type: Array,
    default: () => []
  }
});
</script>

<template>
  <AppLayout>
    <div class="p-6">
      <h1 class="text-2xl font-bold mb-6">Historial de Pagos</h1>

      <!-- Resumen -->
      <div class="bg-gray-50 p-4 rounded mb-6 border border-gray-200">
        <p class="text-sm text-gray-600">
          Total de pagos registrados: <strong>{{ pagos.length }}</strong>
        </p>
      </div>

      <!-- Mensaje flash (opcional) -->
      <div v-if="$page.props.flash.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        {{ $page.props.flash.success }}
      </div>

      <!-- Tabla -->
      <table class="min-w-full bg-white border border-gray-300 rounded">
        <thead class="bg-gray-50">
          <tr>
            <th class="py-3 px-4 text-left text-sm font-semibold">Factura</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Beneficiario</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Monto</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Forma</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Fecha Pago</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Registrado Por</th>
          </tr>
        </thead>
        <tbody>
          <!-- Si no hay pagos -->
          <tr v-if="pagos.length === 0">
            <td colspan="6" class="py-4 text-center text-gray-500">No hay pagos registrados.</td>
          </tr>

          <tr v-for="p in pagos" :key="p.id" class="border-t">
            <td class="py-3 px-4">
              <Link :href="route('facturas.show', p.factura_id)" class="text-blue-600 hover:underline text-sm">
                F-{{ p.factura_id.toString().padStart(6, '0') }}
              </Link>
            </td>
            <td class="py-3 px-4">{{ p.factura?.conexion?.beneficiario?.nombre_completo }}</td>
            <td class="py-3 px-4 font-medium">Bs {{ Number(p.monto_pagado).toFixed(2) }}</td>
            <td class="py-3 px-4">
              <span class="px-2 py-1 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                {{ p.forma_pago }}
              </span>
            </td>
            <td class="py-3 px-4">{{ new Date(p.fecha_pago).toLocaleDateString() }}</td>
            <td class="py-3 px-4">{{ p.usuarioRegistrado?.name }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppLayout>
</template>