<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    afiliado: Object, // Trae afiliado con 'zona', 'requisitos', 'conexiones.zona', 'user', 'observacion'
});

// Helper para la foto
const getPhotoUrl = (path) => {
    if (path) return `/storage/${path}`;
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(props.afiliado.nombre_completo)}&color=7F9CF5&background=EBF4FF`;
};

// Helpers para clases de estado
const estadoClass = (estado) => {
    switch (estado) {
        case 'activo': return 'bg-green-100 text-green-800';
        case 'suspendido': return 'bg-yellow-100 text-yellow-800';
        case 'baja': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};
const estadoServicioClass = (estado) => {
    switch (estado) {
        case 'activo': return 'bg-green-100 text-green-800';
        case 'en_corte': return 'bg-yellow-100 text-yellow-800';
        case 'cortado': return 'bg-red-100 text-red-800';
        case 'Pendiente': return 'bg-blue-100 text-blue-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};
// Helper para formatear fecha
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        date.setMinutes(date.getMinutes() + date.getTimezoneOffset()); // Ajuste zona horaria
        return date.toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' });
    } catch { return dateString; }
};
</script>

<template>
    <AppLayout :title="afiliado.nombre_completo">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Perfil del Afiliado
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    
                    <div class="p-6 md:p-8 border-b dark:border-gray-700 flex flex-col md:flex-row items-center gap-6">
                        <img class="h-24 w-24 rounded-full object-cover" :src="getPhotoUrl(afiliado.profile_photo_path)" :alt="afiliado.nombre_completo">
                        <div class="flex-1 text-center md:text-left">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ afiliado.nombre_completo }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">CI: {{ afiliado.ci }} | Código: {{ afiliado.codigo }}</p>
                            <div class="flex flex-wrap gap-4 mt-2 justify-center md:justify-start">
                                 <span class="px-3 py-1 text-xs font-semibold rounded-full" :class="estadoClass(afiliado.estado)">
                                    Estado: {{ afiliado.estado }}
                                 </span>
                                 <span class="px-3 py-1 text-xs font-semibold rounded-full" :class="estadoServicioClass(afiliado.estado_servicio)">
                                    Servicio: {{ afiliado.estado_servicio }}
                                 </span>
                                 <span v-if="afiliado.adulto_mayor" class="px-3 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                                    Adulto Mayor
                                 </span>
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <Link :href="route('afiliados.edit', afiliado.id)" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded transition">
                                Editar Afiliado
                            </Link>
                        </div>
                    </div>
                    
                    <div class="p-6 md:p-8">
                        
                        <div class="mb-8">
                            <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">Información de Contacto</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                <div><strong class="text-gray-500 dark:text-gray-400">Celular:</strong> <span class="text-gray-700 dark:text-gray-300">{{ afiliado.celular || 'No registrado' }}</span></div>
                                <div><strong class="text-gray-500 dark:text-gray-400">Calle:</strong> <span class="text-gray-700 dark:text-gray-300">{{ afiliado.direccion }}</span></div>
                                <div><strong class="text-gray-500 dark:text-gray-400">Barrio:</strong> <span class="text-gray-700 dark:text-gray-300">{{ afiliado.zona?.nombre || 'No registrado' }}</span></div>
                                <div v-if="afiliado.user"><strong class="text-gray-500 dark:text-gray-400">Email (Usuario):</strong> <span class="text-gray-700 dark:text-gray-300">{{ afiliado.user.email }}</span></div>
                            </div>
                        </div>

                         <div class="mb-8">
                            <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">Datos de Afiliación</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                <div><strong class="text-gray-500 dark:text-gray-400">Tipo:</strong> <span class="text-gray-700 dark:text-gray-300">{{ afiliado.tipo }}</span></div>
                                <div><strong class="text-gray-500 dark:text-gray-400">Tenencia:</strong> <span class="text-gray-700 dark:text-gray-300">{{ afiliado.tenencia }}</span></div>
                                <div><strong class="text-gray-500 dark:text-gray-400">Fecha Afiliación:</strong> <span class="text-gray-700 dark:text-gray-300">{{ formatDate(afiliado.fecha_afiliacion) }}</span></div>
                                <div v_if="afiliado.fecha_baja"><strong class="text-gray-500 dark:text-gray-400">Fecha Baja:</strong> <span class="text-red-500">{{ formatDate(afiliado.fecha_baja) }}</span></div>
                            </div>
                        </div>

                        <div v-if="afiliado.observacion" class="mb-8">
                            <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">Observaciones (Uso Interno)</h4>
                            <p class="text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900 p-4 rounded-md whitespace-pre-wrap">
                                {{ afiliado.observacion }}
                            </p>
                        </div>
                        <div class="mb-8">
                            <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">Requisitos Entregados</h4>
                            <ul v-if="afiliado.requisitos && afiliado.requisitos.length > 0" class="list-disc list-inside space-y-1 text-sm">
                                <li v-for="req in afiliado.requisitos" :key="req.id" class="text-green-600 dark:text-green-400">
                                    <span class="text-gray-700 dark:text-gray-300">{{ req.nombre }}</span>
                                    <span v-if="req.pivot.fecha_entrega" class="text-xs text-gray-500"> (Entregado el: {{ formatDate(req.pivot.fecha_entrega) }})</span>
                                </li>
                            </ul>
                            <p v-else class="text-sm text-gray-500 dark:text-gray-400 italic">Este afiliado no tiene requisitos registrados.</p>
                        </div>
                        
                        <div>
                            <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">Conexiones (Medidores)</h4>
                             
                             <ul v-if="afiliado.conexiones && afiliado.conexiones.length > 0" class="divide-y dark:divide-gray-700 border rounded-md dark:border-gray-700">
                                <li v-for="con in afiliado.conexiones" :key="con.id" class="p-3 flex justify-between items-center">
                                    <div>
                                        <p class="text-sm font-medium text-indigo-600 dark:text-indigo-400">
                                            <Link :href="route('conexiones.show', con.id)">Medidor: {{ con.codigo_medidor }}</Link>
                                        </p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ con.direccion }} ({{ con.zona?.nombre || 'Zona N/A' }})</p>
                                    </div>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" 
                                          :class="con.estado === 'activo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                        {{ con.estado }}
                                    </span>
                                </li>
                            </ul>
                            <p v-else class="text-sm text-gray-500 dark:text-gray-400 italic">Este afiliado no tiene conexiones registradas.</p>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>