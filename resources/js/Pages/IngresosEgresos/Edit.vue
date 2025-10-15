<script setup>
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  registro: {
    type: Object,
    required: true
  }
});
</script>

<template>
  <AppLayout>
    <div class="p-6">
      <h1 class="text-2xl font-bold mb-6">Editar Registro de Ingreso/Egreso</h1>

      <!-- Mensaje flash -->
      <div v-if="$page.props.flash.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        {{ $page.props.flash.success }}
      </div>

      <!-- Formulario -->
      <form @submit.prevent="submit" class="bg-white p-6 rounded shadow border">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <!-- Tipo -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Tipo *</label>
            <select
              v-model="form.tipo"
              class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
              required
            >
              <option value="">Seleccionar</option>
              <option value="ingreso">Ingreso</option>
              <option value="egreso">Egreso</option>
            </select>
            <div v-if="errors.tipo" class="text-red-500 text-xs mt-1">{{ errors.tipo }}</div>
          </div>

          <!-- Categoría -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Categoría *</label>
            <input
              v-model="form.categoria"
              type="text"
              class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Ej: Sueldos, Materiales, Servicios..."
              required
            />
            <div v-if="errors.categoria" class="text-red-500 text-xs mt-1">{{ errors.categoria }}</div>
          </div>
        </div>

        <!-- Descripción -->
        <div class="mb-6">
          <label class="block text-sm font-semibold text-gray-700 mb-2">Descripción</label>
          <textarea
            v-model="form.descripcion"
            class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
            rows="3"
            placeholder="Detalles opcionales..."
          ></textarea>
          <div v-if="errors.descripcion" class="text-red-500 text-xs mt-1">{{ errors.descripcion }}</div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <!-- Monto -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Monto *</label>
            <input
              v-model.number="form.monto"
              type="number"
              step="0.01"
              min="0"
              class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
              required
            />
            <div v-if="errors.monto" class="text-red-500 text-xs mt-1">{{ errors.monto }}</div>
          </div>

          <!-- Fecha -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Fecha *</label>
            <input
              v-model="form.fecha"
              type="date"
              class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
              required
            />
            <div v-if="errors.fecha" class="text-red-500 text-xs mt-1">{{ errors.fecha }}</div>
          </div>
        </div>

        <!-- Botones -->
        <div class="flex space-x-4">
          <button
            type="submit"
            class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition"
          >
            Guardar Cambios
          </button>
          <Link href="/IngresosEgresos" class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 transition">
            Cancelar
          </Link>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script>
export default {
  data() {
    return {
      form: {
        tipo: this.registro.tipo,
        categoria: this.registro.categoria,
        descripcion: this.registro.descripcion || '',
        monto: this.registro.monto,
        fecha: this.registro.fecha,
      },
      errors: {}
    }
  },
  methods: {
    submit() {
      axios.put(`/IngresosEgresos/${this.registro.id}`, this.form)
        .then(() => {
          window.location.href = '/IngresosEgresos';
        })
        .catch(error => {
          if (error.response?.data?.errors) {
            this.errors = error.response.data.errors;
          } else {
            alert('Error al actualizar. Verifica la consola.');
            console.error(error);
          }
        });
    }
  }
}
</script>