<script setup>
import { ref, computed, watch, onUnmounted } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import ViewCounter from '@/Components/ViewCounter.vue'

// --- 1. PROPS ---
const props = defineProps({
    zonas: Array,
    requisitos: Array,
    errors: Object,
});
const page = usePage();

// --- 2. FORMULARIO PRINCIPAL ---
const form = useForm({
    nombre_completo: '',
    ci: '',
    celular: '',
    direccion: '', 
    zona_id: '',     
    tipo: 'socio',
    estado: 'activo',
    estado_servicio: 'Pendiente', // 
    fecha_afiliacion: new Date().toISOString().split('T')[0],
    fecha_baja: null,
    codigo: '',
    tenencia: 'propietario',
    adulto_mayor: false,
    profile_photo: null,
    requisitos_seleccionados: [],
    observacion: '', 
   
});

// --- 3. LÓGICA DE FOTO ---
const photoPreview = ref(null);
const photoInput = ref(null);
const selectNewPhoto = () => photoInput.value.click();
const updatePhotoPreview = () => {
    const photo = photoInput.value.files[0];
    if (!photo) return;
    form.profile_photo = photo;
    const reader = new FileReader();
    reader.onload = (e) => { photoPreview.value = e.target.result; };
    reader.readAsDataURL(photo);
};

// --- 4. LÓGICA DE REQUISITOS (Tu idea) ---
const requisitosFiltrados = computed(() => {
    if (!props.requisitos) return [];
    const tenencia = form.tenencia;
    return props.requisitos.filter(req => {
        return req.es_para_todos ||
               (req.es_para_propietario && tenencia === 'propietario') ||
               (req.es_para_compra_venta && tenencia === 'compra_venta') ||
               (req.es_para_posesion && tenencia === 'posesion');
    });
});
watch(() => form.tenencia, () => {
    form.requisitos_seleccionados = [];
});

// --- 5. LÓGICA DE "NUEVA ZONA" (Barrio) ---
const showNuevaZonaModal = ref(false);
const nuevaZonaForm = useForm({ nombre: '' });
const zonasLocales = ref([...props.zonas]);
const guardarNuevaZona = () => {
    nuevaZonaForm.post(route('zonas.store'), {
        preserveScroll: true,
        onSuccess: () => {
            const nuevaZona = page.props.flash.nueva_zona;
            if (nuevaZona) {
                zonasLocales.value.push(nuevaZona);
                zonasLocales.value.sort((a, b) => a.nombre.localeCompare(b.nombre));
                form.zona_id = nuevaZona.id; 
            }
            showNuevaZonaModal.value = false;
            nuevaZonaForm.reset();
        },
        onError: (errors) => {
            console.error("Error al guardar zona:", errors);
        }
    });
};

// --- 6. LÓGICA DE ENVÍO (SUBMIT) ---
const submit = () => {
    form.post(route('afiliados.store'), {
        onError: (errors) => {
            console.error("Errores de validación:", errors);
        },
    });
};

// --- LÓGICA DE CÁMARA ---
const showCameraModal = ref(false);
const videoPlayer = ref(null);
const canvasElement = ref(null);
const stream = ref(null);

const abrirCamara = async () => {
    showCameraModal.value = true;
    try {
        stream.value = await navigator.mediaDevices.getUserMedia({ 
            video: { width: 1280, height: 720 }, 
            audio: false 
        });
        videoPlayer.value.srcObject = stream.value;
    } catch (err) {
        alert("No se pudo acceder a la cámara. Revisa los permisos.");
        showCameraModal.value = false;
    }
};

const cerrarCamara = () => {
    if (stream.value) {
        stream.value.getTracks().forEach(track => track.stop());
    }
    showCameraModal.value = false;
};

const capturarFoto = () => {
    const context = canvasElement.value.getContext('2d');
    // Dibujamos el video en el canvas
    context.drawImage(videoPlayer.value, 0, 0, 640, 480);
    
    // Convertimos el canvas a un BLOB (archivo) para que Laravel lo reciba bien
    canvasElement.value.toBlob((blob) => {
        const file = new File([blob], "foto_camara.jpg", { type: "image/jpeg" });
        
        // Asignamos al formulario de Inertia
        form.profile_photo = file;
        
        // Generamos la vista previa
        photoPreview.value = URL.createObjectURL(blob);
        
        cerrarCamara();
    }, 'image/jpeg', 0.9);
};

// Limpiar stream si el usuario cierra la pestaña o navega a otro lado
onUnmounted(() => cerrarCamara());
</script>

<template>
    <AppLayout title="Nuevo Afiliado">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Registrar Nuevo Afiliado
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 md:p-8">
                    
                    <div v-if="form.hasErrors" class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-6 dark:bg-red-200 dark:text-red-800" role="alert">
                       <p class="font-bold">Error de Validación</p>
                       <p v-if="form.errors.error_general">{{ form.errors.error_general }}</p>
                       <p v-else>Por favor, revise los campos marcados en rojo.</p>
                       <ul class="list-disc ml-5 text-sm"><li v-for="(error, key) in form.errors" :key="key" v-if="key !== 'error_general'">{{ error }}</li></ul>
                    </div>
                
                    <form @submit.prevent="submit" class="space-y-6">

                        <div class="col-span-6 sm:col-span-4">
                            <input ref="photoInput" type="file" class="hidden" @change="updatePhotoPreview" accept="image/*">
                            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">Foto del Afiliado (Opcional)</label>
                            
                            <div class="mt-2 flex items-center gap-4">
                                <span v-if="!photoPreview" class="block rounded-full w-20 h-20 bg-gray-100 dark:bg-gray-700 flex items-center justify-center border-2 border-dashed border-gray-300">
                                    <svg class="h-12 w-12 text-gray-400 dark:text-gray-500" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                                <img v-if="photoPreview" :src="photoPreview" alt="Vista previa" class="w-20 h-20 rounded-full object-cover border-2 border-indigo-500">

                                <div class="flex flex-col sm:flex-row gap-2">
                                    <button @click.prevent="selectNewPhoto" type="button" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        {{ photoPreview ? 'Cambiar de Galería' : 'Desde Galería' }}
                                    </button>

                                    <button @click.prevent="abrirCamara" type="button" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        Tomar Foto
                                    </button>
                                </div>
                            </div>
                            <div v-if="form.errors.profile_photo" class="text-red-600 text-sm mt-1">{{ form.errors.profile_photo }}</div>
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6 space-y-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Datos Personales</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="nombre_completo" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Nombre Completo *</label>
                                    <input id="nombre_completo" v-model="form.nombre_completo" type="text" 
                                           class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required />
                                    <div v-if="form.errors.nombre_completo" class="text-red-600 text-sm mt-1">{{ form.errors.nombre_completo }}</div>
                                </div>
                                <div>
                                    <label for="ci" class="block font-medium text-sm text-gray-700 dark:text-gray-300">CI / Carnet de Identidad *</label>
                                    <input id="ci" v-model="form.ci" type="text" 
                                           class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required />
                                    <div v-if="form.errors.ci" class="text-red-600 text-sm mt-1">{{ form.errors.ci }}</div>
                                </div>
                                <div>
                                    <label for="celular" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Celular</label>
                                    <input id="celular" v-model="form.celular" type="text" 
                                           class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6 space-y-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Domicilio</h3>
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="zona_id" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Barrio *</label>
                                    <div class="mt-1 flex items-center gap-2"> 
                                        <select id="zona_id" v-model="form.zona_id" 
                                                class="flex-grow border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                                            <option value="" disabled>Seleccione un Barrio</option>
                                            <option v-for="zona in zonasLocales" :key="zona.id" :value="zona.id">{{ zona.nombre }}</option>
                                        </select>
                                        <button @click.prevent="showNuevaZonaModal = true" type="button" class="flex-shrink-0 px-3 py-2 bg-blue-500 text-white rounded-md text-sm hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-500">+</button>
                                    </div>
                                    <div v-if="form.errors.zona_id" class="text-red-600 text-sm mt-1">{{ form.errors.zona_id }}</div>
                                </div>
                                <div>
                                    <label for="direccion" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Calle *</label>
                                    <input id="direccion" v-model="form.direccion" type="text" 
                                           class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required />
                                    <div v-if="form.errors.direccion" class="text-red-600 text-sm mt-1">{{ form.errors.direccion }}</div>
                                </div>
                             </div>
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6 space-y-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Datos de Afiliación</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="codigo" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Código Afiliado *</label>
                                    <input id="codigo" v-model="form.codigo" type="text" 
                                           class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required />
                                    <div v-if="form.errors.codigo" class="text-red-600 text-sm mt-1">{{ form.errors.codigo }}</div>
                                </div>
                                <div>
                                    <label for="tipo" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Tipo *</label>
                                    <select id="tipo" v-model="form.tipo" 
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <option value="socio">Socio</option>
                                        <option value="usuario">Usuario</option>
                                    </select>
                                </div>
                                 <div>
                                    <label for="tenencia" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Tenencia *</label>
                                    <select id="tenencia" v-model="form.tenencia" 
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <option value="propietario">Propietario</option>
                                        <option value="compra_venta">Compra/Venta</option>
                                        <option value="posesion">Posesión</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="fecha_afiliacion" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Fecha Afiliación</label>
                                    <input id="fecha_afiliacion" v-model="form.fecha_afiliacion" type="date" 
                                           class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
                                </div>
                                <div>
                                    <label for="estado" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Estado Contractual *</label>
                                    <select id="estado" v-model="form.estado" 
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <option value="activo">Activo</option>
                                        <option value="suspendido">Suspendido</option>
                                        <option value="baja">Baja</option>
                                    </select>
                                </div>
                                 <div>
                                    <label for="estado_servicio" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Estado Servicio *</label>
                                    <select id="estado_servicio" v-model="form.estado_servicio" 
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <option value="Pendiente">Pendiente </option>
                                        <option value="activo">Activo</option>
                                        <option value="en_corte">En Corte</option>
                                        <option value="cortado">Cortado</option>
                                    </select>
                                    <div v-if="form.errors.estado_servicio" class="text-red-600 text-sm mt-1">{{ form.errors.estado_servicio }}</div>
                                </div>
                                <div class="md:col-span-3 flex items-center gap-2 mt-2">
                                    <input id="adulto_mayor" v-model="form.adulto_mayor" type="checkbox" 
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600" />
                                    <label for="adulto_mayor" class="font-medium text-sm text-gray-700 dark:text-gray-300">¿Es Adulto Mayor? (Aplica descuento)</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                             <h3 class="text-lg font-medium text-gray-900 dark:text-white">Requisitos Entregados</h3>
                             <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Se mostrarán los requisitos según la Tenencia seleccionada.</p>
                             <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                 <template v-for="requisito in props.requisitos" :key="requisito.id">
                                     <label v-if="
                                                requisito.es_para_todos ||
                                                (requisito.es_para_propietario && form.tenencia === 'propietario') ||
                                                (requisito.es_para_compra_venta && form.tenencia === 'compra_venta') ||
                                                (requisito.es_para_posesion && form.tenencia === 'posesion')
                                            "
                                            class="flex items-center gap-2 p-3 border border-gray-200 dark:border-gray-700 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition duration-150">
                                      <input type="checkbox" :value="requisito.id" v-model="form.requisitos_seleccionados" 
                                             class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600"/>
                                      <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ requisito.nombre }}</span>
                                     </label>
                                 </template>
                             </div>
                             <div v-if="requisitosFiltrados.length === 0 && form.tenencia" class="text-sm text-gray-500 dark:text-gray-400 italic mt-4">
                                 (No hay requisitos definidos para la tenencia "{{ form.tenencia }}")
                             </div>
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6 space-y-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Observaciones </h3>
                            <div>
                                <label for="observacion" class="sr-only">Observación</label>
                                <textarea id="observacion" v-model="form.observacion" rows="4" 
                                          class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" 
                                          placeholder="Añadir notas internas sobre el afiliado..."></textarea>
                                <div v-if="form.errors.observacion" class="text-red-600 text-sm mt-1">{{ form.errors.observacion }}</div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-4 mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <Link :href="route('afiliados.index')" 
                                  class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded transition duration-150 ease-in-out shadow">
                                Cancelar
                            </Link>
                            <button type="submit" :disabled="form.processing"
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50 transition duration-150 ease-in-out shadow">
                                {{ form.processing ? 'Guardando...' : 'Guardar Afiliado' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <Modal :show="showNuevaZonaModal" @close="showNuevaZonaModal = false" max-width="md">
            <div class="p-6 dark:bg-gray-800 dark:text-gray-200">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                    Agregar Nuevo Barrio
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    El barrio se añadirá a la lista y se seleccionará automáticamente.
                </p> 
                <form @submit.prevent="guardarNuevaZona" class="mt-4 space-y-4">
                     <div>
                        <label for="nueva_zona_nombre" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Nombre del Barrio *</label>
                        <input id="nueva_zona_nombre" v-model="nuevaZonaForm.nombre" type="text" 
                               class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required />
                        <div v-if="nuevaZonaForm.errors.nombre" class="text-red-600 text-sm mt-1">{{ nuevaZonaForm.errors.nombre }}</div>
                    </div>
                    <div class="flex justify-end gap-4">
                        <button type="button" @click="showNuevaZonaModal = false" 
                                class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded transition duration-150 ease-in-out">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="nuevaZonaForm.processing" 
                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50 transition duration-150 ease-in-out">
                            {{ nuevaZonaForm.processing ? 'Guardando...' : 'Guardar Barrio' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
            <Modal :show="showCameraModal" @close="cerrarCamara" max-width="2xl">
                <div class="p-6 dark:bg-gray-800">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                            Cámara: Captura de Foto de Afiliado
                        </h2>
                        <button @click="cerrarCamara" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <div class="relative bg-black rounded-lg overflow-hidden shadow-inner flex justify-center items-center" style="min-height: 400px;">
                        <video ref="videoPlayer" autoplay playsinline class="w-full h-full object-cover"></video>
                        
                        <canvas ref="canvasElement" width="640" height="480" class="hidden"></canvas>
                        
                        <div class="absolute inset-0 border-2 border-white/20 pointer-events-none flex items-center justify-center">
                            <div class="w-64 h-64 border border-white/40 rounded-full"></div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-between gap-4">
                        <button type="button" @click="cerrarCamara" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded transition">
                            Cerrar
                        </button>
                        
                        <button type="button" @click="capturarFoto" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-8 rounded shadow-lg flex items-center gap-2 transition transform active:scale-95">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 5a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V7a2 2 0 00-2-2h-1.586a1 1 0 01-.707-.293l-1.121-1.121A2 2 0 0011.172 3H8.828a2 2 0 00-1.414.586L6.293 4.707A1 1 0 015.586 5H4zm6 9a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"></path></svg>
                            CAPTURAR AHORA
                        </button>
                    </div>
                </div>
            </Modal>
        <ViewCounter />
    </AppLayout>
</template>