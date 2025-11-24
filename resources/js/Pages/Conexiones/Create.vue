<script setup>
import { ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue'; 
import axios from 'axios';
import ViewCounter from '@/Components/ViewCounter.vue';


const props = defineProps({
    zonas: Array,
    searchAfiliadosUrl: String, 
    errors: Object,
});
const page = usePage();

const form = useForm({
    afiliado_id: null,
    codigo_medidor: '',
    direccion: '',
    zona_id: '', 
    estado: 'activo',
    fecha_instalacion: new Date().toISOString().split('T')[0], 
    tipo_conexion: 'domiciliaria',
});

// --- Lógica de Buscador de Afiliado ---
const searchCi = ref('');
const afiliadoEncontrado = ref(null);
const busquedaMensaje = ref('');
const isSearching = ref(false);

const buscarAfiliado = async () => {
    if (!searchCi.value.trim()) { busquedaMensaje.value = 'Ingrese un CI.'; return; }
    isSearching.value = true;
    afiliadoEncontrado.value = null;
    form.afiliado_id = null;
    busquedaMensaje.value = 'Buscando...';
    
    // Construir la URL de la API (reemplazando el placeholder)
    const url = props.searchAfiliadosUrl.replace('__CI_PLACEHOLDER__', searchCi.value.trim());

    try {
        const response = await axios.get(url);
        if (response.data) {
            afiliadoEncontrado.value = response.data;
            form.afiliado_id = response.data.id;
            form.direccion = response.data.direccion;
            form.zona_id = response.data.zona_id; 
            form.codigo_medidor = response.data.codigo;
            busquedaMensaje.value = `Afiliado: ${response.data.nombre_completo}`;
        }
    } catch (error) {
        busquedaMensaje.value = 'No se encontró ningún afiliado con ese CI (404).';
    } finally {
        isSearching.value = false;
    }
};
// --- Fin Lógica Buscador ---

// --- Lógica de "Nueva Zona" 
const showNuevaZonaModal = ref(false);
const nuevaZonaForm = useForm({ nombre: '' });
const zonasLocales = ref([...props.zonas]); // Lista local reactiva
const guardarNuevaZona = () => {
    nuevaZonaForm.post(route('zonas.store'), { // Ruta para guardar zona
        preserveScroll: true,
        onSuccess: () => {
            // Leemos la 'nueva_zona' que el controlador nos envía en el flash
            const nuevaZona = page.props.flash.nueva_zona;
            if (nuevaZona) {
                zonasLocales.value.push(nuevaZona); 
                zonasLocales.value.sort((a, b) => a.nombre.localeCompare(b.nombre));
                form.zona_id = nuevaZona.id; // Auto-selecciona
            }
            showNuevaZonaModal.value = false;
            nuevaZonaForm.reset();
        },
        onError: (errors) => {
             console.error("Error al guardar zona:", errors);
             // El error (ej. 'nombre ya existe') se mostrará en el modal
        }
    });
};
// --- Fin Lógica Nueva Zona ---

const submit = () => {
    form.post(route('conexiones.store'));
};
</script>

<template>
    <AppLayout title="Crear Conexión">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Crear Nueva Conexión
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 md:p-8">
                    
                    <div v-if="form.hasErrors" class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-6 dark:bg-red-200 dark:text-red-800" role="alert">
                       <p class="font-bold">Error</p>
                       <ul class="list-disc ml-5 text-sm"><li v-for="(error, key) in form.errors" :key="key">{{ error }}</li></ul>
                    </div>
                    
                    <form @submit.prevent="submit" class="space-y-6">
                        
                        <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-md bg-gray-50 dark:bg-gray-900">
                            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">1. Buscar Afiliado por CI</label>
                            <div class="flex gap-2">
                                <input v-model="searchCi" @keydown.enter.prevent="buscarAfiliado" type="text" placeholder="Ingrese CI..." 
                                       class="flex-grow border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
                                <button @click.prevent="buscarAfiliado" :disabled="isSearching" 
                                        class="bg-indigo-600 text-white px-4 py-2 rounded shadow hover:bg-indigo-700 disabled:opacity-50 transition duration-150">
                                    {{ isSearching ? 'Buscando...' : 'Buscar' }}
                                </button>
                            </div>
                            <div v-if="busquedaMensaje" :class="{'text-green-600 dark:text-green-400': form.afiliado_id, 'text-red-600 dark:text-red-400': !form.afiliado_id}" class="text-sm mt-1">{{ busquedaMensaje }}</div>
                            <div v-if="form.errors.afiliado_id" class="text-red-600 text-sm mt-1">{{ form.errors.afiliado_id }}</div>
                        </div>

                        <fieldset :disabled="!form.afiliado_id" class="space-y-4 border-t border-gray-200 dark:border-gray-700 pt-6">
                            <legend class="font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">2. Datos de la Conexión (Medidor)</legend>
                            
                            <div>
                                <label for="codigo_medidor" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Código del Medidor *</label>
                                <input id="codigo_medidor" v-model="form.codigo_medidor" type="text" 
                                       class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required />
                                <div v-if="form.errors.codigo_medidor" class="text-red-600 text-sm mt-1">{{ form.errors.codigo_medidor }}</div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="direccion" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Dirección de Instalación *</label>
                                    <input id="direccion" v-model="form.direccion" type="text" 
                                           class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required />
                                    <div v-if="form.errors.direccion" class="text-red-600 text-sm mt-1">{{ form.errors.direccion }}</div>
                                </div>
                                <div>
                                    <label for="zona_id" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Zona *</label>
                                    <div class="mt-1 flex items-center gap-2">
                                        <select id="zona_id" v-model="form.zona_id" 
                                                class="flex-grow border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                                            <option value="" disabled>Seleccione una zona</option>
                                            <option v-for="zona in zonasLocales" :key="zona.id" :value="zona.id">{{ zona.nombre }}</option>
                                        </select>
                                        <button @click.prevent="showNuevaZonaModal = true" type="button" 
                                                class="flex-shrink-0 px-3 py-2 bg-blue-500 text-white rounded-md text-sm hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-500">+</button>
                                    </div>
                                    <div v-if="form.errors.zona_id" class="text-red-600 text-sm mt-1">{{ form.errors.zona_id }}</div>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="fecha_instalacion" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Fecha de Instalación *</label>
                                    <input id="fecha_instalacion" v-model="form.fecha_instalacion" type="date" 
                                           class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required />
                                    <div v-if="form.errors.fecha_instalacion" class="text-red-600 text-sm mt-1">{{ form.errors.fecha_instalacion }}</div>
                                </div>
                                <div>
                                    <label for="tipo_conexion" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Tipo de Conexión *</label>
                                    <select id="tipo_conexion" v-model="form.tipo_conexion" 
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <option value="domiciliaria">Domiciliaria</option>
                                        <option value="comercial">Comercial</option>
                                        <option value="institucional">Institucional</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="estado" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Estado *</label>
                                    <select id="estado" v-model="form.estado" 
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <option value="activo">Activo</option>
                                        <option value="suspendido">Suspendido</option>
                                        <option value="eliminado">Eliminado</option>
                                    </select>
                                </div>
                            </div>
                        </fieldset>

                        <div class="flex justify-end gap-4 mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <Link :href="route('conexiones.index')" 
                                  class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded transition shadow">Cancelar</Link>
                            <button type="submit" :disabled="form.processing || !form.afiliado_id"
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50 transition shadow">
                                {{ form.processing ? 'Guardando...' : 'Guardar Conexión' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <Modal :show="showNuevaZonaModal" @close="showNuevaZonaModal = false" max-width="md">
             <div class="p-6 dark:bg-gray-800 dark:text-gray-200">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Agregar Nueva Zona</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">La zona se añadirá a la lista y se seleccionará automáticamente.</p>
                <form @submit.prevent="guardarNuevaZona" class="mt-4 space-y-4">
                     <div>
                        <label for="nueva_zona_nombre_create" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Nombre de la Zona *</label>
                        <input id="nueva_zona_nombre_create" v-model="nuevaZonaForm.nombre" type="text" 
                               class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required />
                        <div v-if="nuevaZonaForm.errors.nombre" class="text-red-600 text-sm mt-1">{{ nuevaZonaForm.errors.nombre }}</div>
                    </div>
                    <div class="flex justify-end gap-4">
                        <button type="button" @click="showNuevaZonaModal = false" 
                                class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded transition shadow">Cancelar</button>
                        <button type="submit" :disabled="nuevaZonaForm.processing" 
                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50 transition shadow">
                            {{ nuevaZonaForm.processing ? 'Guardando...' : 'Guardar Zona' }}
                        </button>
                    </div>
                </form>
            </div>
              
        </Modal>
        <ViewCounter />
    </AppLayout>
</template>