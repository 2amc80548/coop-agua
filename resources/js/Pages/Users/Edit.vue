<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref, watch } from 'vue';
import axios from 'axios';

// --- Props ---
const props = defineProps({
    user: Object, // El usuario a editar
    allRoles: Array, // Lista de todos los roles
    searchAfiliadosUrl: String, // ¡NUEVO! API para buscar
    errors: Object,
});

// --- Formulario ---
const form = useForm({
    _method: 'PUT',
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
    role_id: props.user.roles.length > 0 ? props.user.roles[0].id : '', 
    afiliado_id: props.user.afiliado_id, // ¡NUEVO!
});

// --- Lógica del Buscador de Afiliados (¡NUEVO!) ---
const searchCi = ref('');
// Mostrar el afiliado actual si existe
const afiliadoActualNombre = ref(props.user.afiliado?.nombre_completo || 'No vinculado');
const busquedaMensaje = ref('');
const isSearching = ref(false);

const buscarAfiliado = async () => {
    isSearching.value = true;
    busquedaMensaje.value = 'Buscando...';
    const url = props.searchAfiliadosUrl.replace('__CI_PLACEHOLDER__', searchCi.value.trim());
    try {
        const response = await axios.get(url);
        if (response.data) {
            // ¡Actualizamos el formulario!
            form.afiliado_id = response.data.id;
            // ¡NO CAMBIAMOS EL NOMBRE!
            // form.name = response.data.nombre_completo; 
            afiliadoActualNombre.value = response.data.nombre_completo;
            busquedaMensaje.value = `Afiliado seleccionado: ${response.data.nombre_completo}. Presione 'Actualizar' para guardar.`;
            searchCi.value = '';
        }
    } catch (error) {
        busquedaMensaje.value = 'No se encontró ningún afiliado con ese CI (404).';
    } finally {
        isSearching.value = false;
    }
};

// Quitar la vinculación
const clearAfiliado = () => {
    form.afiliado_id = null;
    afiliadoActualNombre.value = 'No vinculado';
    busquedaMensaje.value = 'Se ha quitado la vinculación. Presione "Actualizar" para guardar.';
};
// --- Fin Lógica Buscador ---

// --- Lógica de Roles ---
// Comprueba si el ROL SELECCIONADO es "Usuario"
const isAfiliadoUserRoleSelected = computed(() => {
    const selectedRole = props.allRoles.find(r => r.id === form.role_id);
    return selectedRole?.name === 'Usuario';
});

// --- Enviar Formulario ---
const submit = () => {
    form.put(route('users.update', props.user.id), {
        onError: (errors) => {
            if (errors.password) {
                form.reset('password', 'password_confirmation');
            }
        }
    });
};
</script>

<template>
    <AppLayout title="Editar Usuario">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Editar Usuario: {{ user.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 md:p-8">
                    
                    <div v-if="form.hasErrors" class="bg-red-100 ... mb-6" role="alert">
                       <p class="font-bold">Error</p>
                       <ul class="list-disc ml-5 text-sm mt-1"><li v-for="(error, key) in form.errors" :key="key">{{ error }}</li></ul>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">

                        <div class="space-y-4">
                            <div>
                                <label for="name" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Nombre *</label>
                                <input id="name" v-model="form.name" type="text" 
                                       class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm" 
                                       required />
                                <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</div>
                            </div>

                            <div>
                                <label for="email" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Email (Usuario) *</label>
                                <input id="email" v-model="form.email" type="email" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm" required />
                                <div v-if="form.errors.email" class="text-red-600 text-sm mt-1">{{ form.errors.email }}</div>
                            </div>

                            <div>
                                <label for="role_id" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Rol del Usuario *</label>
                                <select id="role_id" v-model="form.role_id"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm"
                                        required>
                                    <option value="" disabled>Seleccione un rol</option>
                                    <option v-for="role in allRoles" :key="role.id" :value="role.id">
                                        {{ role.name }}
                                    </option>
                                </select>
                                 <div v-if="form.errors.role_id" class="text-red-600 text-sm mt-1">{{ form.errors.role_id }}</div>
                            </div>
                        </div>

                        <div v-if="isAfiliadoUserRoleSelected" class="space-y-4 p-4 border dark:border-gray-700 rounded-md">
                            <h3 class="font-medium text-gray-700 dark:text-gray-300">Vincular Afiliado</h3>
                            <p class="text-sm text-gray-500">Asigne este usuario a un afiliado para habilitar su acceso al portal.</p>
                            
                            <div class="p-2 bg-blue-50 border border-blue-200 rounded text-sm mb-2 dark:bg-gray-900 dark:border-blue-800">
                                <span class="font-medium dark:text-gray-200">Afiliado Vinculado Actualmente:</span>
                                <span class="ml-2 dark:text-gray-300 font-semibold">{{ afiliadoActualNombre }}</span>
                            </div>
                            
                            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">Buscar y Asignar Afiliado por CI</label>
                            <div class="flex gap-2">
                                <input v-model="searchCi" @keydown.enter.prevent="buscarAfiliado" type="text" placeholder="Ingrese CI del afiliado..." class="flex-grow ..." />
                                <button @click.prevent="buscarAfiliado" :disabled="isSearching" class="bg-indigo-600 text-white px-4 py-2 rounded disabled:opacity-50">
                                    {{ isSearching ? '...' : 'Buscar' }}
                                </button>
                                <button @click.prevent="clearAfiliado" type="button" class="bg-red-600 text-white px-4 py-2 rounded" title="Desvincular">
                                    X
                                </button>
                            </div>
                            <div vD-if="busquedaMensaje" class="text-green-600 text-sm mt-1">{{ busquedaMensaje }}</div>
                            <div v-if="form.errors.afiliado_id" class="text-red-600 text-sm mt-1">{{ form.errors.afiliado_id }}</div>
                        </div>
                        
                        <div class="border-t dark:border-gray-700 pt-6 space-y-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Cambiar Contraseña (Opcional)</h3>
                             <div>
                                <label for="password" class="block font-medium text-sm ...">Nueva Contraseña</label>
                                <input id="password" v-model="form.password" type="password" placeholder="Dejar en blanco para no cambiar" class="mt-1 block w-full ..." />
                                <div v-if="form.errors.password" class="text-red-600 text-sm mt-1">{{ form.errors.password }}</div>
                            </div>
                             <div>
                                <label for="password_confirmation" class="block font-medium text-sm ...">Confirmar Nueva Contraseña</label>
                                <input id="password_confirmation" v-model="form.password_confirmation" type="password" placeholder="Repetir contraseña" class="mt-1 block w-full ..." />
                            </div>
                         </div>

                        <div class="flex justify-end gap-4 mt-8 pt-6 border-t dark:border-gray-700">
                            <Link :href="route('users.index')" class="bg-gray-500 ...">Cancelar</Link>
                            <button type="submit" :disabled="form.processing" class="bg-blue-600 ...">
                                {{ form.processing ? 'Actualizando...' : 'Actualizar Usuario' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>