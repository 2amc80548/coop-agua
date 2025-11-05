<script setup>
import { Link, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch } from 'vue';

// --- Props (Datos del Controlador) ---
const props = defineProps({
    users: Object, // ¡Objeto Paginado!
    filters: Object,
    roles: Array,  // Lista de roles para el filtro
});
const page = usePage(); // Para mensajes flash

// --- Formulario de Filtros ---
const filterForm = useForm({
  search: props.filters.search ?? '',
  role: props.filters.role ?? '', // Nuevo filtro
});

// --- Lógica de Búsqueda y Filtros ---
let searchTimeout = null;
const submitFilters = () => {
  filterForm.get(route('users.index'), {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
};
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(submitFilters, 400); // 400ms de espera
};
// Observar el dropdown de rol
watch(() => filterForm.role, submitFilters);

// --- Acción Eliminar (Mejorada) ---
const eliminarUsuario = (user) => {
  if (confirm(`¿Eliminar al usuario "${user.name}"? Esta acción no se puede deshacer.`)) {
    // Usamos el nombre de la ruta y el ID
    router.delete(route('users.destroy', user.id), {
      preserveScroll: true, // No saltar al inicio de la página
      onError: (errors) => {
          // Si el controlador envía un error (ej. no borrar admin), mostrarlo
          if (page.props.errors.error_general) {
              alert(page.props.errors.error_general);
          } else {
              alert('Error al eliminar: ' + Object.values(errors).join(', '));
          }
      }
    });
  }
};
</script>

<template>
  <AppLayout title="Usuarios">
    <template #header>
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Gestión de Usuarios y Roles
        </h2>
    </template>

    <div class="p-4 md:p-6">
      <div class="flex justify-between items-center mb-6">
          <h1 class="text-2xl font-bold text-gray-700 dark:text-gray-300">Usuarios</h1>
          <Link :href="route('users.create')" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 transition duration-150">
            + Crear Usuario
          </Link>
      </div>

      <div v-if="page.props.flash.success" class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-4 shadow-sm" role="alert">
        <p class="font-bold">Éxito</p> <p>{{ page.props.flash.success }}</p>
      </div>
       <div v-if="page.props.flash.error || Object.keys(page.props.errors).length > 0" class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4 shadow-sm" role="alert">
         <p class="font-bold">Error</p>
         <p v-if="page.props.errors.error_general">{{ page.props.errors.error_general }}</p>
         <p v-else>Ocurrió un error.</p>
       </div>

      <div class="mb-4 p-4 bg-white dark:bg-gray-800 rounded shadow-sm grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <div class="md:col-span-2">
          <label for="search" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">Buscar (Nombre, Email)</label>
          <input id="search" type="text" v-model="filterForm.search" @input="debouncedSearch" placeholder="Escriba..." 
                 class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm block w-full text-sm ..."/>
        </div>
        <div>
          <label for="role" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">Filtrar por Rol</label>
          <select id="role" v-model="filterForm.role" class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm block w-full text-sm ...">
            <option value="">Todos los Roles</option>
            <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
          </select>
        </div>
      </div>
      <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded shadow-md">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Solicitante (Nombre)</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Rol(es)</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Afiliado Vinculado</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-if="!users.data.length"> 
                <td colspan="5" class="py-4 px-4 text-center text-sm text-gray-500 dark:text-gray-300">No se encontraron usuarios con los filtros seleccionados.</td> 
            </tr>
            <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50 dark:hover:bg-gray-700 text-sm">
              <td class="px-4 py-3 whitespace-nowrap">
                <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10">
                        <img class="h-10 w-10 rounded-full object-cover" :src="user.profile_photo_url" :alt="user.name">
                    </div>
                    <div class="ml-3">
                        <div class="font-medium text-gray-900 dark:text-white">{{ user.name }}</div>
                    </div>
                </div>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ user.email }}</td>
              <td class="px-4 py-3 whitespace-nowrap">
                <span
                  v-for="role in user.roles"
                  :key="role.id"
                  class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full mr-1"
                  :class="{
                      'bg-blue-100 text-blue-800': role.name === 'Secretaria' || role.name === 'Tecnico' || role.name === 'Usuario',
                      'bg-red-100 text-red-800': role.name === 'Administrador'
                  }"
                >
                  {{ role.name }}
                </span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                <template v-if="user.afiliado">
                  <div class="font-medium text-gray-900 dark:text-white">{{ user.afiliado.nombre_completo }}</div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">CI: {{ user.afiliado.ci }}</div>
                </template>
                <span v-else class="text-gray-400 dark:text-gray-500 italic">N/A (Personal)</span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-sm font-medium space-x-2">
                <Link :href="route('users.edit', user.id)" class="text-yellow-600 hover:text-yellow-900" title="Editar Rol/Datos">Editar</Link>
                <button @click="eliminarUsuario(user)" class="text-red-600 hover:text-red-900" title="Eliminar Usuario">Eliminar</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="mt-6 flex justify-between items-center text-sm" v-if="users.links.length > 3">
        <span class="text-gray-700 dark:text-gray-300">Mostrando {{ users.from }} a {{ users.to }} de {{ users.total }} usuarios</span>
        <div class="flex flex-wrap gap-1">
          <Link v-for="(link, index) in users.links" :key="index" :href="link.url ?? '#'" v-html="link.label"
                class="px-3 py-1 border rounded dark:border-gray-600 dark:text-gray-300"
                :class="{ 
                    'bg-blue-600 text-white border-blue-600': link.active, 
                    'text-gray-400 cursor-default': !link.url, 
                    'hover:bg-gray-100 dark:hover:bg-gray-700': link.url 
                }"
                preserve-scroll preserve-state :disabled="!link.url"/>
        </div>
      </div>
    </div>
  </AppLayout>
</template>