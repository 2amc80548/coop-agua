<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    lectura: Object, // Trae lectura con 'conexion.afiliado' y 'conexion.zona'
    errors: Object,
});

// Formateador de Fecha Seguro
const formatDateForInput = (dateString) => {
    if (!dateString) return '';
    try {
        const date = new Date(dateString);
        date.setMinutes(date.getMinutes() + date.getTimezoneOffset());
        return date.toISOString().split('T')[0];
    } catch (e) { return ''; }
};

const form = useForm({
    fecha_lectura: formatDateForInput(props.lectura.fecha_lectura),
    lectura_anterior: props.lectura.lectura_anterior,
    lectura_actual: props.lectura.lectura_actual,
    observacion: props.lectura.observacion || '',
});

const consumo = computed(() => {
    const anterior = parseFloat(form.lectura_anterior) || 0;
    const actual = parseFloat(form.lectura_actual) || 0;
    if (!isNaN(actual) && actual >= anterior) {
        return (actual - anterior).toFixed(2);
    }
    return '0.00';
});

const submit = () => {
    form.put(route('lecturas.update', props.lectura.id));
};
</script>

<template>
    <AppLayout title="Editar Lectura">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Editar Lectura - Período {{ lectura.periodo }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 md:p-8">

                     <div v-if="$page.props.flash.error || (errors && errors.error_general)"
                           class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-6 shadow-sm" role="alert">
                       <p class="font-bold">Error</p>
                       <p>{{ $page.props.flash.error || (errors ? errors.error_general : 'No se pudo procesar.') }}</p>
                       <ul v-if="errors && !errors.error_general" class="list-disc ml-5 text-sm mt-1">
                           <li v-for="(error, key) in errors" :key="key">{{ error }}</li>
                       </ul>
                     </div>

                    <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-md text-sm">
                        <h3 class="font-medium text-gray-900 dark:text-white mb-2">Conexión</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4">
                            <p><strong class="text-gray-600 dark:text-gray-400">Medidor:</strong> {{ lectura.conexion?.codigo_medidor ?? 'N/A' }}</p>
                            <p><strong class="text-gray-600 dark:text-gray-400">Afiliado:</strong> {{ lectura.conexion?.afiliado?.nombre_completo ?? 'N/A' }}</p>
                            <p class="md:col-span-2"><strong class="text-gray-600 dark:text-gray-400">Dirección:</strong> {{ lectura.conexion?.direccion ?? 'N/A' }}</p>
                            <p class="md:col-span-2"><strong class="text-gray-600 dark:text-gray-400">Zona:</strong> {{ lectura.conexion?.zona?.nombre ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">
                        <fieldset>
                            <legend class="sr-only">Datos de la Lectura a Editar</legend>
                            <div class="mb-4">
                                <label for="fecha_lectura" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Fecha de Lectura</label>
                                <input id="fecha_lectura" v-model="form.fecha_lectura" type="date"
                                       class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm block mt-1 w-full"
                                       required />
                                <div v-if="form.errors.fecha_lectura" class="text-red-600 text-sm mt-1">{{ form.errors.fecha_lectura }}</div>
                            </div>
                             <div class="mb-4">
                                   <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">Lectura Anterior (m³)</label>
                                   <input :value="form.lectura_anterior" type="number" step="0.01"
                                          class="border-gray-300 dark:border-gray-600 rounded-md shadow-sm block mt-1 w-full bg-gray-100 dark:bg-gray-900 cursor-not-allowed"
                                          disabled readonly />
                             </div>
                             <div class="mb-4">
                                <label for="lectura_actual" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Lectura Actual (m³)</label>
                                <input id="lectura_actual" v-model.number="form.lectura_actual"
                                       type="number" step="0.01" :min="form.lectura_anterior"
                                       class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm block mt-1 w-full"
                                       required />
                                <div v-if="form.errors.lectura_actual" class="text-red-600 text-sm mt-1">{{ form.errors.lectura_actual }}</div>
                                <div v-else-if="form.lectura_actual !== '' && form.lectura_actual < form.lectura_anterior" class="text-red-600 text-sm mt-1">
                                    La lectura actual no puede ser menor a la anterior ({{ form.lectura_anterior }}).
                                </div>
                             </div>
                             <div v-if="form.lectura_actual !== '' && form.lectura_actual >= form.lectura_anterior" class="mb-4 p-3 bg-green-50 dark:bg-gray-900 rounded-md">
                                   <label class="block font-medium text-sm text-green-800 dark:text-green-300">Consumo Calculado</label>
                                   <p class="text-xl font-bold text-green-700 dark:text-green-400">{{ consumo }} m³</p>
                             </div>
                             <div class="mb-4">
                                <label for="observacion" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Observación (Opcional)</label>
                                <textarea id="observacion" v-model="form.observacion"
                                          class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm block mt-1 w-full"
                                          rows="3"></textarea>
                                <div v-if="form.errors.observacion" class="text-red-600 text-sm mt-1">{{ form.errors.observacion }}</div>
                             </div>
                        </fieldset>
                        <div class="flex justify-end gap-3 mt-8 border-t border-gray-200 dark:border-gray-700 pt-4">
                            <Link :href="route('lecturas.show', lectura.id)" class="bg-gray-500 ...">
                                Cancelar
                            </Link>
                            <button type="submit"
                                    :disabled="form.processing || form.lectura_actual < form.lectura_anterior"
                                    class="bg-yellow-500 ... disabled:opacity-50"
                                    :class="{'cursor-not-allowed': form.lectura_actual < form.lectura_anterior}">
                                {{ form.processing ? 'Guardando...' : 'Actualizar Lectura' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>