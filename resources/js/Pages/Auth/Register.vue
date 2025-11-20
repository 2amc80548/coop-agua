<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ViewCounter from '@/Components/ViewCounter.vue';
const showPassword = ref(false);
const showConfirmPassword = ref(false);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    terms: false,
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Registrarse" />

    <div class="fixed inset-0 bg-gradient-to-br from-cyan-400 via-teal-500 to-blue-600 dark:bg-gray-900 dark:from-gray-900 dark:to-gray-800 overflow-hidden">
        <img 
            src="/storage/img/2583.jpg" 
            alt="Fondo" 
            class="absolute inset-0 w-full h-full object-cover opacity-30 dark:opacity-5 dark:grayscale"
            onerror="this.style.display='none'"
        />
    </div>

    <div class="relative min-h-screen flex items-center justify-center p-4 md:p-8 z-10">
        <div class="bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm rounded-3xl shadow-2xl p-6 sm:p-8 md:p-10 w-full max-w-xs sm:max-w-sm md:max-w-md animate-float-once">

            <div class="w-24 h-24 sm:w-28 sm:h-28 md:w-32 md:h-32 mx-auto mb-4 sm:mb-6 bg-gradient-to-br from-cyan-500 to-teal-600 rounded-full flex items-center justify-center shadow-xl">
                <img src="/storage/img/AGUA CABEZAS.png" alt="Logo" class="w-20 h-20 rounded-full object-contain">
            </div>

            <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-center text-teal-600 dark:text-cyan-400 mb-1 sm:mb-2">REGISTRARSE</h1>
            <p class="text-center text-gray-600 dark:text-gray-300 text-xs sm:text-sm md:text-base mb-6 sm:mb-8 px-2">Únete a la Cooperativa de Agua</p>

            <form @submit.prevent="submit" class="space-y-4 sm:space-y-6">

                <div class="relative">
                    <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-teal-600 dark:text-cyan-400 text-base sm:text-lg md:text-xl"></i>
                    <input v-model="form.name" type="text" placeholder="Nombre completo" required
                        class="w-full pl-12 sm:pl-14 pr-4 py-3 sm:py-4 md:py-5 bg-gray-50 dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-full text-sm sm:text-base md:text-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:border-teal-500 dark:focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-900 focus:shadow-lg transition-all"
                        autofocus autocomplete="name" />
                    <InputError class="mt-1 sm:mt-2 text-xs" :message="form.errors.name" />
                </div>

                <div class="relative">
                    <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-teal-600 dark:text-cyan-400 text-base sm:text-lg md:text-xl"></i>
                    <input v-model="form.email" type="email" placeholder="Correo electrónico" required
                        class="w-full pl-12 sm:pl-14 pr-4 py-3 sm:py-4 md:py-5 bg-gray-50 dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-full text-sm sm:text-base md:text-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:border-teal-500 dark:focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-900 focus:shadow-lg transition-all"
                        autocomplete="username" />
                    <InputError class="mt-1 sm:mt-2 text-xs" :message="form.errors.email" />
                </div>

                <div class="relative">
                    <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-teal-600 dark:text-cyan-400 text-base sm:text-lg md:text-xl"></i>
                    <input v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="Contraseña" required
                        class="w-full pl-12 sm:pl-14 pr-12 sm:pr-14 py-3 sm:py-4 md:py-5 bg-gray-50 dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-full text-sm sm:text-base md:text-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:border-teal-500 dark:focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-900 focus:shadow-lg transition-all"
                        autocomplete="new-password" />
                    <button type="button" @click="showPassword = !showPassword"
                        class="absolute right-3 sm:right-4 top-1/2 -translate-y-1/2 text-teal-600 dark:text-cyan-400 hover:text-teal-700 dark:hover:text-cyan-300 text-base sm:text-lg md:text-xl">
                        <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                    </button>
                    <InputError class="mt-1 sm:mt-2 text-xs" :message="form.errors.password" />
                </div>

                <div class="relative">
                    <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-teal-600 dark:text-cyan-400 text-base sm:text-lg md:text-xl"></i>
                    <input v-model="form.password_confirmation" :type="showConfirmPassword ? 'text' : 'password'" placeholder="Confirmar contraseña" required
                        class="w-full pl-12 sm:pl-14 pr-12 sm:pr-14 py-3 sm:py-4 md:py-5 bg-gray-50 dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-full text-sm sm:text-base md:text-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:border-teal-500 dark:focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-900 focus:shadow-lg transition-all"
                        autocomplete="new-password" />
                    <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                        class="absolute right-3 sm:right-4 top-1/2 -translate-y-1/2 text-teal-600 dark:text-cyan-400 hover:text-teal-700 dark:hover:text-cyan-300 text-base sm:text-lg md:text-xl">
                        <i :class="showConfirmPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                    </button>
                    <InputError class="mt-1 sm:mt-2 text-xs" :message="form.errors.password_confirmation" />
                </div>

                <div v-if="$page.props.jetstream.hasTermsAndPrivacyPolicyFeature" class="text-xs sm:text-sm">
                    <label class="flex items-center cursor-pointer">
                        <Checkbox v-model:checked="form.terms" name="terms" required />
                        <span class="ml-2 text-gray-700 dark:text-gray-300">Acepto los <Link :href="route('terms.show')" class="text-teal-600 dark:text-cyan-400 hover:underline">Términos</Link> y <Link :href="route('policy.show')" class="text-teal-600 dark:text-cyan-400 hover:underline">Política</Link></span>
                    </label>
                    <InputError class="mt-1 text-xs" :message="form.errors.terms" />
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between text-xs sm:text-sm md:text-base space-y-3 sm:space-y-0">
                    <Link :href="route('login')" class="text-teal-600 dark:text-cyan-400 hover:text-teal-700 dark:hover:text-cyan-300 hover:underline text-center sm:text-left">
                        ¿Ya tienes cuenta? Inicia sesión
                    </Link>
                    <PrimaryButton class="w-full sm:w-auto !py-3 sm:!py-4 md:!py-5 !text-base sm:!text-lg md:!text-xl !font-bold !rounded-full bg-gradient-to-r from-cyan-500 to-teal-600 hover:from-cyan-600 hover:to-teal-700 text-white shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all"
                        :class="{ 'opacity-75 cursor-not-allowed': form.processing }" :disabled="form.processing">
                        REGISTRARSE
                    </PrimaryButton>
                </div>
            </form>


                <ViewCounter />

        </div>   
    </div>
</template>

<style scoped>
@keyframes floatOnce {
    0% { transform: translateY(40px); opacity: 0; }
    100% { transform: translateY(0); opacity: 1; }
}
.animate-float-once { animation: floatOnce 1s ease-out; }
</style>