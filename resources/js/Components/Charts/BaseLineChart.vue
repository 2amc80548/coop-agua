<script setup>
import { ref, watch, onMounted } from 'vue';
import { Line } from 'vue-chartjs';
import {
  Chart as ChartJS,
  LineElement,
  PointElement,
  CategoryScale,
  LinearScale,
  Tooltip,
  Legend,
} from 'chart.js';

ChartJS.register(
  LineElement,
  PointElement,
  CategoryScale,
  LinearScale,
  Tooltip,
  Legend,
);

const props = defineProps({
  labels: { type: Array, required: true },
  datasets: { type: Array, required: true }, // [{ label, data }]
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
      labels: {
        color: '#e5e7eb',
        font: { size: 11 },
      },
    },
    tooltip: {
      mode: 'index',
      intersect: false,
    },
  },
  scales: {
    x: {
      ticks: {
        color: '#9ca3af',
        font: { size: 10 },
      },
      grid: { display: false },
    },
    y: {
      ticks: {
        color: '#9ca3af',
        font: { size: 10 },
      },
      grid: { color: 'rgba(148, 163, 184, 0.1)' },
    },
  },
});

const buildDatasets = () =>
  props.datasets.map((ds, idx) => ({
    label: ds.label,
    data: ds.data,
    borderWidth: 2,
    tension: 0.3,
    fill: false,
    borderColor: idx === 0 ? '#06b6d4' : '#22c55e',
    backgroundColor: idx === 0 ? '#06b6d4' : '#22c55e',
    pointRadius: 3,
    pointHoverRadius: 4,
  }));

onMounted(() => {
  chartData.value = {
    labels: props.labels,
    datasets: buildDatasets(),
  };
});

watch(
  () => [props.labels, props.datasets],
  () => {
    chartData.value = {
      labels: props.labels,
      datasets: buildDatasets(),
    };
  },
  { deep: true },
);
</script>

<template>
  <div class="w-full h-64">
    <Line :data="chartData" :options="chartOptions" />
  </div>
</template>
