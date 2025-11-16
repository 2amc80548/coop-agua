<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

defineProps({ status: String });
const form = useForm({ email: '' });
const submit = () => form.post(route('password.email'));
</script>
 
<template>
    <Head title="Olvidé mi contraseña" />

    <!-- FONDO -->
    <div class="fixed inset-0 bg-gradient-to-br from-cyan-400 via-teal-500 to-blue-600 overflow-hidden">
        <img src="/storage/img/2583.jpg" alt="Fondo" class="absolute inset-0 w-full h-full object-cover opacity-30" onerror="this.style.display='none'" />
    </div>

    <!-- TARJETA -->
    <div class="relative min-h-screen flex items-center justify-center p-4 md:p-8 z-10">
        <div class="bg-white/95 backdrop-blur-sm rounded-3xl shadow-2xl p-6 sm:p-8 md:p-10 w-full max-w-xs sm:max-w-sm md:max-w-md animate-float-once">

            <div class="w-24 h-24 sm:w-28 sm:h-28 md:w-32 md:h-32 mx-auto mb-4 sm:mb-6 bg-gradient-to-br from-cyan-500 to-teal-600 rounded-full flex items-center justify-center shadow-xl">
                <!-- <i class="fas fa-tint text-white text-4xl sm:text-5xl md:text-6xl"></i> -->
                <img src="/storage/img/AGUA CABEZAS.png" alt="Logo" class="w-20 h-20 rounded-full object-contain">
            </div>

            <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-center text-teal-600 mb-1 sm:mb-2">¿OLVIDASTE TU CONTRASEÑA?</h1>
            <p class="text-center text-gray-600 text-xs sm:text-sm md:text-base mb-6 sm:mb-8 px-2">Ingresa tu correo y te enviaremos un enlace para restablecerla.</p>

            <div v-if="status" class="mb-4 text-xs sm:text-sm font-medium text-green-600 text-center bg-green-50 py-2 px-4 rounded-lg">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="relative">
                    <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-teal-600 text-base sm:text-lg md:text-xl"></i>
                    <input v-model="form.email" type="email" placeholder="Correo electrónico" required
                        class="w-full pl-12 sm:pl-14 pr-4 py-3 sm:py-4 md:py-5 bg-gray-50 border-2 border-gray-200 rounded-full text-sm sm:text-base md:text-lg focus:outline-none focus:border-teal-500 focus:bg-white focus:shadow-lg transition-all"
                        autofocus autocomplete="username" />
                    <InputError class="mt-1 sm:mt-2 text-xs" :message="form.errors.email" />
                </div>

                <PrimaryButton class="w-full !py-3 sm:!py-4 md:!py-5 !text-base sm:!text-lg md:!text-xl !font-bold !rounded-full bg-gradient-to-r from-cyan-500 to-teal-600 hover:from-cyan-600 hover:to-teal-700 text-white shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all"
                    :class="{ 'opacity-75 cursor-not-allowed': form.processing }" :disabled="form.processing">
                    Enviar enlace de recuperación
                </PrimaryButton>
            </form>

            <div class="mt-6 text-center text-xs sm:text-sm text-gray-500">
                <Link :href="route('login')" class="text-teal-600 hover:underline">Volver al inicio de sesión</Link>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes floatOnce { 0% { transform: translateY(40px); opacity: 0; } 100% { transform: translateY(0); opacity: 1; } }
.animate-float-once { animation: floatOnce 1s ease-out; }
</style>