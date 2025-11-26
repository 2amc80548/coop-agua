<script setup>
import { Head, Link } from '@inertiajs/vue3'
import ViewCounter from '@/Components/ViewCounter.vue'

// --- DATOS DE LA COOPERATIVA ---
const cooperativa = {
  nombre: 'Asociación de Beneficiarios de Agua Potable',
  localidad: 'de la Localidad de Cabezas',
  slogan: 'Garantizando el líquido elemento para nuestra comunidad.',
  celular: '63591312', 
  direccion: 'Av. Benigno Vaca Nº S/N, Barrio El Carmen',
  ciudad: 'Cabezas - Santa Cruz',
  horario: 'Lun-Vie: 08:00-12:00 / 14:00-18:00 | Sáb: 08:00-12:00',
  mapaUrl: 'https://maps.app.goo.gl/KddN4an1zct27eAa7'
}

const getLogoUrl = () => {
    let baseUrl = route('login'); 
    baseUrl = baseUrl.replace('/login', '')
    return `${baseUrl}/storage/img/AGUA CABEZAS.png`;
};
// --- IMÁGENES ---
const images = {
    // historia: "/img/historia.jpg",
    
    historia: "https://images.unsplash.com/photo-1581244277943-fe4a9c777189?q=80&w=2070&auto=format&fit=crop", 
    
    servicios: "https://images.unsplash.com/photo-1538300342682-cf57afb97285?q=80&w=2070&auto=format&fit=crop", 
    
    
    oficina: "https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=2070&auto=format&fit=crop" 
}

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
})
</script>

<template>
  <Head title="AguaCabezas - Servicio de Agua Potable" />

  <!-- FONDO MEJORADO -->
  <div class="min-h-screen bg-gradient-to-br from-blue-100 via-cyan-50 to-blue-200 text-slate-800 overflow-x-hidden font-sans selection:bg-blue-600 selection:text-white">
    
    <!-- Decoración de fondo -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
      <div class="absolute -top-20 -right-20 w-[35rem] h-[35rem] bg-blue-500/30 rounded-full blur-3xl animate-pulse"></div>
      <div class="absolute top-1/3 -left-20 w-[25rem] h-[25rem] bg-cyan-500/25 rounded-full blur-3xl"></div>
    </div>

    <div class="relative min-h-screen flex flex-col items-center px-4 py-12">
      <div class="w-full max-w-6xl mx-auto flex-1 flex flex-col justify-center">

        <!-- 1. CABECERA -->
        <header class="text-center mb-12 flex flex-col items-center animate-fade-in-down mt-8">
          
          <div class="relative group mb-6 transition-transform hover:scale-105 duration-300">
              <div class="absolute -inset-3 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full blur-md opacity-50 group-hover:opacity-80 transition duration-500"></div>
              <div class="relative bg-white p-4 rounded-full shadow-2xl border-2 border-blue-50">
                 <img :src="getLogoUrl()" alt="Logo Cabezas" class="w-28 h-28 object-contain" />
              </div>
          </div>

          <h1 class="text-3xl md:text-5xl lg:text-6xl font-black text-slate-900 mb-2 tracking-tight drop-shadow-sm leading-tight">
            Asociación de Beneficiarios <br class="hidden md:block" />
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-cyan-600">de Agua Potable</span>
          </h1>
          
          <p class="text-xl md:text-2xl font-semibold text-slate-700 mb-4">
            {{ cooperativa.localidad }}
          </p>
          
          <div class="w-24 h-1.5 bg-gradient-to-r from-blue-600 to-cyan-500 rounded-full mb-6"></div>
        </header>

        <!-- 2. BOTONES PRINCIPALES -->
        <div class="flex flex-wrap justify-center gap-5 mb-16 animate-fade-in-up">
          <Link 
            v-if="canLogin"
            :href="$page.props.auth.user ? route('dashboard') : route('login')"
            class="px-10 py-4 bg-gradient-to-r from-blue-700 to-blue-600 hover:from-blue-600 hover:to-blue-500 text-white font-bold text-lg rounded-full shadow-lg shadow-blue-600/40 transition transform hover:-translate-y-1 flex items-center gap-3"
          >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
            </svg>
            {{ $page.props.auth.user ? 'Ir a mi Panel' : 'Iniciar Sesión' }}
          </Link>
          
          <Link 
            v-if="canRegister && !$page.props.auth.user"
            :href="route('register')"
            class="px-10 py-4 bg-white hover:bg-blue-50 text-blue-800 font-bold text-lg rounded-full shadow-md border-2 border-blue-200 hover:border-blue-400 transition transform hover:-translate-y-1 flex items-center gap-3"
          >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
            </svg>
            Solicitar Acceso
          </Link>
        </div>

        <!-- 3. TARJETAS DE INFORMACIÓN -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          
          <!-- HISTORIA -->
          <div class="group bg-white rounded-[2rem] overflow-hidden shadow-xl border border-blue-100/50 hover:shadow-2xl hover:border-blue-300 transition-all duration-300 flex flex-col h-full">
            <div class="h-52 overflow-hidden relative">
                <img :src="images.historia" class="w-full h-full object-cover group-hover:scale-110 transition duration-700" alt="Historia" />
                <div class="absolute inset-0 bg-gradient-to-t from-blue-900/60 to-transparent"></div>
                <div class="absolute bottom-4 left-5 text-white">
                    <h3 class="text-2xl font-bold drop-shadow-md">Nuestra Historia</h3>
                </div>
            </div>
            <div class="p-6 flex-1 flex flex-col bg-white">
               <p class="text-slate-600 leading-relaxed mb-4 font-medium">
                 Fundada el <strong>23 de Agosto de 1970</strong>. Fruto del esfuerzo y unidad de hombres y mujeres valientes de Cabezas.
               </p>
               <div class="mt-auto pt-4 border-t border-slate-100 text-blue-700">
                   <span class="text-sm font-bold flex items-center gap-1">
                       <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                       Más de 50 años de servicio
                   </span>
               </div>
            </div>
          </div>

          <!-- SERVICIOS -->
          <div class="group bg-white rounded-[2rem] overflow-hidden shadow-xl border border-blue-100/50 hover:shadow-2xl hover:border-cyan-300 transition-all duration-300 flex flex-col h-full">
            <div class="h-52 overflow-hidden relative">
                <img :src="images.servicios" class="w-full h-full object-cover group-hover:scale-110 transition duration-700" alt="Servicios" />
                <div class="absolute inset-0 bg-gradient-to-t from-cyan-900/60 to-transparent"></div>
                 <div class="absolute bottom-4 left-5 text-white">
                    <h3 class="text-2xl font-bold drop-shadow-md">Servicios en Línea</h3>
                </div>
            </div>
            <div class="p-6 flex-1 bg-white">
              <ul class="space-y-3">
                <li class="flex items-center p-3 rounded-xl bg-blue-50/80 text-blue-800 font-bold">
                    <span class="w-2 h-2 bg-blue-500 rounded-full mr-3"></span>
                    Pago rápido con QR
                </li>
                <li class="flex items-center p-3 rounded-xl bg-cyan-50/80 text-cyan-800 font-bold">
                    <span class="w-2 h-2 bg-cyan-500 rounded-full mr-3"></span>
                    Descarga de Facturas
                </li>
                <li class="flex items-center p-3 rounded-xl bg-slate-50/80 text-slate-700 font-bold">
                    <span class="w-2 h-2 bg-slate-500 rounded-full mr-3"></span>
                    Ver Consumos
                </li>
              </ul>
            </div>
          </div>

          <!-- CONTACTO -->
          <div class="group bg-white rounded-[2rem] overflow-hidden shadow-xl border border-blue-100/50 hover:shadow-2xl hover:border-green-300 transition-all duration-300 flex flex-col h-full">
            
            <div class="h-52 overflow-hidden relative">
                <img :src="images.oficina" class="w-full h-full object-cover group-hover:scale-110 transition duration-700" alt="Oficina" />
                <div class="absolute inset-0 bg-gradient-to-t from-green-900/60 to-transparent"></div>
                <div class="absolute bottom-4 left-5 text-white">
                    <h3 class="text-2xl font-bold drop-shadow-md">Contáctanos</h3>
                </div>
            </div>

            <div class="p-6 flex-1 flex flex-col justify-between bg-white">
               <div class="space-y-4">
                   <!-- Botón WhatsApp -->
                   <a :href="'https://wa.me/591' + cooperativa.celular" target="_blank" class="flex items-center p-3 rounded-xl bg-green-50 border border-green-200/50 hover:bg-green-100 transition group/wsp">
                       <div class="bg-green-500 text-white p-2 rounded-full mr-3 shadow-md group-hover/wsp:scale-110 transition">
                           <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.626.712.226 1.36.194 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                       </div>
                       <div>
                           <p class="text-xs text-green-700 font-bold uppercase">WhatsApp</p>
                           <p class="text-slate-800 font-black text-lg">{{ cooperativa.celular }}</p>
                       </div>
                   </a>

                   <!-- Botón Ubicación -->
                   <a :href="cooperativa.mapaUrl" target="_blank" class="flex items-start p-3 rounded-xl bg-blue-50 border border-blue-200/50 hover:bg-blue-100 transition group/map">
                       <div class="bg-blue-500 text-white p-2 rounded-full mr-3 shadow-md mt-1 group-hover/map:animate-bounce">
                           <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                       </div>
                       <div>
                           <p class="text-xs text-blue-700 font-bold uppercase mb-1">Ubicación</p>
                           <p class="text-sm text-slate-700 font-medium leading-tight hover:underline">
                               {{ cooperativa.direccion }}
                           </p>
                       </div>
                   </a>
               </div>
            </div>
          </div>

        </div>

        <!-- 4. FOOTER -->
        <footer class="mt-20 pb-8 flex justify-center items-center animate-fade-in-up">
          <div class="bg-white/50 backdrop-blur-sm p-3 rounded-2xl shadow-sm border border-blue-100 transition transform hover:scale-110 hover:bg-white">
            <ViewCounter />
          </div>
        </footer>

      </div>
    </div>
  </div>
</template>

<style>
/* Animaciones suaves de entrada */
@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-30px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-down { animation: fadeInDown 0.8s cubic-bezier(0.2, 0.8, 0.2, 1); }
.animate-fade-in-up { animation: fadeInUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) 0.2s backwards; }
</style>