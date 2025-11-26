<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ViewCounter from '@/Components/ViewCounter.vue';

defineProps({ status: String });

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};

// --- LOGO  ---
const getLogoUrl = () => {
    let path = window.location.pathname;
    let root = path.replace(/\/forgot-password\/?$/, ''); 
    
    if (root === '/' || root === '') root = '';
    else if (root.endsWith('/')) root = root.slice(0, -1);
    
    return `${root}/storage/img/AGUA CABEZAS.png`;
}

// --- FONDO ---
const getFondoUrl = () => {
    let path = window.location.pathname;
    const pages = ['/login', '/register', '/forgot-password', '/reset-password', '/verify-email']; 
    pages.forEach(p => { if (path.endsWith(p)) path = path.replace(p, ''); });
    if (path.endsWith('/')) path = path.slice(0, -1);  
    return `${path}/storage/img/2583.jpg`;
};
</script>

<template>
    <Head title="Olvidé mi contraseña" />

 <div class="fixed inset-0 bg-gradient-to-br from-blue-100 via-cyan-50 to-blue-200 overflow-hidden">
        <img :src="getFondoUrl()" alt="Fondo" 
            class="absolute inset-0 w-full h-full object-cover opacity-10 mix-blend-multiply"
            onerror="this.style.display='none'"
        />
    </div>

    <div class="relative min-h-screen flex items-center justify-center p-4 md:p-8 z-10">
        <!-- TARJETA -->
        <div class="bg-white/90 backdrop-blur-md rounded-[2rem] shadow-2xl border border-white/50 p-6 sm:p-8 md:p-10 w-full max-w-xs sm:max-w-sm md:max-w-md animate-float-once">

            <!-- LOGO con brillo -->
            <div class="relative w-24 h-24 sm:w-28 sm:h-28 md:w-32 md:h-32 mx-auto mb-4 sm:mb-6 flex items-center justify-center">
                <div class="absolute inset-0 bg-gradient-to-tr from-blue-400 to-cyan-300 rounded-full blur opacity-40"></div>
                <div class="relative bg-white p-2 rounded-full shadow-lg border-2 border-blue-50 w-full h-full flex items-center justify-center">
                    <img :src="getLogoUrl()" alt="Logo" class="w-20 h-20 rounded-full object-contain">
                </div>
            </div>

            <!-- TÍTULOS -->
            <h1 class="text-xl sm:text-2xl md:text-3xl font-black text-center text-slate-800 mb-2 tracking-tight uppercase leading-tight">
                ¿OLVIDASTE TU CONTRASEÑA?
            </h1>
            <p class="text-center text-slate-500 text-xs sm:text-sm md:text-base mb-6 sm:mb-8 px-2 font-medium leading-relaxed">
                Ingresa tu correo y te enviaremos un enlace para restablecerla.
            </p>

            <div v-if="status" class="mb-4 text-xs sm:text-sm font-medium text-blue-600 text-center bg-blue-50 py-2 px-4 rounded-lg border border-blue-100">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                
                <!-- Email -->
                <div class="relative group">
                    <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-blue-400 text-base sm:text-lg md:text-xl group-focus-within:text-blue-600 transition-colors"></i>
                    <input v-model="form.email" type="email" placeholder="Correo electrónico" required
                        class="w-full pl-12 sm:pl-14 pr-4 py-3 sm:py-4 bg-slate-50 border-2 border-slate-100 rounded-full text-sm sm:text-base text-slate-900 focus:outline-none focus:border-blue-500 focus:bg-white focus:shadow-lg transition-all placeholder-slate-400"
                        autofocus autocomplete="username" />
                    <InputError class="mt-1 text-xs" :message="form.errors.email" />
                </div>

                <PrimaryButton class="w-full !py-3 sm:!py-4 !text-base sm:!text-lg !font-bold !rounded-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 text-white shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all justify-center"
                    :class="{ 'opacity-75 cursor-not-allowed': form.processing }" :disabled="form.processing">
                    Enviar enlace de recuperación
                </PrimaryButton>
            </form>

            <div class="mt-8 text-center text-xs sm:text-sm font-medium">
                <Link :href="route('login')" class="text-blue-600 hover:text-blue-800 hover:underline transition-colors">
                    <i class="fas fa-arrow-left mr-1"></i> Volver al inicio de sesión
                </Link>
            </div>

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