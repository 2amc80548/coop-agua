<script setup>
import { reactive } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const factura = usePage().props.factura
const form = reactive({ ...factura })

function submit() {
  router.put(`/facturas/${factura.id}`, form)
}
</script>

<template>
  <div class="max-w-2xl mx-auto bg-white shadow rounded-lg p-6">
    <h1 class="text-xl font-bold mb-4">Editar Factura</h1>

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label class="block font-semibold">Consumo (m³)</label>
        <input v-model="form.consumo_m3" type="number" class="w-full border rounded p-2" />
      </div>

      <div>
        <label class="block font-semibold">Monto Total (Bs)</label>
        <input v-model="form.monto_total" type="number" class="w-full border rounded p-2" />
      </div>

      <div>
        <label class="block font-semibold">Estado</label>
        <select v-model="form.estado" class="w-full border rounded p-2">
          <option value="pendiente">Pendiente</option>
          <option value="pagada">Pagada</option>
          <option value="anulada">Anulada</option>
        </select>
      </div>

      <button class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
        Actualizar Factura
      </button>
    </form>
  </div>
</template>
