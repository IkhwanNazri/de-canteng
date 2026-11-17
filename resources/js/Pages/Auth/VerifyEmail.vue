<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout title="Sahkan Emel">
        <Head title="Pengesahan Emel" />

        <div class="mb-4 text-sm text-gray-600">
            Terima kasih kerana mendaftar! Sila sahkan emel anda melalui pautan yang kami hantar. Tidak terima emel? Kami boleh hantar semula.
        </div>

        <div
            class="mb-4 text-sm font-medium text-green-600"
            v-if="verificationLinkSent"
        >
            Pautan pengesahan baharu telah dihantar ke emel anda.
        </div>

        <form @submit.prevent="submit">
            <div class="mt-4 flex items-center justify-between">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Hantar Semula Emel Pengesahan
                </PrimaryButton>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-hijau focus:ring-offset-2"
                    >Log Keluar</Link
                >
            </div>
        </form>
    </GuestLayout>
</template>
