<script setup>
const getLogoUrl = () => {
    let baseUrl = route('login');
    baseUrl = baseUrl.replace('/login', '');
    return `${baseUrl}/storage/img/AGUA CABEZAS.png`;
}
</script>

<template>
    <img :src="getLogoUrl()" alt="Logo" />
</template>