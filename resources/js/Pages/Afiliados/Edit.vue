<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-6">Editar Afiliado</h1>

    <form @submit.prevent="submit" class="space-y-6 bg-white p-6 rounded shadow">
      <!-- mismos campos que Create, con v-model en form.* -->
      <!-- ... corta por brevedad; usa el mismo layout de Create ... -->

      <!-- ejemplo de algunos campos: -->
      <div>
        <label class="block text-sm font-medium mb-1">Nombre Completo</label>
        <input v-model="form.nombre_completo" type="text" class="border rounded px-3 py-2 w-full" required />
        <div v-if="form.errors.nombre_completo" class="text-red-500 text-sm mt-1">{{ form.errors.nombre_completo }}</div>
      </div>

      <!-- (agrega el resto igual que en Create.vue) -->

      <!-- Requisitos (idéntico a Create, pero ligado a form.req ya precargado) -->
      <div class="mt-4 p-4 border rounded">
        <h2 class="font-semibold mb-2">Requisitos presentados</h2>

        <label class="flex items-center gap-2 mb-1">
          <input type="checkbox" v-model="form.req.fotocopia_ci" />
          <span>Fotocopia de CI</span>
        </label>

        <div v-if="form.tenencia === 'propietario'">
          <label class="flex items-center gap-2 mb-1">
            <input type="checkbox" v-model="form.req.plano_ubicacion" />
            <span>Plano de ubicación</span>
          </label>
        </div>

        <div v-if="form.tenencia === 'compra_venta'">
          <label class="flex items-center gap-2 mb-1">
            <input type="checkbox" v-model="form.req.doc_compra_venta" />
            <span>Documento de compra-venta (notariado)</span>
          </label>
        </div>

        <div v-if="form.tenencia === 'posesion'">
          <label class="flex items-center gap-2 mb-1">
            <input type="checkbox" v-model="form.req.croquis" />
            <span>Croquis</span>
          </label>
          <label class="flex items-center gap-2 mb-1">
            <input type="checkbox" v-model="form.req.certificacion_otb" />
            <span>Certificación OTB</span>
          </label>
        </div>

        <label class="block mt-2">
          <span class="text-sm text-gray-700">Observaciones</span>
          <textarea v-model="form.req.observaciones" class="w-full border rounded px-3 py-2 mt-1" rows="2"></textarea>
        </label>
      </div>

      <div class="flex gap-4">
        <button type="submit" :disabled="form.processing" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 disabled:opacity-50">
          Actualizar
        </button>
        <Link href="/afiliados" class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">
          Cancelar
        </Link>
      </div>
    </form>
  </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ afiliado: Object });

const form = useForm({
  nombre_completo: props.afiliado.nombre_completo,
  ci: props.afiliado.ci,
  celular: props.afiliado.celular,
  direccion: props.afiliado.direccion,
  tipo: props.afiliado.tipo,
  fecha_afiliacion: props.afiliado.fecha_afiliacion,
  fecha_baja: props.afiliado.fecha_baja,
  codigo: props.afiliado.codigo,
  barrio: props.afiliado.barrio,
  tenencia: props.afiliado.tenencia,
  estado: props.afiliado.estado ?? 'activo',
  // requisitos precargados
  req: {
    fotocopia_ci: props.afiliado.requisitos?.fotocopia_ci ?? false,
    plano_ubicacion: props.afiliado.requisitos?.plano_ubicacion ?? false,
    doc_compra_venta: props.afiliado.requisitos?.doc_compra_venta ?? false,
    croquis: props.afiliado.requisitos?.croquis ?? false,
    certificacion_otb: props.afiliado.requisitos?.certificacion_otb ?? false,
    observaciones: props.afiliado.requisitos?.observaciones ?? '',
  }
});

const submit = () => {
  form.put(`/afiliados/${props.afiliado.id}`);
};
</script>
