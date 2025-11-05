<script setup>
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch } from 'vue';

const props = defineProps({
    pagos: Object, // Paginado
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
        router.post(route('pagos.anular', pago.id), {}, { // Asumiendo que creas esta ruta
            preserveScroll: true,
        });
    }
};

</script>

<template>
  <AppLayout title="Historial de Pagos">
    <div class="p-4 md:p-6">
      <h1 class="text-2xl font-bold mb-6 text-gray-700">Historial de Pagos</h1>
      
      <div v-if="$page.props.flash.success" class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-4 shadow-sm" role="alert">
        <p class="font-bold">Éxito</p> <p>{{ $page.props.flash.success }}</p>
      </div>
      <div v-if="$page.props.flash.error || Object.keys($page.props.errors).length > 0" class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4 shadow-sm" role="alert">
         <p class="font-bold">Error</p>
         <p>{{ $page.props.flash.error || 'Ocurrió un error.' }}</p>
         <ul class="list-disc ml-5 text-sm"><li v-for="(error, key) in $page.props.errors" :key="key">{{ error }}</li></ul>
       </div>

      <div class="mb-4 p-4 bg-white rounded shadow-sm grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
        <div>
          <label for="search" class="block font-medium mb-1 text-gray-700">Buscar (Afiliado, CI, Medidor)</label>
          <input id="search" type="text" v-model="filterForm.search" @input="debouncedSearch" placeholder="Escriba..." 
                 class="border-gray-300 rounded-md shadow-sm block w-full text-sm ..."/>
        </div>
        <div>
          <label for="fecha_inicio" class="block font-medium mb-1 text-gray-700">Pagos Desde</label>
          <input id="fecha_inicio" type="date" v-model="filterForm.fecha_inicio" class="border-gray-300 rounded-md shadow-sm block w-full text-sm ..."/>
        </div>
        <div>
           <label for="fecha_fin" class="block font-medium mb-1 text-gray-700">Pagos Hasta</label>
           <input id="fecha_fin" type="date" v-model="filterForm.fecha_fin" class="border-gray-300 rounded-md shadow-sm block w-full text-sm ..."/>
        </div>
         <div>
          <label for="forma_pago" class="block font-medium mb-1 text-gray-700">Forma de Pago</label>
          <select id="forma_pago" v-model="filterForm.forma_pago" class="border-gray-300 rounded-md shadow-sm block w-full text-sm ...">
            <option value="">Todas</option>
            <option>Efectivo</option>
            <option>QR</option>
            <option>Tarjeta</option>
            <option>Transferencia</option>
            <option>Cheque</option>
            <option>Otro</option>
          </select>
        </div>
      </div>

      <div class="overflow-x-auto bg-white rounded shadow-md">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Factura / Período</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Afiliado</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Monto Pagado</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Forma / Referencia</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha de Pago</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cajero</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-if="!pagos.data.length"> <td colspan="7" class="py-4 px-4 text-center text-sm text-gray-500">No se encontraron pagos.</td> </tr>
            <tr v-for="p in pagos.data" :key="p.id" class="hover:bg-gray-50 text-sm">
              <td class="px-4 py-2 whitespace-nowrap">
                <Link :href="route('facturas.show', p.factura_id)" class="font-medium text-indigo-600 hover:text-indigo-900">
                    F-{{ p.factura_id.toString().padStart(6, '0') }}
                </Link>
                 <span class="block text-xs text-gray-500">Per: {{ p.factura?.periodo }}</span>
              </td>
              <td class="px-4 py-2 whitespace-nowrap">
                  <div class="font-medium text-gray-900">{{ p.factura?.conexion?.afiliado?.nombre_completo }}</div>
                  <div class="text-xs text-gray-500">CI: {{ p.factura?.conexion?.afiliado?.ci }}</div>
              </td>
              <td class="px-4 py-2 whitespace-nowrap text-right font-bold text-green-700">Bs {{ formatCurrency(p.monto_pagado) }}</td>
              <td class="px-4 py-2 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                      {{ p.forma_pago }}
                  </span>
                   <span v-if="p.referencia" class="block text-xs text-gray-500 mt-1">Ref: {{ p.referencia }}</span>
              </td>
              <td class="px-4 py-2 whitespace-nowrap text-gray-700">{{ formatDate(p.fecha_pago) }}</td>
              <td class="px-4 py-2 whitespace-nowrap text-xs text-gray-500">{{ p.usuarioRegistrado?.name }}</td>
              <td class="px-4 py-2 whitespace-nowrap text-sm font-medium space-x-2">
                <Link :href="route('pagos.show', p.id)" class="text-indigo-600 hover:text-indigo-900" title="Ver Detalle Pago">Ver</Link>
                <button v-if="$page.props.auth.user.role_names.includes('Administrador')" 
                        @click="anularPago(p)" 
                        class="text-red-600 hover:text-red-900" 
                        title="Anular Pago (Revierte la factura)">
                   Anular
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="mt-6 flex justify-between items-center text-sm" v-if="pagos.total > 0">
        <span class="text-gray-700">Mostrando {{ pagos.from }} a {{ pagos.to }} de {{ pagos.total }} pagos</span>
        <div class="flex flex-wrap gap-1">
          <Link v-for="(link, index) in pagos.links" :key="index" :href="link.url ?? '#'" v-html="link.label"
                class="px-3 py-1 border rounded"
                :class="{ 'bg-blue-600 text-white': link.active, 'text-gray-400 cursor-default': !link.url, 'hover:bg-gray-100': link.url }"
                preserve-scroll preserve-state :disabled="!link.url"/>
        </div>
      </div>

    </div>
  </AppLayout>
</template>