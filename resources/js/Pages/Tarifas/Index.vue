<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
  tarifas: { type: Array, default: () => [] }
});
</script>

<template>
  <app-layout>
    <div class="p-6">
      <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold">Tarifas</h1>
        <Link href="/tarifas/create" class="bg-blue-600 text-white px-4 py-2 rounded">+ Nueva Tarifa</Link>
      </div>

      <div v-if="$page.props.flash?.success" class="bg-green-100 text-green-800 p-3 rounded mb-4">
        {{ $page.props.flash.success }}
      </div>

      <table class="min-w-full bg-white border rounded">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-2 text-left">Vigente desde</th>
            <th class="px-4 py-2 text-left">Vigente hasta</th>
            <th class="px-4 py-2 text-left">Activo</th>
            <th class="px-4 py-2 text-left">Min m³ / Mín</th>
            <th class="px-4 py-2 text-left">Precio m³</th>
            <th class="px-4 py-2 text-left">% Adulto mayor</th>
            <th class="px-4 py-2 text-left">Conceptos</th>
            <th class="px-4 py-2 text-left">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!tarifas.length">
            <td colspan="8" class="px-4 py-3 text-center text-gray-500">No hay tarifas.</td>
          </tr>
          <tr v-for="t in tarifas" :key="t.id" class="border-t">
            <td class="px-4 py-2">{{ t.vigente_desde }}</td>
            <td class="px-4 py-2">{{ t.vigente_hasta || '—' }}</td>
            <td class="px-4 py-2">
              <span :class="t.activo ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700'" class="px-2 py-1 rounded text-xs">
                {{ t.activo ? 'Sí' : 'No' }}
              </span>
            </td>
            <td class="px-4 py-2">{{ t.min_m3 }} / {{ t.min_monto }}</td>
            <td class="px-4 py-2">{{ t.precio_m3 }}</td>
            <td class="px-4 py-2">{{ t.descuento_adulto_mayor_pct }}%</td>
            <td class="px-4 py-2">{{ t.conceptos_count }}</td>
            <td class="px-4 py-2">
              <Link :href="`/tarifas/${t.id}/edit`" class="text-blue-600 hover:underline">Editar</Link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </app-layout>
</template>
 

