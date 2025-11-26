<script setup>
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

import BaseLineChart from '@/Components/Charts/BaseLineChart.vue';
import BaseBarChart from '@/Components/Charts/BaseBarChart.vue';
import BaseDoughnutChart from '@/Components/Charts/BaseDoughnutChart.vue';
import ViewCounter from '@/Components/ViewCounter.vue';

const props = defineProps({
  kpis: { type: Object, required: true },
  charts: { type: Object, required: true },
  tops: { type: Object, required: true },
  recent: { type: Object, required: true },
});

const formatCurrency = (value) =>
  new Intl.NumberFormat('es-BO', {
    style: 'currency',
    currency: 'BOB',
    maximumFractionDigits: 2,
  }).format(Number(value || 0));

const formatNumber = (value) =>
  new Intl.NumberFormat('es-BO').format(Number(value || 0));

const rangoMesTexto = computed(() => {
  if (!props.kpis?.inicioMes || !props.kpis?.finMes) return '';
  return `${props.kpis.inicioMes} al ${props.kpis.finMes}`;
});

/* === DATA PARA LOS GRÁFICOS === */

const facturacionLabels = computed(() =>
  (props.charts.facturacionPorMes || []).map((i) => i.mes),
);
const facturacionData = computed(() =>
  (props.charts.facturacionPorMes || []).map((i) => Number(i.total_facturado || 0)),
);

const recaudacionLabels = computed(() =>
  (props.charts.recaudacionPorMes || []).map((i) => i.mes),
);
const recaudacionData = computed(() =>
  (props.charts.recaudacionPorMes || []).map((i) => Number(i.total_recaudado || 0)),
);

const lineLabels = computed(() =>
  facturacionLabels.value.length
    ? facturacionLabels.value
    : recaudacionLabels.value,
);
const lineDatasets = computed(() => [
  {
    label: 'Facturado',
    data: facturacionData.value,
  },
  {
    label: 'Recaudado',
    data: recaudacionData.value,
  },
]);

const afiliadosNuevosLabels = computed(() =>
  (props.charts.nuevosAfiliadosPorMes || []).map((i) => i.mes),
);
const afiliadosNuevosData = computed(() =>
  (props.charts.nuevosAfiliadosPorMes || []).map((i) => Number(i.cantidad || 0)),
);

const facturasEstadoLabels = computed(() =>
  (props.charts.facturasPorEstadoMes || []).map((i) => i.estado || 'Sin estado'),
);
const facturasEstadoData = computed(() =>
  (props.charts.facturasPorEstadoMes || []).map((i) => Number(i.cantidad || 0)),
);

const afiliadosEstadoLabels = computed(() =>
  (props.charts.afiliadosPorEstadoServicio || []).map(
    (i) => i.estado_servicio || 'Sin estado',
  ),
);
const afiliadosEstadoData = computed(() =>
  (props.charts.afiliadosPorEstadoServicio || []).map(
    (i) => Number(i.cantidad || 0),
  ),
);

const conexionesZonaLabels = computed(() =>
  (props.charts.conexionesPorZona || []).map((i) => i.zona),
);
const conexionesZonaData = computed(() =>
  (props.charts.conexionesPorZona || []).map((i) => Number(i.cantidad || 0)),
);
</script>

<template>
  <AppLayout title="Panel administrativo">
    <template #header>
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
        <div>
          <p class="text-[11px] uppercase tracking-wide text-cyan-600 dark:text-cyan-300">
            Panel administrativo
          </p>
          <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            Dashboard general
          </h2>
          <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400">
            Resumen ejecutivo del sistema &mdash; mes actual:
            <span class="font-semibold text-cyan-600 dark:text-cyan-300">
              {{ rangoMesTexto }}
            </span>
          </p>
        </div>
      </div>
    </template>

    <div class="py-4 md:py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- ==========================
             KPIs PRINCIPALES
             ========================== -->
        <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 md:gap-5">
      
      <div class="relative overflow-hidden bg-gradient-to-br from-cyan-500/90 to-cyan-700 rounded-2xl p-5 shadow-md text-white">
        <div class="absolute -right-8 -top-8 w-24 h-24 bg-white/10 rounded-full"></div>
        <div class="absolute -right-12 top-10 w-32 h-32 bg-white/5 rounded-full"></div>
        <div class="relative space-y-2">
          <p class="text-sm md:text-base font-medium uppercase tracking-wide opacity-90">
            Monto facturado (mes)
          </p>
          <p class="text-3xl md:text-4xl font-bold">
            {{ formatCurrency(kpis.montoFacturadoMes) }}
          </p>
          <div class="space-y-1 mt-2">
            <p class="text-sm text-cyan-50">
              Facturas emitidas:
              <span class="font-bold text-base">{{ formatNumber(kpis.totalFacturasMes) }}</span>
            </p>
            <p class="text-sm text-rose-100">
              Deuda pendiente:
              <span class="font-bold text-base">
                {{ formatCurrency(kpis.montoDeudaMes) }}
              </span>
            </p>
          </div>
        </div>
      </div>

      <div class="relative overflow-hidden bg-gradient-to-br from-emerald-500/90 to-emerald-700 rounded-2xl p-5 shadow-md text-white">
        <div class="absolute -right-10 -top-10 w-24 h-24 bg-white/10 rounded-full"></div>
        <div class="absolute -right-14 top-12 w-32 h-32 bg-white/5 rounded-full"></div>
        <div class="relative space-y-2">
          <p class="text-sm md:text-base font-medium uppercase tracking-wide opacity-90">
            Monto recaudado (mes)
          </p>
          <p class="text-3xl md:text-4xl font-bold">
            {{ formatCurrency(kpis.montoRecaudadoMes) }}
          </p>
          <div class="mt-2">
            <p class="text-sm text-emerald-50">
              Pagos registrados:
              <span class="font-bold text-base">{{ formatNumber(kpis.totalPagosMes) }}</span>
            </p>
          </div>
        </div>
      </div>

      <div class="relative overflow-hidden bg-white dark:bg-gray-800 border border-indigo-100 dark:border-indigo-700/40 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
        <div class="relative space-y-2">
          <p class="text-sm md:text-base font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
            Afiliados
          </p>
          <p class="text-3xl md:text-4xl font-bold text-indigo-600 dark:text-indigo-400">
            {{ formatNumber(kpis.totalAfiliados) }}
          </p>
          
          <p class="text-sm text-gray-600 dark:text-gray-300">
            Nuevos en el mes:
            <span class="font-bold text-indigo-600 dark:text-indigo-300 text-base">
              {{ formatNumber(kpis.nuevosAfiliadosMes) }}
            </span>
          </p>

          <div class="pt-2 border-t border-gray-100 dark:border-gray-700 mt-2">
            <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-400">
              Activos:
              <span class="font-bold text-emerald-600 dark:text-emerald-300">
                {{ formatNumber(kpis.afiliadosActivos) }}
              </span>,
              En corte:
              <span class="font-bold text-amber-600 dark:text-amber-300">
                {{ formatNumber(kpis.afiliadosEnMora) }}
              </span>,
              Cortados:
              <span class="font-bold text-rose-600 dark:text-rose-300">
                {{ formatNumber(kpis.afiliadosCortados) }}
              </span>,
              Pendientes:
              <span class="font-bold text-yellow-600 dark:text-yellow-300">
                {{ formatNumber(kpis.afiliadosPendientes) }}
              </span>
            </p>
          </div>
        </div>
      </div>

      <div class="relative overflow-hidden bg-white dark:bg-gray-800 border border-amber-100 dark:border-amber-700/40 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
        <div class="relative space-y-2">
          <p class="text-sm md:text-base font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
            Sistema
          </p>
          <p class="text-3xl md:text-4xl font-bold text-amber-600 dark:text-amber-400">
            {{ formatNumber(kpis.totalUsuarios) }} <span class="text-lg text-gray-400 font-normal">usuarios</span>
          </p>
          
          <div class="space-y-1 mt-2">
            <p class="text-sm text-gray-600 dark:text-gray-300">
              Nuevos usuarios (mes):
              <span class="font-bold text-amber-600 dark:text-amber-300 text-base">
                {{ formatNumber(kpis.nuevosUsuariosMes) }}
              </span>
            </p>
            <p class="text-sm text-gray-600 dark:text-gray-300">
              Conexiones totales:
              <span class="font-bold text-cyan-600 dark:text-cyan-300 text-base">
                {{ formatNumber(kpis.totalConexiones) }}
              </span>
            </p>
          </div>
        </div>
      </div>

</section>


          <!-- ==========================
             TORTAS / DISTRIBUCIONES
             ========================== -->
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-5 text-xs md:text-sm">
          <!-- Facturas por estado -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-amber-100 dark:border-gray-700 p-4 md:p-5">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-3">
              Facturas por estado (mes)
            </h3>
            <div v-if="facturasEstadoLabels.length" class="h-64">
              <BaseDoughnutChart
                :labels="facturasEstadoLabels"
                :data="facturasEstadoData"
              />
            </div>
            <p v-else class="text-xs text-gray-500 dark:text-gray-400">
              No hay facturas en el mes.
            </p>
          </div>

          <!-- Afiliados por estado de servicio -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-emerald-100 dark:border-gray-700 p-4 md:p-5">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-3">
              Afiliados por estado de servicio
            </h3>
            <div v-if="afiliadosEstadoLabels.length" class="h-64">
              <BaseDoughnutChart
                :labels="afiliadosEstadoLabels"
                :data="afiliadosEstadoData"
              />
            </div>
            <p v-else class="text-xs text-gray-500 dark:text-gray-400">
              No hay datos de estados de servicio.
            </p>
          </div>

          <!-- Conexiones por zona -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-teal-100 dark:border-gray-700 p-4 md:p-5">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-3">
              Top zonas por conexiones
            </h3>
            <div v-if="conexionesZonaLabels.length" class="h-64">
              <BaseBarChart
                :labels="conexionesZonaLabels"
                :data="conexionesZonaData"
                label="Conexiones"
              />
            </div>
            <p v-else class="text-xs text-gray-500 dark:text-gray-400">
              No hay datos de zonas con conexiones.
            </p>
          </div>
        </section>
        <!-- ==========================
             GRÁFICO LINEAL PRINCIPAL
             ========================== -->
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-5 text-xs md:text-sm">
          <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-cyan-100 dark:border-gray-700 p-4 md:p-5">
            <div class="flex items-center justify-between mb-3">
              <h3 class="font-semibold text-gray-800 dark:text-gray-100">
                Facturación vs Recaudación (últimos 6 meses)
              </h3>
            </div>
            <div v-if="lineLabels.length" class="h-64">
              <BaseLineChart :labels="lineLabels" :datasets="lineDatasets" />
            </div>
            <p v-else class="text-xs text-gray-500 dark:text-gray-400">
              No hay datos suficientes para el período.
            </p>
          </div>

          <!-- Afiliados nuevos -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-indigo-100 dark:border-gray-700 p-4 md:p-5">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-3">
              Nuevos afiliados (últimos 6 meses)
            </h3>
            <div v-if="afiliadosNuevosLabels.length" class="h-64">
              <BaseBarChart
                :labels="afiliadosNuevosLabels"
                :data="afiliadosNuevosData"
                label="Afiliados nuevos"
              />
            </div>
            <p v-else class="text-xs text-gray-500 dark:text-gray-400">
              No hay datos de nuevas afiliaciones.
            </p>
          </div>
        </section>

        

        <!-- ==========================
             TOPS DEL MES
             ========================== -->
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-5 text-xs md:text-sm">
          <!-- Top deudores -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-rose-100 dark:border-rose-700/60 p-4 md:p-5">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-3">
              Top 10 deudores del mes
            </h3>
            <div v-if="tops.deudores?.length" class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/40">
                  <tr>
                    <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-600 dark:text-gray-300">
                      Afiliado
                    </th>
                    <th class="px-3 py-2 text-right text-[11px] font-semibold text-gray-600 dark:text-gray-300">
                      Deuda total
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                  <tr
                    v-for="row in tops.deudores"
                    :key="row.id"
                    class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                  >
                    <td class="px-3 py-2 text-gray-800 dark:text-gray-100">
                      {{ row.nombre_completo }}
                    </td>
                    <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200">
                      {{ formatCurrency(row.deuda_total) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-else class="text-xs text-gray-500 dark:text-gray-400">
              No se registran deudas en el mes.
            </p>
          </div>

          <!-- Top puntuales -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-emerald-100 dark:border-emerald-700/60 p-4 md:p-5">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-3">
              Top 10 más puntuales en pagar
            </h3>
            <div v-if="tops.puntuales?.length" class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/40">
                  <tr>
                    <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-600 dark:text-gray-300">
                      Afiliado
                    </th>
                    <th class="px-3 py-2 text-right text-[11px] font-semibold text-gray-600 dark:text-gray-300">
                      Facturas
                    </th>
                    <th class="px-3 py-2 text-right text-[11px] font-semibold text-gray-600 dark:text-gray-300">
                      % puntualidad
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                  <tr
                    v-for="row in tops.puntuales"
                    :key="row.id"
                    class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                  >
                    <td class="px-3 py-2 text-gray-800 dark:text-gray-100">
                      {{ row.nombre_completo }}
                    </td>
                    <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200">
                      {{ formatNumber(row.total_facturas) }}
                    </td>
                    <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200">
                      {{ row.porcentaje_puntualidad }}%
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-else class="text-xs text-gray-500 dark:text-gray-400">
              No hay suficiente historial para calcular la puntualidad.
            </p>
          </div>
        </section>

        <!-- ==========================
             ACTIVIDAD RECIENTE
             ========================== -->
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-5 text-xs md:text-sm mb-4">
          <!-- Últimos pagos -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-cyan-100 dark:border-gray-700 p-4 md:p-5">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-3">
              Últimos pagos registrados
            </h3>
            <div v-if="recent.pagos?.length" class="space-y-2">
              <div
                v-for="p in recent.pagos"
                :key="p.id"
                class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-2 last:border-b-0"
              >
                <div class="flex-1 pr-2">
                  <p class="text-[11px] font-semibold text-gray-800 dark:text-gray-100">
                    {{ formatCurrency(p.monto_pagado) }}
                  </p>
                  <p class="text-[11px] text-gray-500 dark:text-gray-400">
                    {{ p.factura?.conexion?.afiliado?.nombre_completo || 'Sin afiliado' }}
                  </p>
                </div>
                <div class="text-[11px] text-right text-gray-500 dark:text-gray-400">
                  <div>{{ p.fecha_pago }}</div>
                  <div class="italic">
                    {{ p.metodo_pago || p.forma_pago || '—' }}
                  </div>
                </div>
              </div>
            </div>
            <p v-else class="text-xs text-gray-500 dark:text-gray-400">
              No se registraron pagos recientes.
            </p>
          </div>

          <!-- Últimos reclamos -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-amber-100 dark:border-gray-700 p-4 md:p-5">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-3">
              Últimos reclamos
            </h3>
            <div v-if="recent.reclamos?.length" class="space-y-2">
              <div
                v-for="r in recent.reclamos"
                :key="r.id"
                class="flex items-start justify-between border-b border-gray-100 dark:border-gray-700 pb-2 last:border-b-0"
              >
                <div class="flex-1 pr-2">
                  <p class="text-[11px] font-semibold text-gray-800 dark:text-gray-100">
                    {{ r.titulo || 'Reclamo' }}
                  </p>
                  <p class="text-[11px] text-gray-500 dark:text-gray-400 line-clamp-2">
                    {{ r.descripcion }}
                  </p>
                </div>
                <div class="text-[11px] text-right text-gray-500 dark:text-gray-400">
                  <div class="font-semibold">{{ r.estado }}</div>
                  <div>{{ r.created_at?.substring(0, 10) }}</div>
                  <div class="mt-0.5">
                    {{ r.afiliado?.nombre_completo || '' }}
                  </div>
                </div>
              </div>
            </div>
            <p v-else class="text-xs text-gray-500 dark:text-gray-400">
              No se registraron reclamos recientes.
            </p>
          </div>
        </section>

      </div>
      <ViewCounter />
    </div>
  </AppLayout>
</template>
