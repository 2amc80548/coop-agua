<script setup>
import { Link, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ViewCounter from '@/Components/ViewCounter.vue';

const props = defineProps({
    reclamos: Object, // Paginado (solo los del usuario)
    filters: Object,
    error: String,
});
const page = usePage();

// Helpers
const formatDate = (dateString) => { /* (misma función de formateo) */ };
const estadoClass = (estado) => {
    switch (estado) {
        case 'Abierto': return 'bg-yellow-100 text-yellow-800';
        case 'En Revisión': return 'bg-blue-100 text-blue-800';
        case 'Resuelto': return 'bg-green-100 text-green-800';
        case 'Cerrado': return 'bg-gray-100 text-gray-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};
</script>

<template>
    <AppLayout title="Mis Reclamos">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Mis Reclamos
            </h2>
        </template>

        <div class="p-4 md:p-6 max-w-5xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-700 dark:text-gray-300">Mi Historial de Reclamos</h1>
                <Link :href="route('reclamos.create')" class="bg-green-600 text-white px-4 py-2 rounded shadow hover:bg-green-700 transition duration-150">
                    + Presentar Nuevo Reclamo
                </Link>
            </div>
            
            <div v-if="page.props.flash.success" class="bg-green-100 ... mb-4" role="alert">
               <p class="font-bold">Éxito</p> <p>{{ page.props.flash.success }}</p>
            </div>
            <div v-if="error" class="bg-red-100 ... mb-4" role="alert">
               <p class="font-bold">Error</p> <p>{{ error }}</p>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                    <li v-if="!reclamos.data.length" class="p-6 text-center text-gray-500 dark:text-gray-400">
                        No has presentado ningún reclamo.
                    </li>
                    
                    <li v-for="rec in reclamos.data" :key="rec.id" class="p-4 sm:p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <div class="flex flex-col sm:flex-row justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-4">
                                    <span :class="estadoClass(rec.estado)" class="px-3 py-1 text-xs font-semibold rounded-full uppercase">
                                        {{ rec.estado }}
                                    </span>
                                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                        {{ rec.tipo?.nombre || 'N/A' }}
                                    </span>
                                </div>
                                <h3 class="mt-2 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ rec.asunto }}
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Enviado el: {{ formatDate(rec.created_at) }}
                                </p>
                            </div>
                            <div class="flex-shrink-0 mt-4 sm:mt-0">
                                <Link :href="route('reclamos.show', rec.id)" class="text-sm text-indigo-600 hover:underline">
                                    Ver Detalle y Respuesta &rarr;
                                </Link>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
            
            <div class="mt-6 flex justify-between items-center text-sm" v-if="reclamos.links.length > 3">
                </div>
             <ViewCounter />
        </div>
    </AppLayout>
</template>