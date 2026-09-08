<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';
import ViewCounter from '@/Components/ViewCounter.vue';

const props = defineProps({
    lectura: Object,
});

const page = usePage();

const formatDate = (dateString) => {
    if (!dateString) return '—';
    try {
        const date = new Date(dateString);
        date.setMinutes(date.getMinutes() + date.getTimezoneOffset());
        return date.toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' });
    } catch { return dateString; }
};

const formatCurrency = (value) => {
    const numberValue = Number(value) || 0; 
    return numberValue.toFixed(2);
};

const consumoCalculado = computed(() => {
    const anterior = parseFloat(props.lectura.lectura_anterior) || 0;
    const actual = parseFloat(props.lectura.lectura_actual) || 0;
    if (!isNaN(actual) && !isNaN(anterior)) {
      return (actual - anterior).toFixed(2);
    }
    return '0.00';
});

const confirmDelete = (id) => {
    if (confirm('¿Estás seguro de eliminar esta lectura? Esta acción no se puede deshacer.')) {
        router.delete(route('lecturas.destroy', id), {
            preserveScroll: true,
            onError: (errors) => {
               const message = errors.error_general || 'No se pudo eliminar la lectura.';
               alert(`Error: ${message}`);
            },
        });
    }
};

const abrirAviso = (id) => {
    const url = route('lecturas.aviso', id);
    window.open(url, '_blank');
};
</script>

<template>
    <AppLayout title="Detalle de Lectura">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight flex items-center gap-3">
                        <span class="p-2 bg-gradient-to-r from-cyan-500 to-blue-600 rounded-xl text-white shadow-md">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </span>
                        Detalle de Lectura — Período <span class="text-cyan-600 dark:text-cyan-400 font-mono">{{ lectura.periodo }}</span>
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Consumo de agua registrado, estado de facturación e imprisión de avisos de cobranza.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="abrirAviso(lectura.id)" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-medium text-sm transition shadow-md" title="Imprimir Aviso de Cobranza">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Imprimir Aviso
                    </button>
                    <Link :href="route('lecturas.index')" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 font-medium text-sm transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Volver a Lecturas
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Tarjeta Principal de Consumo -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="bg-gradient-to-r from-blue-900 via-cyan-900 to-indigo-900 p-6 md:p-8 text-white flex flex-col md:flex-row items-center justify-between gap-6">
                        <div class="flex items-center gap-6">
                            <div class="w-24 h-24 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 flex flex-col items-center justify-center text-center">
                                <span class="text-xs uppercase tracking-widest text-cyan-300 font-bold">Consumo</span>
                                <span class="text-2xl font-black font-mono text-white">{{ consumoCalculado }}</span>
                                <span class="text-xs text-cyan-200 font-semibold">m³</span>
                            </div>
                            <div>
                                <div class="text-xs uppercase tracking-widest text-cyan-300 font-bold">Medidor: {{ lectura.conexion?.codigo_medidor }}</div>
                                <h1 class="text-2xl font-black tracking-tight text-white mt-1">{{ lectura.conexion?.afiliado?.nombre_completo || 'Sin Afiliado' }}</h1>
                                <div class="text-xs text-gray-300 mt-1">CI: {{ lectura.conexion?.afiliado?.ci || '—' }} | Período: <strong class="text-white">{{ lectura.periodo }}</strong></div>
                            </div>
                        </div>

                        <div class="flex flex-col items-end gap-2">
                            <span :class="lectura.estado === 'pendiente' ? 'bg-amber-400 text-amber-950 font-black' : 'bg-emerald-400 text-emerald-950 font-black'" class="px-4 py-1.5 rounded-full text-xs uppercase tracking-wider shadow-md">
                                Estado: {{ lectura.estado }}
                            </span>
                            <div class="text-xs text-gray-300 mt-1">Lectura tomada el: <strong class="text-white">{{ formatDate(lectura.fecha_lectura) }}</strong></div>
                        </div>
                    </div>
                </div>

                <!-- Cuadrícula: Conexión + Desglose Lectura -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <!-- Ficha 1: Conexión y Titular -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-3 pb-4 mb-4 border-b border-gray-100 dark:border-gray-700">
                            <div class="p-2.5 bg-cyan-50 dark:bg-cyan-900/40 text-cyan-600 dark:text-cyan-400 rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white">Información de Conexión</h3>
                        </div>
                        <dl class="space-y-3 text-sm">
                            <div class="flex justify-between py-1 border-b border-gray-50 dark:border-gray-700/50">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium">Titular:</dt>
                                <dd class="font-bold text-cyan-600 dark:text-cyan-400">
                                    <Link v-if="lectura.conexion?.afiliado" :href="route('afiliados.show', lectura.conexion.afiliado.id)" class="hover:underline">
                                        {{ lectura.conexion.afiliado.nombre_completo }}
                                    </Link>
                                    <span v-else>N/A</span>
                                </dd>
                            </div>
                            <div class="flex justify-between py-1 border-b border-gray-50 dark:border-gray-700/50">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium">Código Medidor:</dt>
                                <dd class="font-mono font-bold text-gray-900 dark:text-gray-100">{{ lectura.conexion?.codigo_medidor }}</dd>
                            </div>
                            <div class="flex justify-between py-1 border-b border-gray-50 dark:border-gray-700/50">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium">Dirección:</dt>
                                <dd class="font-bold text-gray-900 dark:text-gray-100 text-right">{{ lectura.conexion?.direccion }}</dd>
                            </div>
                            <div class="flex justify-between py-1">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium">Zona:</dt>
                                <dd class="font-bold text-gray-900 dark:text-gray-100">{{ lectura.conexion?.zona?.nombre || 'N/A' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Ficha 2: Detalle Métrico de Lectura -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-3 pb-4 mb-4 border-b border-gray-100 dark:border-gray-700">
                            <div class="p-2.5 bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            </div>
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white">Valores de Lectura</h3>
                        </div>
                        <dl class="space-y-3 text-sm">
                            <div class="flex justify-between py-1 border-b border-gray-50 dark:border-gray-700/50">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium">Lectura Anterior:</dt>
                                <dd class="font-mono font-bold text-gray-900 dark:text-gray-100">{{ lectura.lectura_anterior }} m³</dd>
                            </div>
                            <div class="flex justify-between py-1 border-b border-gray-50 dark:border-gray-700/50">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium">Lectura Actual:</dt>
                                <dd class="font-mono font-bold text-gray-900 dark:text-gray-100">{{ lectura.lectura_actual }} m³</dd>
                            </div>
                            <div class="flex justify-between py-1 border-b border-gray-50 dark:border-gray-700/50">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium">Total Consumido:</dt>
                                <dd class="font-mono font-bold text-cyan-600 dark:text-cyan-400 text-base">{{ consumoCalculado }} m³</dd>
                            </div>
                            <div class="flex justify-between py-1">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium">Registrado Por:</dt>
                                <dd class="font-bold text-gray-900 dark:text-gray-100">{{ lectura.usuarioRegistrado?.name || 'Sistema' }}</dd>
                            </div>
                        </dl>
                    </div>

                </div>

                <!-- Factura Asociada -->
                <div v-if="lectura.factura" class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-emerald-200 dark:border-emerald-800/40">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-lg text-emerald-900 dark:text-emerald-300 flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            Factura Asociada
                        </h3>
                        <Link :href="route('facturas.show', lectura.factura.id)" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition">
                            Ver Factura Completa &rarr;
                        </Link>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm bg-emerald-50 dark:bg-emerald-950/30 p-4 rounded-xl">
                        <div>
                            <span class="text-xs text-emerald-700 dark:text-emerald-400 block font-semibold">N° Factura</span>
                            <span class="font-mono font-bold text-emerald-900 dark:text-emerald-100 text-base">F-{{ lectura.factura.id.toString().padStart(6, '0') }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-emerald-700 dark:text-emerald-400 block font-semibold">Monto Total</span>
                            <span class="font-bold text-emerald-900 dark:text-emerald-100 text-base">Bs {{ formatCurrency(lectura.factura.monto_total) }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-emerald-700 dark:text-emerald-400 block font-semibold">Estado Factura</span>
                            <span class="font-bold capitalize text-emerald-800 dark:text-emerald-200">{{ lectura.factura.estado }}</span>
                        </div>
                    </div>
                </div>
                <div v-else class="bg-amber-50 dark:bg-amber-900/20 p-4 rounded-2xl border border-amber-200 dark:border-amber-700/40 text-amber-900 dark:text-amber-300 text-sm font-semibold flex items-center justify-between">
                    <span>Esta lectura está <strong class="uppercase">pendiente</strong> de facturación.</span>
                    <Link :href="route('facturacion.generar.show')" class="px-3 py-1 bg-amber-200 dark:bg-amber-800 text-amber-950 dark:text-amber-100 rounded-lg text-xs font-bold hover:bg-amber-300 transition">
                        Generar Facturas &rarr;
                    </Link>
                </div>

                <!-- Botones de Gestión -->
                <div v-if="lectura.estado === 'pendiente' && $page.props.auth.user.role_names.includes('Administrador')" class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <Link :href="route('lecturas.edit', lectura.id)" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-xl text-xs transition">
                        Editar Lectura
                    </Link>
                    <button @click="confirmDelete(lectura.id)" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs transition">
                        Eliminar Lectura
                    </button>
                </div>

            </div>
        </div>

        <ViewCounter />
    </AppLayout>
</template>