<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ViewCounter from '@/Components/ViewCounter.vue';

const props = defineProps({
    pagos: Object,   // Paginado
    filters: Object,
    error: String,
});

const formatDate = (dateString) => {
    if (!dateString) return '—';
    try {
        const date = new Date(dateString);
        date.setMinutes(date.getMinutes() + date.getTimezoneOffset());
        return date.toLocaleDateString('es-BO', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });
    } catch {
        return dateString;
    }
};

const formatCurrency = (amount) => (parseFloat(amount) || 0).toFixed(2);
</script>

<template>
    <AppLayout title="Historial de Pagos">
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-cyan-600 dark:text-cyan-300">
                        Panel personal
                    </p>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Mi Historial de Pagos
                    </h2>
                    <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400">
                        Revise todos los pagos que ha realizado, con fechas, montos y facturas asociadas.
                    </p>
                </div>
            </div>
        </template>

        <div class="py-6 md:py-10">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Mensaje de error -->
                <div
                    v-if="error"
                    class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-100 px-4 py-3 rounded-md shadow-sm text-sm"
                    role="alert"
                >
                    <p class="font-semibold">Ocurrió un error</p>
                    <p class="mt-1">{{ error }}</p>
                </div>

                <!-- Resumen / info -->
                <section
                    class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl shadow-sm px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2"
                >
                    <div class="text-xs md:text-sm text-gray-600 dark:text-gray-300">
                        <p class="font-semibold">
                            Historial de pagos
                        </p>
                        <p class="mt-0.5 text-[11px] md:text-xs">
                            Los pagos se muestran ordenados del más reciente al más antiguo.
                        </p>
                    </div>
                    <div
                        v-if="pagos.total"
                        class="text-xs md:text-sm text-gray-600 dark:text-gray-300"
                    >
                        <span class="hidden sm:inline">Total de pagos registrados:</span>
                        <span class="font-semibold">
                            {{ pagos.total }}
                        </span>
                    </div>
                </section>

                <!-- Vista móvil: tarjetas -->
                <section class="block md:hidden space-y-3">
                    <div
                        v-if="!pagos.data.length"
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 text-center text-sm text-gray-500 dark:text-gray-300"
                    >
                        No se encontraron pagos registrados.
                    </div>

                    <div
                        v-for="p in pagos.data"
                        :key="p.id"
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 text-sm"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <p class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Pago
                                </p>
                                <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">
                                    Bs {{ formatCurrency(p.monto_pagado) }}
                                </p>
                            </div>
                            <div class="text-right text-[11px] text-gray-500 dark:text-gray-400">
                                <p class="font-semibold">
                                    {{ formatDate(p.fecha_pago) }}
                                </p>
                                <p class="mt-0.5">
                                    {{ p.forma_pago || 'Sin especificar' }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-3 border-t border-gray-100 dark:border-gray-700 pt-2">
                            <p class="text-xs text-gray-600 dark:text-gray-300">
                                Factura:
                                <Link
                                    :href="route('facturas.show', p.factura_id)"
                                    class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline"
                                >
                                    F-{{ p.factura_id.toString().padStart(6, '0') }}
                                </Link>
                            </p>
                            <p
                                v-if="p.factura?.periodo"
                                class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5"
                            >
                                Período:
                                <span class="font-semibold">{{ p.factura.periodo }}</span>
                            </p>
                            <p
                                v-if="p.usuarioRegistrado?.name"
                                class="text-[11px] text-gray-500 dark:text-gray-400 mt-1"
                            >
                                Cajero(a):
                                <span class="font-semibold">
                                    {{ p.usuarioRegistrado.name }}
                                </span>
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Vista escritorio: tabla -->
                <section class="hidden md:block">
                    <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700/80">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                                    >
                                        Fecha de pago
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                                    >
                                        Factura / Período
                                    </th>
                                    <th
                                        class="px-4 py-3 text-right text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                                    >
                                        Monto pagado
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                                    >
                                        Forma de pago
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                                    >
                                        Cajero
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="!pagos.data.length">
                                    <td
                                        colspan="5"
                                        class="py-4 px-4 text-center text-sm text-gray-500 dark:text-gray-300"
                                    >
                                        No se encontraron pagos registrados.
                                    </td>
                                </tr>

                                <tr
                                    v-for="p in pagos.data"
                                    :key="p.id"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700/60 transition"
                                >
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">
                                        {{ formatDate(p.fecha_pago) }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex flex-col">
                                            <Link
                                                :href="route('facturas.show', p.factura_id)"
                                                class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline"
                                            >
                                                F-{{ p.factura_id.toString().padStart(6, '0') }}
                                            </Link>
                                            <span
                                                class="text-[11px] text-gray-500 dark:text-gray-400"
                                            >
                                                Per: {{ p.factura?.periodo || 'N/D' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td
                                        class="px-4 py-3 whitespace-nowrap text-right font-bold text-emerald-700 dark:text-emerald-400"
                                    >
                                        Bs {{ formatCurrency(p.monto_pagado) }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span
                                            class="px-2 inline-flex text-[11px] leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200"
                                        >
                                            {{ p.forma_pago || 'Sin especificar' }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-4 py-3 whitespace-nowrap text-[11px] text-gray-500 dark:text-gray-400"
                                    >
                                        {{ p.usuarioRegistrado?.name || '—' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Paginación -->
                <div
                    v-if="pagos.links.length > 3"
                    class="mt-4 md:mt-6 flex flex-col md:flex-row md:items-center md:justify-between gap-3 text-xs md:text-sm"
                >
                    <span class="text-gray-600 dark:text-gray-300">
                        Mostrando
                        <span class="font-semibold">{{ pagos.from }}</span>
                        a
                        <span class="font-semibold">{{ pagos.to }}</span>
                        de
                        <span class="font-semibold">{{ pagos.total }}</span>
                        pagos
                    </span>

                    <div class="flex flex-wrap gap-1">
                        <Link
                            v-for="(link, index) in pagos.links"
                            :key="index"
                            :href="link.url ?? '#'"
                            v-html="link.label"
                            preserve-scroll
                            preserve-state
                            :class="[
                                'px-3 py-1 rounded-md border text-xs md:text-sm transition',
                                link.active
                                    ? 'bg-cyan-600 border-cyan-600 text-white'
                                    : link.url
                                        ? 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700'
                                        : 'border-transparent text-gray-400 dark:text-gray-500 cursor-default',
                            ]"
                        />
                    </div>
                </div>
            </div>

            <ViewCounter />
        </div>
    </AppLayout>
</template>
