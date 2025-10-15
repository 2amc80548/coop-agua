<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  registro: {
    type: Object,
    required: true
  }
});
</script>

<template>
  <AppLayout>
    <div class="p-6">
      <h1 class="text-2xl font-bold mb-6">Detalle del Registro</h1>

      <!-- Botones -->
      <div class="mb-6 flex space-x-3">
        <Link :href="`/IngresosEgresos/${registro.id}/edit`" class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700">
          Editar
        </Link>
        <Link href="/IngresosEgresos" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
          Volver
        </Link>
      </div>

      <!-- Detalle en tarjeta -->
      <div class="bg-white p-6 rounded shadow border">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <p class="text-sm text-gray-500">Tipo</p>
            <p class="text-lg font-medium">
              <span
                :class="{
                  'bg-green-100 text-green-800': registro.tipo === 'ingreso',
                  'bg-red-100 text-red-800': registro.tipo === 'egreso'
                }"
                class="px-2 py-1 rounded text-xs font-medium"
              >
                {{ registro.tipo === 'ingreso' ? 'Ingreso' : 'Egreso' }}
              </span>
            </p>
          </div>

          <div>
            <p class="text-sm text-gray-500">Categoría</p>
            <p class="text-lg font-medium">{{ registro.categoria }}</p>
          </div>

          <div>
            <p class="text-sm text-gray-500">Descripción</p>
            <p class="text-lg font-medium">{{ registro.descripcion || '—' }}</p>
          </div>

          <div>
            <p class="text-sm text-gray-500">Monto</p>
            <p class="text-xl font-bold" :class="registro.tipo === 'ingreso' ? 'text-green-700' : 'text-red-700'">
              Bs {{ Number(registro.monto).toFixed(2) }}
            </p>
          </div>

          <div>
            <p class="text-sm text-gray-500">Fecha</p>
            <p class="text-lg font-medium">{{ new Date(registro.fecha).toLocaleDateString() }}</p>
          </div>

          <div>
            <p class="text-sm text-gray-500">Registrado por</p>
            <p class="text-lg font-medium">{{ registro.usuarioRegistrado?.name || '—' }}</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>