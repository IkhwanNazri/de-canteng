<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ referralCode: { type: String, default: '' } });

const form = useForm({
    referral_code: props.referralCode,
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout title="Daftar Akaun" subtitle="Beli batik tempatan, atau buka kedai anda sendiri.">
        <Head title="Daftar" />

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="name" value="Nama" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="Emel" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Kata Laluan" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel
                    for="password_confirmation"
                    value="Sahkan Kata Laluan"
                />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <div class="mt-4">
                <InputLabel for="referral_code" value="Kod Referral (pilihan)" />
                <TextInput id="referral_code" type="text" class="mt-1 block w-full uppercase" v-model="form.referral_code" maxlength="12" placeholder="cth: DBAB12CD" />
                <p v-if="form.referral_code" class="mt-1 text-xs text-hijau">Anda dapat +1 cop selepas pesanan pertama dibayar.</p>
                <InputError class="mt-2" :message="form.errors.referral_code" />
            </div>

            <PrimaryButton class="mt-6 w-full" :disabled="form.processing">Daftar</PrimaryButton>

            <p class="mt-6 text-center text-sm text-kelabu">
                Sudah ada akaun?
                <Link :href="route('login')" class="font-semibold text-hijau hover:underline">Log masuk</Link>
            </p>
        </form>
    </GuestLayout>
</template>
