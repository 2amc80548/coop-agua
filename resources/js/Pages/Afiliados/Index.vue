<script setup>
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  afiliados: { type: Array, default: () => [] }
});

const confirmDelete = (id) => {
  if (confirm('¿Estás seguro de eliminar este afiliado? Esta acción no se puede deshacer.')) {
    axios.delete(`/afiliados/${id}`)
      .then(() => window.location.href = '/afiliados')
      .catch(err => {
        alert('Error al eliminar: ' + (err.response?.data?.message || 'Verifica la consola'));
        console.error(err);
      });
  }
};
</script>

<template>
  <app-layout>
    <div class="p-6">
      <h1 class="text-2xl font-bold mb-6">Afiliados</h1>

      <Link href="/afiliados/create" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 mb-6 inline-block">
        + Nuevo Afiliado
      </Link>

      <div v-if="$page.props.flash?.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        {{ $page.props.flash.success }}
      </div>

      <table class="min-w-full bg-white border border-gray-300 rounded">
        <thead class="bg-gray-50">
          <tr>
            <th class="py-3 px-4 text-left text-sm font-semibold">Nombre</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">CI</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Celular</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Dirección</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Código</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Zona</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Tipo</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Estado</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Adulto mayor</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Tenencia</th>
            <th class="py-3 px-4 text-left text-sm font-semibold">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!afiliados.length">
            <td colspan="11" class="py-4 text-center text-gray-500">No hay afiliados registrados.</td>
          </tr>

          <tr v-for="a in afiliados" :key="a.id" class="border-t">
            <td class="py-3 px-4">{{ a.nombre_completo }}</td>
            <td class="py-3 px-4">{{ a.ci }}</td>
            <td class="py-3 px-4">{{ a.celular ?? '—' }}</td>
            <td class="py-3 px-4">{{ a.direccion }}</td>
            <td class="py-3 px-4">{{ a.codigo }}</td>
            <td class="py-3 px-4">{{ a.zona ?? '—' }}</td>
            <td class="py-3 px-4">
              <span :class="{
                'bg-green-100 text-green-800': a.tipo === 'socio',
                'bg-blue-100 text-blue-800': a.tipo === 'usuario'
              }" class="px-2 py-1 rounded text-xs font-medium">
                {{ a.tipo }}
              </span>
            </td>
            <td class="py-3 px-4">
              <span :class="{
                'bg-green-100 text-green-800': a.estado === 'activo',
                'bg-yellow-100 text-yellow-800': a.estado === 'suspendido',
                'bg-red-100 text-red-800': a.estado === 'baja'
              }" class="px-2 py-1 rounded text-xs font-medium">
                {{ a.estado }}
              </span>
            </td>
            <td class="py-3 px-4">
              {{ a.adulto_mayor ? 'Sí' : 'No' }}
            </td>
            <td class="py-3 px-4 capitalize">{{ a.tenencia }}</td>
            <td class="py-3 px-4 space-x-2">
              <Link :href="`/afiliados/${a.id}`" class="text-blue-600 hover:underline text-sm">Ver</Link>
              <Link :href="`/afiliados/${a.id}/edit`" class="text-yellow-600 hover:underline text-sm">Editar</Link>
              <button @click="confirmDelete(a.id)" class="text-red-600 hover:underline text-sm">Eliminar</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </app-layout>
</template>
