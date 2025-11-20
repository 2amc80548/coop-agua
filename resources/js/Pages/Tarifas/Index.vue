<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import ViewCounter from '@/Components/ViewCounter.vue';

const props = defineProps({
  tarifas: { type: Array, default: () => [] },
});

// Para mostrar el tipo de conexión con un texto más bonito
const tipoConexionLabel = (tipo) => {
  switch (tipo) {
    case 'domiciliaria':
      return 'Domiciliaria';
    case 'comercial':
      return 'Comercial';
    case 'institucional':
      return 'Institucional';
    case 'otro':
      return 'Otro';
    default:
      return tipo || '—';
  }
};

const page = usePage();
</script>

<template>
  <AppLayout title="Tarifas">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        Tarifas
      </h2>
    </template>

    <div class="py-8">
      <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-4">
          <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            Listado de Tarifas
          </h1>
          <Link
            :href="route('tarifas.create')"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow text-sm font-semibold transition"
          >
            + Nueva Tarifa
          </Link>
        </div>

        <!-- Flash success -->
        <div
          v-if="page.props.flash?.success"
          class="bg-green-100 text-green-800 border border-green-300 px-4 py-3 rounded mb-4 text-sm"
        >
          {{ page.props.flash.success }}
        </div>

        <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg overflow-hidden">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-200">
              <tr>
                <th class="px-4 py-2 text-left">Vigente desde</th>
                <th class="px-4 py-2 text-left">Vigente hasta</th>
                <th class="px-4 py-2 text-left">Activo</th>
                <!-- NUEVA COLUMNA -->
                <th class="px-4 py-2 text-left">Tipo conexión</th>
                <th class="px-4 py-2 text-left">Min m³ / Mín</th>
                <th class="px-4 py-2 text-left">Precio m³</th>
                <th class="px-4 py-2 text-left">% Adulto mayor</th>
                <th class="px-4 py-2 text-left">Conceptos</th>
                <th class="px-4 py-2 text-left">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-if="!tarifas.length">
                <td
                  colspan="9"
                  class="px-4 py-3 text-center text-gray-500 dark:text-gray-400"
                >
                  No hay tarifas registradas.
                </td>
              </tr>

              <tr
                v-for="t in tarifas"
                :key="t.id"
                class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900 transition"
              >
                <td class="px-4 py-2 text-gray-800 dark:text-gray-100">
                  {{ t.vigente_desde }}
                </td>
                <td class="px-4 py-2 text-gray-800 dark:text-gray-100">
                  {{ t.vigente_hasta || '—' }}
                </td>
                <td class="px-4 py-2">
                  <span
                    class="px-2 py-1 rounded text-xs font-semibold"
                    :class="
                      t.activo
                        ? 'bg-green-100 text-green-800'
                        : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                    "
                  >
                    {{ t.activo ? 'Sí' : 'No' }}
                  </span>
                </td>

                <!-- NUEVA COLUMNA: tipo_conexion -->
                <td class="px-4 py-2 text-gray-800 dark:text-gray-100">
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900 dark:text-blue-200">
                    {{ tipoConexionLabel(t.tipo_conexion) }}
                  </span>
                </td>

                <td class="px-4 py-2 text-gray-800 dark:text-gray-100">
                  {{ t.min_m3 }} / {{ t.min_monto }}
                </td>
                <td class="px-4 py-2 text-gray-800 dark:text-gray-100">
                  {{ t.precio_m3 }}
                </td>
                <td class="px-4 py-2 text-gray-800 dark:text-gray-100">
                  {{ t.descuento_adulto_mayor_pct }}%
                </td>
                <td class="px-4 py-2 text-gray-800 dark:text-gray-100">
                  {{ t.conceptos_count }}
                </td>
                <td class="px-4 py-2">
                  <Link
                    :href="route('tarifas.edit', t.id)"
                    class="text-blue-600 dark:text-blue-400 hover:underline text-sm"
                  >
                    Editar
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <ViewCounter />
    </div>
  </AppLayout>
</template>
