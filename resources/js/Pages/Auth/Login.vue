<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ViewCounter from '@/Components/ViewCounter.vue';

defineProps({ canResetPassword: Boolean, status: String });
const showPassword = ref(false);
const form = useForm({ email: '', password: '', remember: false });

const submit = () => {
    form.transform(data => ({ ...data, remember: form.remember ? 'on' : '' }))
        .post(route('login'), { onFinish: () => form.reset('password') });
};

// --- LOGO  ---
const getLogoUrl = () => {
    let path = window.location.pathname;
    let root = path.replace(/\/login\/?$/, ''); 
    
    if (root === '/' || root === '') root = '';
    else if (root.endsWith('/')) root = root.slice(0, -1);
    
    return `${root}/storage/img/AGUA CABEZAS.png`;
}

// --- FONDO  ---
const getFondoUrl = () => {
    let path = window.location.pathname;
    const pages = ['/login', '/register', '/forgot-password', '/reset-password', '/verify-email']; 
    pages.forEach(p => { if (path.endsWith(p)) path = path.replace(p, ''); });
    if (path.endsWith('/')) path = path.slice(0, -1);  
    return `${path}/storage/img/2583.jpg`;
};


</script>

<template>
    <Head title="Iniciar Sesión" />

    <div class="fixed inset-0 bg-gradient-to-br from-blue-100 via-cyan-50 to-blue-200 overflow-hidden">
        <img :src="getFondoUrl()" alt="Fondo" 
            class="absolute inset-0 w-full h-full object-cover opacity-10 mix-blend-multiply"
            onerror="this.style.display='none'"
        />
    </div>

    <div class="relative min-h-screen flex items-center justify-center p-4 md:p-8 z-10">
        <div class="bg-white/90 backdrop-blur-md rounded-[2rem] shadow-2xl border border-white/50 p-6 sm:p-8 md:p-10 w-full max-w-xs sm:max-w-sm md:max-w-md animate-float-once">
            
            <!-- LOGO con brillo -->
          <div class="relative w-24 h-24 sm:w-28 sm:h-28 md:w-32 md:h-32 mx-auto mb-4 sm:mb-6 flex items-center justify-center">
                <div class="absolute inset-0 bg-gradient-to-tr from-blue-400 to-cyan-300 rounded-full blur opacity-40"></div>
                <div class="relative bg-white p-2 rounded-full shadow-lg border-2 border-blue-50 w-full h-full flex items-center justify-center">
                    <img :src="getLogoUrl()" alt="Logo" class="w-20 h-20 rounded-full object-contain pointer-events-none">
                </div>
            </div>
           

            <h1 class="text-2xl sm:text-3xl md:text-4xl font-black text-center text-slate-800 mb-1 sm:mb-2 tracking-tight">AGUA CABEZAS</h1>
            <p class="text-center text-slate-500 text-xs sm:text-sm md:text-base mb-6 sm:mb-8 px-2 font-medium">Asociación de Beneficiarios de Agua Potable</p>

            <div v-if="status" class="mb-4 sm:mb-6 text-xs sm:text-sm font-medium text-blue-600 text-center bg-blue-50 py-2 px-3 sm:px-4 rounded-lg border border-blue-100">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-4 sm:space-y-6">
                <div class="relative group">
                    <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-blue-400 text-base sm:text-lg md:text-xl group-focus-within:text-blue-600 transition-colors"></i>
                    <input v-model="form.email" type="text" placeholder="Correo" required
                        class="w-full pl-12 sm:pl-14 pr-4 py-3 sm:py-4 md:py-5 bg-slate-50 border-2 border-slate-100 rounded-full text-sm sm:text-base md:text-lg text-slate-900 focus:outline-none focus:border-blue-500 focus:bg-white focus:shadow-lg transition-all placeholder-slate-400"
                        autofocus autocomplete="username" />
                    <InputError class="mt-1 sm:mt-2 text-xs" :message="form.errors.email" />
                </div>

                <div class="relative group">
                    <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-blue-400 text-base sm:text-lg md:text-xl group-focus-within:text-blue-600 transition-colors"></i>
                    <input v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="Contraseña" required
                        class="w-full pl-12 sm:pl-14 pr-12 sm:pr-14 py-3 sm:py-4 md:py-5 bg-slate-50 border-2 border-slate-100 rounded-full text-sm sm:text-base md:text-lg text-slate-900 focus:outline-none focus:border-blue-500 focus:bg-white focus:shadow-lg transition-all placeholder-slate-400"
                        autocomplete="current-password" />
                    <button type="button" @click="showPassword = !showPassword"
                        class="absolute right-3 sm:right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-600 text-base sm:text-lg md:text-xl transition-colors">
                        <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                    </button>
                    <InputError class="mt-1 sm:mt-2 text-xs" :message="form.errors.password" />
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between text-xs sm:text-sm md:text-base space-y-3 sm:space-y-0">
                    <label class="flex items-center cursor-pointer">
                        <Checkbox v-model:checked="form.remember" name="remember" class="text-blue-600 focus:ring-blue-500 border-slate-300 rounded" />
                        <span class="ml-2 text-slate-600">Recordarme</span>
                    </label>
                    <Link v-if="canResetPassword" :href="route('password.request')" class="text-blue-600 hover:text-blue-800 hover:underline font-semibold transition-colors">
                        ¿Olvidaste tu contraseña?
                    </Link>
                </div>

                <PrimaryButton class="w-full !py-3 sm:!py-4 md:!py-5 !text-base sm:!text-lg md:!text-xl !font-bold !rounded-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 text-white shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all justify-center"
                    :class="{ 'opacity-75 cursor-not-allowed': form.processing }" :disabled="form.processing">
                    <i class="fas fa-sign-in-alt mr-2"></i> Ingresar
                </PrimaryButton>
            </form>

            <div class="mt-6 sm:mt-8 text-center text-xs sm:text-sm text-slate-500 space-y-1">
                <p>¿Primera vez? <Link :href="route('register')" class="text-blue-600 font-bold hover:underline">Regístrate aquí</Link></p>
                <div class="opacity-60 scale-90 pt-2">
                    <ViewCounter />
                </div>
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