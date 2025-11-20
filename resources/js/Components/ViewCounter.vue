<template>
  <div class="border-t border-gray-200 bg-gray-50 py-5 text-center text-xs text-gray-600">
    © 2025 Agua Cabezas • 
    Visitas a esta página: 
    <strong class="font-bold text-gray-900">
      {{ visits.toLocaleString() }}
    </strong>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'

const visits = ref(0)

const contar = () => {
  axios.get('/hit-view', { params: { url: usePage().url } })
       .then(r => visits.value = r.data.views)
       .catch(() => visits.value = 0)
}

onMounted(contar)
watch(() => usePage().url, contar)
</script>