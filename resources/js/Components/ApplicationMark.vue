<script setup>
import { ref } from 'vue';

const imageError = ref(false);

const getLogoUrl = () => {
    let baseUrl = route('login');
    baseUrl = baseUrl.replace('/login', '');
    return `${baseUrl}/storage/img/AGUA CABEZAS.png`;
};
</script>

<template>
    <div v-if="imageError" class="flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 text-white shadow-md p-1.5" title="AGUA CABEZAS">
        <svg class="w-full h-full text-white" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2.69l5.66 5.66a8 8 0 11-11.31 0z"/>
        </svg>
    </div>
    <img v-else :src="getLogoUrl()" @error="imageError = true" alt="AGUA CABEZAS Logo" class="h-10 w-10 object-contain rounded-xl" />
</template>