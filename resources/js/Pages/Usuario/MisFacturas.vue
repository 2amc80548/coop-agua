<script setup>
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { watch, ref, onUnmounted } from 'vue';
import ViewCounter from '@/Components/ViewCounter.vue';
import axios from 'axios';

const props = defineProps({
    facturas: Object, // Paginado
    filters: Object,
    periodos: Array,
    error: String,
});

const filterForm = useForm({
    estado: props.filters?.estado ?? 'impaga',
});

// --- VARIABLES DE ESTADO PARA EL QR ---
const showQrModal = ref(false);
const qrLoading = ref(false);
const qrImage = ref(null);
const qrStatus = ref('espera'); // Valores: 'espera', 'pagado'
const currentPaymentNumber = ref(null);
let pollingTimeout = null; // Variable para controlar el tiempo de espera

// --- FUNCIONES DEL QR ---

// 1. Abrir el modal y pedir el QR al servidor
const abrirPagoQr = async (facturaId) => {
    showQrModal.value = true;
    qrLoading.value = true;
    qrImage.value = null;
    qrStatus.value = 'espera';
    currentPaymentNumber.value = null;
    
    try {
        const response = await axios.post(route('pagos.generarQr'), {
            factura_id: facturaId
        });

        if (response.data.status === 'ok') {
            qrImage.value = response.data.qr_image;
            currentPaymentNumber.value = response.data.payment_number;
            qrLoading.value = false;
            
            // Iniciar la vigilancia inteligente
            checkPaymentStatus(); 
        } else {
            alert("Error del Banco: " + response.data.message);
            forceClose();
        }
    } catch (error) {
        console.error("Error de conexión:", error);
        alert("No se pudo conectar con el servicio de pagos.");
        forceClose();
    }
};

// 2. Polling Inteligente (Recursivo para no colgar el sistema)
const checkPaymentStatus = async () => {
    // Si el usuario cerró el modal o ya pagó, detenemos la recursión.
    if (!showQrModal.value || qrStatus.value === 'pagado') return;

    try {
        const res = await axios.post(route('pagos.verificarQr'), {
            payment_number: currentPaymentNumber.value
        });

        // Si el backend confirma el pago (Estados 2 o 5)
        if (res.data.status === 'pagado') {
            qrStatus.value = 'pagado';
            
            // Actualizar la lista de facturas por detrás sin recargar toda la página
            router.reload({ only: ['facturas'], preserveScroll: true });
            
            // Terminamos aquí, no llamamos más al timeout
            return; 
        }
    } catch (error) {
        // Si falla el internet momentáneamente, solo logueamos y reintentamos
        console.warn("Reintentando verificación...", error);
    }

    // TRUCO DE RENDIMIENTO:
    // Esperamos 3 segundos y volvemos a llamar a esta misma función.
    // Al usar setTimeout en vez de setInterval, nunca se acumulan peticiones.
    pollingTimeout = setTimeout(checkPaymentStatus, 3000); 
};

// 3. Lógica de Cierre Seguro
const intentarCerrar = () => {
    // Si ya pagó, cerrar sin preguntar
    if (qrStatus.value === 'pagado') {
        forceClose();
        return;
    }

    // Si está el QR en pantalla, advertir para evitar errores
    if (confirm("¿Estás seguro de cancelar?\n\nSi ya realizaste el pago en tu celular, por favor espera unos segundos a que el sistema lo detecte.\n\nSi sales ahora, no verás la confirmación aquí.")) {
        forceClose();
    }
};

const forceClose = () => {
    showQrModal.value = false;
    qrImage.value = null;
    if (pollingTimeout) clearTimeout(pollingTimeout);
};

// Limpieza al salir de la vista
onUnmounted(() => {
    if (pollingTimeout) clearTimeout(pollingTimeout);
});

// --- FUNCIONES DE UTILIDAD Y FILTROS ---

watch(() => filterForm.estado, (newState) => {
    router.get(route('mi.cuenta'), { estado: newState }, {
        preserveState: true, preserveScroll: true, replace: true,
    });
});

const formatDate = (dateString) => {
    if (!dateString) return '—';
    try {
        const date = new Date(dateString);
        date.setMinutes(date.getMinutes() + date.getTimezoneOffset());
        return date.toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' });
    } catch { return dateString; }
};

const formatCurrency = (amount) => (parseFloat(amount) || 0).toFixed(2);

const estadoClass = (estado) => {
    switch (estado) {
        case 'impaga': return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200';
        case 'pagado': return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200';
        case 'anulada': return 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
    }
};

const imprimirFactura = (facturaId) => {
    window.open(route('facturas.imprimir', facturaId), '_blank');
};
</script>

<template>
    <AppLayout title="Mis Facturas">
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-cyan-600 dark:text-cyan-300">Panel personal</p>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Mis Facturas</h2>
                    <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400">Consulte el estado de sus facturas.</p>
                </div>
            </div>
        </template>

        <div class="py-6 md:py-10">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <div v-if="error" class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-100 px-4 py-3 rounded-md shadow-sm text-sm" role="alert">
                    <p class="font-semibold">Ocurrió un error</p>
                    <p class="mt-1">{{ error }}</p>
                </div>

                <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl shadow-sm px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="text-xs md:text-sm text-gray-600 dark:text-gray-300">
                        <p class="font-semibold">Filtros</p>
                        <p class="mt-0.5 text-[11px] md:text-xs">Seleccione qué tipo de facturas desea visualizar.</p>
                    </div>
                    <div class="w-full sm:w-auto">
                        <label for="estado" class="block text-[11px] font-medium text-gray-600 dark:text-gray-300 mb-1">Estado</label>
                        <select id="estado" v-model="filterForm.estado" class="w-full sm:w-56 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm text-xs md:text-sm focus:border-cyan-400 focus:ring focus:ring-cyan-200 focus:ring-opacity-50">
                            <option value="impaga">Mostrar solo impagas</option>
                            <option value="pagado">Mostrar solo pagadas</option>
                            <option value="todos">Mostrar todas</option>
                        </select>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 dark:border-gray-700">
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                        <li v-if="!facturas.data.length" class="p-6 text-center text-gray-500 dark:text-gray-400 text-sm">
                            No se encontraron facturas con el filtro <span class="font-semibold">"{{ filterForm.estado }}"</span>.
                        </li>

                        <li v-for="factura in facturas.data" :key="factura.id" class="p-4 sm:p-5 hover:bg-gray-50 dark:hover:bg-gray-750 transition">
                            <div class="flex flex-col gap-3 sm:gap-2">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="px-3 py-1 text-[10px] md:text-xs font-semibold rounded-full uppercase tracking-wide" :class="estadoClass(factura.estado)">
                                            {{ factura.estado }}
                                        </span>
                                        <span class="text-xs md:text-sm font-medium text-gray-600 dark:text-gray-300">
                                            Período: <span class="font-semibold">{{ factura.periodo }}</span>
                                        </span>
                                    </div>
                                    <div class="text-xs md:text-sm text-gray-500 dark:text-gray-400">
                                        N° Factura: <span class="font-semibold text-gray-800 dark:text-gray-100">F-{{ factura.id.toString().padStart(6, '0') }}</span>
                                    </div>
                                </div>

                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 sm:gap-4">
                                    <div class="flex-1">
                                        <p class="text-xs md:text-sm text-gray-600 dark:text-gray-300">
                                            Medidor: <span class="font-semibold">{{ factura.conexion?.codigo_medidor || 'N/D' }}</span>
                                        </p>
                                        <p v-if="factura.conexion?.direccion" class="text-[11px] md:text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                            Dirección: <span class="font-semibold">{{ factura.conexion.direccion }}</span>
                                        </p>
                                        <p v-if="factura.fecha_emision" class="text-[11px] md:text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                            Emisión: <span class="font-semibold">{{ formatDate(factura.fecha_emision) }}</span>
                                        </p>
                                    </div>

                                    <div class="text-left sm:text-right sm:min-w-[170px]">
                                        <p class="text-[11px] md:text-xs text-gray-500 dark:text-gray-400">Monto total</p>
                                        <p class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">
                                            Bs {{ formatCurrency(factura.monto_total) }}
                                        </p>
                                        <p v-if="factura.estado === 'impaga'" class="text-xs md:text-sm font-semibold text-red-600 dark:text-red-400 mt-1">
                                            Deuda pendiente: <span>Bs {{ formatCurrency(factura.deuda_pendiente) }}</span>
                                        </p>
                                        <p v-if="factura.estado === 'pagado'" class="text-xs md:text-sm font-semibold text-emerald-600 dark:text-emerald-400 mt-1">
                                            Pagado <span v-if="factura.fecha_pago">el {{ formatDate(factura.fecha_pago) }}</span>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-3 pt-1 border-t border-gray-100 dark:border-gray-700 mt-2 pt-3">
                                    <button 
                                        v-if="factura.estado === 'impaga'"
                                        @click="abrirPagoQr(factura.id)"
                                        class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 focus:bg-purple-700 active:bg-purple-900 focus:outline-none transition ease-in-out duration-150 shadow-sm"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4h2v-4zM5 6h4v4H5V6zm10 0h4v4h-4V6zM5 14h4v4H5v-4z" />
                                        </svg>
                                        Pagar QR
                                    </button>

                                    <Link :href="route('facturas.show', factura.id)" class="inline-flex items-center text-xs md:text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                        Ver detalle
                                    </Link>
                                    <button type="button" @click="imprimirFactura(factura.id)" class="inline-flex items-center text-xs md:text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:underline">
                                        🖨️ Imprimir
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>

                <div v-if="facturas.links.length > 3" class="mt-4 md:mt-6 flex flex-col md:flex-row md:items-center md:justify-between gap-3 text-xs md:text-sm">
                    <span class="text-gray-600 dark:text-gray-300">
                        Mostrando <span class="font-semibold">{{ facturas.from }}</span> a <span class="font-semibold">{{ facturas.to }}</span> de <span class="font-semibold">{{ facturas.total }}</span> facturas
                    </span>
                    <div class="flex flex-wrap gap-1">
                        <Link v-for="(link, index) in facturas.links" :key="index" :href="link.url ?? '#'" v-html="link.label" preserve-scroll preserve-state :class="['px-3 py-1 rounded-md border text-xs md:text-sm transition', link.active ? 'bg-cyan-600 border-cyan-600 text-white' : link.url ? 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700' : 'border-transparent text-gray-400 dark:text-gray-500 cursor-default']" />
                    </div>
                </div>
            </div>
            <ViewCounter />
        </div>

        <div v-if="showQrModal" class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center backdrop-blur-sm">
            
            <div class="fixed inset-0 transform transition-all" @click="intentarCerrar">
                <div class="absolute inset-0 bg-gray-900 opacity-75"></div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-2xl transform transition-all sm:w-full sm:max-w-md p-8 text-center relative border border-gray-200 dark:border-gray-700">
                
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Pago con QR</h3>

                <div v-if="qrLoading" class="py-10 flex flex-col items-center">
                    <div class="animate-spin rounded-full h-14 w-14 border-4 border-gray-200 border-t-purple-600"></div>
                    <p class="mt-6 text-sm font-medium text-gray-500 dark:text-gray-400 animate-pulse">Conectando con el Banco...</p>
                </div>

                <div v-if="!qrLoading && qrImage && qrStatus !== 'pagado'" class="flex flex-col items-center">
                    <div class="bg-white p-3 rounded-xl shadow-lg border-2 border-purple-100 mb-6">
                        <img :src="'data:image/png;base64,' + qrImage" alt="QR Pago" class="w-64 h-64 object-contain" />
                    </div>
                    <div class="flex items-center gap-2 text-purple-500 font-medium text-sm mb-2 animate-pulse">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Esperando confirmación...
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 max-w-xs mx-auto">
                        Abre tu app bancaria y escanea este código. El sistema detectará el pago automáticamente.
                    </p>
                </div>

                <div v-if="qrStatus === 'pagado'" class="py-6 animate-fade-in-up">
                    <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-green-500/10 mb-6 ring-8 ring-green-500/5">
                        <svg class="h-16 w-16 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-green-600 mb-2">¡Pago Recibido!</h3>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mb-8">
                        Tu factura ha sido registrada como pagada.
                    </p>
                    
                    <button @click="forceClose" class="w-full py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl shadow-lg transition transform hover:scale-[1.02]">
                        Entendido, cerrar ventana
                    </button>
                </div>

                <div v-if="qrStatus !== 'pagado' && !qrLoading" class="mt-8 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <button type="button" class="text-sm text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 underline decoration-gray-300 underline-offset-4 transition" @click="intentarCerrar">
                        Cancelar operación
                    </button>
                </div>
            </div>
        </div>

    </AppLayout>
</template>