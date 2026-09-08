<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ViewCounter from '@/Components/ViewCounter.vue';

const props = defineProps({
    factura: Object,
    totalPagado: Number,
    saldoPendiente: Number,
    errors: Object,
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

const formatCurrency = (amount) => {
    return (parseFloat(amount) || 0).toFixed(2);
};

const estadoClass = (estado) => {
    switch (estado) {
        case 'impaga': return 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 border border-amber-200 dark:border-amber-700';
        case 'pagado': return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700';
        case 'anulada': return 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300 border border-rose-200 dark:border-rose-700';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300';
    }
};

const anularFactura = () => {
    if (confirm('¿Estás seguro de ANULAR esta factura? La lectura asociada volverá a estar pendiente.')) {
        router.post(route('facturas.anular', props.factura.id), {}, {
            preserveScroll: true,
        });
    }
};

const abrirImpresionFactura = () => {
    window.open(route('facturas.imprimir', props.factura.id), '_blank');
};
</script>

<template>
    <AppLayout :title="'Factura F-' + factura.id.toString().padStart(6, '0')">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight flex items-center gap-3">
                        <span class="p-2 bg-gradient-to-r from-cyan-500 to-blue-600 rounded-xl text-white shadow-md">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </span>
                        Factura de Servicio <span class="font-mono text-cyan-600 dark:text-cyan-400">#F-{{ factura.id.toString().padStart(6, '0') }}</span>
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Comprobante de consumo de agua potable e historial de pagos.</p>
                </div>
                
                <div class="flex items-center gap-2">
                    <button @click="abrirImpresionFactura" class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-medium text-sm transition shadow-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Imprimir Factura
                    </button>
                    
                    <Link v-if="$page.props.auth.user.role_names.includes('Administrador') || $page.props.auth.user.role_names.includes('Secretaria')" :href="route('facturas.index')" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 font-medium text-sm transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Volver al Listado
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Mensajes Flash -->
                <div v-if="page.props.flash.success" class="bg-emerald-50 dark:bg-emerald-900/30 border-l-4 border-emerald-500 text-emerald-800 dark:text-emerald-200 p-4 rounded-r-xl text-sm shadow-sm">
                    <p class="font-bold flex items-center gap-2">✅ Éxito</p>
                    <p>{{ page.props.flash.success }}</p>
                </div>
                <div v-if="page.props.flash.error || Object.keys(page.props.errors).length > 0" class="bg-rose-50 dark:bg-rose-900/30 border-l-4 border-rose-500 text-rose-800 dark:text-rose-200 p-4 rounded-r-xl text-sm shadow-sm">
                    <p class="font-bold flex items-center gap-2">🚨 Error</p>
                    <p>{{ page.props.flash.error || (errors ? errors.error_general : 'Ocurrió un error.') }}</p>
                </div>

                <!-- Banner Estado Financiero -->
                <div :class="saldoPendiente > 0.01 ? 'bg-gradient-to-r from-amber-500 via-orange-600 to-amber-700' : 'bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700'" class="rounded-2xl shadow-xl p-6 md:p-8 text-white flex flex-col md:flex-row items-center justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="px-3 py-1 bg-white/20 backdrop-blur-md rounded-full text-xs font-bold uppercase tracking-wider text-white">
                                Período: {{ factura.periodo }}
                            </span>
                            <span :class="estadoClass(factura.estado)" class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider">
                                {{ factura.estado }}
                            </span>
                        </div>
                        <h1 class="text-3xl font-black tracking-tight text-white mt-3 flex items-center gap-3">
                            N° F-{{ factura.id.toString().padStart(6, '0') }}
                        </h1>
                        <p class="text-xs text-white/80 mt-1">Emisión: {{ formatDate(factura.fecha_emision) }} | Vencimiento: {{ formatDate(factura.fecha_vencimiento) || 'N/A' }}</p>
                    </div>

                    <div class="flex items-center gap-6 bg-white/10 backdrop-blur-md p-4 rounded-2xl border border-white/20">
                        <div class="text-right">
                            <span class="text-xs text-white/80 block uppercase font-semibold">Total Facturado</span>
                            <span class="font-mono font-bold text-lg text-white">Bs {{ formatCurrency(factura.monto_total) }}</span>
                        </div>
                        <div class="h-8 w-px bg-white/20"></div>
                        <div class="text-right">
                            <span class="text-xs text-white/80 block uppercase font-semibold">Saldo Pendiente</span>
                            <span class="font-mono font-black text-2xl text-white">Bs {{ formatCurrency(saldoPendiente) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Cuadrícula Afiliado + Detalle Lectura -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <!-- Titular & Medidor -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-3 pb-4 mb-4 border-b border-gray-100 dark:border-gray-700">
                            <div class="p-2.5 bg-cyan-50 dark:bg-cyan-900/40 text-cyan-600 dark:text-cyan-400 rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white">Afiliado y Medidor</h3>
                        </div>
                        <dl class="space-y-3 text-sm">
                            <div class="flex justify-between py-1 border-b border-gray-50 dark:border-gray-700/50">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium">Afiliado:</dt>
                                <dd class="font-bold text-cyan-600 dark:text-cyan-400">{{ factura.conexion?.afiliado?.nombre_completo || 'N/A' }}</dd>
                            </div>
                            <div class="flex justify-between py-1 border-b border-gray-50 dark:border-gray-700/50">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium">CI Afiliado:</dt>
                                <dd class="font-bold text-gray-900 dark:text-gray-100 font-mono">{{ factura.conexion?.afiliado?.ci || '—' }}</dd>
                            </div>
                            <div class="flex justify-between py-1 border-b border-gray-50 dark:border-gray-700/50">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium">Código Medidor:</dt>
                                <dd class="font-mono font-bold text-gray-900 dark:text-gray-100">{{ factura.conexion?.codigo_medidor || 'N/A' }}</dd>
                            </div>
                            <div class="flex justify-between py-1">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium">Dirección:</dt>
                                <dd class="font-bold text-gray-900 dark:text-gray-100 text-right">{{ factura.conexion?.direccion || '—' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Detalle de Consumo m3 -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-3 pb-4 mb-4 border-b border-gray-100 dark:border-gray-700">
                            <div class="p-2.5 bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            </div>
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white">Desglose de Consumo</h3>
                        </div>
                        <dl class="space-y-3 text-sm">
                            <div class="flex justify-between py-1 border-b border-gray-50 dark:border-gray-700/50">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium">Volumen Consumido:</dt>
                                <dd class="font-mono font-bold text-cyan-600 dark:text-cyan-400 text-base">{{ formatCurrency(factura.consumo_m3) }} m³</dd>
                            </div>
                            <div v-if="factura.lectura" class="flex justify-between py-1 border-b border-gray-50 dark:border-gray-700/50">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium">Lectura Ant. / Act.:</dt>
                                <dd class="font-mono text-gray-700 dark:text-gray-300">{{ factura.lectura.lectura_anterior }} m³ &rarr; {{ factura.lectura.lectura_actual }} m³</dd>
                            </div>
                            <div class="flex justify-between py-1">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium">Monto Total Facturado:</dt>
                                <dd class="font-mono font-bold text-gray-900 dark:text-white text-base">Bs {{ formatCurrency(factura.monto_total) }}</dd>
                            </div>
                        </dl>
                    </div>

                </div>

                <!-- Historial de Pagos -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-lg text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            Historial de Pagos Realizados
                        </h3>
                        <Link v-if="factura.estado === 'impaga' && ($page.props.auth.user.role_names.includes('Administrador') || $page.props.auth.user.role_names.includes('Secretaria'))" :href="route('pagos.create', { factura_id: factura.id })" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition shadow-md">
                            + Registrar Pago
                        </Link>
                    </div>

                    <div v-if="!factura.pagos || factura.pagos.length === 0" class="text-sm text-gray-400 dark:text-gray-500 italic p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl text-center">
                        No se han registrado pagos para esta factura.
                    </div>
                    <div v-else class="space-y-3">
                        <div v-for="pago in factura.pagos" :key="pago.id" class="p-4 bg-emerald-50/50 dark:bg-emerald-950/20 rounded-xl border border-emerald-100 dark:border-emerald-800/40 flex items-center justify-between">
                            <div>
                                <div class="font-bold text-emerald-900 dark:text-emerald-200 text-sm">Fecha: {{ formatDate(pago.fecha_pago) }} — Forma de Pago: {{ pago.forma_pago }}</div>
                                <div v-if="pago.referencia" class="text-xs text-emerald-700 dark:text-emerald-400 mt-0.5">Ref: {{ pago.referencia }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">Registrado por: {{ pago.usuarioRegistrado?.name || 'N/A' }}</div>
                            </div>
                            <div class="font-mono font-bold text-lg text-emerald-700 dark:text-emerald-300">
                                Bs {{ formatCurrency(pago.monto_pagado) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button v-if="factura.estado === 'impaga' && ($page.props.auth.user.role_names.includes('Administrador') || $page.props.auth.user.role_names.includes('Secretaria'))" @click="anularFactura" class="px-4 py-2 bg-rose-100 text-rose-700 hover:bg-rose-200 dark:bg-rose-900/40 dark:text-rose-300 font-bold rounded-xl text-xs transition">
                        Anular Factura
                    </button>
                    <div v-else></div>

                    <div class="flex gap-3">
                        <button @click="abrirImpresionFactura" class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl text-xs transition shadow-md flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            Imprimir Factura
                        </button>
                    </div>
                </div>

            </div>
        </div>

        <ViewCounter />
    </AppLayout>
</template>