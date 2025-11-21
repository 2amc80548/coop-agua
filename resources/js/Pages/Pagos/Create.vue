<script setup>
import { ref, onUnmounted, watch } from 'vue';
import { useForm, Link, Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios'; // Asegúrate de tener axios instalado (viene por defecto en Laravel)

const props = defineProps({
    factura: Object,
    saldoPendiente: Number,
    errors: Object,
});

// --- LÓGICA DEL FORMULARIO (EFECTIVO) ---
const form = useForm({
    factura_id: props.factura.id,
    fecha_pago: new Date().toISOString().split('T')[0],
    forma_pago: 'Efectivo',
    referencia: '',
});

const submitEfectivo = () => {
    form.post(route('pagos.store'), {
        onSuccess: () => {
            // Opcional: Mostrar notificación
        }
    });
};

// --- LÓGICA DEL QR ---
const qrImage = ref(null);
const qrLoading = ref(false);
const qrPaymentNumber = ref(null);
const qrStatus = ref('espera'); // espera, procesando, pagado, error
let pollingInterval = null;

// 1. Generar el QR
const generarQr = async () => {
    qrLoading.value = true;
    qrStatus.value = 'procesando';
    qrImage.value = null;

    try {
        const response = await axios.post(route('pagos.generarQr'), {
            factura_id: props.factura.id
        });

        if (response.data.status === 'ok') {
            qrImage.value = response.data.qr_image;
            qrPaymentNumber.value = response.data.payment_number;
            qrLoading.value = false;
            iniciarPolling(); // ¡Arrancamos la vigilancia!
        } else {
            alert("Error al generar QR: " + response.data.message);
            qrLoading.value = false;
        }
    } catch (error) {
        console.error(error);
        qrLoading.value = false;
        alert("Error de conexión al generar QR");
    }
};

// 2. Vigilar si ya pagaron
// 2. Vigilar si ya pagaron (Versión Inteligente)
const iniciarPolling = async () => {
    // Si el usuario ya salió o ya pagó, no hacemos nada
    if (qrStatus.value === 'pagado') return;

    try {
        const res = await axios.post(route('pagos.verificarQr'), {
            payment_number: qrPaymentNumber.value
        });

        if (res.data.status === 'pagado') {
            qrStatus.value = 'pagado';
            // ¡Listo! No llamamos más a la función
            return; 
        }
    } catch (error) {
        console.error("Error de red, reintentando...", error);
    }

    // Si no ha pagado, esperamos 2 segundos y volvemos a preguntar
    // Usamos setTimeout en lugar de setInterval para no saturar
    pollingTimeout = setTimeout(iniciarPolling, 2000);
};

// Asegúrate de limpiar esto al salir
onUnmounted(() => {
    if (pollingTimeout) clearTimeout(pollingTimeout);
});

// Limpiar intervalo si el usuario sale de la página
onUnmounted(() => {
    if (pollingInterval) clearInterval(pollingInterval);
});

// Si el usuario cambia de pestaña a Efectivo, detenemos el polling del QR
watch(() => form.forma_pago, (newVal) => {
    if (newVal === 'Efectivo') {
        if (pollingInterval) clearInterval(pollingInterval);
        qrImage.value = null; // Opcional: Resetear QR
    }
});

const formatCurrency = (amount) => (parseFloat(amount) || 0).toFixed(2);
</script>

<template>
    <AppLayout :title="'Pagar F-' + factura.id">
        <Head :title="'Pagar F-' + factura.id" />

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                
                <div v-if="form.errors.error_general || (errors && errors.error_general)" 
                     class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
                    <p class="font-bold">Error</p>
                    <p>{{ form.errors.error_general || errors.error_general }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <div class="md:col-span-1 space-y-6">
                        <div class="bg-white dark:bg-gray-800 shadow-xl rounded-2xl p-6 border-t-4 border-blue-500">
                            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Resumen de Factura</h3>
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Nro Factura</span>
                                    <span class="font-semibold dark:text-gray-200">#{{ factura.id.toString().padStart(6, '0') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Afiliado</span>
                                    <span class="font-semibold text-right dark:text-gray-200">{{ factura.conexion?.afiliado?.nombre_completo }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Medidor</span>
                                    <span class="font-semibold dark:text-gray-200">{{ factura.conexion?.codigo_medidor }}</span>
                                </div>
                                <hr class="dark:border-gray-700">
                                <div class="flex justify-between items-center pt-2">
                                    <span class="text-gray-700 dark:text-gray-300 font-bold">TOTAL A PAGAR</span>
                                    <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">Bs {{ formatCurrency(saldoPendiente) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <div class="bg-white dark:bg-gray-800 shadow-xl rounded-2xl p-6 md:p-8">
                            
                            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-6">Seleccione Método de Pago</h3>

                            <div class="grid grid-cols-2 gap-4 mb-8">
                                <div @click="form.forma_pago = 'Efectivo'"
                                     class="cursor-pointer border-2 rounded-xl p-4 flex flex-col items-center justify-center transition-all duration-200"
                                     :class="form.forma_pago === 'Efectivo' ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-green-300'">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2" :class="form.forma_pago === 'Efectivo' ? 'text-green-600' : 'text-gray-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <span class="font-bold" :class="form.forma_pago === 'Efectivo' ? 'text-green-700 dark:text-green-400' : 'text-gray-500'">Efectivo</span>
                                </div>

                                <div @click="form.forma_pago = 'QR'"
                                     class="cursor-pointer border-2 rounded-xl p-4 flex flex-col items-center justify-center transition-all duration-200"
                                     :class="form.forma_pago === 'QR' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-purple-300'">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2" :class="form.forma_pago === 'QR' ? 'text-purple-600' : 'text-gray-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4h2v-4zM5 6h4v4H5V6zm10 0h4v4h-4V6zM5 14h4v4H5v-4z" />
                                    </svg>
                                    <span class="font-bold" :class="form.forma_pago === 'QR' ? 'text-purple-700 dark:text-purple-400' : 'text-gray-500'">Pago QR</span>
                                </div>
                            </div>

                            <div v-if="form.forma_pago === 'Efectivo'" class="animate-fade-in-down">
                                <form @submit.prevent="submitEfectivo" class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha de Pago</label>
                                        <input v-model="form.fecha_pago" type="date" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Referencia (Opcional)</label>
                                        <input v-model="form.referencia" type="text" placeholder="Ej: Nro de Recibo manual" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                                    </div>

                                    <div class="pt-4">
                                        <button type="submit" :disabled="form.processing" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg shadow-lg transform transition hover:scale-[1.02] disabled:opacity-50">
                                            {{ form.processing ? 'Registrando...' : 'Confirmar Pago en Efectivo' }}
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div v-if="form.forma_pago === 'QR'" class="animate-fade-in-down text-center">
                                
                                <div v-if="!qrImage && !qrLoading" class="py-4">
                                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                                        Se generará un código QR único para esta factura por <b>Bs {{ formatCurrency(saldoPendiente) }}</b>.
                                    </p>
                                    <button @click="generarQr" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-6 rounded-full shadow-lg flex items-center justify-center mx-auto gap-2 transition transform hover:scale-105">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h1v1H5zM3 13a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zm2 2v-1h1v1H5zM13 3a1 1 0 00-1 1v3a1 1 0 001 1h3a1 1 0 001-1V4a1 1 0 00-1-1h-3zm1 2v1h1V5h-1z" clip-rule="evenodd" />
                                            <path d="M11 4a1 1 0 10-2 0v1a1 1 0 102 0V4zM10 7a1 1 0 011 1v2a1 1 0 11-2 0V8a1 1 0 011-1zM16 10a1 1 0 10-2 0v1a1 1 0 102 0v-1zM9 13a1 1 0 011-1h1a1 1 0 110 2v2a1 1 0 11-2 0v-3zM7 11a1 1 0 100-2H6a1 1 0 100 2h1z" />
                                        </svg>
                                        Generar Código QR
                                    </button>
                                </div>

                                <div v-if="qrLoading" class="py-10 flex flex-col items-center">
                                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
                                    <p class="mt-4 text-gray-500">Conectando con el Banco...</p>
                                </div>

                                <div v-if="qrImage && qrStatus !== 'pagado'" class="flex flex-col items-center justify-center py-4">
                                    <div class="bg-white p-4 rounded-xl shadow-inner border border-gray-200 mb-4">
                                        <img :src="'data:image/png;base64,' + qrImage" alt="QR Pago" class="w-64 h-64 object-contain" />
                                    </div>
                                    
                                    <div class="flex items-center gap-2 text-purple-600 font-semibold animate-pulse mb-4">
                                        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Esperando pago del cliente...
                                    </div>
                                    
                                    <p class="text-xs text-gray-500 max-w-xs">
                                        El sistema detectará el pago automáticamente. No cierres esta ventana.
                                    </p>
                                </div>

                                <div v-if="qrStatus === 'pagado'" class="py-10 text-center animate-fade-in-down">
                                    
                                    <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-green-100 mb-6 animate-bounce">
                                        <svg class="h-16 w-16 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    
                                    <h3 class="text-3xl font-bold text-green-700 mb-2">¡Pago Exitoso!</h3>
                                    <p class="text-gray-600 dark:text-gray-400 mb-8">
                                        La transacción ha sido registrada correctamente.
                                    </p>

                                      <<a :href="route('facturas.show', factura.id)" 
                                    class="inline-flex items-center px-6 py-3 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 transition ease-in-out duration-150">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                        </svg>
                                        Ver Factura Pagada
                                    </a>
                                </div>

                            </div>
                        </div>

                        <div class="mt-6 flex justify-center">
                            <Link :href="route('facturas.show', factura.id)" class="text-gray-500 hover:text-gray-700 underline text-sm">
                                Cancelar operación y volver a la factura
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>