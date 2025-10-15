<template>
    <AppLayout>
      <div class="p-6">
        <h1 class="text-2xl font-bold mb-6">Crear Registro de Ingreso/Egreso</h1>
  
        <form @submit.prevent="submit" class="space-y-6 bg-white p-6 rounded shadow border">
          <!-- Tipo -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
            <select
              v-model="form.tipo"
              class="border border-gray-300 rounded px-3 py-2 w-full focus:ring-blue-500 focus:border-blue-500"
              required
            >
              <option value="">Seleccionar tipo</option>
              <option value="ingreso">Ingreso</option>
              <option value="egreso">Egreso</option>
            </select>
            <div v-if="form.errors.tipo" class="text-red-500 text-sm mt-1">{{ form.errors.tipo }}</div>
          </div>
  
          <!-- Categoría -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Categoría *</label>
            <input
              v-model="form.categoria"
              type="text"
              class="border border-gray-300 rounded px-3 py-2 w-full focus:ring-blue-500 focus:border-blue-500"
              placeholder="Ej: Sueldos, Materiales, Servicios..."
              required
            />
            <div v-if="form.errors.categoria" class="text-red-500 text-sm mt-1">{{ form.errors.categoria }}</div>
          </div>
  
          <!-- Descripción -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
            <textarea
              v-model="form.descripcion"
              class="border border-gray-300 rounded px-3 py-2 w-full focus:ring-blue-500 focus:border-blue-500"
              rows="3"
              placeholder="Detalles opcionales..."
            ></textarea>
            <div v-if="form.errors.descripcion" class="text-red-500 text-sm mt-1">{{ form.errors.descripcion }}</div>
          </div>
  
          <!-- Monto -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
            <input
              v-model.number="form.monto"
              type="number"
              step="0.01"
              min="0"
              class="border border-gray-300 rounded px-3 py-2 w-full focus:ring-blue-500 focus:border-blue-500"
              required
            />
            <div v-if="form.errors.monto" class="text-red-500 text-sm mt-1">{{ form.errors.monto }}</div>
          </div>
  
          <!-- Fecha -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
            <input
              v-model="form.fecha"
              type="date"
              class="border border-gray-300 rounded px-3 py-2 w-full focus:ring-blue-500 focus:border-blue-500"
              required
            />
            <div v-if="form.errors.fecha" class="text-red-500 text-sm mt-1">{{ form.errors.fecha }}</div>
          </div>
  
          <!-- Botones -->
          <div class="flex space-x-4">
            <button
              type="submit"
              :disabled="form.processing"
              class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 disabled:opacity-50 transition"
            >
              Guardar Registro
            </button>
            <Link href="/IngresosEgresos" class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 transition">
              Cancelar
            </Link>
          </div>
        </form>
      </div>
    </AppLayout>
  </template>
  
  <script setup>
  import { Link } from '@inertiajs/vue3';
  import { useForm } from '@inertiajs/vue3';
  import AppLayout from '@/Layouts/AppLayout.vue';
  
  const form = useForm({
    tipo: '',
    categoria: '',
    descripcion: '',
    monto: null,
    fecha: new Date().toISOString().split('T')[0], // Fecha actual por defecto
  });
  
  const submit = () => {
    form.post('/IngresosEgresos');
  };
  </script>