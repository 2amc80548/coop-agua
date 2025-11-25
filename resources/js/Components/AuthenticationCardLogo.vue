<script setup>
import { Link } from '@inertiajs/vue3';

// Función que arregla la ruta del logo automáticamente
const getLogoUrl = () => {
    
    let path = window.location.pathname; 

    
    const paginas = [
        '/login', 
        '/register', 
        '/forgot-password', 
        '/reset-password',
        '/verify-email',
        '/two-factor-challenge'
    ];


    paginas.forEach(pagina => {
        if (path.endsWith(pagina)) {
            path = path.replace(pagina, '');
        }
    });

    if (path.endsWith('/')) {
        path = path.slice(0, -1);
    }

    return path + '/storage/img/AGUA CABEZAS.png';
}
</script>

<template>
    <Link :href="'/'">
        <div class="relative w-24 h-24 sm:w-28 sm:h-28 md:w-32 md:h-32 mx-auto mb-4 sm:mb-6 flex items-center justify-center">
            <div class="absolute inset-0 bg-gradient-to-tr from-blue-400 to-cyan-300 rounded-full blur opacity-40"></div>
            <div class="relative bg-white p-2 rounded-full shadow-lg border-2 border-blue-50 w-full h-full flex items-center justify-center">
                <img :src="getLogoUrl()" alt="Logo" class="w-20 h-20 rounded-full object-contain">
            </div>
        </div>
    </Link>
</template>