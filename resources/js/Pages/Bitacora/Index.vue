<script setup>
import { ref, watch } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ViewCounter from '@/Components/ViewCounter.vue';

const props = defineProps({
    logs: Object,
    filters: Object,
    modulos: Array,
    usuarios: Array,
});

const filterForm = useForm({
    search: props.filters.search ?? '',
    modulo: props.filters.modulo ?? '',
    user_id: props.filters.user_id ?? '',
    fecha_inicio: props.filters.fecha_inicio ?? '',
    fecha_fin: props.filters.fecha_fin ?? '',
});

let searchTimeout = null;
const submitFilters = () => {
    filterForm.get(route('bitacora.index'), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(submitFilters, 400);
};

watch(
    () => [filterForm.modulo, filterForm.user_id, filterForm.fecha_inicio, filterForm.fecha_fin],
    submitFilters
);

const limpiarFiltros = () => {
    filterForm.search = '';
    filterForm.modulo = '';
    filterForm.user_id = '';
    filterForm.fecha_inicio = '';
    filterForm.fecha_fin = '';
    submitFilters();
};

// Formato de fechas
const formatDateTime = (dateString) => {
    if (!dateString) return '—';
    try {
        const date = new Date(dateString);
        return date.toLocaleString('es-BO', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });
    } catch {
        return dateString;
    }
};

// Estilos de badge por acción
const actionBadgeClass = (accion) => {
    const act = (accion || '').toLowerCase();
    if (act.includes('crear') || act.includes('store')) {
        return 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300 border-green-300';
    }
    if (act.includes('edit') || act.includes('update') || act.includes('modificar')) {
        return 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 border-blue-300';
    }
    if (act.includes('elimin') || act.includes('destroy') || act.includes('borrar')) {
        return 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300 border-red-300';
    }
    if (act.includes('anular')) {
        return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border-amber-300';
    }
    return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 border-gray-300';
};

// Modal de detalle de valores (old_values vs new_values)
const showDetailModal = ref(false);
const selectedLog = ref(null);

const verDetalle = (log) => {
    selectedLog.value = log;
    showDetailModal.value = true;
};

const cerrarModal = () => {
    showDetailModal.value = false;
    selectedLog.value = null;
};
</script>

<template>
    <AppLayout title="Bitácora de Auditoría">
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-cyan-600 dark:text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        Bitácora de Auditoría del Sistema
                    </h2>
                    <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Historial completo de cambios, acciones y eventos realizados por los usuarios en el sistema.
                    </p>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- FILTROS Y BÚSQUEDA -->
                <div class="bg-white dark:bg-gray-800 p-4 md:p-5 rounded-2xl shadow-sm border border-cyan-100 dark:border-gray-700 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 text-sm">
                    <div class="lg:col-span-2">
                        <label for="search_bitacora" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">
                            Buscar (Descripción, Acción, IP, Usuario)
                        </label>
                        <input
                            id="search_bitacora"
                            type="text"
                            v-model="filterForm.search"
                            @input="debouncedSearch"
                            placeholder="Escriba para buscar..."
                            class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg shadow-sm focus:ring-cyan-500 focus:border-cyan-500"
                        />
                    </div>

                    <div>
                        <label for="modulo_bitacora" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">Módulo</label>
                        <select
                            id="modulo_bitacora"
                            v-model="filterForm.modulo"
                            class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg shadow-sm focus:ring-cyan-500 focus:border-cyan-500"
                        >
                            <option value="">Todos los módulos</option>
                            <option v-for="m in modulos" :key="m" :value="m">{{ m.toUpperCase() }}</option>
                        </select>
                    </div>

                    <div>
                        <label for="user_bitacora" class="block font-medium mb-1 text-gray-700 dark:text-gray-300">Usuario</label>
                        <select
                            id="user_bitacora"
                            v-model="filterForm.user_id"
                            class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg shadow-sm focus:ring-cyan-500 focus:border-cyan-500"
                        >
                            <option value="">Todos los usuarios</option>
                            <option v-for="u in usuarios" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            <label for="fecha_inicio" class="block font-medium mb-1 text-xs text-gray-700 dark:text-gray-300">Desde</label>
                            <input
                                id="fecha_inicio"
                                type="date"
                                v-model="filterForm.fecha_inicio"
                                class="w-full text-xs border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg shadow-sm"
                            />
                        </div>
                        <div class="flex-1">
                            <label for="fecha_fin" class="block font-medium mb-1 text-xs text-gray-700 dark:text-gray-300">Hasta</label>
                            <input
                                id="fecha_fin"
                                type="date"
                                v-model="filterForm.fecha_fin"
                                class="w-full text-xs border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg shadow-sm"
                            />
                        </div>
                    </div>
                </div>

                <!-- TABLA DE BITÁCORA -->
                <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/60">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha / Hora</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Usuario</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Módulo</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acción</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Descripción</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">IP</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Detalle</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <tr v-if="!logs.data.length">
                                <td colspan="7" class="py-8 px-4 text-center text-gray-500 dark:text-gray-400 italic">
                                    No se encontraron registros de bitácora con los filtros aplicados.
                                </td>
                            </tr>
                            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-700 dark:text-gray-300 font-mono">
                                    {{ formatDateTime(log.created_at) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ log.user?.name || 'Sistema' }}</div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500">{{ log.user?.email || '—' }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-md bg-cyan-50 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-800 uppercase">
                                        {{ log.modulo }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span :class="actionBadgeClass(log.accion)" class="px-2.5 py-1 text-xs font-semibold rounded-md border uppercase">
                                        {{ log.accion }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-800 dark:text-gray-200 text-xs max-w-xs truncate" :title="log.descripcion">
                                    {{ log.descripcion }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs font-mono text-gray-500 dark:text-gray-400">
                                    {{ log.ip_address || '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    <button
                                        v-if="log.old_values || log.new_values"
                                        @click="verDetalle(log)"
                                        class="px-2.5 py-1 text-xs font-medium text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-200 bg-indigo-50 dark:bg-indigo-900/30 rounded-md transition"
                                    >
                                        🔍 Ver Valores
                                    </button>
                                    <span v-else class="text-xs text-gray-400 dark:text-gray-600">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINACIÓN -->
                <div class="flex justify-between items-center text-sm pt-2">
                    <span class="text-gray-700 dark:text-gray-300">
                        Mostrando {{ logs.from || 0 }} a {{ logs.to || 0 }} de {{ logs.total || 0 }} registros
                    </span>
                    <div class="flex flex-wrap gap-1">
                        <Link
                            v-for="(link, index) in logs.links"
                            :key="index"
                            :href="link.url ?? '#'"
                            v-html="link.label"
                            class="px-3 py-1 text-xs border rounded-lg dark:border-gray-600 dark:text-gray-300"
                            :class="{
                                'bg-cyan-600 text-white dark:bg-cyan-700 dark:text-white font-bold': link.active,
                                'text-gray-400 cursor-default border-gray-200 dark:text-gray-500': !link.url,
                                'hover:bg-gray-100 border-gray-300 dark:hover:bg-gray-700': link.url
                            }"
                            preserve-scroll
                            preserve-state
                            :disabled="!link.url"
                        />
                    </div>
                </div>

            </div>

            <!-- MODAL DE INSPECCIÓN DE VALORES DE AUDITORÍA -->
            <div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-2xl w-full p-6 border dark:border-gray-700 max-h-[90vh] flex flex-col">
                    <div class="flex justify-between items-center border-b dark:border-gray-700 pb-3 mb-4 flex-shrink-0">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                            🔍 Auditoría de Datos — Registro #{{ selectedLog?.id }}
                        </h3>
                        <button @click="cerrarModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 font-bold">&times;</button>
                    </div>

                    <div class="overflow-y-auto flex-1 space-y-4 pr-1 text-xs">
                        <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-xl space-y-1">
                            <p><strong>Usuario:</strong> {{ selectedLog?.user?.name }} ({{ selectedLog?.user?.email }})</p>
                            <p><strong>Acción:</strong> {{ selectedLog?.accion }} | <strong>Módulo:</strong> {{ selectedLog?.modulo }}</p>
                            <p><strong>Descripción:</strong> {{ selectedLog?.descripcion }}</p>
                            <p><strong>IP:</strong> {{ selectedLog?.ip_address }} | <strong>Fecha:</strong> {{ formatDateTime(selectedLog?.created_at) }}</p>
                        </div>

                        <div v-if="selectedLog?.old_values" class="space-y-1">
                            <h4 class="font-bold text-red-600 dark:text-red-400">Valores Anteriores (Antes del cambio):</h4>
                            <pre class="bg-red-50 dark:bg-red-950/40 text-red-900 dark:text-red-200 p-3 rounded-xl overflow-x-auto font-mono text-[11px] border border-red-200 dark:border-red-800">{{ JSON.stringify(selectedLog.old_values, null, 2) }}</pre>
                        </div>

                        <div v-if="selectedLog?.new_values" class="space-y-1">
                            <h4 class="font-bold text-green-600 dark:text-green-400">Valores Nuevos (Después del cambio):</h4>
                            <pre class="bg-green-50 dark:bg-green-950/40 text-green-900 dark:text-green-200 p-3 rounded-xl overflow-x-auto font-mono text-[11px] border border-green-200 dark:border-green-800">{{ JSON.stringify(selectedLog.new_values, null, 2) }}</pre>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4 border-t dark:border-gray-700 flex-shrink-0">
                        <button @click="cerrarModal" class="px-4 py-2 text-xs font-semibold text-white bg-gray-600 hover:bg-gray-700 rounded-xl shadow transition">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>

            <ViewCounter />
        </div>
    </AppLayout>
</template>
