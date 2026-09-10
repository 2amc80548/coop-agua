<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import ViewCounter from '@/Components/ViewCounter.vue';

const form = useForm({
  vigente_desde: new Date().toISOString().slice(0,10),
  vigente_hasta: '',
  activo: true,
  tipo_conexion: 'domiciliaria',

  min_m3: 7,
  min_monto: 24.50,
  precio_m3: 3.50,
  bloque2_limite_m3: 25,
  bloque2_precio_m3: 4.50,
  bloque3_precio_m3: 6.00,

  descuento_adulto_mayor_pct: 20.00,
  descuento_ley_m3_limite: 15,
  cargo_fijo_mantenimiento: 2.00,

  afiliacion_socio_monto: 200.00,
  afiliacion_usuario_monto: 0.00,
  multa_corte_monto: 30.00,
  cisterna_10k_monto: 80.00,

  notas: ''
});

// Simulador en vivo
const simConsumo = ref(18);
const simAdultoMayor = ref(true);

const simResultado = computed(() => {
    const c = Math.max(0, parseFloat(simConsumo.value) || 0);
    const minM3 = parseInt(form.min_m3) || 0;
    const minMonto = parseFloat(form.min_monto) || 0;
    const precioB1 = parseFloat(form.precio_m3) || 0;
    const b2Lim = parseInt(form.bloque2_limite_m3) || 25;
    const precioB2 = parseFloat(form.bloque2_precio_m3) || precioB1;
    const precioB3 = parseFloat(form.bloque3_precio_m3) || precioB2;
    const cargoMant = parseFloat(form.cargo_fijo_mantenimiento) || 0;

    let b1M3 = 0, b2M3 = 0, b3M3 = 0;
    let montoConsumo = 0;

    if (c <= minM3) {
        b1M3 = c;
        montoConsumo = minMonto;
    } else {
        b1M3 = minM3;
        const rest = c - minM3;
        const capB2 = Math.max(0, b2Lim - minM3);
        b2M3 = Math.min(rest, capB2);
        b3M3 = Math.max(0, rest - b2M3);
        montoConsumo = minMonto + (b2M3 * precioB2) + (b3M3 * precioB3);
    }

    const montoBase = montoConsumo + cargoMant;
    let desc = 0;
    const pctDesc = parseFloat(form.descuento_adulto_mayor_pct) || 0;
    const limLey = parseInt(form.descuento_ley_m3_limite) || 15;

    if (simAdultoMayor.value && pctDesc > 0) {
        let montoElegible = 0;
        if (c <= limLey) {
            montoElegible = montoConsumo;
        } else {
            const m3Exc = Math.max(0, limLey - minM3);
            montoElegible = minMonto + (m3Exc * precioB2);
        }
        desc = Math.round((montoElegible * pctDesc) / 100 * 100) / 100;
    }

    const total = Math.max(0, montoBase - desc);

    return {
        b1M3, b2M3, b3M3,
        montoConsumo: montoConsumo.toFixed(2),
        cargoMant: cargoMant.toFixed(2),
        montoBase: montoBase.toFixed(2),
        desc: desc.toFixed(2),
        total: total.toFixed(2)
    };
});

const submit = () => form.post(route('tarifas.store'));
</script>

<template>
  <app-layout>
    <div class="p-6 max-w-4xl mx-auto">
      <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-4">Nueva Tarifa Dinámica</h1>

      <form @submit.prevent="submit" class="space-y-6 bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">
        
        <!-- Tipo de Conexión y Fechas -->
        <div class="grid md:grid-cols-3 gap-4 border-b border-gray-200 dark:border-gray-700 pb-4">
          <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de conexión</label>
              <select v-model="form.tipo_conexion"
                      class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm mt-1 w-full text-sm">
                  <option value="domiciliaria">Domiciliaria</option>
                  <option value="comercial">Comercial</option>
                  <option value="institucional">Institucional</option>
                  <option value="otro">Otro</option>
              </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Vigente desde</label>
            <input v-model="form.vigente_desde" type="date" 
                   class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md w-full px-3 py-2 text-sm" required />
          </div>
          <div class="flex items-center gap-2 mt-6">
            <input id="activo" type="checkbox" v-model="form.activo" 
                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" />
            <label for="activo" class="text-sm font-medium text-gray-700 dark:text-gray-300">Activa al Guardar</label>
          </div>
        </div>

        <!-- Bloque 1: Consumo Mínimo -->
        <div class="p-4 bg-gray-50 dark:bg-gray-900 border rounded-md">
          <h3 class="font-semibold text-sm text-gray-800 dark:text-gray-200 mb-3">1. Bloque Básico (Mínimo Incluido)</h3>
          <div class="grid md:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Volumen Base (m³)</label>
              <input v-model.number="form.min_m3" type="number" min="0" 
                     class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md w-full px-3 py-1.5 text-sm" />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Monto Mínimo (Bs)</label>
              <input v-model.number="form.min_monto" type="number" step="0.01" 
                     class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md w-full px-3 py-1.5 text-sm" />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Precio Básico (Bs/m³)</label>
              <input v-model.number="form.precio_m3" type="number" step="0.01" 
                     class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md w-full px-3 py-1.5 text-sm" />
            </div>
          </div>
        </div>

        <!-- Bloques Escalonados Excedentes -->
        <div class="p-4 bg-blue-50/50 dark:bg-gray-900 border border-blue-200 dark:border-blue-800 rounded-md">
          <h3 class="font-semibold text-sm text-gray-800 dark:text-gray-200 mb-3">2. Bloques Escalonados por Exceso</h3>
          <div class="grid md:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Límite Bloque 2 (m³)</label>
              <input v-model.number="form.bloque2_limite_m3" type="number" min="0" 
                     class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md w-full px-3 py-1.5 text-sm" placeholder="Ej: 25" />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Precio Bloque 2 (Bs/m³)</label>
              <input v-model.number="form.bloque2_precio_m3" type="number" step="0.01" 
                     class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md w-full px-3 py-1.5 text-sm" placeholder="Ej: 4.50" />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Precio Bloque 3 Alto Consumo (Bs/m³)</label>
              <input v-model.number="form.bloque3_precio_m3" type="number" step="0.01" 
                     class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md w-full px-3 py-1.5 text-sm" placeholder="Ej: 6.00" />
            </div>
          </div>
        </div>

        <!-- Descuentos por Ley & Cargos Fijos -->
        <div class="grid md:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">% Descuento Ley Adulto Mayor</label>
            <input v-model.number="form.descuento_adulto_mayor_pct" type="number" step="0.01" 
                   class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md w-full px-3 py-2 text-sm" />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Límite m³ Ley Adulto Mayor</label>
            <input v-model.number="form.descuento_ley_m3_limite" type="number" 
                   class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md w-full px-3 py-2 text-sm" placeholder="Ej: 15" />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Cargo Fijo Mantenimiento (Bs)</label>
            <input v-model.number="form.cargo_fijo_mantenimiento" type="number" step="0.01" 
                   class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md w-full px-3 py-2 text-sm" />
          </div>
        </div>  

        <!-- Conceptos Administrativos Adicionales -->
        <div class="grid md:grid-cols-4 gap-4 border-t border-gray-200 dark:border-gray-700 pt-4">
          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Afiliación Socio (Bs)</label>
            <input v-model.number="form.afiliacion_socio_monto" type="number" step="0.01" 
                   class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md w-full px-3 py-1.5 text-sm" />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Afiliación Usuario (Bs)</label>
            <input v-model.number="form.afiliacion_usuario_monto" type="number" step="0.01" 
                   class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md w-full px-3 py-1.5 text-sm" />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Multa Reconexión (Bs)</label>
            <input v-model.number="form.multa_corte_monto" type="number" step="0.01" 
                   class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md w-full px-3 py-1.5 text-sm" />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Cisterna 10.000L (Bs)</label>
            <input v-model.number="form.cisterna_10k_monto" type="number" step="0.01" 
                   class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md w-full px-3 py-1.5 text-sm" />
          </div>
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Notas / Descripción</label>
          <input v-model="form.notas" type="text" 
                 class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md w-full px-3 py-2 text-sm" placeholder="Ej: Tarifa aprobada en Asamblea General 2026" />
        </div>

        <!-- SIMULADOR INTERACTIVO DE TARIFAS -->
        <div class="p-4 bg-purple-50 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-800 rounded-lg">
          <h3 class="font-bold text-sm text-purple-900 dark:text-purple-300 mb-2 flex items-center gap-2">
            ⚡ SIMULADOR EN VIVO DE TARIFA
          </h3>
          <p class="text-xs text-purple-700 dark:text-purple-400 mb-3">Pruebe el cálculo en tiempo real con los valores ingresados arriba:</p>
          <div class="grid md:grid-cols-2 gap-4 items-center mb-3">
            <div>
              <label class="block text-xs font-semibold text-purple-900 dark:text-purple-300">Consumo a Probar (m³):</label>
              <input v-model.number="simConsumo" type="number" min="0" class="border-purple-300 rounded-md w-full px-3 py-1 text-sm dark:bg-gray-800 dark:text-white" />
            </div>
            <div class="flex items-center gap-2 mt-4">
              <input id="simAdulto" type="checkbox" v-model="simAdultoMayor" class="rounded text-purple-600 focus:ring-purple-500" />
              <label for="simAdulto" class="text-xs font-semibold text-purple-900 dark:text-purple-300">¿Es Adulto Mayor? (Descuento Ley)</label>
            </div>
          </div>
          <div class="bg-white dark:bg-gray-900 p-3 rounded text-xs space-y-1 dark:text-gray-200">
             <div class="flex justify-between"><span>Bloque 1 (0 - {{ form.min_m3 }} m³):</span><span>Bs {{ form.min_monto }} ({{ simResultado.b1M3 }} m³)</span></div>
             <div class="flex justify-between"><span>Bloque 2 ({{ form.min_m3 }} - {{ form.bloque2_limite_m3 }} m³ @ {{ form.bloque2_precio_m3 }} Bs):</span><span>{{ simResultado.b2M3 }} m³</span></div>
             <div class="flex justify-between"><span>Bloque 3 (> {{ form.bloque2_limite_m3 }} m³ @ {{ form.bloque3_precio_m3 }} Bs):</span><span>{{ simResultado.b3M3 }} m³</span></div>
             <div class="flex justify-between"><span>Cargo Mantenimiento Red:</span><span>Bs {{ simResultado.cargoMant }}</span></div>
             <div v-if="simAdultoMayor" class="flex justify-between text-purple-600 dark:text-purple-400 font-semibold"><span>Descuento Ley ({{ form.descuento_adulto_mayor_pct }}% s/ primer tramo):</span><span>- Bs {{ simResultado.desc }}</span></div>
             <hr class="dark:border-gray-700">
             <div class="flex justify-between font-bold text-sm text-green-700 dark:text-green-400"><span>TOTAL FACTURA SIMULADA:</span><span>Bs {{ simResultado.total }}</span></div>
          </div>
        </div>

        <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
          <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded shadow hover:bg-green-700 transition duration-150 font-semibold text-sm">
            Guardar Tarifa
          </button>
          <Link :href="route('tarifas.index')" class="bg-gray-500 text-white px-4 py-2 rounded shadow hover:bg-gray-600 transition duration-150 text-sm">
            Cancelar
          </Link>
        </div>
      </form>
      <ViewCounter />
    </div>
  </app-layout>
</template>
