<script setup>
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profil" />
    <div class="mx-auto max-w-3xl space-y-6 px-4 py-8 md:py-12">
        <h1 class="font-serif text-3xl font-bold text-hijau-gelap">Profil</h1>

        <div class="rounded-3xl bg-white p-5 shadow-sm sm:p-8">
            <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" class="max-w-xl" />
        </div>

        <div class="rounded-3xl bg-white p-5 shadow-sm sm:p-8">
            <UpdatePasswordForm class="max-w-xl" />
        </div>

        <div class="rounded-3xl bg-white p-5 shadow-sm sm:p-8">
            <DeleteUserForm class="max-w-xl" />
        </div>
    </div>
</template>
