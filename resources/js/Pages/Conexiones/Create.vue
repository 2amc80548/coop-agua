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

    es_antiguo: false,      // El checkbox (true/false)
    lectura_anterior: 0,    // El número que se guardará
});

// --- Lógica de Buscador de Afiliado (ESTILO LECTURA) ---
const searchTerm = ref(''); // Usaremos esta para el input
const searchResults = ref([]);
const isSearching = ref(false);
const showSearchDropdown = ref(false);

let searchTimeout = null;

const onSearchInput = () => {
    clearTimeout(searchTimeout);
    showSearchDropdown.value = true;
    
    if (searchTerm.value.length < 2) {
        searchResults.value = [];
        isSearching.value = false;
        return;
    }

    isSearching.value = true;
    searchTimeout = setTimeout(async () => {
        try {
            // Llamamos a la API enviando el término
            const response = await axios.get('/api/afiliados/buscar', { 
                params: { term: searchTerm.value } 
            });
            searchResults.value = response.data;
        } catch (error) {
            console.error("Error buscando:", error);
        } finally {
            isSearching.value = false;
        }
    }, 300);
};

// Función para seleccionar el afiliado y rellenar el formulario
const selectAfiliado = (a) => {
    form.afiliado_id = a.id;
    form.direccion = a.direccion;
    form.zona_id = a.zona_id; 
    form.codigo_medidor = a.codigo;
    
    searchTerm.value = a.nombre_completo; // Lo que se queda escrito
    showSearchDropdown.value = false;
    searchResults.value = [];
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

const handleBlur = () => { 
    setTimeout(() => { showSearchDropdown.value = false; }, 300); 
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
                        <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">
                            1. Buscar Afiliado (Nombre, CI o Código)
                        </label>
                    <div class="relative">
                        <input 
                            v-model="searchTerm" 
                            @input="onSearchInput" 
                            @focus="showSearchDropdown = true"
                            @blur="handleBlur" 
                            type="text" 
                            placeholder="Buscar por Nombre, CI o Código..." 
                            class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm" 
                            autocomplete="off"
                        />
                        
                        <span v-if="isSearching" class="absolute right-3 top-2 mt-px text-gray-400">
                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </span>

                        <ul v-if="showSearchDropdown && (searchResults.length > 0 || isSearching || searchTerm.length >= 2)" class="absolute z-50 w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-60 overflow-auto">
                            <li v-if="isSearching && !searchResults.length" class="px-4 py-2 text-gray-500 text-sm">Buscando...</li>
                            
                            <li v-for="a in searchResults" :key="a.id" @mousedown="selectAfiliado(a)" class="px-4 py-2 hover:bg-blue-100 dark:hover:bg-gray-600 cursor-pointer border-b last:border-b-0">
                                <div class="text-sm">
                                    <strong class="dark:text-white">{{ a.nombre_completo }}</strong><br>
                                    <span class="text-xs text-gray-600 dark:text-gray-400">CI: {{ a.ci }} | Código: {{ a.codigo }}</span>
                                </div>
                            </li>

                            <li v-if="!isSearching && !searchResults.length && searchTerm.length >= 2" class="px-4 py-2 dark: text-gray-500 text-sm">
                                 Afiliado no encontrado.
                            </li>
                        </ul>
                    </div>
                        
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
                             <div class="mt-4 p-4 border border-gray-200 dark:border-gray-700 rounded-md bg-gray-50 dark:bg-gray-900">
                        <div class="flex items-center">
                            <input 
                                id="check_antiguo" 
                                v-model="form.es_antiguo" 
                                type="checkbox" 
                                class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                            >
                            <label for="check_antiguo" class="ml-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                ¿Es una conexión con lectura anterior? (Medidor antiguo)
                            </label>
                        </div>

                        <div v-if="form.es_antiguo" class="mt-3">
                            <label class="block font-medium text-xs text-gray-600 dark:text-gray-400">Lectura Anterior Acumulada *</label>
                            <input 
                                v-model="form.lectura_anterior" 
                                type="number" 
                                class="mt-1 block w-full md:w-1/4 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Ej: 540"
                                required
                            >
                        </div>
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
                                        <!-- <option value="otro">Otro</option> -->
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