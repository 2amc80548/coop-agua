<!-- resources/js/Pages/Users/Create.vue -->
<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-6">Asignar Usuario a Beneficiario</h1>

    <!-- Búsqueda por CI -->
    <div class="mb-6">
      <label class="block text-sm font-medium mb-1">CI del Beneficiario</label>
      <input
        v-model="ci"
        @input="buscarBeneficiario"
        type="text"
        class="border rounded px-3 py-2 w-full"
        placeholder="Ingresa el CI"
        :disabled="form.processing"
      />
      <div v-if="mensajeBusqueda" class="text-sm mt-2" :class="{
        'text-green-600': beneficiarioEncontrado,
        'text-red-600': !beneficiarioEncontrado && mensajeBusqueda
      }">
        {{ mensajeBusqueda }}
      </div>
    </div>

    <!-- Formulario de usuario (solo si beneficiario encontrado y no tiene usuario) -->
    <form v-if="beneficiarioEncontrado && !beneficiarioTieneUsuario" @submit.prevent="crearUsuario" class="space-y-4">
      <input type="hidden" v-model="form.beneficiario_id" />

      <div>
        <label>Nombre del Beneficiario</label>
        <input v-model="form.name" type="text" class="border rounded px-3 py-2 w-full" disabled />
      </div>

      <div>
        <label>Email</label>
        <input v-model="form.email" type="email" class="border rounded px-3 py-2 w-full" required />
        <div v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</div>
      </div>

      <div>
        <label>Contraseña</label>
        <input v-model="form.password" type="password" class="border rounded px-3 py-2 w-full" required />
        <div v-if="form.errors.password" class="text-red-500 text-sm mt-1">{{ form.errors.password }}</div>
      </div>

      <div class="flex space-x-4">
        <button
          type="submit"
          :disabled="form.processing"
          class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 disabled:opacity-50"
        >
          {{ form.processing ? 'Guardando...' : 'Asignar Usuario' }}
        </button>
        <Link href="/users" class="bg-gray-500 text-white px-6 py-2 rounded">Cancelar</Link>
      </div>
    </form>

    <!-- Mensaje si ya tiene usuario -->
    <div v-else-if="beneficiarioTieneUsuario" class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
      Este beneficiario ya tiene un usuario asignado. No se puede crear otro.
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';

const ci = ref('');
const beneficiarioEncontrado = ref(false);
const beneficiarioTieneUsuario = ref(false);
const mensajeBusqueda = ref('');

const form = useForm({
  name: '',
  email: '',
  password: '',
  beneficiario_id: null,
});

// Buscar beneficiario por CI
const buscarBeneficiario = async () => {
  if (ci.value.length < 3) {
    limpiarFormulario();
    return;
  }

  try {
    const response = await axios.get(`/api/beneficiarios/buscar/${ci.value}`);
    const data = response.data;

    form.name = data.nombre_completo;
    form.beneficiario_id = data.id;
    beneficiarioEncontrado.value = true;
    beneficiarioTieneUsuario.value = data.tiene_usuario;
    mensajeBusqueda.value = `Beneficiario encontrado: ${data.nombre_completo}`;
  } catch (error) {
    limpiarFormulario();
    mensajeBusqueda.value = 'Beneficiario no encontrado.';
  }
};

const limpiarFormulario = () => {
  beneficiarioEncontrado.value = false;
  beneficiarioTieneUsuario.value = false;
  mensajeBusqueda.value = '';
  form.name = '';
  form.beneficiario_id = null;
};

// Crear el usuario
const crearUsuario = () => {
  form.post('/users');
};
</script>