<script setup>
import { ref, watch, onMounted } from 'vue';
import { Bar } from 'vue-chartjs';
import {
  Chart as ChartJS,
  BarElement,
  CategoryScale,
  LinearScale,
  Tooltip,
  Legend,
} from 'chart.js';

ChartJS.register(
  BarElement,
  CategoryScale,
  LinearScale,
  Tooltip,
  Legend,
);

const props = defineProps({
  labels: { type: Array, required: true },
  data:   { type: Array, required: true },
  label:  { type: String, default: '' },
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
      display: !!props.label,
      labels: {
        color: '#e5e7eb',
        font: { size: 11 },
      },
    },
    tooltip: {},
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
      grid: { color: 'rgba(148,163,184,0.1)' },
    },
  },
});

const rebuild = () => {
  chartData.value = {
    labels: props.labels,
    datasets: [
      {
        label: props.label || '',
        data: props.data,
        backgroundColor: '#38bdf8',
        borderRadius: 6,
        barThickness: 18,
      },
    ],
  };
};

onMounted(rebuild);
watch(() => [props.labels, props.data, props.label], rebuild, { deep: true });
</script>

<template>
  <div class="w-full h-64">
    <Bar :data="chartData" :options="chartOptions" />
  </div>
</template>
