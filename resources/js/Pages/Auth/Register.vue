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

const getFondoUrl = () => {
    let path = window.location.pathname;
    const pages = ['/login', '/register', '/forgot-password', '/reset-password', '/verify-email']; 
    pages.forEach(p => { 
        if (path.endsWith(p)) path = path.replace(p, ''); 
    });
    if (path.endsWith('/')) path = path.slice(0, -1);  
    return `${path}/storage/img/2583.jpg`;
};
</script>

<template>
    <Head title="Registrarse" />

<div class="fixed inset-0 bg-gradient-to-br from-blue-100 via-cyan-50 to-blue-200 overflow-hidden">
        <img 
            :src="getFondoUrl()" 
            alt="Fondo" 
            class="absolute inset-0 w-full h-full object-cover opacity-10 mix-blend-multiply"
            onerror="this.style.display='none'"
        />
    </div>

    <div class="relative min-h-screen flex items-center justify-center p-4 md:p-8 z-10">
        <!-- TARJETA: Estilo blanco translúcido -->
        <div class="bg-white/90 backdrop-blur-md rounded-[2rem] shadow-2xl border border-white/50 p-6 sm:p-8 md:p-10 w-full max-w-xs sm:max-w-sm md:max-w-md animate-float-once">

            <!-- LOGO con brillo -->
            <div class="relative w-24 h-24 sm:w-28 sm:h-28 md:w-32 md:h-32 mx-auto mb-4 sm:mb-6 flex items-center justify-center">
                <div class="absolute inset-0 bg-gradient-to-tr from-blue-400 to-cyan-300 rounded-full blur opacity-40"></div>
                <div class="relative bg-white p-2 rounded-full shadow-lg border-2 border-blue-50 w-full h-full flex items-center justify-center">
                    <img src="/storage/img/AGUA CABEZAS.png" alt="Logo" class="w-20 h-20 rounded-full object-contain">
                </div>
            </div>

            <!-- TÍTULOS ORIGINALES -->
            <h1 class="text-2xl sm:text-3xl md:text-4xl font-black text-center text-slate-800 mb-1 sm:mb-2 tracking-tight uppercase">REGISTRARSE</h1>
            <p class="text-center text-slate-500 text-xs sm:text-sm md:text-base mb-6 sm:mb-8 px-2 font-medium">Únete a la Cooperativa de Agua</p>

            <form @submit.prevent="submit" class="space-y-4 sm:space-y-5">

                <!-- Nombre -->
                <div class="relative group">
                    <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-blue-400 text-base sm:text-lg md:text-xl group-focus-within:text-blue-600 transition-colors"></i>
                    <input v-model="form.name" type="text" placeholder="Nombre completo" required
                        class="w-full pl-12 sm:pl-14 pr-4 py-3 sm:py-4 bg-slate-50 border-2 border-slate-100 rounded-full text-sm sm:text-base text-slate-900 focus:outline-none focus:border-blue-500 focus:bg-white focus:shadow-lg transition-all placeholder-slate-400"
                        autofocus autocomplete="name" />
                    <InputError class="mt-1 text-xs" :message="form.errors.name" />
                </div>

                <!-- Email -->
                <div class="relative group">
                    <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-blue-400 text-base sm:text-lg md:text-xl group-focus-within:text-blue-600 transition-colors"></i>
                    <input v-model="form.email" type="email" placeholder="Correo electrónico" required
                        class="w-full pl-12 sm:pl-14 pr-4 py-3 sm:py-4 bg-slate-50 border-2 border-slate-100 rounded-full text-sm sm:text-base text-slate-900 focus:outline-none focus:border-blue-500 focus:bg-white focus:shadow-lg transition-all placeholder-slate-400"
                        autocomplete="username" />
                    <InputError class="mt-1 text-xs" :message="form.errors.email" />
                </div>

                <!-- Password -->
                <div class="relative group">
                    <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-blue-400 text-base sm:text-lg md:text-xl group-focus-within:text-blue-600 transition-colors"></i>
                    <input v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="Contraseña" required
                        class="w-full pl-12 sm:pl-14 pr-12 py-3 sm:py-4 bg-slate-50 border-2 border-slate-100 rounded-full text-sm sm:text-base text-slate-900 focus:outline-none focus:border-blue-500 focus:bg-white focus:shadow-lg transition-all placeholder-slate-400"
                        autocomplete="new-password" />
                    <button type="button" @click="showPassword = !showPassword"
                        class="absolute right-3 sm:right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-600 text-base sm:text-lg transition-colors">
                        <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                    </button>
                    <InputError class="mt-1 text-xs" :message="form.errors.password" />
                </div>

                <!-- Confirm Password -->
                <div class="relative group">
                    <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-blue-400 text-base sm:text-lg md:text-xl group-focus-within:text-blue-600 transition-colors"></i>
                    <input v-model="form.password_confirmation" :type="showConfirmPassword ? 'text' : 'password'" placeholder="Confirmar contraseña" required
                        class="w-full pl-12 sm:pl-14 pr-12 py-3 sm:py-4 bg-slate-50 border-2 border-slate-100 rounded-full text-sm sm:text-base text-slate-900 focus:outline-none focus:border-blue-500 focus:bg-white focus:shadow-lg transition-all placeholder-slate-400"
                        autocomplete="new-password" />
                    <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                        class="absolute right-3 sm:right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-600 text-base sm:text-lg transition-colors">
                        <i :class="showConfirmPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                    </button>
                    <InputError class="mt-1 text-xs" :message="form.errors.password_confirmation" />
                </div>

                <!-- Términos -->
                <div v-if="$page.props.jetstream.hasTermsAndPrivacyPolicyFeature" class="text-xs sm:text-sm">
                    <label class="flex items-center cursor-pointer">
                        <Checkbox v-model:checked="form.terms" name="terms" required class="text-blue-600 focus:ring-blue-500 border-slate-300 rounded" />
                        <span class="ml-2 text-slate-600">Acepto los <Link :href="route('terms.show')" class="text-blue-600 hover:underline font-semibold">Términos</Link> y <Link :href="route('policy.show')" class="text-blue-600 hover:underline font-semibold">Política</Link></span>
                    </label>
                    <InputError class="mt-1 text-xs" :message="form.errors.terms" />
                </div>

                <!-- Botones y Links -->
                <div class="flex flex-col gap-4 mt-6">
                    <PrimaryButton class="w-full !py-3 sm:!py-4 !text-base sm:!text-lg !font-bold !rounded-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 text-white shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all justify-center"
                        :class="{ 'opacity-75 cursor-not-allowed': form.processing }" :disabled="form.processing">
                        REGISTRARSE
                    </PrimaryButton>

                    <Link :href="route('login')" class="text-blue-600 hover:text-blue-800 hover:underline text-center text-sm font-medium transition-colors">
                        ¿Ya tienes cuenta? Inicia sesión
                    </Link>
                </div>
            </form>

            <!-- Contador -->
            <div class="mt-6 text-center opacity-60 scale-90">
                <ViewCounter />
            </div>

        </div>
    </div>
</template>

<style scoped>
@keyframes floatOnce {
    0% { transform: translateY(40px); opacity: 0; }
    100% { transform: translateY(0); opacity: 1; }
}
.animate-float-once { animation: floatOnce 0.8s ease-out; }

:global(.input-error) {
    color: theme('colors.red.500') !important;
}
</style>