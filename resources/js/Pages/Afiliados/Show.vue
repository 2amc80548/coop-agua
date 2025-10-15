<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  afiliado: { type: Object, required: true }
});
</script>

<template>
  <app-layout>
    <div class="p-6 max-w-5xl mx-auto">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Afiliado: {{ afiliado.nombre_completo }}</h1>
        <div class="space-x-2">
          <Link :href="`/afiliados/${afiliado.id}/edit`" class="bg-yellow-600 text-white px-4 py-2 rounded">Editar</Link>
          <Link href="/afiliados" class="bg-gray-600 text-white px-4 py-2 rounded">Volver</Link>
        </div>
      </div>

      <div class="grid md:grid-cols-3 gap-4">
        <div class="bg-white rounded shadow p-4">
          <h2 class="font-semibold mb-3">Identificación</h2>
          <p><strong>CI:</strong> {{ afiliado.ci }}</p>
          <p><strong>Código:</strong> {{ afiliado.codigo }}</p>
          <p><strong>Tipo:</strong> <span class="capitalize">{{ afiliado.tipo }}</span></p>
          <p><strong>Estado:</strong> <span class="capitalize">{{ afiliado.estado }}</span></p>
          <p><strong>Adulto mayor:</strong> {{ afiliado.adulto_mayor ? 'Sí' : 'No' }}</p>
        </div>

        <div class="bg-white rounded shadow p-4">
          <h2 class="font-semibold mb-3">Contacto</h2>
          <p><strong>Celular:</strong> {{ afiliado.celular || '—' }}</p>
          <p><strong>Dirección:</strong> {{ afiliado.direccion }}</p>
          <p><strong>Zona:</strong> {{ afiliado.zona || '—' }}</p>
        </div>

        <div class="bg-white rounded shadow p-4">
          <h2 class="font-semibold mb-3">Administrativo</h2>
          <p><strong>Tenencia:</strong> <span class="capitalize">{{ afiliado.tenencia || '—' }}</span></p>
          <p><strong>Fecha afiliación:</strong> {{ afiliado.fecha_afiliacion || '—' }}</p>
          <p><strong>Fecha baja:</strong> {{ afiliado.fecha_baja || '—' }}</p>
        </div>
      </div>

      <div class="bg-white rounded shadow p-4 mt-6">
        <h2 class="font-semibold mb-3">Requisitos</h2>
        <div v-if="afiliado.requisitos">
          <p class="mb-2"><strong>Escenario:</strong> {{ afiliado.requisitos.escenario }}</p>
          <ul class="grid md:grid-cols-2 gap-2">
            <li>✔ Fotocopia CI: <strong>{{ afiliado.requisitos.fotocopia_ci ? 'Sí' : 'No' }}</strong></li>
            <li>✔ Plano de ubicación: <strong>{{ afiliado.requisitos.plano_ubicacion ? 'Sí' : 'No' }}</strong></li>
            <li>✔ Doc. compra-venta: <strong>{{ afiliado.requisitos.doc_compra_venta ? 'Sí' : 'No' }}</strong></li>
            <li>✔ Croquis: <strong>{{ afiliado.requisitos.croquis ? 'Sí' : 'No' }}</strong></li>
            <li>✔ Certificación OTB: <strong>{{ afiliado.requisitos.certificacion_otb ? 'Sí' : 'No' }}</strong></li>
          </ul>
          <p class="mt-3"><strong>Observaciones:</strong> {{ afiliado.requisitos.observaciones || '—' }}</p>
        </div>
        <div v-else class="text-gray-500">Sin requisitos registrados.</div>
      </div>
    </div>
  </app-layout>
</template>
