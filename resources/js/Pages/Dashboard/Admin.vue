<template>
  <AppLayout title="Panel de administrador">

    <div class="bg-gray-100 min-h-screen p-8">
      <div class="flex justify-between items-center bg-white shadow rounded-lg p-4 mb-8">
        <h1 class="text-3xl font-extrabold text-gray-800">Panel de Control</h1>
        <button 
          @click="logout"
          class="bg-red-500 text-white font-semibold py-2 px-6 rounded-full hover:bg-red-600 transition duration-300"
        >
          Cerrar Sesión
        </button>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 shadow-md rounded-xl border-l-4 border-blue-500">
          <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Socios</h2>
          <p class="text-4xl font-bold text-gray-800 mt-2">{{ totalSocios }}</p>
        </div>
        <div class="bg-white p-6 shadow-md rounded-xl border-l-4 border-yellow-500">
          <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Usuarios</h2>
          <p class="text-4xl font-bold text-gray-800 mt-2">{{ totalUsuarios }}</p>
        </div>
        <div class="bg-white p-6 shadow-md rounded-xl border-l-4 border-green-500">
          <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Conexiones Activas</h2>
          <p class="text-4xl font-bold text-gray-800 mt-2">{{ conexionesActivas }}</p>
          <p class="text-xs text-red-500 mt-1">Inactivas: {{ conexionesInactivas }}</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-green-50 p-6 rounded-xl shadow-md border border-green-200">
          <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Ingresos (mes)</h2>
          <p class="text-3xl font-bold text-green-600 mt-2">Bs. {{ ingresosMes }}</p>
        </div>
        <div class="bg-red-50 p-6 rounded-xl shadow-md border border-red-200">
          <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Egresos (mes)</h2>
          <p class="text-3xl font-bold text-red-600 mt-2">Bs. {{ egresosMes }}</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white p-6 shadow-md rounded-xl">
          <h2 class="text-lg font-semibold text-gray-700 mb-4">Ingresos vs Egresos (6 meses)</h2>
          <div class="h-80">
            <canvas id="ingresosEgresosChart"></canvas>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-6">
          <div class="bg-white p-6 shadow-md rounded-xl">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Top 5 deudores</h2>
            <ul class="divide-y divide-gray-200">
              <li v-for="d in deudoresTop" :key="d.socio_id" class="flex justify-between items-center py-3">
                <span class="text-gray-800">{{ d.socio_nombre }}</span>
                <strong class="font-bold text-red-500">Bs. {{ d.deuda_total }}</strong>
              </li>
            </ul>
          </div>
          
          <div class="bg-white p-6 shadow-md rounded-xl">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Pagos recientes</h2>
            <table class="w-full text-left table-auto">
              <thead>
                <tr class="text-gray-600 border-b-2 border-gray-200">
                  <th class="py-2">Socio</th>
                  <th class="py-2">Monto</th>
                  <th class="py-2">Fecha</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="p in pagosRecientes" :key="p.id" class="border-b border-gray-100 last:border-b-0">
                  <td class="py-2">{{ p.socio_nombre }}</td>
                  <td class="py-2 text-green-600 font-semibold">Bs. {{ p.monto }}</td>
                  <td class="py-2 text-gray-500">{{ new Date(p.created_at).toLocaleDateString() }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { onMounted } from 'vue'
import Chart from 'chart.js/auto'
import { router, usePage } from '@inertiajs/vue3'


// ✅ Accedemos a los datos de la página
const { props } = usePage()
const user = props.auth?.user

// ✅ Obtenemos el rol con múltiples opciones
const userRole = user?.rol || user?.role || user?.cargo || 'Usuario' // fallback

// ✅ Props del dashboard
const propsData = defineProps({
  totalSocios: Number,
  totalUsuarios: Number,
  conexionesActivas: Number,
  conexionesInactivas: Number,
  ingresosMes: Number,
  egresosMes: Number,
  deudoresTop: Array,
  pagosRecientes: Array,
  ingresosEgresos: Array
})

// ✅ Gráfico
onMounted(() => {
  const ctx = document.getElementById('ingresosEgresosChart').getContext('2d')

  const meses = [...new Set(propsData.ingresosEgresos.map(ie => ie.mes))].sort()
  const ingresos = meses.map(mes => {
    const item = propsData.ingresosEgresos.find(ie => ie.mes == mes && ie.tipo === 'ingreso')
    return item ? item.total : 0
  })
  const egresos = meses.map(mes => {
    const item = propsData.ingresosEgresos.find(ie => ie.mes == mes && ie.tipo === 'egreso')
    return item ? item.total : 0
  })

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: meses,
      datasets: [
        {
          label: 'Ingresos',
           ingresos,
          backgroundColor: 'rgba(34, 197, 94, 0.8)',
          borderRadius: 4
        },
        {
          label: 'Egresos',
           egresos,
          backgroundColor: 'rgba(239, 68, 68, 0.8)',
          borderRadius: 4
        }
      ]
    },
    options: { responsive: true, maintainAspectRatio: false }
  })
})

// ✅ Cerrar sesión
const logout = () => {
  router.post(route('logout'))
}
</script>