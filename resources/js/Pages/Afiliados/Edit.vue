<script setup>
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ afiliado: Object });

const form = useForm({
  // NO editables (no se mandan en PUT)
  // ci: props.afiliado.ci,
  // codigo: props.afiliado.codigo,
  // fecha_afiliacion: props.afiliado.fecha_afiliacion,

  // Editables:
  nombre_completo: props.afiliado.nombre_completo,
  celular: props.afiliado.celular,
  direccion: props.afiliado.direccion,
  zona: props.afiliado.zona,
  tipo: props.afiliado.tipo,
  fecha_baja: props.afiliado.fecha_baja,
  tenencia: props.afiliado.tenencia,
  estado: props.afiliado.estado ?? 'activo',
  adulto_mayor: !!props.afiliado.adulto_mayor,
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

<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-6">Editar Afiliado</h1>

    <form @submit.prevent="submit" class="space-y-6 bg-white p-6 rounded shadow">
      <!-- No editables -->
      <div class="grid md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">CI (no editable)</label>
          <input :value="props.afiliado.ci" class="border rounded px-3 py-2 w-full bg-gray-100" readonly />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Código (no editable)</label>
          <input :value="props.afiliado.codigo" class="border rounded px-3 py-2 w-full bg-gray-100" readonly />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Fecha Afiliación (no editable)</label>
          <input :value="props.afiliado.fecha_afiliacion" type="date" class="border rounded px-3 py-2 w-full bg-gray-100" disabled />
        </div>
      </div>

      <!-- Editables -->
      <div>
        <label class="block text-sm font-medium mb-1">Nombre Completo</label>
        <input v-model="form.nombre_completo" type="text" class="border rounded px-3 py-2 w-full" required />
        <div v-if="form.errors.nombre_completo" class="text-red-500 text-sm mt-1">{{ form.errors.nombre_completo }}</div>
      </div>

      <div class="grid md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">Celular</label>
          <input v-model="form.celular" type="text" class="border rounded px-3 py-2 w-full" />
          <div v-if="form.errors.celular" class="text-red-500 text-sm mt-1">{{ form.errors.celular }}</div>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Zona</label>
          <input v-model="form.zona" class="border rounded px-3 py-2 w-full" />
          <div v-if="form.errors.zona" class="text-red-500 text-sm mt-1">{{ form.errors.zona }}</div>
        </div>
        <div class="flex items-center gap-2 mt-6">
          <input id="adulto" type="checkbox" v-model="form.adulto_mayor" />
          <label for="adulto" class="text-sm font-medium">Adulto mayor (20% desc.)</label>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Dirección</label>
        <input v-model="form.direccion" type="text" class="border rounded px-3 py-2 w-full" required />
        <div v-if="form.errors.direccion" class="text-red-500 text-sm mt-1">{{ form.errors.direccion }}</div>
      </div>

      <div class="grid md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">Tipo</label>
          <select v-model="form.tipo" class="border rounded px-3 py-2 w-full" required>
            <option value="socio">Socio</option>
            <option value="usuario">Usuario</option>
          </select>
          <div v-if="form.errors.tipo" class="text-red-500 text-sm mt-1">{{ form.errors.tipo }}</div>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Estado</label>
          <select v-model="form.estado" class="border rounded px-3 py-2 w-full" required>
            <option value="activo">Activo</option>
            <option value="suspendido">Suspendido</option>
            <option value="baja">Baja</option>
          </select>
          <div v-if="form.errors.estado" class="text-red-500 text-sm mt-1">{{ form.errors.estado }}</div>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Fecha de Baja</label>
          <input v-model="form.fecha_baja" type="date" class="border rounded px-3 py-2 w-full" />
          <div v-if="form.errors.fecha_baja" class="text-red-500 text-sm mt-1">{{ form.errors.fecha_baja }}</div>
        </div>
      </div>

      <!-- Requisitos -->
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
        <Link href="/afiliados" class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">Cancelar</Link>
      </div>
    </form>
  </div>
</template>
