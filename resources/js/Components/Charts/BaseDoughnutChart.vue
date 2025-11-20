<script setup>
import { ref, watch, onMounted } from 'vue';
import { Doughnut } from 'vue-chartjs';
import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend,
} from 'chart.js';

ChartJS.register(ArcElement, Tooltip, Legend);

const props = defineProps({
  labels: { type: Array, required: true },
  data:   { type: Array, required: true },
});

const chartData = ref({
  labels: props.labels,
  datasets: [],
});

const chartOptions = ref({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        color: '#e5e7eb',
        font: { size: 10 },
      },
    },
  },
});

const rebuild = () => {
  chartData.value = {
    labels: props.labels,
    datasets: [
      {
        data: props.data,
        backgroundColor: ['#22c55e', '#f97316', '#ef4444', '#06b6d4', '#a855f7'],
        borderWidth: 1,
      },
    ],
  };
};

onMounted(rebuild);
watch(() => [props.labels, props.data], rebuild, { deep: true });
</script>

<template>
  <div class="w-full h-60">
    <Doughnut :data="chartData" :options="chartOptions" />
  </div>
</template>
