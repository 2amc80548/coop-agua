<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue'; // Ajusta la ruta a tu Layout

const props = defineProps({
    lecturasPendientes: { type: Array, default: () => [] },
    periodosDisponibles: { type: Array, default: () => [] },
    filters: Object,
    tarifaInfo: Object,
    errors: Object, // Errores de validación
});

// Para mostrar mensajes de éxito/error
const page = usePage();

// --- Filtro de Período ---
// Usamos 'router.get' para recargar la página con el período seleccionado
const filtroPeriodoForm = useForm({
    periodo: props.filters.periodo ?? (props.periodosDisponibles[0] || ''),
});

const filtrarPorPeriodo = () => {
    filtroPeriodoForm.get(route('facturacion.generar.show'), {
        preserveState: true,
        preserveScroll: true,
    });
};

// --- Formulario Principal para Generar Facturas ---
const form = useForm({
    lectura_ids: [], // Array de IDs de lecturas seleccionadas
});

// --- Lógica de Checkboxes ---
// 'allSelected' es un computed property que reacciona a los cambios
const allSelected = computed({
    // GET: Comprueba si todos los IDs de lecturas pendientes están en form.lectura_ids
    get() {
        return props.lecturasPendientes.length > 0 &&
               props.lecturasPendientes.every(l => form.lectura_ids.includes(l.id));
    },
    // SET: Se activa cuando el usuario marca/desmarca el checkbox "Marcar Todos"
    set(value) {
        if (value) {
            // Si 'value' es true, llena el array con todos los IDs
            form.lectura_ids = props.lecturasPendientes.map(l => l.id);
        } else {
            // Si 'value' es false, vacía el array
            form.lectura_ids = [];
        }
    }
});

// --- Estadísticas (Resumen) ---
const resumenSeleccion = computed(() => {
    const seleccionadas = props.lecturasPendientes.filter(l => 
        form.lectura_ids.includes(l.id)
    );
    
    const total = seleccionadas.reduce((sum, l) => sum + (l.monto_estimado || 0), 0);
    
    return {
        cantidad: seleccionadas.length,
        montoTotal: total.toFixed(2), // Sin redondeo, solo mostrar 2 decimales
    };
});

// --- Enviar Formulario ---
const submitGeneracion = () => {
    if (form.lectura_ids.length === 0) {
        alert('Debe seleccionar al menos una lectura para facturar.');
        return;
    }
    
    if (confirm(`¿Está seguro que desea generar ${resumenSeleccion.value.cantidad} facturas por un total aproximado de Bs ${resumenSeleccion.value.montoTotal}? Esta acción no se puede deshacer.`)) {
        form.post(route('facturacion.generar.store'), {
            onStart: () => {
                // Opcional: mostrar un indicador de carga grande
            },
            onSuccess: () => {
                form.reset(); // Limpia los IDs seleccionados
                // La página se recargará con el mensaje flash de éxito
            },
            onError: (errors) => {
                // Los errores globales se mostrarán automáticamente
                console.error("Error al generar facturas:", errors);
            }
        });
    }
};

// Formatear Moneda
const formatCurrency = (amount) => {
    return (amount ?? 0).toFixed(2);
};

</script>

<template>
    <AppLayout title="Generar Facturas">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Generación Masiva de Facturas
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div v-if="page.props.flash.success" class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-4 shadow-sm" role="alert">
                    <p class="font-bold">Éxito</p>
                    <p>{{ page.props.flash.success }}</p>
                </div>
                <div v-if="page.props.flash.error || Object.keys(errors).length > 0" 
                     class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4 shadow-sm" role="alert">
                    <p class="font-bold">Error al Generar Facturas</p>
                    <p>{{ page.props.flash.error || errors.error_general || 'Revise los detalles.' }}</p>
                     <ul class="list-disc ml-5 text-sm">
                         <li v-if="errors.lectura_ids">{{ errors.lectura_ids }}</li> 
                     </ul>
                </div>

                <div class="bg-white rounded shadow-sm p-4 mb-6">
                    <label for="periodo" class="block font-medium text-sm text-gray-700 mb-1">
                        Seleccione el Período a Facturar
                    </label>
                    <div class="flex gap-4">
                        <select id="periodo" v-model="filtroPeriodoForm.periodo" 
                                class="border-gray-300 rounded-md shadow-sm block w-full md:w-1/3 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option v-if="!periodosDisponibles.length" value="">No hay períodos pendientes</option>
                            <option v-for="p in periodosDisponibles" :key="p" :value="p">{{ p }}</option>
                        </select>
                        <button @click="filtrarPorPeriodo" 
                                class="bg-indigo-600 text-white px-4 py-2 rounded shadow hover:bg-indigo-700 transition"
                                :disabled="!filtroPeriodoForm.periodo || filtroPeriodoForm.processing">
                            Cargar Lecturas
                        </button>
                    </div>
                </div>

                <form @submit.prevent="submitGeneracion">
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        
                        <div class="sticky top-0 z-10 bg-gray-100 border-b p-4 rounded-t-lg flex flex-wrap justify-between items-center gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">
                                    Lecturas Pendientes (Período: {{ filters.periodo || 'N/A' }})
                                </h3>
                                <p v-if="lecturasPendientes.length > 0" class="text-sm text-gray-600">
                                    Se encontraron {{ lecturasPendientes.length }} lecturas pendientes.
                                </p>
                                <p v-else class="text-sm text-gray-500">
                                    No hay lecturas pendientes para este período.
                                </p>
                            </div>
                            <div class="text-right">
                                <button type="submit" 
                                        :disabled="form.processing || form.lectura_ids.length === 0"
                                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded shadow disabled:opacity-50 transition"
                                        :class="{'cursor-not-allowed': form.lectura_ids.length === 0}">
                                    {{ form.processing ? 'Generando...' : `Generar ${resumenSeleccion.cantidad} Facturas` }}
                                </button>
                                 <p class="text-sm font-semibold mt-1">
                                     Total Estimado: Bs {{ resumenSeleccion.montoTotal }}
                                 </p>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="p-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <input type="checkbox" v-model="allSelected" 
                                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                                        </th>
                                        <th class="p-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Afiliado / Medidor</th>
                                        <th class="p-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Lect. Ant.</th>
                                        <th class="p-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Lect. Act.</th>
                                        <th class="p-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Consumo</th>
                                        <th class="p-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Desc.</th>
                                        <th class="p-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Monto Estimado</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-if="!lecturasPendientes.length">
                                        <td colspan="7" class="p-4 text-center text-gray-500">
                                            {{ filters.periodo ? 'No hay lecturas pendientes para este período.' : 'Seleccione un período para cargar lecturas.' }}
                                        </td>
                                    </tr>
                                    <tr v-for="l in lecturasPendientes" :key="l.id" class="hover:bg-gray-50 text-sm">
                                        <td class="p-3">
                                            <input type="checkbox" v-model="form.lectura_ids" :value="l.id"
                                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                                        </td>
                                        <td class="p-3 whitespace-nowrap">
                                            <div class="font-medium text-gray-900">{{ l.conexion.afiliado.nombre_completo }}</div>
                                            <div class="text-xs text-gray-500">Med: {{ l.conexion.codigo_medidor }} | CI: {{ l.conexion.afiliado.ci }}</div>
                                        </td>
                                        <td class="p-3 whitespace-nowrap text-right text-gray-700">{{ l.lectura_anterior }}</td>
                                        <td class="p-3 whitespace-nowrap text-right font-medium text-gray-900">{{ l.lectura_actual }}</td>
                                        <td class="p-3 whitespace-nowrap text-right font-bold text-blue-700">
                                            {{ (l.consumo_calculado ?? 0).toFixed(2) }} m³
                                        </td>
                                        <td class="p-3 whitespace-nowrap text-right font-medium text-purple-600">
                                            Bs {{ formatCurrency(l.descuento_aplicado) }}
                                        </td>
                                        <td class="p-3 whitespace-nowrap text-right font-bold text-green-700">
                                            Bs {{ formatCurrency(l.monto_estimado) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </AppLayout>
</template>