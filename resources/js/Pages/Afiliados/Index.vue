<script setup>
import { ref, watch } from 'vue';
import { Link, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue'; 
import ViewCounter from '@/Components/ViewCounter.vue';

// --- Props (Datos del Controlador) ---
const props = defineProps({
    afiliados: Object, // Paginado
    filters: Object,
    zonas: Array, 
  
});
const page = usePage();

// --- Formulario de Filtros ---
const filterForm = useForm({
  search: props.filters.search ?? '',
  tipo: props.filters.tipo ?? '',
  estado_servicio: props.filters.estado_servicio ?? '',
  zona_id: props.filters.zona_id ?? '',
  adulto_mayor: props.filters.adulto_mayor ?? '',
});

// --- Lógica de Filtros ---
// Observa el formulario y envía los filtros con un retraso (debounce)
// Usamos watch con deep: true para observar el objeto
let filterTimeout;
watch(filterForm, (newValue) => {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        router.get(route('afiliados.index'), newValue, {
            preserveState: true,
            preserveScroll: true,
            replace: true, // No añade al historial del navegador
        });
    }, 300); // 300ms de espera
}, { deep: true });

// --- Lógica de Borrado ---
const confirmDelete = (afiliado) => {
    if (confirm(`¿Estás seguro de eliminar a ${afiliado.nombre_completo}? Esta acción no se puede deshacer.`)) {
        router.delete(route('afiliados.destroy', afiliado.id), {
            preserveScroll: true,
            // onSuccess y onError se manejan con los mensajes flash
        });
    }
};

// --- Helpers (Funciones de Ayuda) ---
// Obtener la URL de la foto de perfil
const getPhotoUrl = (path) => {
    if (path) {
        // Asume que tu disco 'public' está enlazado con 'storage'
        // ¡IMPORTANTE! Ejecuta `php artisan storage:link` en tu terminal si no lo has hecho
        return `/storage/${path}`; 
    }
    // Retorna una imagen placeholder
    return `https://ui-avatars.com/api/?name=${encodeURIComponent('N/A')}&color=7F9CF5&background=EBF4FF`;
};

// Devolver clases de color para el estado de servicio
const estadoServicioClass = (estado) => {
    switch (estado) {
        case 'activo': return 'bg-green-100 text-green-800';
        case 'en_corte': return 'bg-yellow-100 text-yellow-800';
        case 'cortado': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};
</script>

<template>
  <AppLayout title="Afiliados">
    <template #header>
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Gestión de Afiliados
        </h2>
    </template>

    <div class="p-4 md:p-6">
      
      <div class="mb-4">
        <Link :href="route('afiliados.create')" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 transition duration-150">
          + Nuevo Afiliado
        </Link>
      </div>

      <div v-if="page.props.flash.success" class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-4 shadow-sm" role="alert">
        <p class="font-bold">Éxito</p> <p>{{ page.props.flash.success }}</p>
      </div>
      <div v-if="page.props.flash.error || Object.keys(page.props.errors).length > 0" class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4 shadow-sm" role="alert">
         <p class="font-bold">Error</p>
         <p>{{ page.props.flash.error || (page.props.errors.error_general || 'Ocurrió un error.') }}</p>
         <ul class="list-disc ml-5 text-sm">
             <li v-for="(error, key) in page.props.errors" :key="key" v-if="key !== 'error_general'">{{ error }}</li>
         </ul>
       </div>

      <div class="mb-4 p-4 bg-white dark:bg-gray-800 rounded shadow-sm 
                  flex flex-wrap items-end gap-4">

        <!-- Buscar -->
        <div class="flex flex-col w-64">
          <label for="search" class="font-medium mb-1 text-gray-700 dark:text-gray-300">
            Buscar (Nombre, CI, Código)
          </label>
          <input id="search" type="text" v-model="filterForm.search"
                class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 
                        rounded-md shadow-sm text-sm"/>
        </div>

        <!-- Tipo -->
        <div class="flex flex-col w-40">
          <label for="tipo" class="font-medium mb-1 text-gray-700 dark:text-gray-300">Tipo</label>
          <select id="tipo" v-model="filterForm.tipo"
                  class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 
                        rounded-md shadow-sm text-sm">
            <option value="">Todos</option>
            <option value="socio">Socio</option>
            <option value="usuario">Usuario</option>
          </select>
        </div>

        <!-- Estado Servicio -->
        <div class="flex flex-col w-40">
          <label for="estado_servicio" class="font-medium mb-1 text-gray-700 dark:text-gray-300">
            Estado Servicio
          </label>
          <select id="estado_servicio" v-model="filterForm.estado_servicio"
                  class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 
                        rounded-md shadow-sm text-sm">
            <option value="">Todos</option>
            <option value="Pendiente">Pendiente</option>
            <option value="activo">Activo</option>
            <option value="en_corte">En Corte</option>
            <option value="cortado">Cortado</option>
          </select>
        </div>

        <!-- Zona -->
        <div class="flex flex-col w-40">
          <label for="zona_id" class="font-medium mb-1 text-gray-700 dark:text-gray-300">Zona</label>
          <select id="zona_id" v-model="filterForm.zona_id"
                  class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 
                        rounded-md shadow-sm text-sm">
            <option value="">Todas</option>
            <option v-for="zona in zonas" :key="zona.id" :value="zona.id">{{ zona.nombre }}</option>
          </select>
        </div>

        <!-- Adulto Mayor -->
        <div class="flex flex-col w-24">
          <label for="adulto_mayor" class="font-medium mb-1 text-gray-700 dark:text-gray-300">
            A_Mayor
          </label>
          <select id="adulto_mayor" v-model="filterForm.adulto_mayor"
                  class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 
                        rounded-md shadow-sm text-sm">
            <option value="">Todos</option>
            <option value="1">SI</option>
            <option value="0">NO</option>
          </select>
        </div>

      </div>

      <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded shadow-md">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Afiliado</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Contacto</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Zona</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tipo</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado Servicio</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-if="!afiliados.data.length"> 
                <td colspan="6" class="py-4 px-4 text-center text-sm text-gray-500 dark:text-gray-300">No se encontraron afiliados.</td> 
            </tr>
            <tr v-for="a in afiliados.data" :key="a.id" class="hover:bg-gray-50 dark:hover:bg-gray-700 text-sm">
              <td class="px-4 py-2 whitespace-nowrap">
                <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10">
                        <img class="h-10 w-10 rounded-full object-cover" :src="getPhotoUrl(a.profile_photo_path)" :alt="a.nombre_completo">
                    </div>
                    <div class="ml-3">
                        <div class="font-medium text-gray-900 dark:text-white">{{ a.nombre_completo }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">CI: {{ a.ci }} | Código: {{ a.codigo }}</div>
                    </div>
                </div>
              </td>
              <td class="px-4 py-2 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ a.celular }}</td>
              <td class="px-4 py-2 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ a.zona?.nombre || 'N/A' }}</td>
              <td class="px-4 py-2 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                        :class="a.tipo === 'socio' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'">
                    {{ a.tipo }}
                  </span>
              </td>
              <td class="px-4 py-2 whitespace-nowrap">
                  <span :class="estadoServicioClass(a.estado_servicio)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                    {{ a.estado_servicio }}
                  </span>
              </td>
              <td class="px-4 py-2 whitespace-nowrap text-sm font-medium space-x-2">
                <Link :href="route('afiliados.show', a.id)" class="text-indigo-600 hover:text-indigo-900" title="Ver Detalles">Ver</Link>
                <Link :href="route('afiliados.edit', a.id)" class="text-yellow-600 hover:text-yellow-900" title="Editar Afiliado">Editar</Link>
                <button v-if="$page.props.auth.user.role_names.includes('Administrador')" 
                         @click="confirmDelete(a)" 
                         class="text-red-600 hover:text-red-900" 
                         title="Eliminar Afiliado">
                    Eliminar
                 </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="mt-6 flex justify-between items-center text-sm">
        <span class="text-gray-700 dark:text-gray-300">Mostrando {{ afiliados.from }} a {{ afiliados.to }} de {{ afiliados.total }} afiliados</span>
        <div class="flex flex-wrap gap-1">
          <Link v-for="(link, index) in afiliados.links" :key="index" :href="link.url ?? '#'" v-html="link.label"
                class="px-3 py-1 border rounded dark:border-gray-600 dark:text-gray-300"
                :class="{ 
                    'bg-blue-600 text-white border-blue-600': link.active, 
                    'text-gray-400 cursor-default': !link.url, 
                    'hover:bg-gray-100 dark:hover:bg-gray-700': link.url 
                }"
                preserve-scroll preserve-state :disabled="!link.url"/>
        </div>
      </div>
      <ViewCounter />
    </div>
  </AppLayout>
</template>