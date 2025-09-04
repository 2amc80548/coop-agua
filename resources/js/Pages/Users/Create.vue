<!-- resources/js/Pages/Users/Create.vue -->
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
  rolesPersonal: Array,
  roleUsuario: Object
});

const form = useForm({
  tipo: 'personal',
  name: '',
  email: '',
  password: '',
  beneficiario_id: null,
  role_id: null
});

const query = ref('');
const beneficiarios = ref([]);
const seleccionado = ref(null);
const buscando = ref(false);

// Buscar mientras escribe
const buscarBeneficiario = async () => {
  if (query.value.length < 2) {
    beneficiarios.value = [];
    return;
  }

  buscando.value = true;
  try {
    const res = await axios.get(`/api/beneficiarios/buscar?q=${encodeURIComponent(query.value)}`);
    beneficiarios.value = res.data;
  } catch (e) {
    beneficiarios.value = [];
  } finally {
    buscando.value = false;
  }
};

// Seleccionar beneficiario
const seleccionar = (b) => {
  seleccionado.value = b;
  beneficiarios.value = [];
  query.value = b.nombre_completo;
  form.beneficiario_id = b.id;
  form.name = b.nombre_completo; // Nombre inicial
  form.role_id = props.roleUsuario?.id;

  if (!b.puede_tener_mas) {
    alert('Este beneficiario ya tiene 2 usuarios.');
  }
};

// Limpiar al cambiar de tipo
watch(() => form.tipo, () => {
  if (form.tipo === 'personal') {
    form.beneficiario_id = null;
    form.name = '';
    form.role_id = null;
    query.value = '';
    seleccionado.value = null;
    beneficiarios.value = [];
  }
});

// Crear usuario
const crearUsuario = () => {
  if (form.tipo === 'beneficiario' && seleccionado.value && !seleccionado.value.puede_tener_mas) {
    alert('Este beneficiario ya tiene 2 usuarios.');
    return;
  }
  form.post('/users');
};
</script>

<template>
  <app-layout>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-6">Crear Usuario</h1>

    <!-- Tipo -->
    <div class="mb-6">
      <label class="mr-4"><input type="radio" v-model="form.tipo" value="beneficiario" /> Beneficiario</label>
      <label><input type="radio" v-model="form.tipo" value="personal" /> Personal</label>
    </div>

    <!-- Beneficiario -->
    <div v-if="form.tipo === 'beneficiario'" class="border p-4 rounded mb-6">
      <label>Buscar Beneficiario por CI o Nombre</label>
      <input
        v-model="query"
        @input="buscarBeneficiario"
        type="text"
        class="border p-2 w-full"
        placeholder="Escribe para buscar..."
      />

      <!-- Lista -->
      <ul v-if="beneficiarios.length > 0" class="border rounded mt-2 max-h-40 overflow-y-auto">
        <li
          v-for="b in beneficiarios"
          :key="b.id"
          @click="seleccionar(b)"
          class="p-2 hover:bg-gray-100 cursor-pointer border-b last:border-b-0"
          :class="{ 'text-red-500': !b.puede_tener_mas }"
        >
          {{ b.nombre_completo }} (CI: {{ b.ci }}) - {{ b.usuarios_count }}/2 usuarios
        </li>
      </ul>
    </div>

    <!-- Personal -->
    <div v-if="form.tipo === 'personal'" class="border p-4 rounded mb-6">
      <div class="mb-4">
        <label>Nombre</label>
        <input v-model="form.name" class="border p-2 w-full" required />
      </div>
      <div>
        <label>Rol</label>
        <select v-model="form.role_id" class="border p-2 w-full" required>
          <option value="">Seleccionar</option>
          <option v-for="rol in rolesPersonal" :value="rol.id">{{ rol.name }}</option>
        </select>
      </div>
    </div>

    <!-- Campos comunes -->
    <div class="space-y-4">
      <div>
        <label>Nombre del Usuario (editable)</label>
        <input v-model="form.name" class="border p-2 w-full" required />
      </div>
      <div>
        <label>Email</label>
        <input v-model="form.email" type="email" class="border p-2 w-full" required />
        <div v-if="form.errors.email" class="text-red-500 text-sm">{{ form.errors.email }}</div>
      </div>
      <div>
        <label>Contraseña</label>
        <input v-model="form.password" type="password" class="border p-2 w-full" required />
        <div v-if="form.errors.password" class="text-red-500 text-sm">{{ form.errors.password }}</div>
      </div>

      <div class="flex space-x-4">
        <button @click="crearUsuario" :disabled="form.processing" class="bg-blue-600 text-white px-6 py-2 rounded">
          {{ form.processing ? 'Guardando...' : 'Crear Usuario' }}
        </button>
        <Link href="/users" class="bg-gray-500 text-white px-6 py-2 rounded">Cancelar</Link>
      </div>
    </div>
  </div>
  </app-layout>
</template>

<style scoped>
ul { list-style: none; padding: 0; margin: 0; }
li { cursor: pointer; }
</style>