<!-- resources/js/Pages/Users/Edit.vue -->
<template>
  <app-layout>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-6">Editar Usuario</h1>

    <div class="mb-6 p-4 bg-gray-50 rounded">
  <strong>Beneficiario:</strong>
  {{ user.beneficiario?.nombre_completo ?? 'No asignado' }}
  <span v-if="user.beneficiario?.ci">(CI: {{ user.beneficiario.ci }})</span>
</div>

    <form @submit.prevent="actualizar" class="space-y-4">
      <div>
        <label>Nombre</label>
        <input v-model="form.name" type="text" class="border rounded px-3 py-2 w-full" required />
        <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</div>
      </div>

 

      <div>
        <label>Email</label>
        <input v-model="form.email" type="email" class="border rounded px-3 py-2 w-full" required />
        <div v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</div>
      </div>

      <div>
        <label>Contraseña (opcional)</label>
        <input v-model="form.password" type="password" class="border rounded px-3 py-2 w-full" placeholder="Dejar vacío para no cambiar" />
        <div v-if="form.errors.password" class="text-red-500 text-sm mt-1">{{ form.errors.password }}</div>
      </div>

      <div class="flex space-x-4">
        <button
          type="submit"
          :disabled="form.processing"
          class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700"
        >
          Actualizar
        </button>
        <Link href="/users" class="bg-gray-500 text-white px-6 py-2 rounded">Cancelar</Link>
      </div>
    </form>
  </div>
  </app-layout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
  user: Object
});

const form = useForm({
  name: props.user.name,
  email: props.user.email,
  password: '',
});

const actualizar = () => {
  form.put(`/users/${props.user.id}`);
};
</script>