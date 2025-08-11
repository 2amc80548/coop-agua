<template>
   <AppLayout title="Panel Tecnico">
  <div class="p-6">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold mb-4">Panel de Técnico</h1>
        <p>Bienvenido, aquí puedes gestionar conexiones y registrar lecturas de medidores.</p>
      </div>
      
      <button 
        @click="cerrarSesion"
        class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded"
      >
        Cerrar Sesión
      </button>
    </div>
  </div>
</AppLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'; 

const cerrarSesion = () => {
  router.post(route('logout'))
}
</script>