<!-- resources/js/Pages/Users/Create.vue -->
<template>
  <app-layout>
    <div class="p-6 max-w-3xl mx-auto">
      <h1 class="text-2xl font-bold mb-6">Crear Usuario</h1>

      <!-- Flash success -->
      <div v-if="$page.props.flash?.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        {{ $page.props.flash.success }}
      </div>

      <form @submit.prevent="submit" class="space-y-6 bg-white p-6 rounded-lg shadow">
        <!-- Tipo de usuario -->
        <div>
          <label class="block font-semibold mb-1">Tipo de cuenta</label>
          <div class="flex items-center gap-4">
            <label class="inline-flex items-center gap-2">
              <input type="radio" value="afiliado" v-model="form.tipo" />
              <span>Afiliado (usuario final)</span>
            </label>
            <label class="inline-flex items-center gap-2">
              <input type="radio" value="personal" v-model="form.tipo" />
              <span>Personal (Admin/Secretaria/Técnico)</span>
            </label>
          </div>
          <p class="text-sm text-gray-500 mt-1">
            Afiliado: cuenta ligada a un afiliado específico (solo verá sus datos).
          </p>
          <div v-if="errors.tipo" class="text-red-600 text-sm mt-1">{{ errors.tipo }}</div>
        </div>

        <!-- Nombre -->
        <div>
          <label class="block font-semibold mb-1">Nombre completo</label>
          <input
            v-model="form.name"
            type="text"
            class="w-full border rounded px-3 py-2"
            placeholder="Nombre de la persona solicitante"
            required
          />
          <div v-if="errors.name" class="text-red-600 text-sm mt-1">{{ errors.name }}</div>
        </div>

        <!-- Email -->
        <div>
          <label class="block font-semibold mb-1">Correo de acceso</label>
          <input
            v-model="form.email"
            type="email"
            class="w-full border rounded px-3 py-2"
            placeholder="correo@ejemplo.com"
            required
          />
          <div v-if="errors.email" class="text-red-600 text-sm mt-1">{{ errors.email }}</div>
        </div>

        <!-- Password -->
        <div>
          <label class="block font-semibold mb-1">Contraseña</label>
          <input
            v-model="form.password"
            type="password"
            class="w-full border rounded px-3 py-2"
            placeholder="Mínimo 6 caracteres"
            required
          />
          <div v-if="errors.password" class="text-red-600 text-sm mt-1">{{ errors.password }}</div>
        </div>

        <!-- Rol -->
        <div>
          <label class="block font-semibold mb-1">Rol</label>
          <select
            v-model="form.role_id"
            class="w-full border rounded px-3 py-2"
            :disabled="roleOptions.length === 0"
            required
          >
            <option value="" disabled>Selecciona un rol…</option>
            <option v-for="r in roleOptions" :key="r.id" :value="r.id">
              {{ r.name }}
            </option>
          </select>
          <div v-if="errors.role_id" class="text-red-600 text-sm mt-1">{{ errors.role_id }}</div>
          <p v-if="form.tipo === 'afiliado' && !roleUsuario" class="text-sm text-amber-700 mt-1">
            No se encontró el rol para “Usuario”. Revisa tus seeds/roles.
          </p>
        </div>

        <!-- Afiliado (solo si tipo = afiliado) -->
        <div v-if="form.tipo === 'afiliado'">
          <label class="block font-semibold mb-1">Afiliado</label>

          <!-- Buscador -->
          <div class="relative">
            <input
              v-model="query"
              type="text"
              class="w-full border rounded px-3 py-2"
              placeholder="Buscar por CI o nombre…"
              @input="onQueryInput"
              autocomplete="off"
            />
            <div
              v-if="showDropdown && results.length"
              class="absolute z-10 mt-1 w-full bg-white border rounded shadow"
            >
              <button
                v-for="item in results"
                :key="item.id"
                type="button"
                class="block w-full text-left px-3 py-2 hover:bg-gray-50"
                @click="selectAfiliado(item)"
              >
                <div class="font-medium">{{ item.nombre_completo }}</div>
                <div class="text-xs text-gray-500">CI: {{ item.ci }}</div>
                <div class="text-xs" :class="item.puede_tener_mas ? 'text-green-600' : 'text-red-600'">
                  Usuarios asignados: {{ item.usuarios_count }} / 2
                </div>
              </button>
            </div>
          </div>

          <!-- Selección actual -->
          <div v-if="selectedAfiliado" class="mt-2 p-3 bg-gray-50 rounded border">
            <div class="flex items-center justify-between">
              <div>
                <div class="font-semibold">{{ selectedAfiliado.nombre_completo }}</div>
                <div class="text-xs text-gray-500">CI: {{ selectedAfiliado.ci }}</div>
                <div
                  class="text-xs"
                  :class="selectedAfiliado.puede_tener_mas ? 'text-green-700' : 'text-red-700'"
                >
                  Usuarios asignados: {{ selectedAfiliado.usuarios_count }} / 2
                </div>
              </div>
              <button
                type="button"
                class="text-sm text-red-600 hover:underline"
                @click="clearAfiliado"
              >
                Quitar
              </button>
            </div>
          </div>

          <div v-if="errors.afiliado_id" class="text-red-600 text-sm mt-1">{{ errors.afiliado_id }}</div>

          <p v-if="selectedAfiliado && !selectedAfiliado.puede_tener_mas" class="text-sm text-red-600 mt-2">
            Este afiliado ya tiene 2 usuarios asignados. Debes elegir otro.
          </p>
        </div>

        <!-- Botón Guardar -->
        <div class="pt-2">
          <button
            type="submit"
            class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 disabled:opacity-60"
            :disabled="form.processing || (form.tipo==='afiliado' && selectedAfiliado && !selectedAfiliado.puede_tener_mas)"
          >
            Guardar
          </button>
          <Link href="/users" class="ml-3 text-gray-700 hover:underline">Cancelar</Link>
        </div>
      </form>
    </div>
  </app-layout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import { computed, reactive, ref, watch, onMounted } from 'vue'

const props = defineProps({
  rolesPersonal: { type: Array, default: () => [] }, // [{id, name}, ...]
  roleUsuario: { type: Object, default: null }       // {id, name} o null
})

// --------------------
// Form
// --------------------
const form = useForm({
  tipo: 'afiliado', // 'afiliado' | 'personal'
  name: '',
  email: '',
  password: '',
  role_id: '',
  afiliado_id: null
})

// errores de validación de Laravel
const errors = computed(() => form.errors || {})

// Opciones de roles según tipo
const roleOptions = computed(() => {
  if (form.tipo === 'afiliado') {
    return props.roleUsuario ? [props.roleUsuario] : []
  }
  // personal
  return props.rolesPersonal || []
})

// Resetear role_id cuando cambia el tipo
watch(() => form.tipo, () => {
  form.role_id = ''
  if (form.tipo === 'afiliado') {
    // si ya hay afiliado seleccionado y no puede tener más, advertimos en UI (submit se deshabilita)
  } else {
    // al pasar a personal, limpiamos afiliado
    clearAfiliado()
  }
})

// --------------------
// Buscador de Afiliados
// --------------------
const query = ref('')
const results = ref([])
const showDropdown = ref(false)
const selectedAfiliado = ref(null)
let debounceTimer = null

const onQueryInput = () => {
  showDropdown.value = true
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(async () => {
    await fetchAfiliados(query.value)
  }, 300)
}

async function fetchAfiliados(q) {
  results.value = []
  if (!q || q.length < 2) return
  try {
    // Ajusta la URL al endpoint que tengas activo
    const res = await fetch(`/api/afiliados/buscar?q=${encodeURIComponent(q)}`)
    if (!res.ok) return
    const data = await res.json()
    results.value = Array.isArray(data) ? data : []
  } catch (e) {
    console.error('Error buscando afiliados:', e)
  }
}

function selectAfiliado(item) {
  selectedAfiliado.value = item
  form.afiliado_id = item.id
  query.value = `${item.nombre_completo} (CI: ${item.ci})`
  showDropdown.value = false
}

function clearAfiliado() {
  selectedAfiliado.value = null
  form.afiliado_id = null
  query.value = ''
  showDropdown.value = false
}

document.addEventListener('click', (e) => {
  // cierra dropdown si se clickea fuera (sencillo)
  const dropdown = document.querySelector('.relative')
  if (dropdown && !dropdown.contains(e.target)) {
    showDropdown.value = false
  }
})

// --------------------
// Submit
// --------------------
function submit() {
  // Reglas rápidas de front:
  if (form.tipo === 'afiliado') {
    if (!form.afiliado_id) {
      form.setError('afiliado_id', 'Debe seleccionar un afiliado.')
      return
    }
    if (selectedAfiliado.value && !selectedAfiliado.value.puede_tener_mas) {
      form.setError('afiliado_id', 'Este afiliado ya alcanzó el máximo de 2 usuarios.')
      return
    }
  }
  form.post('/users')
}
</script>
