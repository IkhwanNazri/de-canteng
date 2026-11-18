<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Icon from '@/Components/Icon.vue';

const form = useForm({ name: '', description: '', bank_name: '', bank_account: '', referral_code: '' });
const BANKS = ['Maybank', 'CIMB', 'Public Bank', 'RHB', 'Hong Leong', 'AmBank', 'Bank Islam', 'BSN', 'Bank Rakyat', 'Affin', 'Alliance', 'Agrobank'];
</script>

<template>
    <Head title="Buka Kedai" />
    <div class="mx-auto max-w-2xl px-4 py-8 md:py-12">
        <div class="mb-6 flex items-center gap-4">
            <span class="grid size-14 place-items-center rounded-full bg-hijau-gelap text-emas"><Icon name="store" :size="28" /></span>
            <div>
                <h1 class="font-serif text-3xl font-bold text-hijau-gelap">Buka Kedai Anda</h1>
                <p class="text-sm text-kelabu">Percuma. Setiap jualan dikenakan caj platform RM1.</p>
            </div>
        </div>

        <form class="space-y-5 rounded-3xl bg-white p-6 shadow-sm md:p-8" @submit.prevent="form.post(route('shops.store'))">
            <div>
                <InputLabel for="name" value="Nama Kedai" />
                <TextInput id="name" v-model="form.name" class="mt-1 block w-full" required autofocus />
                <InputError :message="form.errors.name" class="mt-2" />
            </div>
            <div>
                <InputLabel for="description" value="Penerangan (pilihan)" />
                <textarea
                    id="description"
                    v-model="form.description"
                    rows="3"
                    class="mt-1 block w-full rounded-xl border border-pasir px-4 py-2.5 text-sm outline-none focus:border-hijau"
                />
                <InputError :message="form.errors.description" class="mt-2" />
            </div>
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <InputLabel for="bank_name" value="Bank (untuk terima bayaran)" />
                    <select id="bank_name" v-model="form.bank_name" required class="mt-1 block w-full rounded-xl border border-pasir bg-white px-4 py-2.5 text-sm">
                        <option value="" disabled>Pilih bank</option>
                        <option v-for="b in BANKS" :key="b">{{ b }}</option>
                    </select>
                    <InputError :message="form.errors.bank_name" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="bank_account" value="Nombor Akaun" />
                    <TextInput id="bank_account" v-model="form.bank_account" inputmode="numeric" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.bank_account" class="mt-2" />
                </div>
            </div>
            <div>
                <InputLabel for="referral_code" value="Kod referral penjual (pilihan)" />
                <TextInput id="referral_code" v-model="form.referral_code" maxlength="12" class="mt-1 block w-full uppercase" placeholder="Kod daripada penjual yang ajak anda" />
                <p class="mt-1 text-xs text-kelabu">Kedai anda dan kedai yang mengajak dapat 20 pesanan bebas caj platform.</p>
                <InputError :message="form.errors.referral_code" class="mt-2" />
            </div>
            <PrimaryButton class="w-full" :disabled="form.processing">Buka Kedai</PrimaryButton>
        </form>
    </div>
</template>
