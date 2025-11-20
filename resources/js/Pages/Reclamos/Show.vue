<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ViewCounter from '@/Components/ViewCounter.vue';


const props = defineProps({
    reclamo: Object, // Trae el reclamo con todas sus relaciones
    errors: Object,
});

const isAdminOrSecretaria = computed(() => 
    usePage().props.auth.user.role_names.includes('Administrador') ||
    usePage().props.auth.user.role_names.includes('Secretaria')
);

// Formulario de RESPUESTA (solo para Admin)
const formRespuesta = useForm({
    respuesta_admin: props.reclamo.respuesta_admin || '',
    estado: props.reclamo.estado || 'En Revisión',
});

const submitRespuesta = () => {
    formRespuesta.put(route('reclamos.update', props.reclamo.id), {
        preserveScroll: true,
        // onSuccess se maneja con el flash message
    });
};

const formatDate = (dateString) => { /* (función de formateo) */ };
</script>

<template>
    <AppLayout :title="'Reclamo #' + reclamo.id">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Detalle del Reclamo #{{ reclamo.id }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                
                <div v-if="$page.props.flash.success" class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-4 shadow-sm dark:bg-green-900 dark:border-green-700 dark:text-green-100" role="alert">
                   <p class="font-bold">Éxito</p> <p>{{ $page.props.flash.success }}</p>
                </div>
                
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    
                    <div class="p-6 md:p-8 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">Asunto: {{ reclamo.asunto }}</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-700 dark:text-gray-300">
                            <p><strong class="text-gray-500 dark:text-gray-400">Afiliado:</strong> {{ reclamo.afiliado?.nombre_completo }}</p>
                            <p><strong class="text-gray-500 dark:text-gray-400">CI:</strong> {{ reclamo.afiliado?.ci }}</p>
                            <p><strong class="text-gray-500 dark:text-gray-400">Tipo:</strong> {{ reclamo.tipo?.nombre }}</p>
                            <p><strong class="text-gray-500 dark:text-gray-400">Medidor (si aplica):</strong> {{ reclamo.conexion?.codigo_medidor || 'N/A' }}</p>
                            <p><strong class="text-gray-500 dark:text-gray-400">Estado:</strong> 
                                <span class="font-bold" :class="{
                                    'text-yellow-600 dark:text-yellow-400': reclamo.estado === 'Abierto',
                                    'text-blue-600 dark:text-blue-400': reclamo.estado === 'En Revisión',
                                    'text-green-600 dark:text-green-400': reclamo.estado === 'Resuelto',
                                    'text-gray-600 dark:text-gray-400': reclamo.estado === 'Cerrado',
                                }">{{ reclamo.estado }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="p-6 md:p-8 space-y-6">
                        
                        <div class="flex gap-3">
                            <div class="flex-shrink-0">
                                <img class="h-10 w-10 rounded-full object-cover" :src="reclamo.usuario?.profile_photo_url" :alt="reclamo.usuario?.name">
                            </div>
                            <div class="flex-1 bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ reclamo.usuario?.name }} (Afiliado)</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(reclamo.created_at) }}</p>
                                <p class="mt-3 text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ reclamo.mensaje_usuario }}</p>
                            </div>
                        </div>

                        <div v-if="reclamo.respuesta_admin" class="flex gap-3">
                            <div class="flex-1 bg-blue-50 dark:bg-blue-900 rounded-lg p-4 text-right">
                                <p class="text-sm font-medium text-blue-900 dark:text-blue-300">Respuesta de {{ reclamo.resueltoPor?.name || 'Administración' }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(reclamo.updated_at) }}</p>
                                <p class="mt-3 text-gray-700 dark:text-gray-300 whitespace-pre-wrap text-left">{{ reclamo.respuesta_admin }}</p>
                            </div>
                             <div class="flex-shrink-0">
                                 <img class="h-10 w-10 rounded-full object-cover" :src="reclamo.resueltoPor?.profile_photo_url" :alt="reclamo.resueltoPor?.name">
                             </div>
                        </div>
                        
                        <div v-if="isAdminOrSecretaria" class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">Responder o Actualizar Estado</h3>
                            <form @submit.prevent="submitRespuesta" class="space-y-4">
                                <div>
                                    <label for="respuesta_admin" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Respuesta</label>
                                    <textarea id="respuesta_admin" v-model="formRespuesta.respuesta_admin" rows="4" 
                                              class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" 
                                              placeholder="Escriba la respuesta al afiliado..."></textarea>
                                    <div v-if="formRespuesta.errors.respuesta_admin" class="text-red-600 text-sm mt-1">{{ formRespuesta.errors.respuesta_admin }}</div>
                                </div>
                                <div>
                                    <label for="estado_select" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Cambiar Estado</label>
                                    <select id="estado_select" v-model="formRespuesta.estado" 
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <option value="Abierto">Abierto</option>
                                        <option value="En Revisión">En Revisión</option>
                                        <option value="Resuelto">Resuelto</option>
                                        <option value="Cerrado">Cerrado</option>
                                    </select>
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit" :disabled="formRespuesta.processing" 
                                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow transition duration-150">
                                        {{ formRespuesta.processing ? 'Guardando...' : 'Guardar Respuesta' }}
                                    </button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
            <ViewCounter />
        </div>
    </AppLayout>
</template>