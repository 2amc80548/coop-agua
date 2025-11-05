<script setup>
import { Link, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue'; // Ajusta la ruta a tu Layout
import { ref, watch, computed } from 'vue'; // Añadimos computed y usePage

// Props recibidas del controlador (index)
const props = defineProps({
    lecturas: { type: Object, default: () => ({ data: [] }) }, // Objeto paginado
    filters: { type: Object, default: () => ({}) },          // Filtros actuales
    periodos: { type: Array, default: () => [] }             // Lista de períodos para el dropdown
});

// Para mostrar el botón de Imprimir Aviso si venimos de 'store'
const page = usePage();
const lecturaRecienteId = computed(() => page.props.flash.lectura_id_reciente);

// Formulario para los filtros
const filterForm = useForm({
  search: props.filters.search ?? '',
  periodo: props.filters.periodo ?? '',
  estado: props.filters.estado ?? '',
});

// --- Lógica de Filtros Rápida ---
let searchTimeout = null;
const submitFilters = () => {
  filterForm.get(route('lecturas.index'), {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
};
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(submitFilters, 300);
};
watch(() => [filterForm.periodo, filterForm.estado], submitFilters);
// --- Fin Lógica Filtros ---

// Lógica para Eliminar (si la necesitas)
const confirmDelete = (id) => {
    if (confirm('¿Estás seguro de eliminar esta lectura? Esta acción es irreversible.')) {
        router.delete(route('lecturas.destroy', id), {
            preserveScroll: true,
            onError: (errors) => {
                 const message = errors.error_general || 'No se pudo eliminar la lectura (puede que ya esté facturada).';
                 alert(`Error: ${message}`);
                 console.error("Error al eliminar lectura:", errors);
            }
        });
    }
};

// Función para formatear fechas
const formatDate = (dateString) => {
    if (!dateString) return '—';
    try {
        const date = new Date(dateString);
        date.setMinutes(date.getMinutes() + date.getTimezoneOffset());
        return date.toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' });
    } catch { return dateString; }
}

// Abrir aviso en nueva pestaña
const abrirAviso = (id) => {
    const url = route('lecturas.aviso', id);
    window.open(url, '_blank');
}

</script>

<template>
  <AppLayout title="Lecturas">
    <div class="p-4 md:p-6">
      <h1 class="text-2xl font-bold mb-6 text-gray-700">Gestión de Lecturas</h1>

      <div class="flex flex-wrap items-center justify-between mb-6 gap-4">
           <Link :href="route('lecturas.create')" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 transition duration-150">
             + Nueva Lectura
           </Link>
           <button v-if="lecturaRecienteId"
                   @click="abrirAviso(lecturaRecienteId)"
                   class="bg-teal-500 text-white px-4 py-2 rounded shadow hover:bg-teal-600 transition duration-150 text-sm">
                🖨️ Imprimir Aviso (ID: {{ lecturaRecienteId }})
           </button>
       </div>


      <div v-if="$page.props.flash.success" class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-4 shadow-sm" role="alert">
        <p class="font-bold">Éxito</p>
        <p>{{ $page.props.flash.success }}</p>
      </div>
       <div v-if="$page.props.flash.error || Object.keys($page.props.errors).length > 0" class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4 shadow-sm" role="alert">
         <p class="font-bold">Error</p>
         <p>{{ $page.props.flash.error || 'Ocurrió un error.' }}</p>
         <ul class="list-disc ml-5 text-sm">
             <li v-for="(error, key) in $page.props.errors" :key="key">{{ error }}</li>
         </ul>
       </div>

      <div class="mb-4 p-4 bg-white rounded shadow-sm grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
        <div class="sm:col-span-2">
          <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Buscar (Medidor, CI/Nombre)</label>
          <input id="search" type="text" v-model="filterForm.search" @input="debouncedSearch" placeholder="Escribe para buscar..."
                 class="border-gray-300 rounded-md shadow-sm block w-full text-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"/>
        </div>
        <div>
          <label for="periodo" class="block text-sm font-medium text-gray-700 mb-1">Período</label>
          <select id="periodo" v-model="filterForm.periodo" class="border-gray-300 rounded-md shadow-sm block w-full text-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            <option value="">Todos</option>
            <option v-for="p in periodos" :key="p" :value="p">{{ p }}</option>
          </select>
        </div>
        <div>
          <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
          <select id="estado" v-model="filterForm.estado" class="border-gray-300 rounded-md shadow-sm block w-full text-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            <option value="">Todos</option>
            <option value="pendiente">Pendiente</option>
            <option value="facturado">Facturado</option>
          </select>
        </div>
      </div>
      <div class="overflow-x-auto bg-white rounded shadow-md">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medidor</th>
              <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Afiliado</th>
              <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Período</th>
              <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
              <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">L. Ant.</th>
              <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">L. Act.</th>
              <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Consumo</th>
              <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
              <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registró</th>
              <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-if="!lecturas.data.length">
              <td colspan="10" class="py-4 px-4 text-center text-sm text-gray-500">
                  {{ filterForm.search || filterForm.periodo || filterForm.estado ? 'No se encontraron lecturas con esos filtros.' : 'No hay lecturas registradas.' }}
              </td>
            </tr>
            <tr v-for="l in lecturas.data" :key="l.id" class="hover:bg-gray-50 text-sm">
              <td class="px-4 py-2 whitespace-nowrap text-gray-700">{{ l.conexion?.codigo_medidor }}</td>
              <td class="px-4 py-2 whitespace-nowrap">
                  <div class="font-medium text-gray-900">{{ l.conexion?.afiliado?.nombre_completo }}</div>
                  <div class="text-xs text-gray-500">CI: {{ l.conexion?.afiliado?.ci }}</div>
              </td>
              <td class="px-4 py-2 whitespace-nowrap text-gray-700">{{ l.periodo }}</td>
              <td class="px-4 py-2 whitespace-nowrap text-gray-700">{{ formatDate(l.fecha_lectura) }}</td>
              <td class="px-4 py-2 whitespace-nowrap text-right text-gray-700">{{ l.lectura_anterior }}</td>
              <td class="px-4 py-2 whitespace-nowrap text-right font-medium text-gray-900">{{ l.lectura_actual }}</td>
              <td class="px-4 py-2 whitespace-nowrap text-right font-bold text-blue-700">
                  {{ (l.lectura_actual - l.lectura_anterior).toFixed(2) }} m³
              </td>
               <td class="px-4 py-2 whitespace-nowrap">
                  <span :class="{
                          'bg-yellow-100 text-yellow-800': l.estado === 'pendiente',
                          'bg-green-100 text-green-800': l.estado === 'facturado',
                      }" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                      {{ l.estado }}
                  </span>
              </td>
              <td class="px-4 py-2 whitespace-nowrap text-xs text-gray-500">{{ l.usuarioRegistrado?.name }}</td>
              <td class="px-4 py-2 whitespace-nowrap text-sm font-medium space-x-2">
                <Link :href="route('lecturas.show', l.id)" class="text-indigo-600 hover:text-indigo-900" title="Ver Detalles">Ver</Link>
                <Link v-if="l.estado === 'pendiente'" :href="route('lecturas.edit', l.id)" class="text-yellow-600 hover:text-yellow-900" title="Editar Lectura">Editar</Link>
                <button v-if="l.estado === 'pendiente'" @click="confirmDelete(l.id)" class="text-red-600 hover:text-red-900" title="Eliminar Lectura">Eliminar</button>
                 <button @click="abrirAviso(l.id)" class="text-teal-600 hover:text-teal-900" title="Imprimir Aviso">🖨️</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="mt-6 flex justify-between items-center text-sm" v-if="lecturas.total > 0">
        <span class="text-gray-700">
          Mostrando {{ lecturas.from }} a {{ lecturas.to }} de {{ lecturas.total }} lecturas
        </span>
        <div class="flex flex-wrap gap-1">
          <Link
            v-for="(link, index) in lecturas.links"
            :key="index"
            :href="link.url ?? '#'"
            v-html="link.label"
            class="px-3 py-1 border rounded"
            :class="{
              'bg-blue-600 text-white': link.active,
              'text-gray-400 cursor-default border-gray-200': !link.url,
              'hover:bg-gray-100 border-gray-300': link.url
            }"
            preserve-scroll preserve-state
            :disabled="!link.url"
          />
        </div>
      </div>
      </div>
  </AppLayout>
</template>