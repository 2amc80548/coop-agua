<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ViewCounter from '@/Components/ViewCounter.vue';
import { ref } from 'vue';

const props = defineProps({
    afiliado: Object,
    resumenFinanciero: Object,
});

const page = usePage();
const activeTab = ref('resumen'); // 'resumen', 'conexiones', 'documentos', 'traspasos'

// --- Formulario para Subir Documento ---
const docForm = useForm({
    tipo_documento: 'CI',
    archivo: null,
    observacion: '',
});

const submitDoc = () => {
    docForm.post(route('afiliados.documentos.store', props.afiliado.id), {
        preserveScroll: true,
        onSuccess: () => {
            docForm.reset();
        },
    });
};

const eliminarDocumento = (docId) => {
    if (confirm('¿Desea eliminar este documento del expediente?')) {
        useForm({}).delete(route('afiliados.documentos.destroy', docId), {
            preserveScroll: true,
        });
    }
};

// --- Formulario para Traspaso de Titularidad ---
const showTraspasoModal = ref(false);
const selectedConexion = ref(null);
const traspasoForm = useForm({
    nuevo_afiliado_id: '',
    motivo: '',
});

const openTraspasoModal = (conexion) => {
    selectedConexion.value = conexion;
    traspasoForm.reset();
    showTraspasoModal.value = true;
};

const submitTraspaso = () => {
    if (!selectedConexion.value) return;
    traspasoForm.post(route('conexiones.traspasar', selectedConexion.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showTraspasoModal.value = false;
            traspasoForm.reset();
        },
    });
};

const getPhotoUrl = (path) => {
    if (path) {
        const baseUrl = window.location.origin + window.location.pathname.split('/afiliados')[0];
        return `${baseUrl}/storage/${path}`;
    }
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(props.afiliado.nombre_completo)}&color=7F9CF5&background=EBF4FF`;
};

const estadoClass = (estado) => {
    switch (estado) {
        case 'activo': return 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300';
        case 'suspendido': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300';
        case 'baja': return 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
};

const estadoServicioClass = (estado) => {
    switch (estado) {
        case 'activo': return 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300';
        case 'en_corte': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300';
        case 'cortado': return 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300';
        case 'Pendiente': return 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
};

const estadoFinancieroBadgeClass = (estado) => {
    switch (estado) {
        case 'Al Día': return 'bg-green-500 text-white';
        case 'En Mora': return 'bg-yellow-500 text-white';
        case 'Con Orden de Corte': return 'bg-red-600 text-white font-bold animate-pulse';
        default: return 'bg-gray-500 text-white';
    }
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        date.setMinutes(date.getMinutes() + date.getTimezoneOffset());
        return date.toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' });
    } catch { return dateString; }
};

const showPhotoModal = ref(false);
</script>

<template>
    <AppLayout :title="afiliado.nombre_completo">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Expediente del Socio / Afiliado
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <div v-if="page.props.flash.success" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm">
                    <p class="font-bold">Éxito</p>
                    <p>{{ page.props.flash.success }}</p>
                </div>
                <div v-if="page.props.errors && Object.keys(page.props.errors).length > 0" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm">
                    <p class="font-bold">Atención</p>
                    <ul class="list-disc ml-5 text-sm"><li v-for="(err, k) in page.props.errors" :key="k">{{ err }}</li></ul>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    
                    <!-- Encabezado del Afiliado -->
                    <div class="p-6 md:p-8 border-b border-gray-200 dark:border-gray-700 flex flex-col md:flex-row items-center gap-6">
                        <div class="relative group cursor-pointer" @click="showPhotoModal = true">
                            <img 
                                class="h-24 w-24 rounded-full object-cover border-4 border-white dark:border-gray-700 shadow-lg hover:scale-105 transition-transform duration-200" 
                                :src="getPhotoUrl(afiliado.profile_photo_path)" 
                                :alt="afiliado.nombre_completo"
                            >
                        </div>
                        <div class="flex-1 text-center md:text-left">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ afiliado.nombre_completo }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">CI: {{ afiliado.ci }} | Código Socio: {{ afiliado.codigo }}</p>
                            <div class="flex flex-wrap gap-2 mt-3 justify-center md:justify-start">
                                 <span class="px-3 py-1 text-xs font-semibold rounded-full" :class="estadoClass(afiliado.estado)">
                                    Estado: {{ afiliado.estado }}
                                 </span>
                                 <span class="px-3 py-1 text-xs font-semibold rounded-full" :class="estadoServicioClass(afiliado.estado_servicio)">
                                    Servicio: {{ afiliado.estado_servicio }}
                                 </span>
                                 <span v-if="afiliado.adulto_mayor" class="px-3 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">
                                    Adulto Mayor (Descuento 20%)
                                 </span>
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <Link :href="route('afiliados.edit', afiliado.id)" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded transition shadow">
                                Editar Datos
                            </Link>
                        </div>
                    </div>

                    <!-- Tarjetas de Resumen Financiero Consolidado -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 p-6 bg-gray-50 dark:bg-gray-900/40 border-b border-gray-200 dark:border-gray-700">
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Estado Financiero</p>
                            <span class="mt-2 inline-block px-3 py-1 text-xs font-bold rounded-full" :class="estadoFinancieroBadgeClass(resumenFinanciero?.estado_financiero)">
                                {{ resumenFinanciero?.estado_financiero || 'Al Día' }}
                            </span>
                        </div>
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Deuda Total Pendiente</p>
                            <p class="text-xl font-bold" :class="resumenFinanciero?.deuda_total_pendiente > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'">
                                Bs {{ resumenFinanciero?.deuda_total_pendiente?.toFixed(2) || '0.00' }}
                            </p>
                        </div>
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Facturas Impagas</p>
                            <p class="text-xl font-bold text-gray-800 dark:text-gray-200">
                                {{ resumenFinanciero?.total_facturas_impagas || 0 }} factura(s)
                            </p>
                        </div>
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Conexiones Asignadas</p>
                            <p class="text-xl font-bold text-blue-600 dark:text-blue-400">
                                {{ resumenFinanciero?.total_conexiones || 0 }} medidor(es)
                            </p>
                        </div>
                    </div>

                    <!-- Navegación por Pestañas -->
                    <div class="border-b border-gray-200 dark:border-gray-700 px-6 pt-4 flex gap-4 text-sm font-medium">
                        <button @click="activeTab = 'resumen'" class="pb-3 border-b-2 transition" :class="activeTab === 'resumen' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'">
                            Información General
                        </button>
                        <button @click="activeTab = 'conexiones'" class="pb-3 border-b-2 transition" :class="activeTab === 'conexiones' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'">
                            Medidores / Conexiones ({{ afiliado.conexiones?.length || 0 }})
                        </button>
                        <button @click="activeTab = 'documentos'" class="pb-3 border-b-2 transition" :class="activeTab === 'documentos' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'">
                            📁 Expediente Digital ({{ afiliado.documentos?.length || 0 }})
                        </button>
                        <button @click="activeTab = 'traspasos'" class="pb-3 border-b-2 transition" :class="activeTab === 'traspasos' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'">
                            🔄 Historial Traspasos
                        </button>
                    </div>

                    <!-- Pestaña 1: Información General -->
                    <div v-if="activeTab === 'resumen'" class="p-6 md:p-8 space-y-6">
                        <div>
                            <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">Contacto y Ubicación</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm bg-gray-50 dark:bg-gray-900/50 p-4 rounded-md">
                                <div><strong class="text-gray-500 dark:text-gray-400">Celular:</strong> {{ afiliado.celular || 'No registrado' }}</div>
                                <div><strong class="text-gray-500 dark:text-gray-400">Dirección:</strong> {{ afiliado.direccion || '—' }}</div>
                                <div><strong class="text-gray-500 dark:text-gray-400">Barrio / Zona:</strong> {{ afiliado.zona?.nombre || 'No registrado' }}</div>
                                <div v-if="afiliado.user"><strong class="text-gray-500 dark:text-gray-400">Email Portal:</strong> {{ afiliado.user.email }}</div>
                            </div>
                        </div>

                        <div>
                            <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">Requisitos de Registro</h4>
                            <ul v-if="afiliado.requisitos && afiliado.requisitos.length > 0" class="list-disc list-inside space-y-1 text-sm">
                                <li v-for="req in afiliado.requisitos" :key="req.id" class="text-green-600 dark:text-green-400">
                                    <span class="text-gray-700 dark:text-gray-300">{{ req.nombre }}</span>
                                    <span v-if="req.pivot?.fecha_entrega" class="text-xs text-gray-500"> ({{ formatDate(req.pivot.fecha_entrega) }})</span>
                                </li>
                            </ul>
                            <p v-else class="text-sm text-gray-500 italic">No hay requisitos registrados.</p>
                        </div>
                    </div>

                    <!-- Pestaña 2: Conexiones & Traspaso -->
                    <div v-if="activeTab === 'conexiones'" class="p-6 md:p-8 space-y-4">
                        <div class="flex justify-between items-center">
                            <h4 class="font-semibold text-gray-800 dark:text-gray-200">Medidores Asignados</h4>
                        </div>
                        <div v-if="afiliado.conexiones && afiliado.conexiones.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700 border rounded-md dark:border-gray-700">
                            <div v-for="con in afiliado.conexiones" :key="con.id" class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-sm">
                                <div>
                                    <p class="font-bold text-indigo-600 dark:text-indigo-400">Medidor: #{{ con.codigo_medidor }}</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">{{ con.direccion }} ({{ con.zona?.nombre || 'Zona N/A' }})</p>
                                    <p class="text-xs text-gray-500 mt-1">Tipo: {{ con.tipo_conexion }} | Instalado: {{ formatDate(con.fecha_instalacion) }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full" :class="con.estado === 'activo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                        {{ con.estado }}
                                    </span>
                                    <button @click="openTraspasoModal(con)" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-3 py-1.5 rounded shadow transition">
                                        Traspasar Titularidad
                                    </button>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-500 italic">Este socio no tiene conexiones de agua asignadas.</p>
                    </div>

                    <!-- Pestaña 3: Expediente Digital (Documentos) -->
                    <div v-if="activeTab === 'documentos'" class="p-6 md:p-8 space-y-6">
                        <!-- Formulario Subir Documento -->
                        <form @submit.prevent="submitDoc" class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700 space-y-4">
                            <h4 class="font-semibold text-gray-800 dark:text-gray-200 text-sm">Cargar Nuevo Documento al Expediente</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo Documento</label>
                                    <select v-model="docForm.tipo_documento" class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md w-full">
                                        <option value="CI">Cédula de Identidad (CI)</option>
                                        <option value="Titulo_Propiedad">Título de Propiedad / Minuta</option>
                                        <option value="Croquis_Ubicacion">Croquis de Ubicación</option>
                                        <option value="Contrato">Contrato de Servicios</option>
                                        <option value="Otro">Otro Documento</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Archivo (PDF, PNG, JPG max 5MB)</label>
                                    <input type="file" @change="e => docForm.archivo = e.target.files[0]" class="text-xs border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md w-full p-1.5" required />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Observación</label>
                                    <input v-model="docForm.observacion" type="text" placeholder="Ej. Copia legible 2026" class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md w-full" />
                                </div>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" :disabled="docForm.processing" class="bg-green-600 hover:bg-green-700 text-white text-xs font-bold px-4 py-2 rounded shadow transition">
                                    {{ docForm.processing ? 'Subiendo...' : 'Subir Documento' }}
                                </button>
                            </div>
                        </form>

                        <!-- Lista de Documentos Subidos -->
                        <div v-if="afiliado.documentos && afiliado.documentos.length > 0" class="space-y-3">
                            <h4 class="font-semibold text-gray-800 dark:text-gray-200 text-sm">Documentos Almacenados</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="doc in afiliado.documentos" :key="doc.id" class="p-4 border rounded-lg dark:border-gray-700 bg-white dark:bg-gray-800 flex justify-between items-start text-sm shadow-sm">
                                    <div>
                                        <span class="px-2 py-0.5 text-xs font-bold rounded bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                            {{ doc.tipo_documento }}
                                        </span>
                                        <p class="font-medium text-gray-900 dark:text-white mt-1">{{ doc.nombre_archivo }}</p>
                                        <p v-if="doc.observacion" class="text-xs text-gray-500 italic mt-0.5">{{ doc.observacion }}</p>
                                        <p class="text-xs text-gray-400 mt-2">Subido por: {{ doc.usuario_subio?.name || 'Sistema' }} el {{ formatDate(doc.created_at) }}</p>
                                    </div>
                                    <div class="flex gap-2">
                                        <a :href="`/storage/${doc.archivo_path}`" target="_blank" class="text-blue-600 hover:underline text-xs font-semibold">Ver / Descargar</a>
                                        <button @click="eliminarDocumento(doc.id)" class="text-red-600 hover:text-red-800 text-xs font-semibold ml-2">Eliminar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-500 italic">No hay documentos digitalizados adjuntos a este expediente.</p>
                    </div>

                    <!-- Pestaña 4: Historial de Traspasos -->
                    <div v-if="activeTab === 'traspasos'" class="p-6 md:p-8 space-y-6">
                        <div>
                            <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-3 text-sm">Traspasos Recibidos (Nuevos Medidores Adquiridos)</h4>
                            <ul v-if="afiliado.traspasos_entrantes && afiliado.traspasos_entrantes.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700 border rounded-md dark:border-gray-700">
                                <li v-for="t in afiliado.traspasos_entrantes" :key="t.id" class="p-3 text-sm">
                                    <p class="font-medium">Medidor #{{ t.conexion?.codigo_medidor }} recivido de: {{ t.afiliado_anterior?.nombre_completo }} (CI: {{ t.afiliado_anterior?.ci }})</p>
                                    <p class="text-xs text-gray-500">Fecha: {{ formatDate(t.fecha_traspaso) }} | Motivo: {{ t.motivo }}</p>
                                </li>
                            </ul>
                            <p v-else class="text-sm text-gray-500 italic">No ha recibido traspasos de medidores.</p>
                        </div>

                        <div>
                            <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-3 text-sm">Traspasos Salientes (Medidores Transferidos a Otros Socios)</h4>
                            <ul v-if="afiliado.traspasos_salientes && afiliado.traspasos_salientes.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700 border rounded-md dark:border-gray-700">
                                <li v-for="t in afiliado.traspasos_salientes" :key="t.id" class="p-3 text-sm">
                                    <p class="font-medium">Medidor #{{ t.conexion?.codigo_medidor }} transferido a: {{ t.afiliado_nuevo?.nombre_completo }} (CI: {{ t.afiliado_nuevo?.ci }})</p>
                                    <p class="text-xs text-gray-500">Fecha: {{ formatDate(t.fecha_traspaso) }} | Motivo: {{ t.motivo }}</p>
                                </li>
                            </ul>
                            <p v-else class="text-sm text-gray-500 italic">No ha transferido medidores a otros socios.</p>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Modal Traspaso de Titularidad -->
            <div v-if="showTraspasoModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70">
                <div class="bg-white dark:bg-gray-800 rounded-lg max-w-md w-full p-6 space-y-4 shadow-xl">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Traspasar Medidor #{{ selectedConexion?.codigo_medidor }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Esta acción cambiará el titular del medidor hacia otro socio de la cooperativa.</p>
                    
                    <form @submit.prevent="submitTraspaso" class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">ID o Código del Nuevo Socio *</label>
                            <input v-model="traspasoForm.nuevo_afiliado_id" type="number" placeholder="Ingrese el ID del nuevo socio..." class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md text-sm" required />
                            <div v-if="traspasoForm.errors.nuevo_afiliado_id" class="text-red-600 text-xs mt-1">{{ traspasoForm.errors.nuevo_afiliado_id }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Motivo del Traspaso *</label>
                            <textarea v-model="traspasoForm.motivo" rows="3" placeholder="Ej. Compra-venta del inmueble o cesión de derecho de agua" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md text-sm" required></textarea>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button type="button" @click="showTraspasoModal = false" class="px-4 py-2 bg-gray-500 text-white rounded text-xs font-bold hover:bg-gray-600">Cancelar</button>
                            <button type="submit" :disabled="traspasoForm.processing" class="px-4 py-2 bg-blue-600 text-white rounded text-xs font-bold hover:bg-blue-700 disabled:opacity-50">
                                {{ traspasoForm.processing ? 'Procesando...' : 'Confirmar Traspaso' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <ViewCounter />
        </div>
    </AppLayout>
</template>