<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';
import Modal from '@/Components/Modal.vue'; // (Importado por si lo necesitas)

// --- 1. PROPS ---
const props = defineProps({
    errors: Object,
    apiUrlSearchConexiones: String,
    apiUrlPendientes: String,
    apiUrlTarifaActiva: String,
    periodoActual: String,
    ultimoPeriodoRegistrado: String,
    zonas: Array, // ¡Importante para el filtro de pendientes!
});
const page = usePage();

// --- 2. FORMULARIO PRINCIPAL ---
const form = useForm({
    conexion_id: null,
    fecha_lectura: new Date().toISOString().split('T')[0],
    lectura_anterior: 0,
    lectura_actual: '',
    observacion: '',
    _afiliado_adulto_mayor: false, 
    _conexion_info: null,
});

// --- 3. ESTADO DE UI Y DATOS ---
const tarifaActiva = ref(null);
const isLoadingTarifa = ref(true);
const searchTerm = ref('');
const searchResults = ref([]);
const isSearching = ref(false);
const showSearchDropdown = ref(false);
const pendientes = ref([]);
const isLoadingPendientes = ref(false);
const filtroZonaPendientes = ref(''); // ¡Esto será un zona_id!
const periodoPendientes = ref(props.ultimoPeriodoRegistrado);

// --- 4. Carga de Datos Inicial (Tarifa y Pendientes) ---
onMounted(async () => {
    // 1. Cargar Tarifa Activa
    try {
        const response = await axios.get(props.apiUrlTarifaActiva);
        tarifaActiva.value = response.data;
    } catch (error) {
        console.error("Error cargando tarifa activa:", error);
        alert("Error CRÍTICO: No se pudo cargar la tarifa activa. Los cálculos de monto no funcionarán.");
    } finally {
        isLoadingTarifa.value = false;
    }
    // 2. Cargar Pendientes
    await cargarPendientes(); 
});

// --- 5. Lógica para Lista de Pendientes (¡CORREGIDA!) ---
const cargarPendientes = async () => {
    isLoadingPendientes.value = true;
    pendientes.value = [];
    try {
        const response = await axios.get(props.apiUrlPendientes, {
            params: {
                periodo: periodoPendientes.value,
                zona_id: filtroZonaPendientes.value || null // ¡CORREGIDO! Enviar 'zona_id'
            }
        });
        pendientes.value = response.data;
    } catch (error) {
        console.error(`Error cargando pendientes:`, error);
    } finally {
        isLoadingPendientes.value = false;
    }
};
// Recargar lista si cambian los filtros
watch([periodoPendientes, filtroZonaPendientes], cargarPendientes);

// --- 6. Lógica del Buscador ---
let searchTimeout = null;
const onSearchInput = () => {
    clearTimeout(searchTimeout);
    if (form.conexion_id) resetSeleccion();
    searchResults.value = [];
    showSearchDropdown.value = true;
    if (searchTerm.value.length < 2) { isSearching.value = false; return; }
    isSearching.value = true;
    
    searchTimeout = setTimeout(async () => {
        try {
            const response = await axios.get(props.apiUrlSearchConexiones, { params: { term: searchTerm.value } });
            searchResults.value = response.data;
        } catch (error) { console.error("Error buscando:", error); } 
        finally { isSearching.value = false; }
    }, 300);
};

// --- 7. Lógica de Selección y Reset ---
const selectConexion = (conexion) => {
    form.conexion_id = conexion.id;
    form.lectura_anterior = conexion.lectura_anterior ?? 0;
    form._afiliado_adulto_mayor = conexion.afiliado_adulto_mayor ?? false;
    form._conexion_info = conexion;
    // Corregido a 'afiliado_nombre'
    searchTerm.value = `${conexion.afiliado_nombre} (Med: ${conexion.codigo_medidor})`;
    showSearchDropdown.value = false;
    searchResults.value = [];
    document.getElementById('lectura_actual')?.focus(); 
};

const resetSeleccion = () => {
     form.reset();
     form.fecha_lectura = new Date().toISOString().split('T')[0];
     form._afiliado_adulto_mayor = false;
     form._conexion_info = null;
     searchTerm.value = '';
     searchResults.value = [];
     showSearchDropdown.value = false;
};

const handleBlur = () => { setTimeout(() => { showSearchDropdown.value = false; }, 300); };

// --- 8. Cálculos en Vivo (Computed) ---
const periodoCalculado = computed(() => {
    try {
        if (!form.fecha_lectura) return '---- --';
        const [year, month] = form.fecha_lectura.split('-');
        return `${year}-${month}`;
    } catch { return 'Fecha inválida'; }
});

const consumo = computed(() => {
    const anterior = parseFloat(form.lectura_anterior);
    const actual = parseFloat(form.lectura_actual);
    if (!isNaN(actual) && !isNaN(anterior) && actual >= anterior) {
        return (actual - anterior);
    }
    return 0;
});

const montoEstimado = computed(() => {
    if (!tarifaActiva.value || form.lectura_actual === '' || consumo.value < 0) {
        return { monto: '0.00', descuento: '0.00', total: '0.00' };
    }
    const cons = consumo.value;
    const tarifa = tarifaActiva.value;
    let montoCalculado = 0;
    // Lógica de cálculo (como la definimos)
    if (cons <= tarifa.min_m3 && tarifa.min_monto > (cons * tarifa.precio_m3) ) {
        montoCalculado = tarifa.min_monto;
    } else {
        montoCalculado = cons * tarifa.precio_m3;
    }
    let descuento = 0;
    if (form._afiliado_adulto_mayor && tarifa.descuento_adulto_mayor_pct > 0) {
        descuento = (montoCalculado * tarifa.descuento_adulto_mayor_pct) / 100;
    }
    const total = montoCalculado - descuento;
    return {
        monto: montoCalculado.toFixed(2),
        descuento: descuento.toFixed(2),
        total: (total >= 0 ? total : 0).toFixed(2),
    };
});

// --- 9. Enviar Formulario ---
const submit = () => {
    form.post(route('lecturas.store'), {
        preserveScroll: true,
        onSuccess: () => {
             resetSeleccion(); 
             cargarPendientes(); // Recargar lista de pendientes
        },
        onError: (errors) => {
            console.error("Errores de validación:", errors);
        }
    });
};
</script>

<template>
    <AppLayout title="Registrar Lectura">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Registrar Nueva Lectura</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-3 gap-8">

                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 md:p-8">
                        
                        <div v-if="form.hasErrors" class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-6" role="alert">
                           <p class="font-bold">Error</p>
                           <ul class="list-disc ml-5 text-sm"><li v-for="(error, key) in form.errors" :key="key">{{ error }}</li></ul>
                        </div>
                    
                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="mb-6">
                                <label for="searchConexion" class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">
                                    1. Buscar Conexión (Cód. Medidor, CI o Nombre)
                                </label>
                                <div class="relative">
                                    <input id="searchConexion" v-model="searchTerm" @input="onSearchInput" @focus="showSearchDropdown = true" @blur="handleBlur"
                                           type="text" placeholder="Escriba 2+ caracteres..."
                                           class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" autocomplete="off" />
                                    <span v-if="isSearching" class="absolute right-3 top-2 mt-px text-gray-400"> <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    </span>
                                    <ul v-if="showSearchDropdown && (searchResults.length > 0 || isSearching || searchTerm.length >= 2)" class="absolute z-20 mt-1 w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-60 overflow-auto">
                                        <li v-if="isSearching && !searchResults.length" class="px-4 py-2 text-gray-500 dark:text-gray-300 text-sm">Buscando...</li>
                                        <li v-for="c in searchResults" :key="c.id || 'error'" @mousedown="selectConexion(c)" class="px-4 py-2 hover:bg-indigo-50 dark:hover:bg-gray-600 cursor-pointer border-b dark:border-gray-600 last:border-b-0">
                                            <div v-if="!c.error" class="text-sm">
                                                <strong class="dark:text-white">{{ c.afiliado_nombre }}</strong> <span v-if="c.afiliado_adulto_mayor" class="text-xs text-purple-600 dark:text-purple-400 font-semibold">[A. Mayor]</span><br />
                                                <span class="text-xs text-gray-600 dark:text-gray-400">Med: {{ c.codigo_medidor }} | Dir: {{ c.direccion }} | Ant: {{ c.lectura_anterior }} m³</span>
                                            </div>
                                            <span v-else class="text-red-500">{{ c.message }}</span>
                                        </li>
                                        <li v-if="!isSearching && !searchResults.length && searchTerm.length >= 2" class="px-4 py-2 text-gray-500 dark:text-gray-300 text-sm">No se encontraron conexiones.</li>
                                    </ul>
                                </div>
                                <div v-if="form.errors.conexion_id" class="text-red-600 text-sm mt-1">{{ form.errors.conexion_id }}</div>
                            </div>

                            <fieldset :disabled="!form.conexion_id" class="space-y-4 border-t dark:border-gray-700 pt-4 mt-6">
                                <legend class="font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">2. Registrar Lectura</legend>
                                
                                <div v-if="!form.conexion_id" class="text-sm text-gray-500 dark:text-gray-400 italic">Seleccione una conexión para continuar...</div>

                                <div v-if="form._conexion_info" class="mb-4 p-3 bg-blue-50 dark:bg-gray-900 border border-blue-200 dark:border-blue-800 rounded-md text-sm">
                                     <div class="flex justify-between items-center">
                                         <div>
                                             <p class="dark:text-white"><strong>Conexión:</strong> {{ form._conexion_info.codigo_medidor }} - {{ form._conexion_info.afiliado_nombre }}</p>
                                             <p class="mt-1"><strong>Lectura Anterior:</strong> <span class="font-bold text-lg text-blue-700 dark:text-blue-400">{{ form.lectura_anterior }}</span> m³</p>
                                             <p v-if="form._afiliado_adulto_mayor" class="text-xs text-purple-600 dark:text-purple-400 font-semibold">Aplica descuento Adulto Mayor</p>
                                         </div>
                                         <button type="button" @click="resetSeleccion" class="text-xs text-red-600 hover:underline">Limpiar</button>
                                     </div>
                                </div>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="fecha_lectura" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Fecha de Lectura</label>
                                        <input id="fecha_lectura" v-model="form.fecha_lectura" type="date" class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm block mt-1 w-full disabled:bg-gray-100" required />
                                        <div v-if="form.errors.fecha_lectura" class="text-red-600 text-sm mt-1">{{ form.errors.fecha_lectura }}</div>
                                    </div>
                                    <div class="mt-1">
                                        <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">Período a Registrar</label>
                                        <p class="text-xl font-semibold text-gray-900 dark:text-white pt-2">{{ periodoCalculado }}</p>
                                    </div>
                                </div>

                                <div>
                                    <label for="lectura_actual" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Lectura Actual (m³)</label>
                                    <input id="lectura_actual" v-model.number="form.lectura_actual" type="number" step="0.01" :min="form.lectura_anterior"
                                           class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm block mt-1 w-full disabled:bg-gray-100 text-lg p-2" required />
                                    <div v-if="form.errors.lectura_actual" class="text-red-600 text-sm mt-1">{{ form.errors.lectura_actual }}</div>
                                </div>

                                <div v-if="form.lectura_actual && form.lectura_actual >= form.lectura_anterior" 
                                     class="p-4 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-md text-sm">
                                    <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">Cálculo Estimado (Aviso)</h4>
                                    <div v-if="isLoadingTarifa" class="text-gray-500 dark:text-gray-400">Cargando tarifa...</div>
                                    <div v-else-if="!tarifaActiva" class="text-red-500">Error: No se pudo cargar la tarifa.</div>
                                    <div v-else class="space-y-1 dark:text-gray-300">
                                        <p class="flex justify-between"><span>Consumo:</span> <span class="font-medium">{{ consumo.toFixed(2) }} m³</span></p>
                                        <p class="flex justify-between"><span>Monto Cálculo:</span> <span>Bs {{ montoEstimado.monto }}</span></p>
                                        <p v-if="form._afiliado_adulto_mayor" class="flex justify-between text-purple-600 dark:text-purple-400">
                                            <span>Descuento ({{ tarifaActiva.descuento_adulto_mayor_pct }}%):</span> 
                                            <span>- Bs {{ montoEstimado.descuento }}</span>
                                        </p>
                                        <hr class="my-1 dark:border-gray-600">
                                        <p class="flex justify-between text-base font-bold">
                                            <span>Monto Estimado:</span> 
                                            <span class="text-green-700 dark:text-green-400">Bs {{ montoEstimado.total }}</span>
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <label for="observacion" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Observación (Opcional)</label>
                                    <textarea id="observacion" v-model="form.observacion" class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm block mt-1 w-full disabled:bg-gray-100" rows="2"></textarea>
                                    <div v-if="form.errors.observacion" class="text-red-600 text-sm mt-1">{{ form.errors.observacion }}</div>
                                </div>
                            </fieldset>

                            <div class="flex justify-end gap-3 mt-8 border-t dark:border-gray-700 pt-4">
                                <Link :href="route('lecturas.index')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded transition">Cancelar</Link>
                                <button type="submit"
                                        :disabled="form.processing || !form.conexion_id || !form.lectura_actual || form.lectura_actual < form.lectura_anterior"
                                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50 transition"
                                        :class="{'cursor-not-allowed': !form.conexion_id || !form.lectura_actual || form.lectura_actual < form.lectura_anterior}">
                                    {{ form.processing ? 'Guardando...' : 'Registrar Lectura' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Pendientes de Lectura</h3>
                        
                        <div class="grid grid-cols-2 gap-4 mb-4">
                             <div>
                                 <label for="periodoPend" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Período</label>
                                 <input id="periodoPend" type="month" v-model="periodoPendientes" class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm block mt-1 w-full text-sm p-1.5"/>
                             </div>
                             <div>
                                 <label for="zonaPend" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Zona</label>
                                 <select id="zonaPend" v-model="filtroZonaPendientes" class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm block mt-1 w-full text-sm p-1.5">
                                     <option value="">Todas</option>
                                     <option v-for="zona in props.zonas" :key="zona.id" :value="zona.id">{{ zona.nombre }}</option>
                                 </select>
                             </div>
                         </div>

                        <div class="max-h-[600px] overflow-y-auto border rounded-md dark:border-gray-700 divide-y dark:divide-gray-600">
                            <div v-if="isLoadingPendientes" class="p-4 text-center text-gray-500 dark:text-gray-400 text-sm">Cargando...</div>
                            <div v-else-if="!pendientes.length" class="p-4 text-center text-gray-500 dark:text-gray-400 text-sm">
                                No hay pendientes para {{ periodoPendientes }}...
                            </div>
                            <ul v-else>
                                <li v-for="p in pendientes" :key="p.id"
                                    @click="selectConexion(p)"
                                    class="px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer text-sm"
                                    :class="{ 'bg-blue-50 dark:bg-blue-900 font-semibold border-l-4 border-blue-500': form.conexion_id === p.id }" >
                                    <strong class="text-gray-900 dark:text-white">{{ p.afiliado_nombre }}</strong>
                                    <span v-if="p.afiliado_adulto_mayor" class="text-xs text-purple-600">[A.M]</span><br>
                                    <span class="text-xs text-gray-600 dark:text-gray-400">Med: {{ p.codigo_medidor }}</span><br>
                                    <span class="text-xs text-gray-600 dark:text-gray-400">Dir: {{ p.direccion }} ({{ p.zona_nombre || 'N/A' }})</span><br>
                                    <span class="text-xs text-gray-500">Ant: {{ p.lectura_anterior }} m³</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>