<script setup>
import { ref, computed, watch } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';
import ViewCounter from '@/Components/ViewCounter.vue';

// --- Props (del controlador 'create') --- 
const props = defineProps({
    rolesPersonal: Array,
    roleUsuario: Object,
    searchAfiliadosUrl: String,
    errors: Object,
});

// --- Formulario ---
const form = useForm({
    tipo: 'personal',
    name: '', // ¡El nombre siempre está aquí!
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
    form.name = ''; // Limpiar nombre al cambiar de tipo
    if (newTipo === 'afiliado' && props.roleUsuario) {
        form.role_id = props.roleUsuario.id; // Auto-selecciona 'Usuario'
    }
    clearAfiliado();
});

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
            // ¡¡BORRADO!! Ya no sobreescribimos form.name
            // form.name = response.data.nombre_completo; 
            searchMessage.value = `Afiliado encontrado: ${response.data.nombre_completo}`;
        }
    } catch (error) {
        searchMessage.value = 'Error al buscar o afiliado no encontrado (404).';
    } finally {
        isSearching.value = false;
    }
};

const clearAfiliado = () => {
    searchResult.value = null;
    form.afiliado_id = null;
    searchCi.value = '';
    searchMessage.value = '';
    // No borramos form.name
};
// --- Fin Lógica Buscador ---

// --- Enviar Formulario ---
const submit = () => {
    // ¡¡BORRADO!! Ya no forzamos el nombre en el submit
    
    form.post(route('users.store'), {
        onError: (errors) => {
            console.error("Errores:", errors);
            form.reset('password', 'password_confirmation');
        },
        onSuccess: () => {
            form.reset();
            clearAfiliado();
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
                                    <input type="radio" v-model="form.tipo" value="personal" class="text-blue-600 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Personal (Admin, Sec, Tec)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" v-model="form.tipo" value="afiliado" class="text-blue-600 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Afiliado (Cliente)</span>
                                </label>
                            </div>
                        </div>

                        <div class="space-y-4 p-4 border border-gray-200 dark:border-gray-700 rounded-md">
                            <h3 class="font-medium text-gray-700 dark:text-gray-300">Datos Principales</h3>
                            
                            <div>
                                <label for="name_personal" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Nombre Completo del Usuario *</label>
                                <input id="name_personal" v-model="form.name" type="text" 
                                       class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required />
                                <p v-if="form.tipo === 'afiliado'" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Este es el nombre de la persona que usará la cuenta (ej. Juan Perez).
                                </p>
                                <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</div>
                            </div>
                            
                            <div>
                                <label for="role_personal" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Rol de Usuario *</label>
                                <select id="role_personal" v-model="form.role_id" 
                                        :disabled="form.tipo === 'afiliado'"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm dark:text-gray-200"
                                        :class="form.tipo === 'afiliado' ? 'bg-gray-100 dark:bg-gray-900 text-gray-500' : 'bg-white dark:bg-gray-700'"
                                        required>
                                    <option value="" disabled>Seleccione un rol...</option>
                                    <option v-for="role in roleOptions" :key="role.id" :value="role.id">{{ role.name }}</option>
                                </select>
                                <div v-if="form.errors.role_id" class="text-red-600 text-sm mt-1">{{ form.errors.role_id }}</div>
                            </div>
                        </div>

                        <div v-if="form.tipo === 'afiliado'" class="space-y-4 p-4 border border-gray-200 dark:border-gray-700 rounded-md">
                            <h3 class="font-medium text-gray-700 dark:text-gray-300">Vincular Afiliado</h3>
                            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">Buscar Afiliado por CI (Opcional)</label>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Vincule esta cuenta a un afiliado (ej. Maria Rodriguez) para que pueda ver sus facturas.</p>
                            <div class="flex gap-2">
                                <input v-model="searchCi" @keydown.enter.prevent="buscarAfiliado" type="text" placeholder="Ingrese CI del afiliado..." 
                                       class="flex-grow border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
                                <button @click.prevent="buscarAfiliado" :disabled="isSearching" class="bg-indigo-600 text-white px-4 py-2 rounded shadow hover:bg-indigo-700 disabled:opacity-50 transition duration-150">
                                    {{ isSearching ? 'Buscando...' : 'Buscar' }}
                                </button>
                            </div>
                            <div v-if="searchMessage" :class="{'text-green-600 dark:text-green-400': form.afiliado_id, 'text-red-600 dark:text-red-400': !form.afiliado_id}" class="text-sm mt-1">{{ searchMessage }}</div>
                            <div v-if="form.errors.afiliado_id" class="text-red-600 text-sm mt-1">{{ form.errors.afiliado_id }}</div>
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6 space-y-4">
                             <div>
                                <label for="email" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Email (para inicio de sesión) *</label>
                                <input id="email" v-model="form.email" type="email" 
                                       class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required />
                                <div v-if="form.errors.email" class="text-red-600 text-sm mt-1">{{ form.errors.email }}</div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="password" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Contraseña *</label>
                                    <input id="password" v-model="form.password" type="password" 
                                           class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required />
                                    <div v-if="form.errors.password" class="text-red-600 text-sm mt-1">{{ form.errors.password }}</div>
                                </div>
                                <div>
                                    <label for="password_confirmation" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Confirmar Contraseña *</label>
                                    <input id="password_confirmation" v-model="form.password_confirmation" type="password" 
                                           class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required />
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-4 mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <Link :href="route('users.index')" class="bg-gray-500 text-white px-4 py-2 rounded shadow hover:bg-gray-600 transition duration-150">Cancelar</Link>
                            <button type="submit" 
                                    :disabled="form.processing"
                                    class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 disabled:opacity-50 transition duration-150">
                                {{ form.processing ? 'Guardando...' : 'Crear Usuario' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <ViewCounter />
        </div>
    </AppLayout>
</template>