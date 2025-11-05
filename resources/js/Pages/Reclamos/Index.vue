<script setup>
import { Link, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { watch } from 'vue';

const props = defineProps({
  reclamos: Object,   // Paginado
  filters: Object,
  reclamoTipos: Array // Catálogo de tipos
});

const page = usePage();

// --- FILTROS ---
const filterForm = useForm({
  estado: props.filters?.estado ?? 'Abierto',
  reclamo_tipo_id: props.filters?.reclamo_tipo_id ?? ''
});

// Dispara navegación cuando cambie cualquier filtro
watch(
  () => ({ estado: filterForm.estado, reclamo_tipo_id: filterForm.reclamo_tipo_id }),
  (vals) => {
    router.get(route('reclamos.index'), vals, {
      preserveState: true,
      preserveScroll: true,
      replace: true
    });
  },
  { deep: false }
);

// --- TIPOS DE RECLAMO (crear) ---
const formTipo = useForm({ nombre: '' });

const crearTipo = () => {
  formTipo.post(route('reclamoTipos.store'), {
    preserveScroll: true,
    onSuccess: () => formTipo.reset('nombre')
  });
};

// --- HELPERS ---
const formatDate = (dateString) => {
  if (!dateString) return '—';
  try {
    const date = new Date(dateString);
    date.setMinutes(date.getMinutes() + date.getTimezoneOffset());
    return date.toLocaleDateString('es-BO', {
      day: '2-digit', month: '2-digit', year: 'numeric',
      hour: '2-digit', minute: '2-digit'
    });
  } catch { return dateString; }
};

const estadoClass = (estado) => {
  switch (estado) {
    case 'Abierto': return 'bg-yellow-100 text-yellow-800';
    case 'En Revisión': return 'bg-blue-100 text-blue-800';
    case 'Resuelto': return 'bg-green-100 text-green-800';
    case 'Cerrado': return 'bg-gray-100 text-gray-800';
    default: return 'bg-gray-100 text-gray-800';
  }
};
</script>

<template>
  <AppLayout title="Gestión de Reclamos">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        Gestión de Reclamos
      </h2>
    </template>

    <!-- Tipos de Reclamo -->
    <div class="p-4 md:p-6 pt-0">
      <div class="mb-4 p-4 bg-white dark:bg-gray-800 rounded shadow-sm">
        <h3 class="font-semibold mb-2 text-gray-800 dark:text-gray-200">Tipos de Reclamo</h3>
        <form @submit.prevent="crearTipo" class="flex gap-2">
          <input v-model="formTipo.nombre" type="text" class="border rounded px-3 py-2 w-full"
                 placeholder="Nuevo tipo (ej. 'Corte de servicio')" />
          <button class="bg-blue-600 text-white px-3 py-2 rounded" :disabled="formTipo.processing">
            {{ formTipo.processing ? 'Guardando...' : 'Agregar' }}
          </button>
        </form>
        <div v-if="formTipo.errors.nombre" class="text-red-600 text-sm mt-1">
          {{ formTipo.errors.nombre }}
        </div>
      </div>
    </div>

    <div class="p-4 md:p-6">
      <div v-if="page.props.flash?.success"
           class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-4 shadow-sm"
           role="alert">
        <p class="font-bold">Éxito</p>
        <p>{{ page.props.flash.success }}</p>
      </div>

      <!-- Filtros -->
      <div class="mb-4 p-4 bg-white dark:bg-gray-800 rounded shadow-sm grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <div>
          <label for="estado" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">Estado</label>
          <select id="estado" v-model="filterForm.estado"
                  class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm block w-full text-sm">
            <option value="Abierto">Abiertos (Por defecto)</option>
            <option value="En Revisión">En Revisión</option>
            <option value="Resuelto">Resueltos</option>
            <option value="Cerrado">Cerrados</option>
            <option value="todos">Todos</option>
          </select>
        </div>
        <div>
          <label for="reclamo_tipo_id" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">Tipo de Reclamo</label>
          <select id="reclamo_tipo_id" v-model="filterForm.reclamo_tipo_id"
                  class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm block w-full text-sm">
            <option value="">Todos</option>
            <option v-for="tipo in reclamoTipos" :key="tipo.id" :value="tipo.id">{{ tipo.nombre }}</option>
          </select>
        </div>
      </div>

      <!-- Tabla -->
      <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded shadow-md">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-4 py-3 text-left">Afiliado</th>
              <th class="px-4 py-3 text-left">Asunto / Tipo</th>
              <th class="px-4 py-3 text-left">Estado</th>
              <th class="px-4 py-3 text-left">Fecha Recibido</th>
              <th class="px-4 py-3 text-left">Acciones</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-if="!reclamos.data?.length">
              <td colspan="5" class="py-4 px-4 text-center text-sm text-gray-500 dark:text-gray-300">
                No se encontraron reclamos con esos filtros.
              </td>
            </tr>
            <tr v-for="rec in reclamos.data" :key="rec.id" class="hover:bg-gray-50 dark:hover:bg-gray-700 text-sm">
              <td class="px-4 py-2 whitespace-nowrap">
                <div class="font-medium text-gray-900 dark:text-white">{{ rec.afiliado?.nombre_completo }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">CI: {{ rec.afiliado?.ci }}</div>
              </td>
              <td class="px-4 py-2">
                <div class="font-medium text-gray-900 dark:text-white">{{ rec.asunto }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Tipo: {{ rec.tipo?.nombre || 'N/A' }}</div>
              </td>
              <td class="px-4 py-2 whitespace-nowrap">
                <span :class="estadoClass(rec.estado)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                  {{ rec.estado }}
                </span>
              </td>
              <td class="px-4 py-2 whitespace-nowrap text-gray-700 dark:text-gray-300">
                {{ formatDate(rec.created_at) }}
              </td>
              <td class="px-4 py-2 whitespace-nowrap text-sm font-medium space-x-2">
                <Link :href="route('reclamos.show', rec.id)" class="text-indigo-600 hover:text-indigo-900" title="Ver / Responder">
                  Ver / Responder
                </Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div class="mt-6 flex justify-between items-center text-sm" v-if="(reclamos.links?.length || 0) > 3">
        <span class="text-gray-700 dark:text-gray-300">
          Mostrando {{ reclamos.from }} a {{ reclamos.to }} de {{ reclamos.total }} reclamos
        </span>
        <div class="flex flex-wrap gap-1">
          <Link v-for="(link, index) in reclamos.links" :key="index"
                :href="link.url ?? '#'" v-html="link.label"
                class="px-3 py-1 border rounded"
                :class="{
                  'bg-blue-600 text-white': link.active,
                  'text-gray-400 pointer-events-none': !link.url,
                  'hover:bg-gray-100 dark:hover:bg-gray-700': link.url
                }"
                preserve-scroll preserve-state />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
