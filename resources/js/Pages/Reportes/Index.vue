<script setup>
import { computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ViewCounter from '@/Components/ViewCounter.vue';
const props = defineProps({
  resumen: {
    type: Object,
    required: true,
  },
  detallePagos: {
    type: Object,
    required: true,
  },
  detalleFacturas: {
    type: Object,
    required: true,
  },
  detalleAfiliados: {
    type: Object,
    required: true,
  },
  detalleReclamos: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    required: true,
  },
});

// Formulario de filtros (GET)
const filterForm = useForm({
  fecha_inicio: props.filters.fecha_inicio ?? '',
  fecha_fin: props.filters.fecha_fin ?? '',
  estado_servicio: props.filters.estado_servicio ?? '',
  // Si luego quieres zona_id, se agrega aquí
});

// Enviar filtros
const submitFilters = () => {
  filterForm.get(route('reportes.index'), {
    preserveState: true,
    replace: true,
  });
};

// Formateadores
const formatCurrency = (value) => {
  const n = Number(value || 0);
  return new Intl.NumberFormat('es-BO', {
    style: 'currency',
    currency: 'BOB',
    maximumFractionDigits: 2,
  }).format(n);
};

const formatNumber = (value) => {
  return new Intl.NumberFormat('es-BO').format(Number(value || 0));
};

const rangoTexto = computed(() => {
  if (!props.resumen?.rango) return '';
  return `${props.resumen.rango.inicio} al ${props.resumen.rango.fin}`;
});
</script>

<template>
  <AppLayout title="Reportes">
    <template #header>
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
        <div>
          <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
            Gestión
          </p>
          <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Reportes
          </h2>
          <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400">
            Resumen de facturación, pagos, afiliados, lecturas y reclamos
          </p>
        </div>
      </div>
    </template>

    <div class="py-4 md:py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- ===========================
             FILTROS
             =========================== -->
        <section class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-cyan-100 dark:border-gray-700 p-4 md:p-5">
          <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
            <div class="space-y-2 text-sm md:text-base">
              <h3 class="font-semibold text-gray-800 dark:text-gray-100">
                Filtros de reporte
              </h3>
              <p class="text-xs md:text-sm text-gray-600 dark:text-gray-300">
                Rango actual: <span class="font-semibold">{{ rangoTexto }}</span>
              </p>
            </div>

            <form @submit.prevent="submitFilters" class="grid grid-cols-1 sm:grid-cols-3 gap-3 w-full md:w-auto text-xs md:text-sm">
              <!-- Fecha inicio -->
              <div>
                <label for="fecha_inicio" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">
                  Desde
                </label>
                <input
                  id="fecha_inicio"
                  type="date"
                  v-model="filterForm.fecha_inicio"
                  class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-md shadow-sm focus:border-cyan-400 focus:ring focus:ring-cyan-200 focus:ring-opacity-50"
                >
              </div>

              <!-- Fecha fin -->
              <div>
                <label for="fecha_fin" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">
                  Hasta
                </label>
                <input
                  id="fecha_fin"
                  type="date"
                  v-model="filterForm.fecha_fin"
                  class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-md shadow-sm focus:border-cyan-400 focus:ring focus:ring-cyan-200 focus:ring-opacity-50"
                >
              </div>

              <!-- Estado servicio (opcional) -->
              <div>
                <label for="estado_servicio" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">
                  Estado servicio afiliado
                </label>
                <select
                  id="estado_servicio"
                  v-model="filterForm.estado_servicio"
                  class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-md shadow-sm focus:border-cyan-400 focus:ring focus:ring-cyan-200 focus:ring-opacity-50"
                >
                  <option value="">Todos</option>
                  <option value="activo">Activo</option>
                  <option value="en_corte">En corte</option>
                  <option value="cortado">Cortado</option>
                </select>
              </div>

              <!-- Botones -->
              <div class="sm:col-span-3 flex flex-wrap gap-2 justify-end pt-1">
                <button
                  type="button"
                  class="px-3 py-1.5 rounded-md text-xs md:text-sm border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition"
                  @click="() => { window.location.href = route('reportes.index'); }"
                >
                  Limpiar
                </button>
                <button
                  type="submit"
                  class="px-4 py-1.5 rounded-md text-xs md:text-sm font-semibold bg-cyan-600 hover:bg-cyan-700 text-white shadow-sm transition"
                  :disabled="filterForm.processing"
                >
                  Aplicar filtros
                </button>
              </div>
            </form>
          </div>

          <!-- Errores de validación -->
          <div v-if="Object.keys(filterForm.errors).length" class="mt-3 text-xs text-red-500 space-y-1">
            <div v-for="(error, key) in filterForm.errors" :key="key">
              • {{ error }}
            </div>
          </div>
        </section>

        <!-- ===========================
             TARJETAS RESUMEN
             =========================== -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5 text-xs md:text-sm">
          <!-- Total recaudado -->
          <div class="bg-white dark:bg-gray-800 border border-cyan-100 dark:border-gray-700 rounded-xl p-4 shadow-sm flex flex-col justify-between">
            <div>
              <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Total recaudado
              </p>
              <p class="mt-2 text-lg md:text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                {{ formatCurrency(resumen.totalRecaudado) }}
              </p>
            </div>
            <p class="mt-3 text-[11px] md:text-xs text-gray-500 dark:text-gray-400">
              Cantidad de pagos: <span class="font-semibold">{{ formatNumber(resumen.cantidadPagos) }}</span>
            </p>
          </div>


          <!-- Dentro de la sección de tarjetas resumen -->
          <div class="bg-white dark:bg-gray-800 border border-cyan-100 dark:border-gray-700 rounded-xl p-4 shadow-sm flex flex-col justify-between">
            <div>
              <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Conexiones
              </p>
              <p class="mt-2 text-lg md:text-2xl font-bold text-teal-600 dark:text-teal-400">
                {{ formatNumber(resumen.totalConexiones) }} conexiones
              </p>
            </div>
            <p class="mt-3 text-[11px] md:text-xs text-gray-500 dark:text-gray-400">
              Consumo promedio: 
              <span class="font-semibold">
                {{ formatNumber(resumen.consumoPromedioM3) }} m³
              </span>
            </p>
          </div>


          <!-- Facturas y deuda -->
          <div class="bg-white dark:bg-gray-800 border border-cyan-100 dark:border-gray-700 rounded-xl p-4 shadow-sm flex flex-col justify-between">
            <div>
              <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Facturación
              </p>
              <p class="mt-2 text-lg md:text-2xl font-bold text-cyan-600 dark:text-cyan-400">
                {{ formatNumber(resumen.totalFacturas) }} facturas
              </p>
            </div>
            <p class="mt-3 text-[11px] md:text-xs text-gray-500 dark:text-gray-400">
              Deuda pendiente: <span class="font-semibold text-rose-600 dark:text-rose-400">{{ formatCurrency(resumen.totalDeudaPendiente) }}</span>
            </p>
          </div>

          <!-- Afiliados -->
          <div class="bg-white dark:bg-gray-800 border border-cyan-100 dark:border-gray-700 rounded-xl p-4 shadow-sm flex flex-col justify-between">
            <div>
              <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Afiliados
              </p>
              <p class="mt-2 text-lg md:text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                {{ formatNumber(resumen.totalAfiliados) }} afiliados
              </p>
            </div>
            <p class="mt-3 text-[11px] md:text-xs text-gray-500 dark:text-gray-400 space-y-0.5">
              <span class="block">
                Nuevos en el periodo: <span class="font-semibold">{{ formatNumber(resumen.nuevosAfiliados) }}</span>
              </span>
              <span class="block">
                Adultos mayores: <span class="font-semibold">{{ formatNumber(resumen.totalAdultosMayores) }}</span>
              </span>
            </p>
          </div>

          <!-- Reclamos y lecturas -->
          <div class="bg-white dark:bg-gray-800 border border-cyan-100 dark:border-gray-700 rounded-xl p-4 shadow-sm flex flex-col justify-between">
            <div>
              <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Operaciones
              </p>
              <p class="mt-2 text-lg md:text-2xl font-bold text-amber-600 dark:text-amber-400">
                {{ formatNumber(resumen.reclamosRecibidos) }} reclamos
              </p>
            </div>
            <p class="mt-3 text-[11px] md:text-xs text-gray-500 dark:text-gray-400 space-y-0.5">
              <span class="block">
                Resueltos / cerrados: <span class="font-semibold">{{ formatNumber(resumen.reclamosResueltos) }}</span>
              </span>
              <span class="block">
                Lecturas registradas: <span class="font-semibold">{{ formatNumber(resumen.lecturasRegistradas) }}</span>
              </span>
            </p>
          </div>
        </section>

        <!-- ===========================
             DETALLE DE PAGOS
             =========================== -->
        <section class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-cyan-100 dark:border-gray-700 p-4 md:p-5 text-xs md:text-sm">
          <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100">
              Pagos por método
            </h3>
          </div>

          <div v-if="detallePagos.porMetodo?.length" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-900/40">
                <tr>
                  <th class="px-3 py-2 text-left text-[11px] md:text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                    Método de pago
                  </th>
                  <th class="px-3 py-2 text-right text-[11px] md:text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                    Cantidad
                  </th>
                  <th class="px-3 py-2 text-right text-[11px] md:text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                    Total recaudado
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                <tr
                  v-for="row in detallePagos.porMetodo"
                  :key="row.forma_pago || 'sin-metodo'"
                  class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                >
                  <td class="px-3 py-2 whitespace-nowrap text-gray-800 dark:text-gray-100">
                    {{ row.forma_pago || 'Sin especificar' }}
                  </td>
                  <td class="px-3 py-2 whitespace-nowrap text-right text-gray-700 dark:text-gray-200">
                    {{ formatNumber(row.cantidad) }}
                  </td>
                  <td class="px-3 py-2 whitespace-nowrap text-right text-gray-700 dark:text-gray-200">
                    {{ formatCurrency(row.total_monto) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-else class="text-gray-500 dark:text-gray-400 text-xs">
            No se registraron pagos en el periodo seleccionado.
          </p>
        </section>

        <!-- ===========================
             DETALLE DE FACTURAS
             =========================== -->
        <section class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-cyan-100 dark:border-gray-700 p-4 md:p-5 text-xs md:text-sm">
          <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100">
              Facturas por estado
            </h3>
          </div>

          <div v-if="detalleFacturas.porEstado?.length" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-900/40">
                <tr>
                  <th class="px-3 py-2 text-left text-[11px] md:text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                    Estado
                  </th>
                  <th class="px-3 py-2 text-right text-[11px] md:text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                    Cantidad
                  </th>
                  <th class="px-3 py-2 text-right text-[11px] md:text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                    Monto total
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                <tr
                  v-for="row in detalleFacturas.porEstado"
                  :key="row.estado || 'sin-estado'"
                  class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                >
                  <td class="px-3 py-2 whitespace-nowrap text-gray-800 dark:text-gray-100">
                    {{ row.estado || 'Sin estado' }}
                  </td>
                  <td class="px-3 py-2 whitespace-nowrap text-right text-gray-700 dark:text-gray-200">
                    {{ formatNumber(row.cantidad) }}
                  </td>
                  <td class="px-3 py-2 whitespace-nowrap text-right text-gray-700 dark:text-gray-200">
                    {{ formatCurrency(row.total_monto) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-else class="text-gray-500 dark:text-gray-400 text-xs">
            No se generaron facturas en el periodo seleccionado.
          </p>
        </section>

        <!-- ===========================
             DETALLE DE AFILIADOS
             =========================== -->
        <section class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-cyan-100 dark:border-gray-700 p-4 md:p-5 text-xs md:text-sm">
          <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100">
              Afiliados por estado de servicio
            </h3>
          </div>

          <div v-if="detalleAfiliados.porEstadoServicio?.length" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-900/40">
                <tr>
                  <th class="px-3 py-2 text-left text-[11px] md:text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                    Estado de servicio
                  </th>
                  <th class="px-3 py-2 text-right text-[11px] md:text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                    Cantidad
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                <tr
                  v-for="row in detalleAfiliados.porEstadoServicio"
                  :key="row.estado_servicio || 'sin-estado-servicio'"
                  class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                >
                  <td class="px-3 py-2 whitespace-nowrap text-gray-800 dark:text-gray-100">
                    {{ row.estado_servicio || 'Sin estado' }}
                  </td>
                  <td class="px-3 py-2 whitespace-nowrap text-right text-gray-700 dark:text-gray-200">
                    {{ formatNumber(row.cantidad) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-else class="text-gray-500 dark:text-gray-400 text-xs">
            No se encontraron afiliados con los filtros seleccionados.
          </p>
        </section>

        <!-- ===========================
             DETALLE DE RECLAMOS
             =========================== -->
        <section class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-cyan-100 dark:border-gray-700 p-4 md:p-5 text-xs md:text-sm mb-4">
          <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100">
              Reclamos por estado
            </h3>
          </div>

          <div v-if="detalleReclamos.porEstado?.length" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-900/40">
                <tr>
                  <th class="px-3 py-2 text-left text-[11px] md:text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                    Estado
                  </th>
                  <th class="px-3 py-2 text-right text-[11px] md:text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                    Cantidad
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                <tr
                  v-for="row in detalleReclamos.porEstado"
                  :key="row.estado || 'sin-estado-reclamo'"
                  class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                >
                  <td class="px-3 py-2 whitespace-nowrap text-gray-800 dark:text-gray-100">
                    {{ row.estado || 'Sin estado' }}
                  </td>
                  <td class="px-3 py-2 whitespace-nowrap text-right text-gray-700 dark:text-gray-200">
                    {{ formatNumber(row.cantidad) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-else class="text-gray-500 dark:text-gray-400 text-xs">
            No se registraron reclamos en el periodo seleccionado.
          </p>
        </section>

      </div>
      <ViewCounter />
    </div>
  </AppLayout>
</template>
