<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DeleteUserForm from '@/Pages/Profile/Partials/DeleteUserForm.vue';
import LogoutOtherBrowserSessionsForm from '@/Pages/Profile/Partials/LogoutOtherBrowserSessionsForm.vue';
import SectionBorder from '@/Components/SectionBorder.vue';
import TwoFactorAuthenticationForm from '@/Pages/Profile/Partials/TwoFactorAuthenticationForm.vue';
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from '@/Pages/Profile/Partials/UpdateProfileInformationForm.vue';
import ViewCounter from '@/Components/ViewCounter.vue';

defineProps({
    confirmsTwoFactorAuthentication: Boolean,
    sessions: Array,
});
</script>

<template>
    <AppLayout title="Perfil de Usuario">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Mi Perfil de Usuario
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">

                <!-- BANNER DE DATOS Y ESTADO DEL USUARIO -->
                <div class="bg-white dark:bg-gray-800 shadow-xl rounded-2xl p-6 mb-8 border border-cyan-100 dark:border-gray-700">
                    <div class="flex flex-col md:flex-row items-center gap-6">
                        <img class="h-20 w-20 rounded-full object-cover ring-4 ring-cyan-500 shadow" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name" />

                        <div class="flex-1 text-center md:text-left space-y-1">
                            <div class="flex flex-wrap items-center justify-center md:justify-start gap-2">
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $page.props.auth.user.name }}</h3>
                                
                                <span v-if="$page.props.auth.user.is_active !== false" class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300 border border-green-300">
                                    ● Cuenta Activa
                                </span>
                                <span v-else class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300 border border-red-300">
                                    ● Cuenta Inactiva
                                </span>
                            </div>

                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $page.props.auth.user.email }}</p>

                            <div class="flex flex-wrap items-center justify-center md:justify-start gap-3 pt-2 text-xs">
                                <div v-if="$page.props.auth.user.role_names?.length" class="flex gap-1">
                                    <span v-for="role in $page.props.auth.user.role_names" :key="role" class="px-2.5 py-0.5 font-bold uppercase rounded-md bg-cyan-100 text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-300">
                                        {{ role }}
                                    </span>
                                </div>
                                <div v-if="$page.props.auth.user.zona" class="text-gray-600 dark:text-gray-300">
                                    📍 <strong>Zona Asignada:</strong> {{ $page.props.auth.user.zona?.nombre }}
                                </div>
                                <div v-if="$page.props.auth.user.afiliado" class="text-gray-600 dark:text-gray-300">
                                    👤 <strong>Socio Afiliado:</strong> {{ $page.props.auth.user.afiliado?.nombre_completo }} (CI: {{ $page.props.auth.user.afiliado?.ci }})
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="$page.props.jetstream.canUpdateProfileInformation">
                    <UpdateProfileInformationForm :user="$page.props.auth.user" />

                    <SectionBorder />
                </div>

                <div v-if="$page.props.jetstream.canUpdatePassword">
                    <UpdatePasswordForm class="mt-10 sm:mt-0" />

                    <SectionBorder />
                </div>

                <div v-if="$page.props.jetstream.canManageTwoFactorAuthentication">
                    <TwoFactorAuthenticationForm
                        :requires-confirmation="confirmsTwoFactorAuthentication"
                        class="mt-10 sm:mt-0"
                    />

                    <SectionBorder />
                </div>

                <LogoutOtherBrowserSessionsForm :sessions="sessions" class="mt-10 sm:mt-0" />

                <template v-if="$page.props.jetstream.hasAccountDeletionFeatures && !$page.props.auth.user.role_names?.includes('Administrador')">
                    <SectionBorder />

                    <DeleteUserForm class="mt-10 sm:mt-0" />
                </template>
            </div>
            <ViewCounter />
        </div>
    </AppLayout>
</template>
