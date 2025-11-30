<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import ViewCounter from '@/Components/ViewCounter.vue';

const props = defineProps({
  tarifas: { type: Array, default: () => [] },
});

const page = usePage();

// --- Helpers Visuales ---

// 1. Formateo de fechas para quitar la hora y formato largo
const formatDate = (dateString) => {
    if (!dateString) return '—';
    try {
        const date = new Date(dateString);
        // Ajuste de zona horaria simple para visualización
        date.setMinutes(date.getMinutes() + date.getTimezoneOffset());
        return date.toLocaleDateString('es-BO', { 
            day: '2-digit', 
            month: '2-digit', 
            year: 'numeric' 
        });
    } catch {
        return dateString;
    }
};

// 2. Etiquetas para tipos de conexión
const tipoConexionLabel = (tipo) => {
  switch (tipo) {
    case 'domiciliaria': return 'Domiciliaria';
    case 'comercial': return 'Comercial';
    case 'institucional': return 'Institucional';
    case 'otro': return 'Otro';
    default: return tipo || '—';
  }
};

// 3. Estilos para el estado (Activo / Inactivo)
const estadoClass = (activo) => {
    return activo 
        ? 'bg-green-100 text-green-800' 
        : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
};

// --- Acciones ---
const confirmDelete = (tarifa) => {
  if (confirm(`¿Eliminar esta tarifa? Esta acción no se puede deshacer.`)) {
    router.delete(route('tarifas.destroy', tarifa.id), {
      preserveScroll: true,
      onError: () => alert('No se pudo eliminar la tarifa. Verifique que no tenga usos asociados.'),
    });
  }
};
</script>

<template>
  <AppLayout title="Tarifas">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        Gestión de Tarifas
      </h2>
    </template>

    <div class="p-4 md:p-6">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-700 dark:text-gray-300">Listado de Tarifas</h1>
        <Link 
            :href="route('tarifas.create')" 
            class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 transition duration-150"
        >
          + Nueva Tarifa
        </Link>
      </div>

      <div v-if="page.props.flash.success" class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-4 shadow-sm" role="alert">
         <p class="font-bold">Éxito</p> <p>{{ page.props.flash.success }}</p>
      </div>
      
      <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded shadow-md">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Vigencia</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tipo Conexión</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Consumo Mín / Precio</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Conceptos</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-if="!tarifas.length">
                <td colspan="6" class="py-4 px-4 text-center text-sm text-gray-500 dark:text-gray-300">
                    No hay tarifas registradas.
                </td>
            </tr>

            <tr v-for="t in tarifas" :key="t.id" class="hover:bg-gray-50 dark:hover:bg-gray-700 text-sm">
              
              <td class="px-4 py-2 whitespace-nowrap">
                <div class="font-medium text-gray-900 dark:text-white">
                    Desde: {{ formatDate(t.vigente_desde) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Hasta: {{ formatDate(t.vigente_hasta) }}
                </div>
              </td>

              <td class="px-4 py-2 whitespace-nowrap">
                <span :class="estadoClass(t.activo)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                  {{ t.activo ? 'Activo' : 'Inactivo' }}
                </span>
              </td>

              <td class="px-4 py-2 whitespace-nowrap">
                 <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900 dark:text-blue-200">
                    {{ tipoConexionLabel(t.tipo_conexion) }}
                 </span>
              </td>

              <td class="px-4 py-2 whitespace-nowrap">
                <div class="text-gray-900 dark:text-white">Min: {{ t.min_m3 }}m³ / {{ t.min_monto }}Bs</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Precio: {{ t.precio_m3 }}Bs/m³</div>
                <div v-if="t.descuento_adulto_mayor_pct > 0" class="text-xs text-green-600 font-bold">
                    Desc. Adulto Mayor: {{ t.descuento_adulto_mayor_pct }}%
                </div>
              </td>

              <td class="px-4 py-2 whitespace-nowrap text-gray-700 dark:text-gray-300">
                {{ t.conceptos_count }} ítems
              </td>

              <td class="px-4 py-2 whitespace-nowrap text-sm font-medium space-x-2">
                <Link 
                    :href="route('tarifas.edit', t.id)" 
                    class="text-indigo-600 hover:text-indigo-900" 
                    title="Editar Tarifa"
                >
                    Editar
                </Link>
                
                <button 
                    @click="confirmDelete(t)" 
                    class="text-red-600 hover:text-red-900" 
                    title="Eliminar Tarifa"
                >
                    Eliminar
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="mt-6">
        <ViewCounter />
      </div>
      
    </div>
  </AppLayout>
</template>