<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';
import ViewCounter from '@/Components/ViewCounter.vue';

// Recibe la 'lectura' con sus relaciones cargadas desde el controlador show()
const props = defineProps({
    lectura: Object,
});

const page = usePage();
const lecturaRecienteId = computed(() => page.props.flash.lectura_id_reciente);

// Función para formatear fechas (DD/MM/YYYY)
const formatDate = (dateString) => {
    if (!dateString) return '—';
    try {
        const date = new Date(dateString);
        date.setMinutes(date.getMinutes() + date.getTimezoneOffset()); // Ajuste zona horaria
        return date.toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' });
    } catch (e) {
        console.error("Error formateando fecha:", dateString, e);
        return dateString;
    }
};

// --- FUNCIÓN CORREGIDA ---
// Helper para formatear moneda (convierte a número primero)
const formatCurrency = (value) => {
    // Convierte el valor (que puede ser string, null o undefined) a un número
    const numberValue = Number(value) || 0; 
    return numberValue.toFixed(2);
};
// -------------------------

// Calcular consumo (toFixed para decimales)
const consumoCalculado = computed(() => {
    const anterior = parseFloat(props.lectura.lectura_anterior) || 0;
    const actual = parseFloat(props.lectura.lectura_actual) || 0;
    if (!isNaN(actual) && !isNaN(anterior)) {
      return (actual - anterior).toFixed(2);
    }
    return '0.00'; // Cambiado de 'Error' a '0.00'
});


// Función para eliminar
const confirmDelete = (id) => {
    if (confirm('¿Estás seguro de eliminar esta lectura? Esta acción es irreversible.')) {
        router.delete(route('lecturas.destroy', id), {
            preserveScroll: true,
            onError: (errors) => {
               const message = errors.error_general || 'No se pudo eliminar (¿Ya está facturada?).';
               alert(`Error: ${message}`);
            },
        });
    }
};

// Abrir aviso en nueva pestaña
const abrirAviso = (id) => {
    const url = route('lecturas.aviso', id);
    window.open(url, '_blank');
}
</script>

<template>
    <AppLayout title="Detalle de Lectura">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Detalle de Lectura - Período {{ lectura.periodo }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                 <div v-if="$page.props.flash.error || Object.keys($page.props.errors).length > 0"
                       class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4 shadow-sm" role="alert">
                   <p class="font-bold">Error</p>
                   <p>{{ $page.props.flash.error || (errors ? errors.error_general : 'Ocurrió un error.') }}</p>
                   <ul v-if="errors && !errors.error_general" class="list-disc ml-5 text-sm mt-1">
                       <li v-for="(error, key) in errors" :key="key">{{ error }}</li>
                   </ul>
                 </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 md:p-8">

                    <div class="mb-6 pb-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-3">Datos de la Conexión</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
                            <p><strong class="text-gray-600 dark:text-gray-400">Afiliado:</strong> 
                                <Link :href="route('afiliados.show', lectura.conexion.afiliado.id)" class="text-indigo-600 hover:underline">
                                    {{ lectura.conexion?.afiliado?.nombre_completo ?? 'N/A' }}
                                </Link>
                            </p>
                            <p><strong class="text-gray-600 dark:text-gray-400">CI Afiliado:</strong> <span class="text-gray-900 dark:text-gray-100">{{ lectura.conexion?.afiliado?.ci ?? 'N/A' }}</span></p>
                            <p><strong class="text-gray-600 dark:text-gray-400">Medidor:</strong> <span class="text-gray-900 dark:text-gray-100">{{ lectura.conexion?.codigo_medidor ?? 'N/A' }}</span></p>
                            <p><strong class="text-gray-600 dark:text-gray-400">Dirección:</strong> <span class="text-gray-900 dark:text-gray-100">{{ lectura.conexion?.direccion ?? 'N/A' }}</span></p>
                            <p><strong class="text-gray-600 dark:text-gray-400">Zona:</strong> <span class="text-gray-900 dark:text-gray-100">{{ lectura.conexion?.zona?.nombre ?? 'N/A' }}</span></p>
                        </div>
                    </div>

                    <div class="mb-6 pb-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-3">Detalles de la Lectura</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
                            <p><strong class="text-gray-600 dark:text-gray-400">Período:</strong> <span class="text-gray-900 dark:text-gray-100">{{ lectura.periodo }}</span></p>
                            <p><strong class="text-gray-600 dark:text-gray-400">Fecha de Lectura:</strong> <span class="text-gray-900 dark:text-gray-100">{{ formatDate(lectura.fecha_lectura) }}</span></p>
                            <p><strong class="text-gray-600 dark:text-gray-400">Lectura Anterior:</strong> <span class="text-gray-900 dark:text-gray-100">{{ lectura.lectura_anterior }} m³</span></p>
                            <p><strong class="text-gray-600 dark:text-gray-400">Lectura Actual:</strong> <span class="text-gray-900 dark:text-gray-100">{{ lectura.lectura_actual }} m³</span></p>
                            <p class="sm:col-span-2"><strong class="text-gray-600 dark:text-gray-400">Consumo Registrado:</strong> <span class="font-bold text-lg text-blue-700 dark:text-blue-400">{{ consumoCalculado }} m³</span></p>
                            <p><strong class="text-gray-600 dark:text-gray-400">Estado:</strong>
                                <span :class="{
                                          'px-2 py-0.5 rounded text-xs font-medium': true,
                                          'bg-yellow-100 text-yellow-800': lectura.estado === 'pendiente',
                                          'bg-green-100 text-green-800': lectura.estado === 'facturado',
                                      }">
                                    {{ lectura.estado }}
                                </span>
                            </p>
                            <p><strong class="text-gray-600 dark:text-gray-400">Registrado por:</strong> <span class="text-gray-900 dark:text-gray-100">{{ lectura.usuarioRegistrado?.name || 'N/A' }}</span></p>
                            <p v-if="lectura.observacion" class="sm:col-span-2"><strong class="text-gray-600 dark:text-gray-400">Observación:</strong> <span class="text-gray-900 dark:text-gray-100">{{ lectura.observacion }}</span></p>
                        </div>
                    </div>

                    <div v-if="lectura.factura" class="mb-6 p-4 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-md">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-3">Factura Asociada</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
                            <p><strong>N° Factura:</strong> F-{{ lectura.factura.id.toString().padStart(6, '0') }}</p>
                             
                             <p><strong>Monto Total:</strong> Bs {{ formatCurrency(lectura.factura.monto_total) }}</p>
                             
                             <p><strong>Estado Factura:</strong>
                                 <span :class="{
                                     'px-2 py-0.5 rounded text-xs font-medium': true,
                                     'bg-yellow-100 text-yellow-800': lectura.factura.estado === 'impaga', // Asumiendo 'impaga'
                                     'bg-green-100 text-green-800': lectura.factura.estado === 'pagado',
                                     'bg-red-100 text-red-800': lectura.factura.estado === 'anulada',
                                 }" class="dark:bg-opacity-20">
                                     {{ lectura.factura.estado }}
                                 </span>
                             </p>
                             <p class="sm:col-span-2 mt-2">
                                 <Link :href="route('facturas.show', lectura.factura.id)"
                                       class="text-sm text-blue-600 dark:text-blue-400 hover:underline font-medium">
                                     Ver Detalles de la Factura &rarr;
                                 </Link>
                             </p>
                        </div>
                    </div>
                     <div v-else class="mb-6 p-4 bg-yellow-50 dark:bg-gray-900 border border-yellow-200 dark:border-yellow-800 rounded-md text-sm text-yellow-800 dark:text-yellow-300">
                         Esta lectura está <span class="font-semibold">pendiente</span> de facturación.
                     </div>

                    <div class="flex flex-wrap justify-between items-center mt-8 pt-4 border-t border-gray-200 dark:border-gray-700 gap-4">
                        <div class="flex gap-2">
                             <Link v-if="lectura.estado === 'pendiente' && $page.props.auth.user.role_names.includes('Administrador')"
                                   :href="route('lecturas.edit', lectura.id)"
                                   class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded text-sm transition">
                                 Editar
                             </Link>
                             <button v-if="lectura.estado === 'pendiente' && $page.props.auth.user.role_names.includes('Administrador')"
                                     @click="confirmDelete(lectura.id)"
                                     class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded text-sm transition">
                                 Eliminar
                             </button>
                             <button @click="abrirAviso(lectura.id)"
                                     class="bg-teal-500 hover:bg-teal-600 text-white font-bold py-2 px-4 rounded text-sm transition"
                                     title="Imprimir Aviso de Cobranza">
                                 🖨️ Aviso
                             </button>
                        </div>

                        <Link :href="route('lecturas.index')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded transition">
                            Volver al Listado
                        </Link>
                    </div>

                </div>
            </div>
            <ViewCounter />
        </div>
    </AppLayout>
</template>