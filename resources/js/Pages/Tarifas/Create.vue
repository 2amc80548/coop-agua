<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';

const form = useForm({
  vigente_desde: new Date().toISOString().slice(0,10),
  vigente_hasta: '',
  activo: true,

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
      <h1 class="text-2xl font-bold mb-4">Nueva Tarifa</h1>

      <form @submit.prevent="submit" class="space-y-4 bg-white p-4 rounded shadow">
        <div class="grid md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium">Vigente desde</label>
            <input v-model="form.vigente_desde" type="date" class="border rounded w-full px-3 py-2" required />
          </div>
          <div>
            <label class="block text-sm font-medium">Vigente hasta</label>
            <input v-model="form.vigente_hasta" type="date" class="border rounded w-full px-3 py-2" />
          </div>
          <div class="flex items-center gap-2 mt-6">
            <input id="activo" type="checkbox" v-model="form.activo" />
            <label for="activo">Activa</label>
          </div>
        </div>

        <div class="grid md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium">Umbral (m³)</label>
            <input v-model.number="form.min_m3" type="number" min="0" class="border rounded w-full px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium">Monto mínimo</label>
            <input v-model.number="form.min_monto" type="number" step="0.01" class="border rounded w-full px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium">Precio por m³</label>
            <input v-model.number="form.precio_m3" type="number" step="0.01" class="border rounded w-full px-3 py-2" />
          </div>
        </div>

        <div class="grid md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium">% Adulto mayor</label>
            <input v-model.number="form.descuento_adulto_mayor_pct" type="number" step="0.01" class="border rounded w-full px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium">Afiliación socio</label>
            <input v-model.number="form.afiliacion_socio_monto" type="number" step="0.01" class="border rounded w-full px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium">Afiliación usuario</label>
            <input v-model.number="form.afiliacion_usuario_monto" type="number" step="0.01" class="border rounded w-full px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium">Multa corte</label>
            <input v-model.number="form.multa_corte_monto" type="number" step="0.01" class="border rounded w-full px-3 py-2" />
          </div>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium">Cisterna 10.000L</label>
            <input v-model.number="form.cisterna_10k_monto" type="number" step="0.01" class="border rounded w-full px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium">Notas</label>
            <input v-model="form.notas" type="text" class="border rounded w-full px-3 py-2" />
          </div>
        </div>

        <div class="flex gap-3">
          <button class="bg-green-600 text-white px-4 py-2 rounded">Guardar</button>
          <Link href="/tarifas" class="bg-gray-500 text-white px-4 py-2 rounded">Cancelar</Link>
        </div>
      </form>
    </div>
  </app-layout>
</template>
