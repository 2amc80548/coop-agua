<script setup>
/**
 * DataFilterBar.vue — Barra de filtros reutilizable para listados
 *
 * Centraliza el patrón de filtrado que se repetía en Facturas/Index.vue,
 * Pagos/Index.vue, Lecturas/Index.vue, etc. con pequeñas variaciones.
 *
 * Uso básico (búsqueda simple):
 *   <DataFilterBar v-model:search="filters.search" @filter="aplicarFiltros" />
 *
 * Con filtros extra (slots):
 *   <DataFilterBar v-model:search="filters.search" @filter="aplicarFiltros">
 *     <template #extra>
 *       <select v-model="filters.estado" class="...">...</select>
 *     </template>
 *   </DataFilterBar>
 *
 * Props:
 *   search        — Texto de búsqueda (v-model:search)
 *   placeholder   — Placeholder del input de búsqueda
 *   showSearch    — Mostrar u ocultar el campo de búsqueda (default: true)
 *
 * Eventos:
 *   @filter       — Emitido al hacer click en "Filtrar" o presionar Enter
 *   @clear        — Emitido al hacer click en "Limpiar"
 *   @update:search — v-model estándar de Vue 3
 */
import { ref } from 'vue';

const props = defineProps({
    search: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Buscar...',
    },
    showSearch: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['update:search', 'filter', 'clear']);

const localSearch = ref(props.search);

const onSearch = (e) => {
    localSearch.value = e.target.value;
    emit('update:search', localSearch.value);
};

const onFilter = () => {
    emit('filter');
};

const onClear = () => {
    localSearch.value = '';
    emit('update:search', '');
    emit('clear');
};

const onKeydown = (e) => {
    if (e.key === 'Enter') onFilter();
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-4 mb-6 border border-gray-100 dark:border-gray-700">
        <div class="flex flex-wrap items-end gap-3">

            <!-- Campo de búsqueda principal -->
            <div v-if="showSearch" class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                    Búsqueda
                </label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400"
                         xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        :value="localSearch"
                        @input="onSearch"
                        @keydown="onKeydown"
                        type="text"
                        :placeholder="placeholder"
                        class="pl-9 pr-4 py-2 w-full text-sm border border-gray-300 dark:border-gray-600
                               dark:bg-gray-700 dark:text-gray-200 rounded-lg
                               focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                               transition duration-150"
                    />
                </div>
            </div>

            <!-- Slot para filtros extra (fechas, selects, etc.) -->
            <slot name="extra" />

            <!-- Botones de acción -->
            <div class="flex gap-2 self-end">
                <button
                    @click="onFilter"
                    type="button"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700
                           text-white text-sm font-semibold rounded-lg shadow-sm
                           transition duration-150 hover:shadow-md active:scale-[0.98]"
                >
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z" />
                    </svg>
                    Filtrar
                </button>

                <button
                    @click="onClear"
                    type="button"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-100 hover:bg-gray-200
                           dark:bg-gray-700 dark:hover:bg-gray-600
                           text-gray-600 dark:text-gray-300 text-sm font-semibold rounded-lg
                           transition duration-150 active:scale-[0.98]"
                >
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Limpiar
                </button>
            </div>
        </div>
    </div>
</template>
