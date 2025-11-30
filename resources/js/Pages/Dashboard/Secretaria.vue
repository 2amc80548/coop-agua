<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

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

/* === DATA CHARTS === */
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
  { label: 'Facturado', data: facturacionData.value },
  { label: 'Recaudado', data: recaudacionData.value },
]);

const afiliadosNuevosLabels = computed(() =>
  (props.charts.nuevosAfiliadosPorMes || []).map((i) => i.mes),
);
const afiliadosNuevosData = computed(() =>
  (props.charts.nuevosAfiliadosPorMes || []).map((i) => Number(i.cantidad || 0)),
);

const facturasEstadoLabels = computed(() =>
  (props.charts.facturasPorEstadoMes || []).map((i) => i.estado || '—'),
);
const facturasEstadoData = computed(() =>
  (props.charts.facturasPorEstadoMes || []).map((i) => Number(i.cantidad || 0)),
);

const afiliadosEstadoLabels = computed(() =>
  (props.charts.afiliadosPorEstadoServicio || []).map(
    (i) => i.estado_servicio || '—',
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
  <AppLayout title="Panel de Secretaría">
    <template #header>
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
        <div>
          <p class="text-[11px] uppercase tracking-wide text-cyan-600 dark:text-cyan-300">
            Panel de Secretaría
          </p>
          <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            Dashboard general
          </h2>
          <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400">
            Resumen del sistema — mes actual:
            <span class="font-semibold text-cyan-600 dark:text-cyan-300">
              {{ rangoMesTexto }}
            </span>
          </p>
        </div>
      </div>
    </template>

    <div class="py-4 md:py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- ===================
             KPIS PRINCIPALES
        ======================-->
        <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 md:gap-5">

          <!-- Facturación del mes -->
          <div class="relative overflow-hidden bg-gradient-to-br from-cyan-500/90 to-cyan-700 rounded-2xl p-5 shadow-md text-white">
            <div class="absolute -right-8 -top-8 w-24 h-24 bg-white/10 rounded-full"></div>
            <div class="absolute -right-12 top-10 w-32 h-32 bg-white/5 rounded-full"></div>

            <div class="relative space-y-2">
              <p class="text-sm uppercase tracking-wide opacity-90">Monto facturado (mes)</p>
              <p class="text-3xl font-bold">{{ formatCurrency(kpis.montoFacturadoMes) }}</p>

              <p class="text-sm text-cyan-50">
                Facturas emitidas:
                <span class="font-bold text-base">{{ formatNumber(kpis.totalFacturasMes) }}</span>
              </p>
              <p class="text-sm text-rose-100">
                Deuda pendiente:
                <span class="font-bold text-base">{{ formatCurrency(kpis.montoDeudaMes) }}</span>
              </p>
            </div>
          </div>

          <!-- Recaudación -->
          <div class="relative overflow-hidden bg-gradient-to-br from-emerald-500/90 to-emerald-700 rounded-2xl p-5 shadow-md text-white">
            <div class="absolute -right-10 -top-10 w-24 h-24 bg-white/10 rounded-full"></div>
            <div class="absolute -right-14 top-12 w-32 h-32 bg-white/5 rounded-full"></div>

            <div class="relative space-y-2">
              <p class="text-sm uppercase opacity-90">Monto recaudado (mes)</p>
              <p class="text-3xl font-bold">{{ formatCurrency(kpis.montoRecaudadoMes) }}</p>

              <p class="text-sm text-emerald-50">
                Pagos registrados:
                <span class="font-bold text-base">{{ formatNumber(kpis.totalPagosMes) }}</span>
              </p>
            </div>
          </div>

          <!-- Afiliados -->
          <div class="relative overflow-hidden bg-white dark:bg-gray-800 border border-indigo-100 dark:border-indigo-700/40 rounded-2xl p-5 shadow-sm">
            <div class="relative space-y-2">
              <p class="text-sm font-medium uppercase text-gray-500 dark:text-gray-400">Afiliados</p>

              <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">
                {{ formatNumber(kpis.totalAfiliados) }}
              </p>

              <p class="text-sm text-gray-600 dark:text-gray-300">
                Nuevos en el mes:
                <span class="font-bold text-indigo-600 dark:text-indigo-300 text-base">
                  {{ formatNumber(kpis.nuevosAfiliadosMes) }}
                </span>
              </p>

              <div class="pt-2 border-t border-gray-100 dark:border-gray-700 mt-2">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  Activos:
                  <span class="font-bold text-emerald-600 dark:text-emerald-300">{{ formatNumber(kpis.afiliadosActivos) }}</span>,
                  En corte:
                  <span class="font-bold text-amber-600 dark:text-amber-300">{{ formatNumber(kpis.afiliadosEnMora) }}</span>,
                  Cortados:
                  <span class="font-bold text-rose-600 dark:text-rose-300">{{ formatNumber(kpis.afiliadosCortados) }}</span>
                   Pendientes:
                  <span class="font-bold text-rose-600 dark:text-rose-300">{{ formatNumber(kpis.afiliadosPendientes) }}</span>
                </p>
              </div>
            </div>
          </div>

          <!-- Conexiones -->
          <div class="relative overflow-hidden bg-white dark:bg-gray-800 border border-amber-100 dark:border-amber-700/40 rounded-2xl p-5 shadow-sm">
            <div class="relative space-y-2">
              <p class="text-sm font-medium uppercase text-gray-500 dark:text-gray-400">
                Conexiones registradas
              </p>

              <p class="text-3xl font-bold text-amber-600 dark:text-amber-400">
                {{ formatNumber(kpis.totalConexiones) }}
              </p>
            </div>
          </div>
        </section>

        <!-- =======================
             TORTAS
        ========================-->
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-5 text-xs md:text-sm">

          <!-- Facturas estado -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-amber-100 dark:border-gray-700 p-4">
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

          <!-- Afiliados estado servicio -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-emerald-100 dark:border-gray-700 p-4">
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
              No hay datos para mostrar.
            </p>
          </div>

          <!-- Conexiones zona -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-teal-100 dark:border-gray-700 p-4">
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

            <p v-else class="text-xs text-gray-500 dark:text-gray-400">No hay datos.</p>
          </div>
        </section>

        <!-- =======================
             GRÁFICOS PRINCIPALES
        ========================-->
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-5 text-xs md:text-sm">

          <!-- Facturación vs Recaudación -->
          <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-cyan-100 dark:border-gray-700 p-4">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-3">
              Facturación vs Recaudación (últimos 6 meses)
            </h3>

            <div v-if="lineLabels.length" class="h-64">
              <BaseLineChart :labels="lineLabels" :datasets="lineDatasets" />
            </div>

            <p v-else class="text-xs text-gray-500 dark:text-gray-400">
              No hay datos suficientes.
            </p>
          </div>

          <!-- Nuevos afiliados -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-indigo-100 dark:border-gray-700 p-4">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-3">
              Afiliados nuevos (últimos 6 meses)
            </h3>

            <div v-if="afiliadosNuevosLabels.length" class="h-64">
              <BaseBarChart
                :labels="afiliadosNuevosLabels"
                :data="afiliadosNuevosData"
                label="Afiliados"
              />
            </div>

            <p v-else class="text-xs text-gray-500 dark:text-gray-400">
              No hay datos registrados.
            </p>
          </div>
        </section>

        <!-- =======================
             TOPS y RECIENTES
        ========================-->
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-5 mb-4">

          <!-- Top deudores -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-rose-100 dark:border-rose-700/60 p-4">
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
                  <tr v-for="row in tops.deudores" :key="row.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
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

            <p v-else class="text-xs text-gray-500 dark:text-gray-400">No hay deudas.</p>
          </div>

          <!-- Últimos reclamos -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-amber-100 dark:border-gray-700 p-4">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-3">
              Últimos reclamos ingresados
            </h3>

            <div v-if="recent.reclamos?.length" class="space-y-2">
              <div v-for="r in recent.reclamos" :key="r.id" class="flex items-start justify-between border-b border-gray-100 dark:border-gray-700 pb-2 last:border-b-0">
                <div class="flex-1 pr-2">
                  <p class="text-[12px] font-semibold text-gray-800 dark:text-gray-100">
                    {{ r.titulo || 'Reclamo' }}
                  </p>
                  <p class="text-[11px] text-gray-500 dark:text-gray-400 line-clamp-2">
                    {{ r.descripcion }}
                  </p>
                </div>

                <div class="text-right text-[11px] text-gray-500 dark:text-gray-400">
                  <div>{{ r.estado }}</div>
                  <div>{{ r.created_at?.substring(0, 10) }}</div>
                  <div class="mt-1 text-gray-700 dark:text-gray-200">
                    {{ r.afiliado?.nombre_completo }}
                  </div>
                </div>
              </div>
            </div>

            <p v-else class="text-xs text-gray-500 dark:text-gray-400">No hay reclamos recientes.</p>
          </div>
        </section>

        <!-- =======================
             ÚLTIMOS PAGOS
        ========================-->
        <section class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-cyan-100 dark:border-gray-700 p-4 md:p-5">
          <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-3">
            Últimos pagos registrados
          </h3>

          <div v-if="recent.pagos?.length" class="space-y-2">
            <div v-for="p in recent.pagos" :key="p.id" class="border-b border-gray-100 dark:border-gray-700 pb-2 last:border-b-0">
              <p class="text-[12px] font-semibold text-gray-800 dark:text-gray-100">
                {{ formatCurrency(p.monto_pagado) }}
              </p>
              <p class="text-[11px] text-gray-500 dark:text-gray-400">
                {{ p.factura?.conexion?.afiliado?.nombre_completo || 'Sin afiliado' }}
              </p>
              <p class="text-[11px] text-gray-500 dark:text-gray-400">
                {{ p.fecha_pago }}
              </p>
            </div>
          </div>

          <p v-else class="text-xs text-gray-500 dark:text-gray-400">No hay pagos recientes.</p>
        </section>

      </div>

      <ViewCounter />
    </div>
  </AppLayout>
</template>
