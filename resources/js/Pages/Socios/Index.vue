<template>
  <div :class="{ 'dark': darkMode }" class="bg-gray-100 dark:bg-gray-900 transition-colors duration-300 min-h-screen">
  <AppLayout title="Dashboard Admin">
 <div class="p-6">
   <h1 class="text-2xl font-bold mb-4">Lista de Socios</h1>

   <div v-if="socios.length === 0" class="text-gray-500">
     No hay socios registrados.
   </div>

   <table v-else class="min-w-full bg-white border border-gray-200 shadow rounded">
     <thead class="bg-gray-100">
       <tr>
         <th class="text-left px-4 py-2 border-b">ID</th>
         <th class="text-left px-4 py-2 border-b">Nombre Completo</th>
         <th class="text-left px-4 py-2 border-b">CI</th>
         <th class="text-left px-4 py-2 border-b">Teléfono</th>
         <th class="text-left px-4 py-2 border-b">Dirección</th>
       </tr>
     </thead>
     <tbody>
       <tr v-for="socio in socios" :key="socio.id" class="hover:bg-gray-50">
         <td class="px-4 py-2 border-b">{{ socio.id }}</td>
         <td class="px-4 py-2 border-b">{{ socio.nombre_completo }}</td>
         <td class="px-4 py-2 border-b">{{ socio.ci }}</td>
         <td class="px-4 py-2 border-b">{{ socio.telefono ?? 'No registrado' }}</td>
         <td class="px-4 py-2 border-b">{{ socio.direccion }}</td>
       </tr>
     </tbody>
   </table>
 </div>
 </AppLayout>
</div>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
defineProps({
 socios: Array
});
</script>
