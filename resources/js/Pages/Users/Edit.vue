<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';

// --- Props (del controlador 'edit') ---
const props = defineProps({
    user: Object, // El usuario a editar (con 'afiliado' y 'roles' cargados)
    allRoles: Array, // Lista de todos los roles (id, name)
    errors: Object,
});

// --- Formulario ---
const form = useForm({
    _method: 'PUT', // Para la actualización
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '', // Para confirmación
    // Encontrar el ID del rol actual del usuario
    role_id: props.user.roles.length > 0 ? props.user.roles[0].id : '', 
});

// Determinar si es un usuario de Afiliado (para bloquear campos)
const isAfiliadoUser = computed(() => {
    return props.user.roles.some(role => role.name === 'Usuario');
});

// --- Enviar Formulario ---
const submit = () => {
    form.put(route('users.update', props.user.id), {
        onError: (errors) => {
            console.error("Errores al actualizar:", errors);
            // Si la contraseña falla, limpia los campos
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
                    
                    <div v-if="form.hasErrors" class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-6" role="alert">
                       <p class="font-bold">Error</p>
                       <p>Hubo errores en el formulario.</p>
                       <ul class="list-disc ml-5 text-sm mt-1"><li v-for="(error, key) in form.errors" :key="key">{{ error }}</li></ul>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">

                        <div v-if="user.afiliado" class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-md text-sm">
                            <h3 class="font-medium text-blue-900 mb-1">Usuario Vinculado a Afiliado</h3>
                            <p><strong class="text-gray-600">Nombre:</strong> 
                                <Link :href="route('afiliados.show', user.afiliado.id)" class="text-indigo-600 hover:underline">
                                    {{ user.afiliado.nombre_completo }}
                                </Link>
                            </p>
                            <p><strong class="text-gray-600">CI:</strong> {{ user.afiliado.ci }}</p>
                             <p class="text-xs text-gray-500 mt-2">El nombre de este usuario está sincronizado con el afiliado. El rol no se puede cambiar.</p>
                        </div>
                        
                        <div>
                            <label for="name" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Nombre</label>
                            <input id="name" v-model="form.name" type="text" 
                                   :disabled="isAfiliadoUser"
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm" 
                                   :class="isAfiliadoUser ? 'bg-gray-100 dark:bg-gray-900 cursor-not-allowed' : 'dark:bg-gray-700 dark:text-gray-200'"
                                   required />
                            <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</div>
                        </div>

                        <div>
                            <label for="email" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Email (Usuario)</label>
                            <input id="email" v-model="form.email" type="email" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm" required />
                            <div v-if="form.errors.email" class="text-red-600 text-sm mt-1">{{ form.errors.email }}</div>
                        </div>

                        <div>
                            <label for="role_id" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Rol del Usuario</label>
                            <select id="role_id" v-model="form.role_id" 
                                    :disabled="isAfiliadoUser"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm"
                                    :class="isAfiliadoUser ? 'bg-gray-100 dark:bg-gray-900 cursor-not-allowed' : 'dark:bg-gray-700 dark:text-gray-200'"
                                    required>
                                <option value="" disabled>Seleccione un rol</option>
                                <template v-for="role in allRoles" :key="role.id">
                                    <option v-if="!isAfiliadoUser ? role.name !== 'Usuario' : role.name === 'Usuario'" :value="role.id">
                                        {{ role.name }}
                                    </option>
                                </template>
                            </select>
                             <div v-if="form.errors.role_id" class="text-red-600 text-sm mt-1">{{ form.errors.role_id }}</div>
                        </div>
                        
                        <div class="border-t dark:border-gray-700 pt-6 space-y-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Cambiar Contraseña (Opcional)</h3>
                             <div>
                                <label for="password" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Nueva Contraseña</label>
                                <input id="password" v-model="form.password" type="password" placeholder="Dejar en blanco para no cambiar" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm" />
                                <div v-if="form.errors.password" class="text-red-600 text-sm mt-1">{{ form.errors.password }}</div>
                            </div>
                             <div>
                                <label for="password_confirmation" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Confirmar Nueva Contraseña</label>
                                <input id="password_confirmation" v-model="form.password_confirmation" type="password" placeholder="Repetir contraseña" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm" />
                            </div>
                         </div>

                        <div class="flex justify-end gap-4 mt-8 pt-6 border-t dark:border-gray-700">
                            <Link :href="route('users.index')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded transition">
                                Cancelar
                            </Link>
                            <button type="submit" 
                                    :disabled="form.processing"
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50 transition">
                                {{ form.processing ? 'Actualizando...' : 'Actualizar Usuario' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>