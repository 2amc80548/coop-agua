<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ViewCounter from '@/Components/ViewCounter.vue';

const props = defineProps({
    reclamoTipos: Array,
    conexiones: Array,
    errors: Object,
});

const form = useForm({
    reclamo_tipo_id: '',
    conexion_id: props.conexiones.length === 1 ? props.conexiones[0].id : '', 
    asunto: '',
    mensaje_usuario: '',
});

const submit = () => {
    form.post(route('reclamos.store'));
};
</script>

<template>
    <AppLayout title="Nuevo Reclamo">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Presentar un Nuevo Reclamo
            </h2>
        </template>

        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white ... p-6 md:p-8">

                <div v-if="form.errors.error_general" 
                     class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg dark:bg-red-900 dark:text-red-300" 
                     role="alert">
                    <span class="font-medium">¡Error!</span> {{ form.errors.error_general }}
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                </form>
            </div>
        </div>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 md:p-8">
                    
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label for="reclamo_tipo_id" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Tipo de Reclamo *</label>
                            <select id="reclamo_tipo_id" v-model="form.reclamo_tipo_id" class="mt-1 block w-full ..." required>
                                <option value="" disabled>Seleccione una categoría...</option>
                                <option v-for="tipo in reclamoTipos" :key="tipo.id" :value="tipo.id">{{ tipo.nombre }}</option>
                            </select>
                            <div v-if="form.errors.reclamo_tipo_id" class="text-red-600 text-sm mt-1">{{ form.errors.reclamo_tipo_id }}</div>
                        </div>

                         <div v-if="conexiones.length > 1">
                            <label for="conexion_id" class="block font-medium text-sm text-gray-700 dark:text-gray-300">¿Sobre qué medidor es el reclamo? (Opcional)</label>
                            <select id="conexion_id" v-model="form.conexion_id" class="mt-1 block w-full ...">
                                <option value="">(Ninguno en específico)</option>
                                <option v-for="con in conexiones" :key="con.id" :value="con.id">
                                    Medidor: {{ con.codigo_medidor }} ({{ con.direccion }})
                                </option>
                            </select>
                            <div v-if="form.errors.conexion_id" class="text-red-600 text-sm mt-1">{{ form.errors.conexion_id }}</div>
                        </div> 

                        <div>
                            <label for="asunto" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Asunto *</label>
                            <input id="asunto" v-model="form.asunto" type="text" placeholder="Ej: Mi factura de este mes es muy alta" class="mt-1 block w-full ..." required />
                            <div v-if="form.errors.asunto" class="text-red-600 text-sm mt-1">{{ form.errors.asunto }}</div>
                        </div>

                        <div>
                            <label for="mensaje_usuario" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Mensaje (Explique su reclamo) *</label>
                            <textarea id="mensaje_usuario" v-model="form.mensaje_usuario" rows="6" class="mt-1 block w-full ..." placeholder="Por favor, sea lo más detallado posible..." required></textarea>
                            <div v-if="form.errors.mensaje_usuario" class="text-red-600 text-sm mt-1">{{ form.errors.mensaje_usuario }}</div>
                        </div>
                        
                        <div class="flex justify-end gap-4 mt-8 pt-6 border-t dark:border-gray-700">
                            <Link :href="route('reclamos.usuarioIndex')" class="bg-gray-500 ...">Cancelar</Link>
                            <button type="submit" :disabled="form.processing" class="bg-green-600 ...">
                                {{ form.processing ? 'Enviando...' : 'Enviar Reclamo' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
             <ViewCounter />
        </div>
    </AppLayout>
</template>