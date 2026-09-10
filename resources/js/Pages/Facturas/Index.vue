<script setup>
import { Link, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue'; // Ajusta ruta
import { ref, watch, computed } from 'vue';
import ViewCounter from '@/Components/ViewCounter.vue';

// --- Props (Datos del Controlador) ---
const props = defineProps({
    facturas: Object, // Paginado
    filters: Object,
    periodos: Array,
});
const page = usePage(); // Para mensajes flash

// --- Formulario de Filtros ---
const filterForm = useForm({
  search: props.filters.search ?? '',
  periodo: props.filters.periodo ?? '',
  estado: props.filters.estado ?? 'impaga', // 'impaga' por defecto
  fecha_inicio: props.filters.fecha_inicio ?? '',
  fecha_fin: props.filters.fecha_fin ?? '',
});

// --- Lógica de Búsqueda y Filtros ---
let searchTimeout = null;
const submitFilters = () => {
  filterForm.get(route('facturas.index'), {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
};
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(submitFilters, 400); 
};
watch(() => [filterForm.periodo, filterForm.estado, filterForm.fecha_inicio, filterForm.fecha_fin], submitFilters);

// --- Funciones de Formato ---
const formatDate = (dateString) => {
    if (!dateString) return '—'; 
    try { 
        const date = new Date(dateString); 
        date.setMinutes(date.getMinutes() + date.getTimezoneOffset()); 
        return date.toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' }); 
    } catch { return dateString; }
};
const formatCurrency = (amount) => {
    return (parseFloat(amount) || 0).toFixed(2);
};
const estadoClass = (estado) => { 
    switch (estado) {
        case 'impaga': return 'bg-yellow-100 text-yellow-800';
        case 'pagado': return 'bg-green-100 text-green-800';
        case 'anulada': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

// --- Acciones ---
const showAnularModal = ref(false);
const facturaParaAnular = ref(null);
const motivoAnulacion = ref('');
const errorAnulacion = ref('');
const anulando = ref(false);

const abrirModalAnular = (factura) => {
    facturaParaAnular.value = factura;
    motivoAnulacion.value = '';
    errorAnulacion.value = '';
    showAnularModal.value = true;
};

const cerrarModalAnular = () => {
    showAnularModal.value = false;
    facturaParaAnular.value = null;
    motivoAnulacion.value = '';
    errorAnulacion.value = '';
};

const confirmarAnularFactura = () => {
    if (!motivoAnulacion.value || motivoAnulacion.value.trim().length < 5) {
        errorAnulacion.value = 'Debe ingresar un motivo de al menos 5 caracteres.';
        return;
    }

    anulando.value = true;
    router.post(route('facturas.anular', facturaParaAnular.value.id), {
        motivo_anulacion: motivoAnulacion.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            anulando.value = false;
            cerrarModalAnular();
        },
        onError: (errors) => {
            anulando.value = false;
            errorAnulacion.value = errors.motivo_anulacion || errors.error_general || 'Error al anular la factura.';
        }
    });
};

const imprimirFactura = (facturaId) => {
  const url = route('facturas.imprimir', facturaId);
  window.open(url, '_blank');
};

</script>

<template>
  <AppLayout title="Gestión de Facturas">
    <div class="p-4 md:p-6">
      <h1 class="text-2xl font-bold mb-6 text-gray-700 dark:text-gray-300">Gestión de Facturas</h1>

      <div v-if="page.props.flash.success" class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-4 shadow-sm dark:bg-green-900 dark:border-green-700 dark:text-green-100" role="alert">
        <p class="font-bold">Éxito</p> <p>{{ page.props.flash.success }}</p>
      </div>
      <div v-if="page.props.flash.error || Object.keys(page.props.errors).length > 0" class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4 shadow-sm dark:bg-red-900 dark:border-red-700 dark:text-red-100" role="alert">
          <p class="font-bold">Error</p>
          <p>{{ page.props.flash.error || 'Ocurrió un error.' }}</p>
          <ul class="list-disc ml-5 text-sm"><li v-for="(error, key) in page.props.errors" :key="key">{{ error }}</li></ul>
        </div>
        <div v-if="page.props.flash.info" class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 px-4 py-3 rounded mb-4 shadow-sm dark:bg-blue-900 dark:border-blue-700 dark:text-blue-100" role="alert">
          <p class="font-bold">Información</p> <p>{{ page.props.flash.info }}</p>
        </div>

      <div class="mb-4 p-4 bg-white dark:bg-gray-800 rounded shadow-sm grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 text-sm">
        <div class="lg:col-span-2">
          <label for="search" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">Buscar (N°Fact, Medidor, CI/Nombre)</label>
          <input id="search" type="text" v-model="filterForm.search" @input="debouncedSearch" placeholder="Escriba..." 
                 class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm block w-full text-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"/>
        </div>
        <div>
          <label for="periodo" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">Período</label>
          <select id="periodo" v-model="filterForm.periodo" 
                  class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm block w-full text-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            <option value="">Todos</option>
            <option v-for="p in periodos" :key="p" :value="p">{{ p }}</option>
          </select>
        </div>
        <div>
          <label for="estado" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">Estado</label>
          <select id="estado" v-model="filterForm.estado" 
                  class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm block w-full text-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            <option value="impaga">Impagas </option>
            <option value="pagado">Pagadas</option>
            <option value="anulada">Anuladas</option>
            <option value="todos">Todas</option>
          </select>
        </div>
          <div class="grid grid-cols-2 gap-2">
              <div>
                <label for="fecha_inicio" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">F. Emisión Desde</label>
                <input id="fecha_inicio" type="date" v-model="filterForm.fecha_inicio" 
                       class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm block w-full text-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"/>
              </div>
              <div>
                 <label for="fecha_fin" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">F. Emisión Hasta</label>
                 <input id="fecha_fin" type="date" v-model="filterForm.fecha_fin" 
                        class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm block w-full text-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"/>
              </div>
          </div>
      </div>

      <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded shadow-md">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">N° Factura</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Afiliado</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Período</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">F. Emisión</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Monto Total</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Deuda Pend.</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-if="!facturas.data.length"> 
                <td colspan="8" class="py-4 px-4 text-center text-sm text-gray-500 dark:text-gray-400">No se encontraron facturas con los filtros seleccionados.</td> 
            </tr>
            <tr v-for="f in facturas.data" :key="f.id" class="hover:bg-gray-50 dark:hover:bg-gray-700 text-sm">
              <td class="px-4 py-2 whitespace-nowrap">
                <Link :href="route('facturas.show', f.id)" class="font-medium text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-200">
                    F-{{ f.id.toString().padStart(6, '0') }}
                </Link>
              </td>
              <td class="px-4 py-2 whitespace-nowrap">
                  <div class="font-medium text-gray-900 dark:text-white">{{ f.conexion?.afiliado?.nombre_completo }}</div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">CI: {{ f.conexion?.afiliado?.ci }} (Med: {{ f.conexion?.codigo_medidor }})</div>
              </td>
              <td class="px-4 py-2 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ f.periodo }}</td>
              <td class="px-4 py-2 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ formatDate(f.fecha_emision) }}</td>
              <td class="px-4 py-2 whitespace-nowrap text-right font-medium text-gray-800 dark:text-gray-200">Bs {{ formatCurrency(f.monto_total) }}</td>
               <td class="px-4 py-2 whitespace-nowrap text-right font-bold" 
                   :class="{'text-red-600 dark:text-red-400': f.deuda_pendiente > 0, 'text-green-600 dark:text-green-400': f.deuda_pendiente <= 0}">
                 Bs {{ formatCurrency(f.deuda_pendiente) }}
               </td>
              <td class="px-4 py-2 whitespace-nowrap">
                  <span :class="estadoClass(f.estado)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                    {{ f.estado }}
                  </span>
              </td>
              <td class="px-4 py-2 whitespace-nowrap text-sm font-medium space-x-2">
                <Link :href="route('facturas.show', f.id)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-200" title="Ver Detalles y Pagos">Ver</Link>
                <Link v-if="f.estado === 'impaga'" :href="route('pagos.create', { factura_id: f.id })" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-200" title="Registrar Pago">Pagar</Link>
                <button v-if="f.estado === 'impaga'" @click="abrirModalAnular(f)" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-200" title="Anular Factura">Anular</button>
                <button @click="imprimirFactura(f.id)" class="text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-200" title="Imprimir Factura">
                  🖨️
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="mt-6 flex justify-between items-center text-sm"> 
        <span class="text-gray-700 dark:text-gray-300"> 
          Mostrando {{ facturas.from || 0 }} a {{ facturas.to || 0 }} de {{ facturas.total || 0 }} facturas
        </span>
        
        <div class="flex flex-wrap gap-1"> 
          <Link 
            v-for="(link, index) in facturas.links" 
            :key="index" 
            :href="link.url ?? '#'" 
            v-html="link.label"
            class="px-3 py-1 border rounded dark:border-gray-600 dark:text-gray-300"
            :class="{ 
                'bg-blue-600 text-white dark:bg-blue-700 dark:text-white': link.active, 
                'text-gray-400 cursor-default border-gray-200 dark:text-gray-500 dark:border-gray-700': !link.url, 
                'hover:bg-gray-100 border-gray-300 dark:hover:bg-gray-700 dark:border-gray-600': link.url 
            }"
            preserve-scroll 
            preserve-state 
            :disabled="!link.url"
          />
        </div>
      </div>

      <!-- MODAL DE ANULACIÓN CONTROLADA -->
      <div v-if="showAnularModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-md w-full p-6 border dark:border-gray-700">
          <div class="flex justify-between items-center border-b dark:border-gray-700 pb-3 mb-4">
            <h3 class="text-lg font-bold text-red-600 dark:text-red-400 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Anulación de Factura
            </h3>
            <button @click="cerrarModalAnular" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 font-bold">&times;</button>
          </div>

          <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">
            Está por anular la factura <strong>F-{{ String(facturaParaAnular?.id).padStart(6, '0') }}</strong> por un monto de <strong>Bs {{ formatCurrency(facturaParaAnular?.monto_total) }}</strong>.
          </p>
          <p class="text-xs text-amber-600 dark:text-amber-400 font-semibold mb-4 bg-amber-50 dark:bg-amber-900/30 p-2 rounded border border-amber-200 dark:border-amber-800">
            ⚠️ La lectura asociada volverá a estado "pendiente" para permitir una nueva facturación o corrección.
          </p>

          <div class="mb-4">
            <label for="motivo_anulacion" class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1">
              Motivo de Anulación *
            </label>
            <textarea
              id="motivo_anulacion"
              v-model="motivoAnulacion"
              rows="3"
              placeholder="Escriba el motivo detallado de la anulación (Ej: error en lectura, factura duplicada)..."
              class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500"
            ></textarea>
            <p v-if="errorAnulacion" class="text-xs text-red-600 mt-1 font-semibold">{{ errorAnulacion }}</p>
          </div>

          <div class="flex justify-end gap-3 pt-2 border-t dark:border-gray-700">
            <button
              @click="cerrarModalAnular"
              :disabled="anulando"
              class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition"
            >
              Cancelar
            </button>
            <button
              @click="confirmarAnularFactura"
              :disabled="anulando"
              class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg shadow transition flex items-center gap-2 disabled:opacity-50"
            >
              <span v-if="anulando" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
              {{ anulando ? 'Anulando...' : 'Confirmar Anulación' }}
            </button>
          </div>
        </div>
      </div>

      <ViewCounter />
    </div>
  </AppLayout>
</template>