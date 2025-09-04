<!-- resources/js/Pages/Users/Index.vue -->
<template>
  <app-layout>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-6">Usuarios</h1>
    <Link href="/users/create" class="bg-blue-600 text-white px-4 py-2 rounded mb-6 inline-block">
      + Crear Usuario
    </Link>

    <!-- Mensaje flash -->
    <div v-if="$page.props.flash.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
      {{ $page.props.flash.success }}
    </div>

    <table class="min-w-full bg-white border rounded">
      <thead class="bg-gray-50">
        <tr>
          <th class="py-3 px-4 text-left">Nombre</th>
          <th class="py-3 px-4 text-left">Email</th>
          <th class="py-3 px-4 text-left">Rol</th>
          <th class="py-3 px-4 text-left">Beneficiario (CI)</th>
          <th class="py-3 px-4 text-left">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="user in users" :key="user.id" class="border-t">
          <td class="py-3 px-4">{{ user.name }}</td>
          <td class="py-3 px-4">{{ user.email }}</td>
          <td class="py-3 px-4">
            <span
              v-for="role in user.roles"
              :key="role.id"
              class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs mr-1"
            >
              {{ role.name }}
            </span>
          </td>
          <td class="py-3 px-4">
            <template v-if="user.beneficiario">
              <div>{{ user.beneficiario.nombre_completo }}</div>
              <div class="text-xs text-gray-500">CI: {{ user.beneficiario.ci }}</div>
            </template>
            <span v-else class="text-gray-400">Sin beneficiario</span>
          </td>
          <td class="py-3 px-4 space-x-2">
            <Link :href="`/users/${user.id}/edit`" class="text-yellow-600 hover:underline text-sm">Editar</Link>
            <button @click="eliminarUsuario(user.id)" class="text-red-600 hover:underline text-sm">Eliminar</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  </app-layout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';

defineProps({ users: Array });

const eliminarUsuario = (id) => {
  if (confirm('¿Eliminar este usuario? Esta acción no se puede deshacer.')) {
    router.delete(`/users/${id}`, {
      onSuccess: () => {
        console.log('Eliminado correctamente');
      },
      onError: (errors) => {
        alert('Error: ' + Object.values(errors).join(', '));
      }
    });
  }
};
</script>