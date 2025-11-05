<script setup>
import { ref, computed, watch } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';

// --- Props (del controlador 'create') --- 
const props = defineProps({
    rolesPersonal: Array,
    roleUsuario: Object,
    searchAfiliadosUrl: String, // URL de la API (ej. .../buscar-por-ci/__CI_PLACEHOLDER__)
    errors: Object,
});

// --- Formulario ---
const form = useForm({
    tipo: 'personal',
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role_id: '',
    afiliado_id: null,
});

// --- Lógica de Roles Dinámicos ---
const roleOptions = computed(() => {
    if (form.tipo === 'afiliado') {
        return props.roleUsuario ? [props.roleUsuario] : [];
    }
    return props.rolesPersonal || [];
});

watch(() => form.tipo, (newTipo) => {
    form.role_id = '';
    if (newTipo === 'afiliado' && props.roleUsuario) {
        form.role_id = props.roleUsuario.id;
    } else {
        clearAfiliado();
    }
});
// --- Fin Lógica de Roles ---

// --- Lógica del Buscador de Afiliados ---
const searchCi = ref('');
const searchResult = ref(null);
const searchMessage = ref('');
const isSearching = ref(false);

const buscarAfiliado = async () => {
    if (!searchCi.value.trim()) {
        searchMessage.value = 'Ingrese un CI para buscar.';
        return;
    }
    isSearching.value = true;
    searchResult.value = null;
    form.afiliado_id = null;
    searchMessage.value = 'Buscando...';

    const url = props.searchAfiliadosUrl.replace('__CI_PLACEHOLDER__', searchCi.value.trim()); 
    
    try {
        const response = await axios.get(url);
        if (response.data) {
            searchResult.value = response.data;
            form.afiliado_id = response.data.id;
            form.name = response.data.nombre_completo; // Autocompleta el nombre
            searchMessage.value = `Afiliado: ${response.data.nombre_completo}`;
        }
        // No necesitas 'else' aquí, el 'catch' (404) maneja cuando no se encuentra
    } catch (error) {
        searchMessage.value = 'Error al buscar o afiliado no encontrado (404).';
        console.error("Error buscando afiliado:", error);
    } finally {
        isSearching.value = false;
    }
};

const clearAfiliado = () => {
    searchResult.value = null;
    form.afiliado_id = null;
    searchCi.value = '';
    searchMessage.value = '';
    if (form.tipo === 'afiliado') {
        form.name = ''; 
    }
};
// --- Fin Lógica Buscador ---

// --- Enviar Formulario ---
const submit = () => {
    // Si es 'afiliado', nos aseguramos que el 'name' sea el del afiliado
    if (form.tipo === 'afiliado' && searchResult.value) {
        form.name = searchResult.value.nombre_completo;
    }
    
    form.post(route('users.store'), {
        onError: (errors) => {
            console.error("Errores de validación:", errors);
            form.reset('password', 'password_confirmation'); // Limpia contraseñas
        },
        onSuccess: () => {
            form.reset(); // Limpia todo el formulario
            clearAfiliado(); // Limpia el buscador
        }
    });
};
</script>
<template>
    <AppLayout title="Crear Usuario">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Crear Nuevo Usuario
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 md:p-8">
                    
                    <form @submit.prevent="submit" class="space-y-6">
                        
                        <div>
                            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">Tipo de Usuario</label>
                            <div class="mt-2 flex gap-6">
                                <label class="flex items-center">
                                    <input type="radio" v-model="form.tipo" value="personal" class="text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Personal (Admin, Secretaria, etc.)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" v-model="form.tipo" value="afiliado" class="text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Afiliado (Cliente)</span>
                                </label>
                            </div>
                        </div>

                        <div v-if="form.tipo === 'personal'" class="space-y-4 p-4 border dark:border-gray-700 rounded-md">
                            <h3 class="font-medium text-gray-700 dark:text-gray-300">Datos del Personal</h3>
                            <div>
                                <label for="name_personal" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Nombre Completo *</label>
                                <input id="name_personal" v-model="form.name" type="text" class="mt-1 block w-full ... " required />
                                <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</div>
                            </div>
                            <div>
                                <label for="role_personal" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Rol de Personal *</label>
                                <select id="role_personal" v-model="form.role_id" class="mt-1 block w-full ..." required>
                                    <option value="" disabled>Seleccione un rol</option>
                                    <option v-for="role in roleOptions" :key="role.id" :value="role.id">{{ role.name }}</option>
                                </select>
                                 <div v-if="form.errors.role_id" class="text-red-600 text-sm mt-1">{{ form.errors.role_id }}</div>
                            </div>
                        </div>

                        <div v-if="form.tipo === 'afiliado'" class="space-y-4 p-4 border dark:border-gray-700 rounded-md">
                            <h3 class="font-medium text-gray-700 dark:text-gray-300">Datos del Afiliado</h3>
                            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">Buscar Afiliado por CI *</label>
                            <div class="flex gap-2">
                                <input v-model="searchCi" @keydown.enter.prevent="buscarAfiliado" type="text" placeholder="Ingrese CI del afiliado..." class="flex-grow ..." />
                                <button @click.prevent="buscarAfiliado" :disabled="isSearching" class="bg-indigo-600 text-white px-4 py-2 rounded disabled:opacity-50">
                                    {{ isSearching ? 'Buscando...' : 'Buscar' }}
                                </button>
                            </div>
                            <div v-if="searchMessage" :class="{'text-green-600': form.afiliado_id, 'text-red-600': !form.afiliado_id}" class="text-sm mt-1">{{ searchMessage }}</div>
                            <div v-if="form.errors.afiliado_id" class="text-red-600 text-sm mt-1">{{ form.errors.afiliado_id }}</div>
                            
                            <input v-model="form.name" type="hidden" />
                            <input v-model="form.role_id" type="hidden" />
                        </div>

                        <div class="border-t dark:border-gray-700 pt-6 space-y-4">
                             <div>
                                <label for="email" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Email (para inicio de sesión) *</label>
                                <input id="email" v-model="form.email" type="email" class="mt-1 block w-full ..." required />
                                <div v-if="form.errors.email" class="text-red-600 text-sm mt-1">{{ form.errors.email }}</div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="password" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Contraseña *</label>
                                    <input id="password" v-model="form.password" type="password" class="mt-1 block w-full ..." required />
                                    <div v-if="form.errors.password" class="text-red-600 text-sm mt-1">{{ form.errors.password }}</div>
                                </div>
                                <div>
                                    <label for="password_confirmation" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Confirmar Contraseña *</label>
                                    <input id="password_confirmation" v-model="form.password_confirmation" type="password" class="mt-1 block w-full ..." required />
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-4 mt-8 pt-6 border-t dark:border-gray-700">
                            <Link :href="route('users.index')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded transition">
                                Cancelar
                            </Link>
                            <button type="submit" 
                                    :disabled="form.processing || (form.tipo === 'afiliado' && !form.afiliado_id)"
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50 transition">
                                {{ form.processing ? 'Guardando...' : 'Crear Usuario' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>