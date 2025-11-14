<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
  tarifa: Object
});

const form = useForm({
  vigente_desde: props.tarifa.vigente_desde,
  vigente_hasta: props.tarifa.vigente_hasta,
  activo: props.tarifa.activo,
   tipo_conexion: props.tarifa.tipo_conexion,

  min_m3: props.tarifa.min_m3,
  min_monto: props.tarifa.min_monto,
  precio_m3: props.tarifa.precio_m3,

  descuento_adulto_mayor_pct: props.tarifa.descuento_adulto_mayor_pct,

  afiliacion_socio_monto: props.tarifa.afiliacion_socio_monto,
  afiliacion_usuario_monto: props.tarifa.afiliacion_usuario_monto,
  multa_corte_monto: props.tarifa.multa_corte_monto,
  cisterna_10k_monto: props.tarifa.cisterna_10k_monto,

  notas: props.tarifa.notas ?? ''
});

const submit = () => form.put(`/tarifas/${props.tarifa.id}`);

const newConcept = useForm({
  codigo: '',
  nombre: '',
  tipo: 'FIJO',
  valor: 0,
  aplica_sobre: 'n/a',
  activo: true
});

const addConcept = () => {
  newConcept.post(`/tarifas/${props.tarifa.id}/conceptos`, {
    onSuccess: () => router.reload({ only: ['tarifa'] })
  });
};

const updateConcept = (c) => {
  const f = useForm({
    nombre: c.nombre,
    tipo: c.tipo,
    valor: c.valor,
    aplica_sobre: c.aplica_sobre,
    activo: c.activo
  });
  f.put(`/tarifas/${props.tarifa.id}/conceptos/${c.id}`, {
    onSuccess: () => router.reload({ only: ['tarifa'] })
  });
};

const deleteConcept = (c) => {
  if (!confirm('¿Eliminar concepto?')) return;
  router.delete(`/tarifas/${props.tarifa.id}/conceptos/${c.id}`, {
    onSuccess: () => router.reload({ only: ['tarifa'] })
  });
};
</script>

<template>
  <app-layout>
    <div class="p-6 max-w-5xl mx-auto">
      <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold">Editar Tarifa</h1>
        <Link href="/tarifas" class="bg-gray-600 text-white px-4 py-2 rounded">Volver</Link>
      </div>

      <div v-if="$page.props.flash?.success" class="bg-green-100 text-green-800 p-3 rounded mb-4">
        {{ $page.props.flash.success }}
      </div>

      <form @submit.prevent="submit" class="space-y-4 bg-white p-4 rounded shadow">
        
        <label class="block text-sm font-medium">Tipo de conexión</label>
        <select v-model="form.tipo_conexion"
                class="border-gray-300 rounded-md shadow-sm mt-1 w-full">
                <option value="domiciliaria">Domiciliaria</option>
                <option value="comercial">Comercial</option>
                <option value="institucional">Institucional</option>
                <option value="otro">Otro</option>
        </select>
        <div v-if="form.errors.tipo_conexion" class="text-red-600 text-sm">
            {{ form.errors.tipo_conexion }}
        </div>

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
          <button class="bg-green-600 text-white px-4 py-2 rounded">Guardar cambios</button>
        </div>
      </form>

      <!-- Conceptos -->
      <div class="mt-8 bg-white p-4 rounded shadow">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-xl font-semibold">Conceptos de la tarifa</h2>
        </div>

        <!-- Alta de concepto -->
        <div class="border rounded p-3 mb-4">
          <h3 class="font-medium mb-2">Agregar concepto</h3>
          <div class="grid md:grid-cols-5 gap-3">
            <input v-model="newConcept.codigo" placeholder="Código (ej: reconexion)" class="border rounded px-3 py-2" />
            <input v-model="newConcept.nombre" placeholder="Nombre" class="border rounded px-3 py-2" />
            <select v-model="newConcept.tipo" class="border rounded px-3 py-2">
              <option value="FIJO">FIJO</option>
              <option value="PORCENTAJE">PORCENTAJE</option>
            </select>
            <input v-model.number="newConcept.valor" type="number" step="0.01" placeholder="Valor" class="border rounded px-3 py-2" />
            <select v-model="newConcept.aplica_sobre" class="border rounded px-3 py-2">
              <option value="n/a">n/a</option>
              <option value="consumo">consumo</option>
              <option value="subtotal">subtotal</option>
            </select>
          </div>
          <div class="flex items-center gap-2 mt-2">
            <input id="nc_activo" type="checkbox" v-model="newConcept.activo" />
            <label for="nc_activo">Activo</label>
          </div>
          <div class="mt-3">
            <button @click="addConcept" class="bg-blue-600 text-white px-4 py-2 rounded">Agregar</button>
          </div>
        </div>

        <!-- Lista / edición rápida -->
        <table class="min-w-full border rounded">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-3 py-2 text-left">Código</th>
              <th class="px-3 py-2 text-left">Nombre</th>
              <th class="px-3 py-2 text-left">Tipo</th>
              <th class="px-3 py-2 text-left">Valor</th>
              <th class="px-3 py-2 text-left">Aplica sobre</th>
              <th class="px-3 py-2 text-left">Activo</th>
              <th class="px-3 py-2 text-left">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!props.tarifa.conceptos?.length">
              <td colspan="7" class="px-3 py-3 text-center text-gray-500">Sin conceptos.</td>
            </tr>
            <tr v-for="c in props.tarifa.conceptos" :key="c.id" class="border-t">
              <td class="px-3 py-2">{{ c.codigo }}</td>
              <td class="px-3 py-2">
                <input v-model="c.nombre" class="border rounded px-2 py-1 w-full" />
              </td>
              <td class="px-3 py-2">
                <select v-model="c.tipo" class="border rounded px-2 py-1">
                  <option value="FIJO">FIJO</option>
                  <option value="PORCENTAJE">PORCENTAJE</option>
                </select>
              </td>
              <td class="px-3 py-2">
                <input v-model.number="c.valor" type="number" step="0.01" class="border rounded px-2 py-1 w-full" />
              </td>
              <td class="px-3 py-2">
                <select v-model="c.aplica_sobre" class="border rounded px-2 py-1">
                  <option value="n/a">n/a</option>
                  <option value="consumo">consumo</option>
                  <option value="subtotal">subtotal</option>
                </select>
              </td>
              <td class="px-3 py-2">
                <input type="checkbox" v-model="c.activo" />
              </td>
              <td class="px-3 py-2 space-x-2">
                <button @click="updateConcept(c)" class="text-blue-600">Guardar</button>
                <button @click="deleteConcept(c)" class="text-red-600">Eliminar</button>
              </td>
            </tr>
          </tbody>
        </table>

      </div>
    </div>
  </app-layout>
</template>
