<script setup>
import { ref, computed, watch } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue'; // Para los modales

// --- 1. PROPS ---
const props = defineProps({
    afiliado: Object, // Trae afiliado con 'zona' y 'requisitos' cargados
    zonas: Array,
    requisitos: Array, // Lista completa de requisitos
    errors: Object,
});
const page = usePage();

// --- 2. PERMISOS ---
const isAdmin = computed(() => usePage().props.auth.user.role_names.includes('Administrador'));
const habilitarCamposProtegidos = ref(false);

// --- 3. FORMATEADOR SEGURO DE FECHA ---
const formatFechafield = (dateString) => {
    if (!dateString) return null;
    try {
        return dateString.split('T')[0].split(' ')[0];
    } catch (e) {
        console.warn("Fecha inválida recibida:", dateString);
        return null;
    }
};

// --- 4. FORMULARIO PRINCIPAL (¡CORREGIDO!) ---
const form = useForm({
    _method: 'PUT',
    nombre_completo: props.afiliado.nombre_completo,
    ci: props.afiliado.ci,
    celular: props.afiliado.celular,
    direccion: props.afiliado.direccion, // Calle
    zona_id: props.afiliado.zona_id,       // Barrio
    tipo: props.afiliado.tipo,
    estado: props.afiliado.estado,
    estado_servicio: props.afiliado.estado_servicio,
    fecha_afiliacion: formatFechafield(props.afiliado.fecha_afiliacion),
    fecha_baja: formatFechafield(props.afiliado.fecha_baja),
    codigo: props.afiliado.codigo,
    tenencia: props.afiliado.tenencia,
    adulto_mayor: props.afiliado.adulto_mayor,
    profile_photo: null,
    clear_photo: false,
    
    // ¡AÑADIDO!
    observacion: props.afiliado.observacion || '', 
    
    requisitos_seleccionados: props.afiliado.requisitos?.map(r => r.id) ?? [],
    force_baja: false,
});

// --- 5. LÓGICA DE FOTO ---
const photoPreview = ref(null);
const photoInput = ref(null);
const existingPhotoUrl = computed(() => props.afiliado.profile_photo_path ? `/storage/${props.afiliado.profile_photo_path}` : null);
const selectNewPhoto = () => { photoInput.value.click(); };
const updatePhotoPreview = () => {
    const photo = photoInput.value.files[0];
    if (!photo) return;
    form.profile_photo = photo;
    form.clear_photo = false;
    const reader = new FileReader();
    reader.onload = (e) => { photoPreview.value = e.target.result; };
    reader.readAsDataURL(photo);
};
const clearPhoto = () => {
    form.profile_photo = null;
    photoPreview.value = null;
    form.clear_photo = true;
};

// --- 6. LÓGICA DE REQUISITOS ---
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
watch(() => form.tenencia, (newTenencia) => {
    const nuevosReqFiltradosIds = requisitosFiltrados.value.map(r => r.id);
    form.requisitos_seleccionados = form.requisitos_seleccionados.filter(id => 
        nuevosReqFiltradosIds.includes(id)
    );
});

// --- 7. LÓGICA DE "NUEVO BARRIO" ---
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
    });
};

// --- 8. LÓGICA DE BAJA ---
const confirmarBajaModal = ref(false);
const originalEstado = props.afiliado.estado;

const submit = () => {
    if (form.estado === 'baja' && originalEstado !== 'baja' && !form.force_baja) {
        confirmarBajaModal.value = true;
        return;
    }
    form.post(route('afiliados.update', props.afiliado.id), {
        onError: (errors) => console.error("Errores:", errors),
        onSuccess: () => {
            if (form.profile_photo) clearPhoto();
            form.force_baja = false;
        }
    });
};

const submitForzandoBaja = () => {
    form.force_baja = true;
    confirmarBajaModal.value = false;
    submit();
};
</script>

<template>
    <AppLayout title="Editar Afiliado">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Editar Afiliado: {{ form.nombre_completo }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 md:p-8">
                    
                    <div v-if="form.hasErrors" class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-6" role="alert">
                       <p class="font-bold">Error</p>
                       <p v-if="form.errors.error_general">{{ form.errors.error_general }}</p>
                       <p v-else>Hubo errores en el formulario.</p>
                       <ul class="list-disc ml-5 text-sm"><li v-for="(error, key) in form.errors" :key="key" v-if="key !== 'error_general'">{{ error }}</li></ul>
                    </div>
                     <div v-if="$page.props.flash.confirmar_baja" class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 px-4 py-3 rounded mb-6" role="alert">
                         <p class="font-bold">Confirmación Requerida</p>
                         <p>{{ $page.props.flash.confirmar_baja }}</p>
                     </div>

                    <form @submit.prevent="submit" class="space-y-6">

                        <div class="col-span-6 sm:col-span-4">
                            <input ref="photoInput" type="file" class="hidden" @change="updatePhotoPreview" accept="image/*">
                            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">Foto del Afiliado</label>
                            <div class="mt-2 flex items-center gap-4">
                                <img v-if="photoPreview" :src="photoPreview" alt="Vista previa" class="w-20 h-20 rounded-full object-cover">
                                <img v-else-if="existingPhotoUrl && !form.clear_photo" :src="existingPhotoUrl" :alt="form.nombre_completo" class="w-20 h-20 rounded-full object-cover">
                                <span v-else class="block rounded-full w-20 h-20 bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                    <svg class="h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48"><path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                </span>
                                 <button @click.prevent="selectNewPhoto" type="button" class="bg-gray-200 dark:bg-gray-700 dark:text-gray-200 text-gray-700 py-2 px-4 rounded-md text-sm hover:bg-gray-300">
                                    {{ existingPhotoUrl || photoPreview ? 'Cambiar Foto' : 'Seleccionar Foto' }}
                                </button>
                                <button v-if="existingPhotoUrl && !form.clear_photo || photoPreview" @click.prevent="clearPhoto" type="button" class="text-sm text-red-600 hover:underline">
                                    Quitar Foto
                                </button>
                            </div>
                            <div v-if="form.errors.profile_photo" class="text-red-600 text-sm mt-1">{{ form.errors.profile_photo }}</div>
                        </div>

                        <div class="border-t dark:border-gray-700 pt-6 space-y-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Datos Personales</h3>
                            
                            <div v-if="isAdmin" class="p-3 bg-yellow-50 dark:bg-gray-900 border border-yellow-300 dark:border-gray-700 rounded-md">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" v-model="habilitarCamposProtegidos" class="rounded border-gray-300 text-yellow-600 shadow-sm focus:ring-yellow-500" />
                                    <span class="text-sm font-medium text-yellow-800 dark:text-yellow-300">Habilitar edición (Admin): CI y Fecha Afiliación</span>
                                </label>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="nombre_completo" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Nombre Completo *</label>
                                    <input id="nombre_completo" v-model="form.nombre_completo" type="text" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm" required />
                                    <div v-if="form.errors.nombre_completo" class="text-red-600 text-sm mt-1">{{ form.errors.nombre_completo }}</div>
                                </div>
                                <div>
                                    <label for="ci" class="block font-medium text-sm text-gray-700 dark:text-gray-300">CI (Protegido) *</label>
                                    <input id="ci" v-model="form.ci" type="text" 
                                           :disabled="!habilitarCamposProtegidos" 
                                           class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm"
                                           :class="!habilitarCamposProtegidos ? 'bg-gray-100 dark:bg-gray-900 cursor-not-allowed' : 'dark:bg-gray-700 dark:text-gray-200'"
                                           required />
                                    <div v-if="form.errors.ci" class="text-red-600 text-sm mt-1">{{ form.errors.ci }}</div>
                                </div>
                                <div>
                                    <label for="celular" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Celular</label>
                                    <input id="celular" v-model="form.celular" type="text" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm" />
                                </div>
                            </div>
                        </div>

                        <div class="border-t dark:border-gray-700 pt-6 space-y-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Domicilio</h3>
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="zona_id" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Barrio *</label>
                                    <div class="mt-1 flex items-center gap-2">
                                        <select id="zona_id" v-model="form.zona_id" class="flex-grow border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm" required>
                                            <option value="" disabled>Seleccione un barrio</option>
                                            <option v-for="zona in zonasLocales" :key="zona.id" :value="zona.id">{{ zona.nombre }}</option>
                                        </select>
                                        <button @click.prevent="showNuevaZonaModal = true" type="button" class="flex-shrink-0 px-3 py-2 bg-blue-500 text-white rounded-md text-sm hover:bg-blue-600">+</button>
                                    </div>
                                    <div v-if="form.errors.zona_id" class="text-red-600 text-sm mt-1">{{ form.errors.zona_id }}</div>
                                </div>
                                <div>
                                    <label for="direccion" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Calle *</label>
                                    <input id="direccion" v-model="form.direccion" type="text" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm" required />
                                    <div v-if="form.errors.direccion" class="text-red-600 text-sm mt-1">{{ form.errors.direccion }}</div>
                                </div>
                             </div>
                        </div>

                        <div class="border-t dark:border-gray-700 pt-6 space-y-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Datos de Afiliación</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="codigo" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Código Afiliado (Editable) *</label>
                                    <input id="codigo" v-model="form.codigo" type="text" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm" required />
                                    <div v-if="form.errors.codigo" class="text-red-600 text-sm mt-1">{{ form.errors.codigo }}</div>
                                </div>
                                <div>
                                    <label for="tipo" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Tipo *</label>
                                    <select id="tipo" v-model="form.tipo" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm">
                                        <option value="socio">Socio</option>
                                        <option value="usuario">Usuario</option>
                                    </select>
                                </div>
                                 <div>
                                    <label for="tenencia" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Tenencia *</label>
                                    <select id="tenencia" v-model="form.tenencia" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm">
                                        <option value="propietario">Propietario</option>
                                        <option value="compra_venta">Compra/Venta</option>
                                        <option value="posesion">Posesión</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="fecha_afiliacion" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Fecha Afiliación (Protegido)</label>
                                    <input id="fecha_afiliacion" v-model="form.fecha_afiliacion" type="date" 
                                           :disabled="!habilitarCamposProtegidos"
                                           class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm"
                                           :class="!habilitarCamposProtegidos ? 'bg-gray-100 dark:bg-gray-900 cursor-not-allowed' : 'dark:bg-gray-700 dark:text-gray-200'" />
                                    <div v-if="form.errors.fecha_afiliacion" class="text-red-600 text-sm mt-1">{{ form.errors.fecha_afiliacion }}</div>
                                </div>
                                <div>
                                    <label for="estado" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Estado Contractual</label>
                                    <select id="estado" v-model="form.estado" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm">
                                        <option value="activo">Activo</option>
                                        <option value="suspendido">Suspendido</option>
                                        <option value="baja">Baja</option>
                                    </select>
                                </div>
                                 <div>
                                    <label for="estado_servicio" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Estado Servicio</label>
                                    <select id="estado_servicio" v-model="form.estado_servicio" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm">
                                        <option value="Pendiente">Pendiente</option>
                                        <option value="activo">Activo</option>
                                        <option value="en_corte">En Corte</option>
                                        <option value="cortado">Cortado</option>
                                    </select>
                                     <div v-if="form.errors.estado_servicio" class="text-red-600 text-sm mt-1">{{ form.errors.estado_servicio }}</div>
                                </div>
                                <div>
                                    <label for="fecha_baja" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Fecha de Baja</label>
                                    <input id="fecha_baja" v-model="form.fecha_baja" type="date" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm" />
                                    <div v-if="form.errors.fecha_baja" class="text-red-600 text-sm mt-1">{{ form.errors.fecha_baja }}</div>
                                </div>
                                <div class="md:col-span-2 flex items-center gap-2 mt-2">
                                    <input id="adulto_mayor" v-model="form.adulto_mayor" type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" />
                                    <label for="adulto_mayor" class="font-medium text-sm text-gray-700 dark:text-gray-300">¿Es Adulto Mayor? (Aplica descuento)</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="border-t dark:border-gray-700 pt-6">
                             <h3 class="text-lg font-medium text-gray-900 dark:text-white">Requisitos Entregados</h3>
                             <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Actualice los documentos que el afiliado ha entregado.</p>
                             <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                 <template v-for="requisito in requisitosFiltrados" :key="requisito.id">
                                     <label v-if="
                                                requisito.es_para_todos ||
                                                (requisito.es_para_propietario && form.tenencia === 'propietario') ||
                                                (requisito.es_para_compra_venta && form.tenencia === 'compra_venta') ||
                                                (requisito.es_para_posesion && form.tenencia === 'posesion')
                                            "
                                            class="flex items-center gap-2 p-3 border dark:border-gray-700 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700">
                                         <input 
                                             type="checkbox" 
                                             :value="requisito.id" 
                                             v-model="form.requisitos_seleccionados"
                                             class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                         />
                                         <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ requisito.nombre }}</span>
                                     </label>
                                 </template>
                             </div>
                             <div v-if="requisitosFiltrados && requisitosFiltrados.length === 0 && form.tenencia" class="text-sm text-gray-500 italic mt-4">
                                 (No hay requisitos definidos para la tenencia "{{ form.tenencia }}")
                             </div>
                        </div>

                        <div class="border-t dark:border-gray-700 pt-6 space-y-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Observaciones (Uso Interno)</h3>
                            <div>
                                <label for="observacion_edit" class="sr-only">Observación</label>
                                <textarea id="observacion_edit" v-model="form.observacion" rows="4" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm" placeholder="Añadir notas internas sobre el afiliado..."></textarea>
                                <div v-if="form.errors.observacion" class="text-red-600 text-sm mt-1">{{ form.errors.observacion }}</div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-4 mt-8 pt-6 border-t dark:border-gray-700">
                            <Link :href="route('afiliados.index')" 
                                  class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded transition duration-150 ease-in-out">
                                Cancelar
                            </Link>
                            <button type="submit" :disabled="form.processing" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50 transition duration-150 ease-in-out">
                                {{ form.processing ? 'Actualizando...' : 'Actualizar Afiliado' }}
                            </button>
                        </div>
                    </form>
                    
                    <Modal :show="confirmarBajaModal" @close="confirmarBajaModal = false" max-width="md">
                         <div class="p-6 dark:bg-gray-800 dark:text-gray-200">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                                ¿Confirmar Baja?
                            </h2>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                ¿Está seguro de cambiar el estado de este afiliado a "Baja"? 
                                Las conexiones asociadas ya no se incluirán en nuevas lecturas.
                            </p>
                            <div class="mt-6 flex justify-end gap-4">
                                <button @click="confirmarBajaModal = false" 
                                        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded transition duration-150 ease-in-out">
                                    Cancelar
                                </button>
                                <button @click="submitForzandoBaja" 
                                        class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition duration-150 ease-in-out">
                                    Sí, dar de Baja
                                </button>
                            </div>
                        </div>
                    </Modal>

                    <Modal :show="showNuevaZonaModal" @close="showNuevaZonaModal = false" max-width="md">
                        <div class="p-6 dark:bg-gray-800 dark:text-gray-200">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Agregar Nuevo Barrio</h2>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">El barrio se añadirá a la lista y se seleccionará automáticamente.</p>
                            <form @submit.prevent="guardarNuevaZona" class="mt-4 space-y-4">
                                <div>
                                    <label for="nueva_zona_nombre_edit" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Nombre del Barrio *</label>
                                    <input id="nueva_zona_nombre_edit" v-model="nuevaZonaForm.nombre" type="text" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm" required />
                                    <div v-if="nuevaZonaForm.errors.nombre" class="text-red-600 text-sm mt-1">{{ nuevaZonaForm.errors.nombre }}</div>
                                </div>
                                <div class="flex justify-end gap-4">
                                    <button type="button" @click="showNuevaZonaModal = false" 
                                            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded transition duration-150 ease-in-out">
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

                </div>
            </div>
        </div>
    </AppLayout>
</template>