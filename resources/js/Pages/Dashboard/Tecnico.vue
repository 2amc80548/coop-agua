<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseLineChart from '@/Components/Charts/BaseLineChart.vue';

const props = defineProps({
  kpis: Object,
  charts: Object,
  avanceZonas: Array,
  periodoActual: String,
});

const lineLabels = props.charts.rendimientoMes.map(i => i.periodo);
const lineData = props.charts.rendimientoMes.map(i => i.total);
</script>

<template>
  <AppLayout title="Dashboard Técnico">

    <!-- ENCABEZADO -->
    <template #header>
      <div>
        <p class="text-[11px] uppercase tracking-wide text-cyan-600 dark:text-cyan-300">
          Panel Técnico
        </p>

        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100">
          Dashboard de Lecturas
        </h2>

        <p class="text-xs text-gray-500 dark:text-gray-400">
          Período actual:
          <span class="font-semibold text-cyan-600 dark:text-cyan-300">
            {{ periodoActual }}
          </span>
        </p>
      </div>
    </template>


    <!-- CONTENIDO DEL DASHBOARD -->
    <div class="py-6">
      <div class="max-w-7xl mx-auto space-y-6 px-4">

        <!-- ======================================================
            TARJETAS KPI (MISMO ESTILO ADMIN)
        ======================================================= -->
        <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 md:gap-5">

          <!-- Lecturas del mes -->
          <div class="relative overflow-hidden bg-gradient-to-br from-cyan-500/90 to-cyan-700 rounded-2xl p-5 shadow-md text-white">
            <div class="absolute -right-8 -top-8 w-24 h-24 bg-white/10 rounded-full"></div>
            <p class="text-sm uppercase tracking-wide opacity-90">Lecturas del mes</p>
            <p class="text-4xl font-bold">{{ kpis.lecturasMes }}</p>
          </div>

          <!-- Pendientes -->
          <div class="relative overflow-hidden bg-gradient-to-br from-rose-500/90 to-rose-700 rounded-2xl p-5 shadow-md text-white">
            <div class="absolute -right-8 -top-8 w-24 h-24 bg-white/10 rounded-full"></div>
            <p class="text-sm uppercase tracking-wide opacity-90">Pendientes del mes</p>
            <p class="text-4xl font-bold">{{ kpis.pendientesMes }}</p>
          </div>

          <!-- Conexiones activas -->
          <div class="relative overflow-hidden bg-gradient-to-br from-emerald-500/90 to-emerald-700 rounded-2xl p-5 shadow-md text-white">
            <div class="absolute -right-8 -top-8 w-24 h-24 bg-white/10 rounded-full"></div>
            <p class="text-sm uppercase tracking-wide opacity-90">Conexiones activas</p>
            <p class="text-4xl font-bold">{{ kpis.totalConexiones }}</p>
          </div>

          <!-- Avance del mes -->
          <div class="relative overflow-hidden bg-gradient-to-br from-indigo-500/90 to-indigo-700 rounded-2xl p-5 shadow-md text-white">
            <div class="absolute -right-8 -top-8 w-24 h-24 bg-white/10 rounded-full"></div>
            <p class="text-sm uppercase tracking-wide opacity-90">Avance del mes</p>
            <p class="text-4xl font-bold">{{ kpis.avanceMes }}%</p>
          </div>

        </section>




        <!-- ======================================================
            GRÁFICO LINEAL – LECTURAS ÚLTIMOS MESES
        ======================================================= -->
        <section class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-cyan-100 dark:border-gray-700 p-5">
          <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-3">
            Lecturas realizadas en los últimos meses
          </h3>

          <div v-if="lineLabels.length" class="h-64">
            <BaseLineChart
              :labels="lineLabels"
              :datasets="[
                { label: 'Lecturas', data: lineData }
              ]"
            />
          </div>

          <p v-else class="text-xs text-gray-400">
            No hay suficientes datos de meses anteriores.
          </p>
        </section>




        <!-- ======================================================
            TABLA – AVANCE POR ZONA (POR MES)
        ======================================================= -->
        <section class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-indigo-100 dark:border-gray-700 p-5">
          <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-4">
            Avance por zona (mes actual)
          </h3>

          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">

            <!-- Encabezado -->
            <thead class="bg-gray-50 dark:bg-gray-900/40">
              <tr>
                <th class="px-3 py-2 text-left">Zona</th>
                <th class="px-3 py-2 text-center">Lecturadas</th>
                <th class="px-3 py-2 text-center">Total</th>
                <th class="px-3 py-2 text-right">Avance</th>
              </tr>
            </thead>

            <!-- Filas -->
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
              <tr v-for="z in avanceZonas" :key="z.zona">
                <td class="px-3 py-2">{{ z.zona }}</td>
                <td class="px-3 py-2 text-center font-semibold text-cyan-600">{{ z.lecturadas }}</td>
                <td class="px-3 py-2 text-center">{{ z.total_conexiones }}</td>
                <td class="px-3 py-2 text-right font-bold text-indigo-600">
                  {{ z.total_conexiones ? ((z.lecturadas / z.total_conexiones) * 100).toFixed(1) : 0 }}%
                </td>
              </tr>
            </tbody>

          </table>
        </section>


      </div>
    </div>

  </AppLayout>
</template>
