<template>
    <div class="p-6">
      <h1 class="text-2xl font-bold mb-6">Editar Beneficiario</h1>
  
      <form @submit.prevent="submit" class="space-y-6">
        <div>
          <label class="block text-sm font-medium mb-1">Nombre Completo</label>
          <input v-model="form.nombre_completo" type="text" class="border rounded px-3 py-2 w-full" required />
          <div v-if="form.errors.nombre_completo" class="text-red-500 text-sm mt-1">{{ form.errors.nombre_completo }}</div>
        </div>
  
        <div>
          <label class="block text-sm font-medium mb-1">CI</label>
          <input v-model="form.ci" type="text" class="border rounded px-3 py-2 w-full" required />
          <div v-if="form.errors.ci" class="text-red-500 text-sm mt-1">{{ form.errors.ci }}</div>
        </div>
  
        <div>
          <label class="block text-sm font-medium mb-1">Teléfono</label>
          <input v-model="form.telefono" type="text" class="border rounded px-3 py-2 w-full" />
          <div v-if="form.errors.telefono" class="text-red-500 text-sm mt-1">{{ form.errors.telefono }}</div>
        </div>
  
        <div>
          <label class="block text-sm font-medium mb-1">Dirección</label>
          <input v-model="form.direccion" type="text" class="border rounded px-3 py-2 w-full" required />
          <div v-if="form.errors.direccion" class="text-red-500 text-sm mt-1">{{ form.errors.direccion }}</div>
        </div>
  
        <div>
          <label class="block text-sm font-medium mb-1">Tipo</label>
          <select v-model="form.tipo" class="border rounded px-3 py-2 w-full" required>
            <option value="">Seleccionar tipo</option>
            <option value="socio">Socio</option>
            <option value="usuario">Usuario</option>
          </select>
          <div v-if="form.errors.tipo" class="text-red-500 text-sm mt-1">{{ form.errors.tipo }}</div>
        </div>
  
        <div class="flex space-x-4">
          <button type="submit" :disabled="form.processing" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 disabled:opacity-50">
            Actualizar
          </button>
          <Link href="/beneficiarios" class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">
            Cancelar
          </Link>
        </div>
      </form>
    </div>
  </template>
  
  <script setup>
  import { Link } from '@inertiajs/vue3';
  import { useForm } from '@inertiajs/vue3';
  
  const props = defineProps({
    beneficiario: Object
  });
  
  const form = useForm({
    nombre_completo: props.beneficiario.nombre_completo,
    ci: props.beneficiario.ci,
    telefono: props.beneficiario.telefono,
    direccion: props.beneficiario.direccion,
    tipo: props.beneficiario.tipo,
  });
  
  const submit = () => {
    form.put(`/beneficiarios/${props.beneficiario.id}`);
  };
  </script>