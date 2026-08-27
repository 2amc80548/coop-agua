<script setup>
/**
 * StatusBadge.vue — Componente reutilizable de etiqueta de estado
 *
 * Uso:
 *   <StatusBadge :status="factura.estado" />
 *   <StatusBadge :status="factura.estado_siat" type="siat" />
 *   <StatusBadge :status="lectura.estado" type="lectura" />
 *   <StatusBadge :status="afiliado.estado" type="afiliado" />
 *
 * Props:
 *   status  — El valor del estado (string)
 *   type    — Qué mapa de colores usar: 'factura' (default) | 'siat' | 'lectura' | 'afiliado'
 */
const props = defineProps({
    status: {
        type: String,
        default: '',
    },
    type: {
        type: String,
        default: 'factura',
        validator: (v) => ['factura', 'siat', 'lectura', 'afiliado', 'conexion'].includes(v),
    },
});

// Mapa de colores por tipo de estado
const colorMaps = {
    factura: {
        impaga:   'bg-red-100 text-red-700 ring-red-300',
        pagado:   'bg-green-100 text-green-700 ring-green-300',
        anulada:  'bg-gray-100 text-gray-500 ring-gray-300',
    },
    siat: {
        PENDIENTE:    'bg-yellow-100 text-yellow-700 ring-yellow-300',
        EN_PROCESO:   'bg-blue-100 text-blue-600 ring-blue-300',
        VALIDADA:     'bg-green-100 text-green-700 ring-green-300',
        RECHAZADA:    'bg-red-100 text-red-700 ring-red-300',
        ANULADA_SIAT: 'bg-gray-100 text-gray-500 ring-gray-300',
        OBSERVADA:    'bg-orange-100 text-orange-700 ring-orange-300',
        CONTINGENCIA: 'bg-purple-100 text-purple-700 ring-purple-300',
    },
    lectura: {
        pendiente:   'bg-yellow-100 text-yellow-700 ring-yellow-300',
        facturado:   'bg-green-100 text-green-700 ring-green-300',
        anulado:     'bg-gray-100 text-gray-500 ring-gray-300',
    },
    afiliado: {
        activo:     'bg-green-100 text-green-700 ring-green-300',
        inactivo:   'bg-gray-100 text-gray-500 ring-gray-300',
        suspendido: 'bg-red-100 text-red-700 ring-red-300',
        moroso:     'bg-orange-100 text-orange-700 ring-orange-300',
    },
    conexion: {
        activo:     'bg-green-100 text-green-700 ring-green-300',
        suspendido: 'bg-red-100 text-red-700 ring-red-300',
        eliminado:  'bg-gray-100 text-gray-500 ring-gray-300',
    },
};

// Color de fallback si el estado no existe en el mapa
const FALLBACK_COLOR = 'bg-slate-100 text-slate-500 ring-slate-200';

const badgeClass = computed(() => {
    const map = colorMaps[props.type] ?? colorMaps.factura;
    return map[props.status] ?? FALLBACK_COLOR;
});

const label = computed(() => {
    if (!props.status) return '—';
    // Capitalizar primera letra para presentación
    return props.status.charAt(0).toUpperCase() + props.status.slice(1).toLowerCase();
});
</script>

<script>
import { computed } from 'vue';
export default { name: 'StatusBadge' };
</script>

<template>
    <span
        :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ring-1 ring-inset', badgeClass]"
        :title="status"
    >
        {{ label }}
    </span>
</template>
