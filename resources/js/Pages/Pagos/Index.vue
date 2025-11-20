<script setup>
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch } from 'vue';
import ViewCounter from '@/Components/ViewCounter.vue';

const props = defineProps({
    pagos: Object, 
    filters: Object,
});

const filterForm = useForm({
  search: props.filters.search ?? '',
  fecha_inicio: props.filters.fecha_inicio ?? '',
  fecha_fin: props.filters.fecha_fin ?? '',
  forma_pago: props.filters.forma_pago ?? '',
});

let searchTimeout = null;
const submitFilters = () => { filterForm.get(route('pagos.index'), { preserveState: true, preserveScroll: true, replace: true }); };
const debouncedSearch = () => { clearTimeout(searchTimeout); searchTimeout = setTimeout(submitFilters, 400); };
watch(() => [filterForm.fecha_inicio, filterForm.fecha_fin, filterForm.forma_pago], submitFilters);

const formatDate = (dateString) => {
    if (!dateString) return '—'; 
    try { 
        const date = new Date(dateString); 
        date.setMinutes(date.getMinutes() + date.getTimezoneOffset()); 
        return date.toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' }); 
    } catch { return dateString; }
};
const formatCurrency = (amount) => { return (parseFloat(amount) || 0).toFixed(2); };

// Lógica para Anular un Pago (Solo Admin)
const anularPago = (pago) => {
    if (confirm(`¿Estás seguro de ANULAR este pago (ID: ${pago.id}) por Bs ${formatCurrency(pago.monto_pagado)}?\n\nEsta acción revertirá la Factura F-${pago.factura_id} a 'impaga'.`)) {
        router.post(route('pagos.anular', pago.id), {}, {
            preserveScroll: true,
        });
    }
};

</script>

<template>
  <AppLayout title="Historial de Pagos">
    <div class="p-4 md:p-6">
      <h1 class="text-2xl font-bold mb-6 text-gray-700 dark:text-gray-300">Historial de Pagos</h1>
      
      <div v-if="$page.props.flash.success" class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-4 shadow-sm dark:bg-green-900 dark:border-green-700 dark:text-green-100" role="alert">
        <p class="font-bold">Éxito</p> <p>{{ $page.props.flash.success }}</p>
      </div>
      <div v-if="$page.props.flash.error || Object.keys($page.props.errors).length > 0" class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4 shadow-sm dark:bg-red-900 dark:border-red-700 dark:text-red-100" role="alert">
         <p class="font-bold">Error</p>
         <p>{{ $page.props.flash.error || 'Ocurrió un error.' }}</p>
         <ul class="list-disc ml-5 text-sm"><li v-for="(error, key) in $page.props.errors" :key="key">{{ error }}</li></ul>
        </div>

      <div class="mb-4 p-4 bg-white dark:bg-gray-800 rounded shadow-sm grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
        <div>
          <label for="search" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">Buscar (Afiliado, CI, Medidor)</label>
          <input id="search" type="text" v-model="filterForm.search" @input="debouncedSearch" placeholder="Escriba..." 
                 class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm block w-full text-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"/>
        </div>
        <div>
          <label for="fecha_inicio" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">Pagos Desde</label>
          <input id="fecha_inicio" type="date" v-model="filterForm.fecha_inicio" 
                 class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm block w-full text-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"/>
        </div>
        <div>
           <label for="fecha_fin" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">Pagos Hasta</label>
           <input id="fecha_fin" type="date" v-model="filterForm.fecha_fin" 
                  class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm block w-full text-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"/>
        </div>
          <div>
           <label for="forma_pago" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">Forma de Pago</label>
           <select id="forma_pago" v-model="filterForm.forma_pago" 
                   class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm block w-full text-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
             <option value="">Todas</option>
             <option>Efectivo</option>
             <option>QR</option>

           </select>
         </div>
      </div>

      <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded shadow-md">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Factura / Período</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Afiliado</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Monto Pagado</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Forma / Referencia</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha de Pago</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cajero</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-if="!pagos.data.length"> <td colspan="7" class="py-4 px-4 text-center text-sm text-gray-500 dark:text-gray-400">No se encontraron pagos.</td> </tr>
            <tr v-for="p in pagos.data" :key="p.id" class="hover:bg-gray-50 dark:hover:bg-gray-700 text-sm">
              <td class="px-4 py-2 whitespace-nowrap">
                <Link :href="route('facturas.show', p.factura_id)" class="font-medium text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-200">
                    F-{{ p.factura_id.toString().padStart(6, '0') }}
                </Link>
               <span class="block text-xs text-gray-500 dark:text-gray-400">Per: {{ p.factura?.periodo }}</span>
              </td>
              <td class="px-4 py-2 whitespace-nowrap">
                  <div class="font-medium text-gray-900 dark:text-white">{{ p.factura?.conexion?.afiliado?.nombre_completo }}</div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">CI: {{ p.factura?.conexion?.afiliado?.ci }}</div>
              </td>
              <td class="px-4 py-2 whitespace-nowrap text-right font-bold text-green-700 dark:text-green-400">Bs {{ formatCurrency(p.monto_pagado) }}</td>
              <td class="px-4 py-2 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-100">
                      {{ p.forma_pago }}
                  </span>
                 <span v-if="p.referencia" class="block text-xs text-gray-500 dark:text-gray-400 mt-1">Ref: {{ p.referencia }}</span>
              </td>
              <td class="px-4 py-2 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ formatDate(p.fecha_pago) }}</td>
              <td class="px-4 py-2 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">{{ p.usuarioRegistrado?.name }}</td>
              <td class="px-4 py-2 whitespace-nowrap text-sm font-medium space-x-2">
                <Link :href="route('pagos.show', p.id)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-200" title="Ver Detalle Pago">Ver</Link>
                <!-- <Link v-if="f.estado === 'impaga'" :href="route('pagos.create', { factura_id: f.id })" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-200" title="Registrar Pago">Pagar</Link> -->
                <button v-if="$page.props.auth.user.role_names.includes('Administrador')" 
                         @click="anularPago(p)" 
                         class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-200" 
                         title="Anular Pago (Revierte la factura)">
                    Anular
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="mt-6 flex justify-between items-center text-sm" v-if="pagos.total > 0">
        <span class="text-gray-700 dark:text-gray-300">Mostrando {{ pagos.from }} a {{ pagos.to }} de {{ pagos.total }} pagos</span>
        <div class="flex flex-wrap gap-1">
          <Link v-for="(link, index) in pagos.links" :key="index" :href="link.url ?? '#'" v-html="link.label"
                class="px-3 py-1 border rounded dark:border-gray-600 dark:text-gray-300"
                :class="{ 'bg-blue-600 text-white dark:bg-blue-700 dark:text-white': link.active, 'text-gray-400 cursor-default border-gray-200 dark:text-gray-500 dark:border-gray-700': !link.url, 'hover:bg-gray-100 border-gray-300 dark:hover:bg-gray-700 dark:border-gray-600': link.url }"
                preserve-scroll preserve-state :disabled="!link.url"/>
        </div>
      </div>
      <ViewCounter />
    </div>
  </AppLayout>
</template>