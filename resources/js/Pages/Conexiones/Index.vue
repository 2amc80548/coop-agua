<script setup>
import { Link, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch } from 'vue';
import ViewCounter from '@/Components/ViewCounter.vue';

const props = defineProps({
    conexiones: Object, // Objeto Paginado (viene de ->paginate())
    filters: Object,    
    zonas: Array,       // Lista de zonas para el dropdown
});
const page = usePage(); // Para mensajes flash

// --- Formulario de Filtros ---
const filterForm = useForm({
  search: props.filters.search ?? '',
  zona_id: props.filters.zona_id ?? '',
  estado: props.filters.estado ?? '',
  tipo_conexion: props.filters.tipo_conexion ?? '',
});

// --- Lógica de Filtros (Rápida y Profesional) ---
let searchTimeout = null;
// Función que envía todos los filtros
const submitFilters = () => {
    filterForm.get(route('conexiones.index'), { 
        preserveState: true, 
        preserveScroll: true, 
        replace: true 
    });
};
// Retraso (debounce) para el campo de búsqueda
const debouncedSearch = () => { 
    clearTimeout(searchTimeout); 
    searchTimeout = setTimeout(submitFilters, 400); // Espera 400ms
};
// Observa los dropdowns para filtrar al instante
watch(() => [filterForm.zona_id, filterForm.estado, filterForm.tipo_conexion], submitFilters);


// --- Acciones ---
const confirmDelete = (conexion) => {
  if (confirm(`¿Eliminar medidor ${conexion.codigo_medidor}? Esta acción no se puede deshacer.`)) {
    router.delete(route('conexiones.destroy', conexion.id), {
      preserveScroll: true,
      onError: (errors) => {
          // Muestra el error de "No se puede eliminar" del controlador
          alert(errors.error_general || 'No se pudo eliminar la conexión.');
      }
    });
  }
};

// --- Helpers Visuales ---
const estadoClass = (estado) => {
    switch (estado) {
        case 'activo': return 'bg-green-100 text-green-800';
        case 'suspendido': return 'bg-yellow-100 text-yellow-800';
        case 'eliminado': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};
const formatDate = (dateString) => {
    if (!dateString) return '—'; 
    try { 
        const date = new Date(dateString); 
        date.setMinutes(date.getMinutes() + date.getTimezoneOffset()); 
        return date.toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' }); 
    } catch { return dateString; }
};
</script>

<template>
    <AppLayout title="Conexiones">
      <template #header>
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Gestión de Conexiones (Medidores)
        </h2>
      </template>

      <div class="p-4 md:p-6">
        <div class="flex justify-between items-center mb-6">
          <h1 class="text-2xl font-bold text-gray-700 dark:text-gray-300">Conexiones</h1>
          <Link :href="route('conexiones.create')" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 transition duration-150">
            + Nueva Conexión
          </Link>
        </div>
        
        <div v-if="page.props.flash.success" class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-4 shadow-sm" role="alert">
           <p class="font-bold">Éxito</p> <p>{{ page.props.flash.success }}</p>
        </div>
        <div v-if="page.props.flash.error || (page.props.errors && Object.keys(page.props.errors).length > 0)" class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4 shadow-sm" role="alert">
           <p class="font-bold">Error</p>
           <p>{{ page.props.flash.error || (page.props.errors.error_general || 'Ocurrió un error.') }}</p>
           <ul v-if="page.props.errors && !page.props.errors.error_general" class="list-disc ml-5 text-sm">
               <li v-for="(error, key) in page.props.errors" :key="key">{{ error }}</li>
           </ul>
        </div>

        <div class="mb-4 p-4 bg-white dark:bg-gray-800 rounded shadow-sm 
                    grid grid-cols-1 md:grid-cols-5 lg:grid-cols-5 gap-4 text-sm">
            <div class="md:col-span-2">
                <label for="search" class="block font-medium mb-1 text-gray-700 dark:text-gray-300"> Buscar (Medidor, CI/Nombre)</label>
                <input id="search" type="text" v-model="filterForm.search" @input="debouncedSearch" placeholder="Escriba..."
                      class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200  rounded-md shadow-sm block w-full text-sm 
                              focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"/>
            </div>
            <div>
                <label for="zona_id" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">Zona</label>
                <select id="zona_id" v-model="filterForm.zona_id" class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm block w-full text-sm">
                    <option value="">Todas</option>
                    <option v-for="zona in zonas" :key="zona.id" :value="zona.id">{{ zona.nombre }}</option>
                </select>
            </div>
            <div>
                <label for="estado" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">Estado</label>
                <select id="estado" v-model="filterForm.estado" class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm block w-full text-sm">
                    <option value="">Todos</option>
                    <option value="activo">Activo</option>
                    <option value="suspendido">Suspendido</option>
                    <option value="eliminado">Eliminado</option>
                </select>
            </div>
            <div>
                <label for="tipo_conexion" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">Tipo de conexión</label>
                <select id="tipo_conexion" v-model="filterForm.tipo_conexion" class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm block w-full text-sm">
                    <option value="">Todos</option>
                    <option value="domiciliaria">Domiciliaria</option>
                    <option value="comercial">Comercial</option>
                    <option value="institucional">Institucional</option>
                </select>
            </div>

        </div>


        <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded shadow-md">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Medidor</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Afiliado</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Dirección / Zona</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tipo</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-if="!conexiones.data.length"> <td colspan="6" class="py-4 px-4 text-center text-sm text-gray-500 dark:text-gray-300">No se encontraron conexiones.</td> </tr>
              <tr v-for="c in conexiones.data" :key="c.id" class="hover:bg-gray-50 dark:hover:bg-gray-700 text-sm">
                <td class="px-4 py-2 whitespace-nowrap font-medium text-gray-900 dark:text-white">{{ c.codigo_medidor }}</td>
                <td class="px-4 py-2 whitespace-nowrap">
                    <div class="font-medium text-gray-900 dark:text-white">{{ c.afiliado?.nombre_completo }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">CI: {{ c.afiliado?.ci }}</div>
                </td>
                <td class="px-4 py-2 whitespace-nowrap">
                    <div class="text-gray-900 dark:text-white">{{ c.direccion }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Zona: {{ c.zona?.nombre || 'N/A' }}</div>
                </td>
                <td class="px-4 py-2 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ c.tipo_conexion }}</td>
                <td class="px-4 py-2 whitespace-nowrap">
                    <span :class="estadoClass(c.estado)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                      {{ c.estado }}
                    </span>
                </td>
                <td class="px-4 py-2 whitespace-nowrap text-sm font-medium space-x-2">
                  <Link :href="route('conexiones.show', c.id)" class="text-indigo-600 hover:text-indigo-900" title="Ver Detalles">Ver</Link>
                  <Link :href="route('conexiones.edit', c.id)" class="text-yellow-600 hover:text-yellow-900" title="Editar Conexión">Editar</Link>
                  <button v-if="$page.props.auth.user.role_names.includes('Administrador')" 
                          @click="confirmDelete(c)" 
                          class="text-red-600 hover:text-red-900" 
                          title="Eliminar Conexión">
                     Eliminar
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="mt-6 flex justify-between items-center text-sm">
            <span class="text-gray-700 dark:text-gray-300">Mostrando {{ conexiones.from }} a {{ conexiones.to }} de {{ conexiones.total }} conexiones</span>
            <div class="flex flex-wrap gap-1">
              <Link v-for="(link, index) in conexiones.links" :key="index" :href="link.url ?? '#'" v-html="link.label"
                    class="px-3 py-1 border rounded dark:border-gray-600 dark:text-gray-300"
                    :class="{ 'bg-blue-600 text-white ...': link.active, 'text-gray-400 ...': !link.url, 'hover:bg-gray-100 ...': link.url }"
                    preserve-scroll preserve-state :disabled="!link.url"/>
            </div>          
        </div>  
        <ViewCounter />    
      </div>
    </AppLayout>
</template>