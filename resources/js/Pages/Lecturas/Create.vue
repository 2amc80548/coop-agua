<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

// ✅ Guardamos props
const props = defineProps({
    conexiones: {
        type: Array,
        default: () => []
    },
    errors: Object,
});
console.log('Props recibidas:', props);

const form = useForm({
    conexion_id: '',
    fecha_lectura: new Date().toISOString().split('T')[0],
    lectura_anterior: '',
    lectura_actual: '',
    observacion: '',
});

const search = ref('');
const showDropdown = ref(false);

// ✅ Filtrado seguro
const conexionesFiltradas = computed(() => {
    const term = search.value?.trim().toLowerCase() || '';
    if (!term || !Array.isArray(props.conexiones)) return [];
    return props.conexiones.filter(c => {
        const nombre = c.beneficiario?.nombre_completo?.toLowerCase() || '';
        const medidor = c.codigo_medidor?.toLowerCase() || '';
        return nombre.includes(term) || medidor.includes(term);
    });
});

// ✅ Seleccionar conexión
const selectConexion = (c) => {
    form.conexion_id = c.id;
    form.lectura_anterior = c.lectura_anterior || 0;
    search.value = `${c.beneficiario?.nombre_completo} (Medidor: ${c.codigo_medidor})`;
    showDropdown.value = false;
};

// ✅ Consumo
const consumo = computed(() => {
    const anterior = parseFloat(form.lectura_anterior) || 0;
    const actual = parseFloat(form.lectura_actual) || 0;
    return actual >= anterior ? actual - anterior : 0;
});

// ✅ Focus y blur
const handleFocus = () => {
    if (!form.conexion_id) {
        search.value = '';
    }
    showDropdown.value = true;
};

const handleBlur = () => {
    setTimeout(() => {
        showDropdown.value = false;
    }, 200);
};

// ✅ Watch seguro
watch(search, (val) => {
    if (!val) {
        showDropdown.value = false;
    } else {
        showDropdown.value = true;
    }
});
</script>

<template>
    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Registrar Nueva Lectura
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <form @submit.prevent="form.post(route('lecturas.store'))">
                        <!-- Búsqueda con autocompletado -->
                        <div class="mb-6">
                            <label class="block font-medium text-sm text-gray-700">Buscar Conexión</label>
                            <div class="relative">
                                <input
                                    v-model="search"
                                    @focus="handleFocus"
                                    @blur="handleBlur"
                                    type="text"
                                    placeholder="Nombre del beneficiario o código de medidor..."
                                    class="border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm block mt-1 w-full"
                                    autocomplete="off"
                                />

                                <!-- Resultados -->
                                <ul
                                    v-if="showDropdown && search"
                                    class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto"
                                >
                                    <li
                                        v-for="c in conexionesFiltradas"
                                        :key="c.id"
                                        @click="selectConexion(c)"
                                        class="px-4 py-2 hover:bg-indigo-50 cursor-pointer border-b border-gray-100 last:border-b-0"
                                    >
                                        <div class="text-sm">
                                            <strong>{{ c.beneficiario.nombre_completo }}</strong>
                                            <br />
                                            <span class="text-gray-600">
                                                Medidor: {{ c.codigo_medidor }} | 
                                                Anterior: {{ c.lectura_anterior }} m³
                                            </span>
                                        </div>
                                    </li>
                                </ul>

                                <ul
                                    v-else-if="showDropdown && !conexionesFiltradas.length && search"
                                    class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg"
                                >
                                    <li class="px-4 py-2 text-gray-500 text-sm">No se encontraron conexiones.</li>
                                </ul>
                            </div>
                            <div v-if="errors.conexion_id" class="text-red-600 text-sm mt-1">{{ errors.conexion_id }}</div>
                        </div>

                        <!-- Conexión seleccionada -->
                        <div v-if="form.conexion_id" class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-md">
                            <h3 class="text-sm font-medium text-blue-900">Conexión Seleccionada</h3>
                            <p class="mt-2"><strong>Lectura Anterior:</strong> 
                                <span class="font-bold text-lg text-blue-700">{{ form.lectura_anterior }}</span> m³
                            </p>
                        </div>

                        <!-- Lectura actual -->
                        <div class="mb-4">
                            <label class="block font-medium text-sm text-gray-700">Lectura Actual (m³)</label>
                            <input
                                v-model.number="form.lectura_actual"
                                type="number"
                                :min="form.lectura_anterior"
                                class="border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm block mt-1 w-full"
                                required
                            />
                            <div v-if="errors.lectura_actual" class="text-red-600 text-sm mt-1">{{ errors.lectura_actual }}</div>
                        </div>

                        <!-- Consumo calculado -->
                        <div v-if="form.lectura_actual" class="mb-4 p-3 bg-green-50 rounded-md">
                            <label class="block font-medium text-sm text-gray-700">Consumo Registrado</label>
                            <p class="text-xl font-bold text-green-700">
                                {{ consumo }} m³
                            </p>
                        </div>

                        <!-- Fecha de lectura -->
                        <div class="mb-4">
                            <label class="block font-medium text-sm text-gray-700">Fecha de Lectura</label>
                            <input
                                v-model="form.fecha_lectura"
                                type="date"
                                class="border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm block mt-1 w-full"
                                required
                            />
                            <div v-if="errors.fecha_lectura" class="text-red-600 text-sm mt-1">{{ errors.fecha_lectura }}</div>
                        </div>

                        <!-- Observación -->
                        <div class="mb-4">
                            <label class="block font-medium text-sm text-gray-700">Observación</label>
                            <textarea
                                v-model="form.observacion"
                                class="border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm block mt-1 w-full"
                                rows="3"
                            ></textarea>
                        </div>

                        <!-- Botones -->
                        <div class="flex justify-end gap-3">
                            <Link :href="route('lecturas.index')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Cancelar
                            </Link>

                            <button
                                 type="submit"
                                    :disabled="form.processing || !form.conexion_id || !form.lectura_actual"
                                class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                            >
                                {{ form.processing ? 'Guardando...' : 'Registrar Lectura' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>