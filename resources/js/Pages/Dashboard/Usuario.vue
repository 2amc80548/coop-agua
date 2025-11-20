<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ViewCounter from '@/Components/ViewCounter.vue';

// Gráficos (opcional, si ya los tienes creados)
import BaseLineChart from '@/Components/Charts/BaseLineChart.vue';
import BaseDoughnutChart from '@/Components/Charts/BaseDoughnutChart.vue';

// --- 1. PROPS ---
const props = defineProps({
    summary: {
        type: Object,
        required: false,
    }, 
    error: {
        type: String,
        required: false,
    },
});

// --- 2. HELPERS VISUALES ---
const formatCurrency = (amount) => {
    const n = parseFloat(amount) || 0;
    return n.toFixed(2);
};

const formatNumber = (value) =>
    new Intl.NumberFormat('es-BO').format(Number(value || 0));

const estadoServicioClass = computed(() => {
    if (!props.summary) return 'bg-gray-100 text-gray-800';
    switch (props.summary.estado_servicio) {
        case 'activo':
            return 'bg-green-50 text-green-800 dark:bg-green-900/40 dark:text-green-200';
        case 'en_corte':
            return 'bg-yellow-50 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200';
        case 'cortado':
            return 'bg-red-50 text-red-800 dark:bg-red-900/40 dark:text-red-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
    }
});

const estadoServicioTexto = computed(() => {
    if (!props.summary) return 'Desconocido';
    switch (props.summary.estado_servicio) {
        case 'activo':   return 'Servicio Activo';
        case 'en_corte': return 'Servicio en Proceso de Corte';
        case 'cortado':  return 'Servicio Cortado';
        default:         return 'Desconocido';
    }
});

// --- 3. DATA PARA GRÁFICOS ---
// Historial de consumo: [{ periodo, consumo_m3 }]
const consumoLabels = computed(() =>
    (props.summary?.historial_consumo || []).map((i) => i.periodo),
);
const consumoData = computed(() =>
    (props.summary?.historial_consumo || []).map((i) => Number(i.consumo_m3 || 0)),
);

// Facturas por estado: [{ estado, cantidad }]
const facturasEstadoLabels = computed(() =>
    (props.summary?.facturas_por_estado || []).map((i) => i.estado || 'Sin estado'),
);
const facturasEstadoData = computed(() =>
    (props.summary?.facturas_por_estado || []).map((i) => Number(i.cantidad || 0)),
);

// Información de riesgo (solo visual)
const riesgoTexto = computed(() => {
    if (!props.summary) return 'Sin información';
    const deuda = Number(props.summary.deuda_total || 0);
    const pendientes = Number(props.summary.facturas_pendientes_count || 0);

    if (deuda === 0 && pendientes === 0) return 'Sin riesgo de corte';
    if (pendientes === 1 && deuda > 0)   return 'Riesgo bajo (1 factura pendiente)';
    if (pendientes === 2)                return 'Riesgo medio (2 facturas pendientes)';
    if (pendientes >= 3)                 return 'Riesgo alto (3 o más facturas)';
    return 'Riesgo moderado';
});

const riesgoClass = computed(() => {
    if (!props.summary) return 'bg-gray-100 text-gray-800';
    const pendientes = Number(props.summary.facturas_pendientes_count || 0);
    if (pendientes === 0)  return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200';
    if (pendientes === 1)  return 'bg-amber-50 text-amber-700 dark:bg-amber-900/40 dark:text-amber-200';
    if (pendientes >= 2)   return 'bg-red-50 text-red-700 dark:bg-red-900/40 dark:text-red-200';
    return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
});
</script>

<template>
    <AppLayout title="Mi Resumen">
        <Head title="Mi Resumen" />

        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-cyan-600 dark:text-cyan-300">
                        Panel personal
                    </p>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Bienvenido, {{ summary?.afiliado_nombre || 'Usuario' }}
                    </h2>
                    <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400">
                        Aquí puede ver el estado de su servicio, su deuda y su consumo reciente.
                    </p>
                </div>
            </div>
        </template>

        <div class="py-6 md:py-10">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- ERROR -->
                <div
                    v-if="error"
                    class="bg-red-50 dark:bg-red-900/30 border-l-4 border-red-500 text-red-800 dark:text-red-200 p-4 rounded-md shadow-sm"
                    role="alert"
                >
                    <p class="font-bold text-sm">Error de vinculación</p>
                    <p class="text-xs md:text-sm mt-1">{{ error }}</p>
                </div>

                <div v-if="summary" class="space-y-6">

                    <!-- ESTADO DEL SERVICIO -->
                    <section
                        class="relative overflow-hidden rounded-2xl shadow-md border border-cyan-100 dark:border-gray-700 bg-gradient-to-r from-cyan-500/90 to-cyan-700 text-white"
                    >
                        <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full" />
                        <div class="absolute -right-20 top-12 w-48 h-48 bg-white/5 rounded-full" />

                        <div class="relative p-6 md:p-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div>
                                <p class="text-[11px] uppercase tracking-wide text-cyan-100">
                                    Estado de su servicio
                                </p>
                                <h3 class="text-2xl md:text-3xl font-bold mt-1">
                                    {{ estadoServicioTexto }}
                                </h3>
                                <p class="mt-2 text-xs md:text-sm text-cyan-100">
                                    Código de Afiliado:
                                    <span class="font-semibold">
                                        {{ summary.afiliado_codigo }}
                                    </span>
                                </p>
                            </div>

                            <div class="space-y-2 text-xs md:text-sm w-full md:w-64">
                                <div
                                    class="rounded-xl px-3 py-2 flex items-center gap-2 text-gray-900 dark:text-gray-50"
                                    :class="riesgoClass"
                                >
                                    <span class="text-lg">
                                        ⚠️
                                    </span>
                                    <div>
                                        <p class="font-semibold text-[12px]">
                                            Nivel de riesgo
                                        </p>
                                        <p class="text-[11px] opacity-90">
                                            {{ riesgoTexto }}
                                        </p>
                                    </div>
                                </div>
                                <p class="text-[11px] text-cyan-100">
                                    Si tiene dudas, comuníquese con la oficina de la Asociación
                                    o revise sus facturas en el apartado
                                    <span class="font-semibold">"Mi Cuenta"</span>.
                                </p>
                            </div>
                        </div>
                    </section>

                    <!-- TARJETAS RESUMEN -->
                    <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5 text-xs md:text-sm">

                        <!-- Deuda total -->
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-2xl p-5 border border-red-100 dark:border-red-700/40">
                            <h4 class="text-[11px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Deuda total
                            </h4>
                            <p class="mt-3 text-2xl md:text-3xl font-bold text-red-600 dark:text-red-400">
                                Bs {{ formatCurrency(summary.deuda_total) }}
                            </p>
                            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                Corresponde a todas sus facturas con saldo pendiente.
                            </p>
                            <Link
                                :href="route('mi.cuenta')"
                                class="mt-4 inline-flex items-center text-[11px] font-semibold text-blue-600 dark:text-blue-400 hover:underline"
                            >
                                Ver facturas pendientes
                                <span class="ml-1">&rarr;</span>
                            </Link>
                        </div>

                        <!-- Facturas pendientes -->
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-2xl p-5 border border-amber-100 dark:border-amber-700/40">
                            <h4 class="text-[11px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Facturas pendientes
                            </h4>
                            <p class="mt-3 text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">
                                {{ formatNumber(summary.facturas_pendientes_count) }}
                            </p>
                            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                Facturas con estado <span class="font-semibold">'impaga'</span>.
                            </p>
                        </div>

                        <!-- Último consumo -->
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-2xl p-5 border border-cyan-100 dark:border-cyan-700/40">
                            <h4 class="text-[11px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Último consumo registrado
                            </h4>
                            <p class="mt-3 text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">
                                {{ formatCurrency(summary.ultimo_consumo_m3) }} m³
                            </p>
                            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                Período:
                                <span class="font-semibold">
                                    {{ summary.ultimo_periodo || 'No disponible' }}
                                </span>
                            </p>
                        </div>

                        <!-- Lecturas / info adicional -->
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-2xl p-5 border border-indigo-100 dark:border-indigo-700/40">
                            <h4 class="text-[11px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Información general
                            </h4>
                            <ul class="mt-3 space-y-1 text-[11px] text-gray-600 dark:text-gray-300">
                                <li>
                                    • Conexiones asociadas:
                                    <span class="font-semibold">
                                        {{ formatNumber(summary.conexiones_count ?? 1) }}
                                    </span>
                                </li>
                                <li v-if="summary.ult_pago_fecha || summary.ult_pago_monto">
                                    • Último pago:
                                    <span class="font-semibold">
                                        {{ summary.ult_pago_fecha || '' }}
                                    </span>
                                    <span v-if="summary.ult_pago_monto">
                                        — Bs {{ formatCurrency(summary.ult_pago_monto) }}
                                    </span>
                                </li>
                                <li v-else>
                                    • No se encontraron pagos recientes.
                                </li>
                            </ul>
                        </div>

                    </section>

                    <!-- GRÁFICOS PERSONALES -->
                    <section class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-5 text-xs md:text-sm">
                        <!-- Historial de consumo -->
                        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-cyan-100 dark:border-gray-700 p-4 md:p-5">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="font-semibold text-gray-800 dark:text-gray-100">
                                    Historial de consumo (m³)
                                </h3>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                    Vista de sus últimos períodos de lectura
                                </p>
                            </div>
                            <div v-if="consumoLabels.length" class="h-64">
                                <BaseLineChart
                                    :labels="consumoLabels"
                                    :datasets="[
                                        { label: 'Consumo (m³)', data: consumoData }
                                    ]"
                                />
                            </div>
                            <p v-else class="text-xs text-gray-500 dark:text-gray-400">
                                Aún no hay historial de consumo suficiente para mostrar un gráfico.
                            </p>
                        </div>

                        <!-- Estado de sus facturas -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-amber-100 dark:border-gray-700 p-4 md:p-5">
                            <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-3">
                                Estado de sus facturas
                            </h3>
                            <div v-if="facturasEstadoLabels.length" class="h-64">
                                <BaseDoughnutChart
                                    :labels="facturasEstadoLabels"
                                    :data="facturasEstadoData"
                                />
                            </div>
                            <p v-else class="text-xs text-gray-500 dark:text-gray-400">
                                No hay información suficiente de facturas para mostrar el gráfico.
                            </p>
                        </div>
                    </section>

                    <!-- ACCIONES RÁPIDAS -->
                    <section class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                            Acciones rápidas
                        </h3>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <Link
                                :href="route('mi.cuenta')"
                                class="w-full sm:w-auto text-center px-6 py-3 bg-blue-600 text-white text-sm font-semibold rounded-md shadow hover:bg-blue-700 transition"
                            >
                                Ver mis facturas
                            </Link>
                            <Link
                                :href="route('pagos.mihistorial')"
                                class="w-full sm:w-auto text-center px-6 py-3 bg-emerald-600 text-white text-sm font-semibold rounded-md shadow hover:bg-emerald-700 transition"
                            >
                                Ver historial de pagos
                            </Link>
                            <Link
                                :href="route('profile.show')"
                                class="w-full sm:w-auto text-center px-6 py-3 bg-gray-500 text-white text-sm font-semibold rounded-md shadow hover:bg-gray-600 transition"
                            >
                                Editar mi perfil
                            </Link>
                        </div>
                    </section>

                </div>
            </div>

            <!-- CONTADOR DE VISITAS -->
            <ViewCounter />
        </div>
    </AppLayout>
</template>
