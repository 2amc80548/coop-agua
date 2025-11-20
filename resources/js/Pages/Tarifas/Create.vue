<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import ViewCounter from '@/Components/ViewCounter.vue';

const form = useForm({
  vigente_desde: new Date().toISOString().slice(0,10),
  vigente_hasta: '',
  activo: true,
  tipo_conexion: 'domiciliaria',

  min_m3: 7,
  min_monto: 24.50,
  precio_m3: 3.50,

  descuento_adulto_mayor_pct: 20.00,

  afiliacion_socio_monto: 200.00,
  afiliacion_usuario_monto: 0.00,
  multa_corte_monto: 30.00,
  cisterna_10k_monto: 80.00,

  notas: ''
});

const submit = () => form.post('/tarifas');
</script>

<template>
  <app-layout>
    <div class="p-6 max-w-3xl mx-auto">
      <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-4">Nueva Tarifa</h1>

      <form @submit.prevent="submit" class="space-y-4 bg-white dark:bg-gray-800 p-6 rounded shadow">
        
        <!-- Tipo de Conexión -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de conexión</label>
            <select v-model="form.tipo_conexion"
                    class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm mt-1 w-full focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <option value="domiciliaria">Domiciliaria</option>
                <option value="comercial">Comercial</option>
                <option value="institucional">Institucional</option>
                <option value="otro">Otro</option>
            </select>
            <div v-if="form.errors.tipo_conexion" class="text-red-600 text-sm mt-1">
                {{ form.errors.tipo_conexion }}
            </div>
        </div>

        <div class="grid md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Vigente desde</label>
            <input v-model="form.vigente_desde" type="date" 
                   class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md w-full px-3 py-2 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Vigente hasta</label>
            <input v-model="form.vigente_hasta" type="date" 
                   class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md w-full px-3 py-2 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
          </div>
          <div class="flex items-center gap-2 mt-6">
            <input id="activo" type="checkbox" v-model="form.activo" 
                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" />
            <label for="activo" class="text-sm font-medium text-gray-700 dark:text-gray-300">Activa</label>
          </div>
        </div>

        <div class="grid md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Umbral (m³)</label>
            <input v-model.number="form.min_m3" type="number" min="0" 
                   class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md w-full px-3 py-2 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Monto mínimo</label>
            <input v-model.number="form.min_monto" type="number" step="0.01" 
                   class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md w-full px-3 py-2 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Precio por m³</label>
            <input v-model.number="form.precio_m3" type="number" step="0.01" 
                   class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md w-full px-3 py-2 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
          </div>
        </div>

        <div class="grid md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">% Adulto mayor</label>
            <input v-model.number="form.descuento_adulto_mayor_pct" type="number" step="0.01" 
                   class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md w-full px-3 py-2 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Afiliación socio</label>
            <input v-model.number="form.afiliacion_socio_monto" type="number" step="0.01" 
                   class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md w-full px-3 py-2 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Afiliación usuario</label>
            <input v-model.number="form.afiliacion_usuario_monto" type="number" step="0.01" 
                   class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md w-full px-3 py-2 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Multa corte</label>
            <input v-model.number="form.multa_corte_monto" type="number" step="0.01" 
                   class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md w-full px-3 py-2 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
          </div>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cisterna 10.000L</label>
            <input v-model.number="form.cisterna_10k_monto" type="number" step="0.01" 
                   class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md w-full px-3 py-2 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notas</label>
            <input v-model="form.notas" type="text" 
                   class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md w-full px-3 py-2 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
          </div>
        </div>

        <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
          <button class="bg-green-600 text-white px-4 py-2 rounded shadow hover:bg-green-700 transition duration-150">Guardar</button>
          <Link href="/tarifas" class="bg-gray-500 text-white px-4 py-2 rounded shadow hover:bg-gray-600 transition duration-150">Cancelar</Link>
        </div>
      </form>
      <ViewCounter />
    </div>
  </app-layout>
</template>
