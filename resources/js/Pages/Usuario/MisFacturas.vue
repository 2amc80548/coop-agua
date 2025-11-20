<script setup>
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { watch } from 'vue';
import ViewCounter from '@/Components/ViewCounter.vue';

const props = defineProps({
    facturas: Object, // Paginado
    filters: Object,
    periodos: Array,
    error: String,
});

const filterForm = useForm({
    estado: props.filters?.estado ?? 'impaga',
});

// Cuando el filtro cambia, recarga la página
watch(
    () => filterForm.estado,
    (newState) => {
        router.get(
            route('mi.cuenta'),
            { estado: newState },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    },
);

const formatDate = (dateString) => {
    if (!dateString) return '—';
    try {
        const date = new Date(dateString);
        date.setMinutes(date.getMinutes() + date.getTimezoneOffset());
        return date.toLocaleDateString('es-BO', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });
    } catch {
        return dateString;
    }
};

const formatCurrency = (amount) => (parseFloat(amount) || 0).toFixed(2);

const estadoClass = (estado) => {
    switch (estado) {
        case 'impaga':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200';
        case 'pagado':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200';
        case 'anulada':
            return 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
    }
};

// Abrir factura para imprimir
const imprimirFactura = (facturaId) => {
    window.open(route('facturas.imprimir', facturaId), '_blank');
};
</script>

<template>
    <AppLayout title="Mis Facturas">
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-cyan-600 dark:text-cyan-300">
                        Panel personal
                    </p>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Mis Facturas
                    </h2>
                    <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400">
                        Consulte el estado de sus facturas, montos y períodos de facturación.
                    </p>
                </div>
            </div>
        </template>

        <div class="py-6 md:py-10">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Mensaje de error -->
                <div
                    v-if="error"
                    class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-100 px-4 py-3 rounded-md shadow-sm text-sm"
                    role="alert"
                >
                    <p class="font-semibold">Ocurrió un error</p>
                    <p class="mt-1">{{ error }}</p>
                </div>

                <!-- Filtros -->
                <div
                    class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl shadow-sm px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                >
                    <div class="text-xs md:text-sm text-gray-600 dark:text-gray-300">
                        <p class="font-semibold">
                            Filtros
                        </p>
                        <p class="mt-0.5 text-[11px] md:text-xs">
                            Seleccione qué tipo de facturas desea visualizar.
                        </p>
                    </div>

                    <div class="w-full sm:w-auto">
                        <label
                            for="estado"
                            class="block text-[11px] font-medium text-gray-600 dark:text-gray-300 mb-1"
                        >
                            Estado
                        </label>
                        <select
                            id="estado"
                            v-model="filterForm.estado"
                            class="w-full sm:w-56 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm text-xs md:text-sm focus:border-cyan-400 focus:ring focus:ring-cyan-200 focus:ring-opacity-50"
                        >
                            <option value="impaga">Mostrar solo impagas</option>
                            <option value="pagado">Mostrar solo pagadas</option>
                            <option value="todos">Mostrar todas</option>
                        </select>
                    </div>
                </div>

                <!-- Listado de facturas -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 dark:border-gray-700">
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                        <!-- Sin resultados -->
                        <li
                            v-if="!facturas.data.length"
                            class="p-6 text-center text-gray-500 dark:text-gray-400 text-sm"
                        >
                            No se encontraron facturas con el filtro
                            <span class="font-semibold">"{{ filterForm.estado }}"</span>.
                        </li>

                        <!-- Facturas -->
                        <li
                            v-for="factura in facturas.data"
                            :key="factura.id"
                            class="p-4 sm:p-5 hover:bg-gray-50 dark:hover:bg-gray-750 transition"
                        >
                            <div class="flex flex-col gap-3 sm:gap-2">
                                <!-- Primera fila: estado + periodo + ID -->
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span
                                            class="px-3 py-1 text-[10px] md:text-xs font-semibold rounded-full uppercase tracking-wide"
                                            :class="estadoClass(factura.estado)"
                                        >
                                            {{ factura.estado }}
                                        </span>
                                        <span class="text-xs md:text-sm font-medium text-gray-600 dark:text-gray-300">
                                            Período:
                                            <span class="font-semibold">
                                                {{ factura.periodo }}
                                            </span>
                                        </span>
                                    </div>
                                    <div class="text-xs md:text-sm text-gray-500 dark:text-gray-400">
                                        N° Factura:
                                        <span class="font-semibold text-gray-800 dark:text-gray-100">
                                            F-{{ factura.id.toString().padStart(6, '0') }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Segunda fila: datos + montos -->
                                <div
                                    class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 sm:gap-4"
                                >
                                    <!-- Información de conexión -->
                                    <div class="flex-1">
                                        <p class="text-xs md:text-sm text-gray-600 dark:text-gray-300">
                                            Medidor:
                                            <span class="font-semibold">
                                                {{ factura.conexion?.codigo_medidor || 'N/D' }}
                                            </span>
                                        </p>
                                        <p
                                            v-if="factura.conexion?.direccion"
                                            class="text-[11px] md:text-xs text-gray-500 dark:text-gray-400 mt-0.5"
                                        >
                                            Dirección:
                                            <span class="font-semibold">
                                                {{ factura.conexion.direccion }}
                                            </span>
                                        </p>

                                        <p
                                            v-if="factura.fecha_emision"
                                            class="text-[11px] md:text-xs text-gray-500 dark:text-gray-400 mt-0.5"
                                        >
                                            Fecha de emisión:
                                            <span class="font-semibold">
                                                {{ formatDate(factura.fecha_emision) }}
                                            </span>
                                        </p>
                                        <p
                                            v-if="factura.fecha_vencimiento"
                                            class="text-[11px] md:text-xs text-gray-500 dark:text-gray-400 mt-0.5"
                                        >
                                            Fecha de vencimiento:
                                            <span class="font-semibold">
                                                {{ formatDate(factura.fecha_vencimiento) }}
                                            </span>
                                        </p>
                                    </div>

                                    <!-- Montos -->
                                    <div class="text-left sm:text-right sm:min-w-[170px]">
                                        <p class="text-[11px] md:text-xs text-gray-500 dark:text-gray-400">
                                            Monto total
                                        </p>
                                        <p class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">
                                            Bs {{ formatCurrency(factura.monto_total) }}
                                        </p>

                                        <p
                                            v-if="factura.estado === 'impaga'"
                                            class="text-xs md:text-sm font-semibold text-red-600 dark:text-red-400 mt-1"
                                        >
                                            Deuda pendiente:
                                            <span>
                                                Bs {{ formatCurrency(factura.deuda_pendiente) }}
                                            </span>
                                        </p>

                                        <p
                                            v-if="factura.estado === 'pagado'"
                                            class="text-xs md:text-sm font-semibold text-emerald-600 dark:text-emerald-400 mt-1"
                                        >
                                            Pagado
                                            <span v-if="factura.fecha_pago">
                                                el {{ formatDate(factura.fecha_pago) }}
                                            </span>
                                        </p>

                                        <p
                                            v-if="factura.estado === 'anulada'"
                                            class="text-xs md:text-sm font-semibold text-rose-600 dark:text-rose-400 mt-1"
                                        >
                                            Factura anulada
                                        </p>
                                    </div>
                                </div>

                                <!-- Acciones -->
                                <div class="flex flex-wrap gap-3 pt-1 border-t border-gray-100 dark:border-gray-700 mt-2 pt-3">
                                    <Link
                                        :href="route('facturas.show', factura.id)"
                                        class="inline-flex items-center text-xs md:text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline"
                                    >
                                        Ver detalle
                                    </Link>
                                    <button
                                        type="button"
                                        @click="imprimirFactura(factura.id)"
                                        class="inline-flex items-center text-xs md:text-sm font-medium text-purple-600 dark:text-purple-400 hover:underline"
                                    >
                                        🖨️ Imprimir factura
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Paginación -->
                <div
                    v-if="facturas.links.length > 3"
                    class="mt-4 md:mt-6 flex flex-col md:flex-row md:items-center md:justify-between gap-3 text-xs md:text-sm"
                >
                    <span class="text-gray-600 dark:text-gray-300">
                        Mostrando
                        <span class="font-semibold">{{ facturas.from }}</span>
                        a
                        <span class="font-semibold">{{ facturas.to }}</span>
                        de
                        <span class="font-semibold">{{ facturas.total }}</span>
                        facturas
                    </span>

                    <div class="flex flex-wrap gap-1">
                        <Link
                            v-for="(link, index) in facturas.links"
                            :key="index"
                            :href="link.url ?? '#'"
                            v-html="link.label"
                            preserve-scroll
                            preserve-state
                            :class="[
                                'px-3 py-1 rounded-md border text-xs md:text-sm transition',
                                link.active
                                    ? 'bg-cyan-600 border-cyan-600 text-white'
                                    : link.url
                                        ? 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700'
                                        : 'border-transparent text-gray-400 dark:text-gray-500 cursor-default',
                            ]"
                        />
                    </div>
                </div>
            </div>

            <!-- Contador de vistas -->
            <ViewCounter />
        </div>
    </AppLayout>
</template>
