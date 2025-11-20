<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ViewCounter from '@/Components/ViewCounter.vue';

defineProps(['email', 'token']);
const showPassword = ref(false);
const showConfirm = ref(false);

const form = useForm({
    token: route().params.token,
    email: route().params.email || '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.update'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Restablecer Contraseña" />

    <!-- FONDO -->
    <div class="fixed inset-0 bg-gradient-to-br from-cyan-400 via-teal-500 to-blue-600 overflow-hidden">
        <img 
            src="/storage/img/2583.jpg" 
            alt="Fondo" 
            class="absolute inset-0 w-full h-full object-cover opacity-30"
            onerror="this.style.display='none'"
        />
    </div>

    <!-- TARJETA RESPONSIVA -->
    <div class="relative min-h-screen flex items-center justify-center p-4 md:p-8 z-10">
        <div class="bg-white/95 backdrop-blur-sm rounded-3xl shadow-2xl p-6 sm:p-8 md:p-10 w-full max-w-xs sm:max-w-sm md:max-w-md animate-float-once">

            <!-- LOGO -->
            <div class="w-24 h-24 sm:w-28 sm:h-28 md:w-32 md:h-32 mx-auto mb-4 sm:mb-6 bg-gradient-to-br from-cyan-500 to-teal-600 rounded-full flex items-center justify-center shadow-xl">
                <i class="fas fa-tint text-white text-4xl sm:text-5xl md:text-6xl"></i>
            </div>

            <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-center text-teal-600 mb-1 sm:mb-2">NUEVA CONTRASEÑA</h1>
            <p class="text-center text-gray-600 text-xs sm:text-sm md:text-base mb-6 sm:mb-8 px-2">Elige una contraseña segura y fácil de recordar</p>

            <!-- EMAIL (solo si viene) -->
            <div v-if="email" class="text-center text-sm text-gray-600 mb-4 bg-gray-50 py-2 px-4 rounded-lg">
                {{ email }}
            </div>

            <form @submit.prevent="submit" class="space-y-6">

                <!-- NUEVA CONTRASEÑA -->
                <div class="relative">
                    <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-teal-600 text-base sm:text-lg md:text-xl"></i>
                    <input 
                        v-model="form.password" 
                        :type="showPassword ? 'text' : 'password'" 
                        placeholder="Nueva contraseña" 
                        required
                        class="w-full pl-12 sm:pl-14 pr-12 sm:pr-14 py-3 sm:py-4 md:py-5 bg-gray-50 border-2 border-gray-200 rounded-full text-sm sm:text-base md:text-lg focus:outline-none focus:border-teal-500 focus:bg-white focus:shadow-lg transition-all"
                        autocomplete="new-password"
                    />
                    <button type="button" @click="showPassword = !showPassword"
                        class="absolute right-3 sm:right-4 top-1/2 -translate-y-1/2 text-teal-600 hover:text-teal-700 text-base sm:text-lg md:text-xl">
                        <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                    </button>
                    <InputError class="mt-1 sm:mt-2 text-xs" :message="form.errors.password" />
                </div>

                <!-- CONFIRMAR CONTRASEÑA -->
                <div class="relative">
                    <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-teal-600 text-base sm:text-lg md:text-xl"></i>
                    <input 
                        v-model="form.password_confirmation" 
                        :type="showConfirm ? 'text' : 'password'" 
                        placeholder="Confirmar contraseña" 
                        required
                        class="w-full pl-12 sm:pl-14 pr-12 sm:pr-14 py-3 sm:py-4 md:py-5 bg-gray-50 border-2 border-gray-200 rounded-full text-sm sm:text-base md:text-lg focus:outline-none focus:border-teal-500 focus:bg-white focus:shadow-lg transition-all"
                        autocomplete="new-password"
                    />
                    <button type="button" @click="showConfirm = !showConfirm"
                        class="absolute right-3 sm:right-4 top-1/2 -translate-y-1/2 text-teal-600 hover:text-teal-700 text-base sm:text-lg md:text-xl">
                        <i :class="showConfirm ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                    </button>
                    <InputError class="mt-1 sm:mt-2 text-xs" :message="form.errors.password_confirmation" />
                </div>

                <!-- BOTÓN -->
                <PrimaryButton 
                    class="w-full !py-3 sm:!py-4 md:!py-5 !text-base sm:!text-lg md:!text-xl !font-bold !rounded-full bg-gradient-to-r from-cyan-500 to-teal-600 hover:from-cyan-600 hover:to-teal-700 text-white shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all"
                    :class="{ 'opacity-75 cursor-not-allowed': form.processing }" 
                    :disabled="form.processing"
                >
                    Cambiar Contraseña
                </PrimaryButton>
            </form>

            <div class="mt-6 text-center text-xs sm:text-sm text-gray-500">
                <Link :href="route('login')" class="text-teal-600 hover:underline">Volver al inicio de sesión</Link>
            </div>
        </div>
        <ViewCounter />
    </div>
</template>

<style scoped>
@keyframes floatOnce {
    0% { transform: translateY(40px); opacity: 0; }
    100% { transform: translateY(0); opacity: 1; }
}
.animate-float-once { animation: floatOnce 1s ease-out; }
</style>