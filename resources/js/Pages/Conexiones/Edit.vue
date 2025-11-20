<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import axios from 'axios';
import ViewCounter from '@/Components/ViewCounter.vue';

const props = defineProps({
    conexion: Object, // Trae conexión con 'afiliado'
    zonas: Array,
    searchAfiliadosUrl: String,
    errors: Object,
});
const page = usePage();

// Formateador de Fecha Seguro
const formatFechafield = (dateString) => {
    if (!dateString) return null;
    try { return dateString.split('T')[0].split(' ')[0]; } 
    catch (e) { return null; }
};

const form = useForm({
    _method: 'PUT',
    afiliado_id: props.conexion.afiliado_id,
    codigo_medidor: props.conexion.codigo_medidor,
    direccion: props.conexion.direccion,
    zona_id: props.conexion.zona_id, // ¡Actualizado!
    estado: props.conexion.estado,
    fecha_instalacion: formatFechafield(props.conexion.fecha_instalacion), // ¡Corregido!
    tipo_conexion: props.conexion.tipo_conexion,
});

// --- Lógica de Buscador de Afiliado ---
const searchCi = ref('');
const afiliadoActual = ref(props.conexion.afiliado); 
const busquedaMensaje = ref(`Afiliado actual: ${props.conexion.afiliado?.nombre_completo || 'N/A'}`);
const isSearching = ref(false);

const buscarAfiliado = async () => {
    isSearching.value = true;
    busquedaMensaje.value = 'Buscando...';
    const url = props.searchAfiliadosUrl.replace('__CI_PLACEHOLDER__', searchCi.value.trim());
    try {
        const response = await axios.get(url);
        if (response.data) {
            afiliadoActual.value = response.data;
            form.afiliado_id = response.data.id;
            form.direccion = response.data.direccion;
            form.zona_id = response.data.zona_id;
            busquedaMensaje.value = `¡Nuevo Afiliado seleccionado!: ${response.data.nombre_completo}`;
        }
    } catch (error) {
        busquedaMensaje.value = 'No se encontró ningún afiliado con ese CI (404).';
    } finally {
        isSearching.value = false;
    }
};

// --- Lógica de "Nueva Zona" ---
const showNuevaZonaModal = ref(false);
const nuevaZonaForm = useForm({ nombre: '' });
const zonasLocales = ref([...props.zonas]); 
const guardarNuevaZona = () => {
    nuevaZonaForm.post(route('zonas.store'), {
        preserveScroll: true,
        onSuccess: () => {
            const nuevaZona = page.props.flash.nueva_zona;
            if (nuevaZona) {
                zonasLocales.value.push(nuevaZona);
                zonasLocales.value.sort((a, b) => a.nombre.localeCompare(b.nombre));
                form.zona_id = nuevaZona.id;
            }
            showNuevaZonaModal.value = false;
            nuevaZonaForm.reset();
        },
    });
};

const submit = () => {
    form.put(route('conexiones.update', props.conexion.id));
};
</script>

<template>
    <AppLayout title="Editar Conexión">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Editar Conexión: {{ form.codigo_medidor }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 md:p-8">
                    
                    <div v-if="form.hasErrors" class="bg-red-100 ... mb-6" role="alert">
                       <p class="font-bold">Error</p>
                       <ul class="list-disc ml-5 text-sm"><li v-for="(error, key) in form.errors" :key="key">{{ error }}</li></ul>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        
                        <div class="p-4 border dark:border-gray-700 rounded-md bg-gray-50 dark:bg-gray-900">
                            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">1. Afiliado Asignado (Cambiar)</label>
                            <div class="p-2 bg-blue-50 border border-blue-200 rounded text-sm mb-2">
                                {{ busquedaMensaje }}
                            </div>
                            <div class="flex gap-2">
                                <input v-model="searchCi" @keydown.enter.prevent="buscarAfiliado" type="text" placeholder="Ingrese CI para cambiar..." class="flex-grow ..." />
                                <button @click.prevent="buscarAfiliado" :disabled="isSearching" class="bg-indigo-600 ...">
                                    {{ isSearching ? '...' : 'Buscar' }}
                                </button>
                            </div>
                            <div v-if="form.errors.afiliado_id" class="text-red-600 text-sm mt-1">{{ form.errors.afiliado_id }}</div>
                        </div>

                        <fieldset class="space-y-4 border-t dark:border-gray-700 pt-6">
                            <legend class="font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">2. Datos de la Conexión (Medidor)</legend>
                            
                            <div>
                                <label for="codigo_medidor" class="block font-medium text-sm ...">Código del Medidor *</label>
                                <input id="codigo_medidor" v-model="form.codigo_medidor" type="text" class="mt-1 block w-full ..." required />
                                <div v-if="form.errors.codigo_medidor" class="text-red-600 text-sm mt-1">{{ form.errors.codigo_medidor }}</div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="direccion" class="block font-medium text-sm ...">Dirección de Instalación *</label>
                                    <input id="direccion" v-model="form.direccion" type="text" class="mt-1 block w-full ..." required />
                                    <div v-if="form.errors.direccion" class="text-red-600 text-sm mt-1">{{ form.errors.direccion }}</div>
                                </div>
                                <div>
                                    <label for="zona_id_edit" class="block font-medium text-sm ...">Zona *</label>
                                    <div class="mt-1 flex items-center gap-2">
                                        <select id="zona_id_edit" v-model="form.zona_id" class="flex-grow border-gray-300 ... rounded-md shadow-sm" required>
                                            <option value="" disabled>Seleccione una zona</option>
                                            <option v-for="zona in zonasLocales" :key="zona.id" :value="zona.id">{{ zona.nombre }}</option>
                                        </select>
                                        <button @click.prevent="showNuevaZonaModal = true" type="button" class="flex-shrink-0 px-3 py-2 bg-blue-500 ...">+</button>
                                    </div>
                                    <div v-if="form.errors.zona_id" class="text-red-600 text-sm mt-1">{{ form.errors.zona_id }}</div>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="fecha_instalacion" class="block font-medium text-sm ...">Fecha de Instalación *</label>
                                    <input id="fecha_instalacion" v-model="form.fecha_instalacion" type="date" class="mt-1 block w-full ..." required />
                                    <div v-if="form.errors.fecha_instalacion" class="text-red-600 text-sm mt-1">{{ form.errors.fecha_instalacion }}</div>
                                </div>
                                <div>
                                    <label for="tipo_conexion" class="block font-medium text-sm ...">Tipo de Conexión *</label>
                                    <select id="tipo_conexion" v-model="form.tipo_conexion" class="mt-1 block w-full ...">
                                        <option value="domiciliaria">Domiciliaria</option>
                                        <option value="comercial">Comercial</option>
                                        <option value="institucional">Institucional</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="estado" class="block font-medium text-sm ...">Estado *</label>
                                    <select id="estado" v-model="form.estado" class="mt-1 block w-full ...">
                                        <option value="activo">Activo</option>
                                        <option value="suspendido">Suspendido</option>
                                        <option value="eliminado">Eliminado</option>
                                    </select>
                                </div>
                            </div>
                        </fieldset>

                        <div class="flex justify-end gap-4 mt-8 pt-6 border-t dark:border-gray-700">
                            <Link :href="route('conexiones.index')" class="bg-gray-500 ...">Cancelar</Link>
                            <button type="submit" :disabled="form.processing"
                                    class="bg-blue-600 ... disabled:opacity-50">
                                {{ form.processing ? 'Actualizando...' : 'Actualizar Conexión' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <Modal :show="showNuevaZonaModal" @close="showNuevaZonaModal = false" max-width="md">
             <div class="p-6 dark:bg-gray-800 dark:text-gray-200">
                <h2 class="text-lg font-medium ...">Agregar Nueva Zona</h2>
                <p class="mt-2 text-sm ...">La zona se añadirá a la lista y se seleccionará automáticamente.</p>
                <form @submit.prevent="guardarNuevaZona" class="mt-4 space-y-4">
                     <div>
                        <label for="nueva_zona_nombre_edit" class="block font-medium text-sm ...">Nombre de la Zona *</label>
                        <input id="nueva_zona_nombre_edit" v-model="nuevaZonaForm.nombre" type="text" class="mt-1 block w-full ..." required />
                        <div v-if="nuevaZonaForm.errors.nombre" class="text-red-600 text-sm mt-1">{{ nuevaZonaForm.errors.nombre }}</div>
                    </div>
                    <div class="flex justify-end gap-4">
                        <button type="button" @click="showNuevaZonaModal = false" class="bg-gray-500 ...">Cancelar</button>
                        <button type="submit" :disabled="nuevaZonaForm.processing" class="bg-blue-600 ...">
                            {{ nuevaZonaForm.processing ? 'Guardando...' : 'Guardar Zona' }}
                        </button>
                    </div>
                </form>
               
            </div>
        </Modal>
         <ViewCounter />
    </AppLayout>
</template>