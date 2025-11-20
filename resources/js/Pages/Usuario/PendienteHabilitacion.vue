<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ViewCounter from '@/Components/ViewCounter.vue';

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <AppLayout title="Pendiente">
        <Head title="Pendiente de Habilitación" />

        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Cuenta Pendiente de Habilitación
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 md:p-8">
                    
                    <h3 class="text-2xl font-bold text-center text-yellow-600 dark:text-yellow-400">
                        ¡Casi listo!
                    </h3>
                    
                    <p class="mt-4 text-center text-gray-700 dark:text-gray-300">
                        Tu cuenta de usuario ha sido creada exitosamente.
                    </p>
                    
                    <p class="mt-4 text-lg text-center text-gray-900 dark:text-white">
                        Para poder ver tus facturas y consumos, por favor dirígete a las oficinas de la cooperativa para que nuestro personal pueda **vincular esta cuenta a tu registro de afiliado**.
                    </p>

                    <p class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
                        (Si ya te encuentras en la oficina, por favor informa al personal que ya has creado tu cuenta de usuario).
                    </p>
                    
                    <div class="mt-8 flex justify-center">
                        <DangerButton @click="logout">
                            Cerrar Sesión
                        </DangerButton>
                    </div>
                </div>
            </div>
             <ViewCounter />
        </div>
    </AppLayout>
</template>