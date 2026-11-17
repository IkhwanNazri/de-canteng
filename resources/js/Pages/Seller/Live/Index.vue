<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import SellerNav from '@/Components/SellerNav.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { rm } from '@/format.js';

const props = defineProps({
    sessions: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
});

const STATUS = { scheduled: ['Dijadual', 'bg-pasir'], live: ['LIVE', 'bg-red-600 text-white'], ended: ['Tamat', 'bg-gray-200 text-kelabu'] };
const form = useForm({ title: '', product_ids: [] });
</script>

<template>
    <Head title="Live Saya" />
    <div class="mx-auto max-w-[1200px] px-4 py-8">
        <p class="text-sm text-kelabu">Pusat Penjual</p>
        <h1 class="mb-4 font-serif text-3xl font-bold text-hijau-gelap">Live Shopping</h1>
        <SellerNav />

        <div class="grid gap-6 md:grid-cols-[1fr_380px]">
            <div class="space-y-3">
                <Link
                    v-for="s in sessions"
                    :key="s.id"
                    :href="route('seller.live.show', s.id)"
                    class="flex items-center gap-4 rounded-2xl bg-white p-4 shadow-sm hover:-translate-y-0.5 transition"
                >
                    <span class="rounded-md px-2 py-0.5 text-xs font-bold" :class="STATUS[s.status][1]">{{ STATUS[s.status][0] }}</span>
                    <span class="flex-1 font-semibold">{{ s.title }}</span>
                    <span class="text-sm text-kelabu">{{ s.products_count }} produk</span>
                </Link>
                <p v-if="!sessions.length" class="rounded-2xl bg-white p-10 text-center text-kelabu">Belum ada sesi live.</p>
            </div>

            <form class="space-y-4 rounded-3xl bg-white p-6 shadow-sm" @submit.prevent="form.post(route('seller.live.store'))">
                <h2 class="font-serif text-xl font-bold">Sesi Live Baharu</h2>
                <div>
                    <TextInput v-model="form.title" placeholder="Tajuk, cth: Koleksi Raya 2027" class="block w-full" required />
                    <InputError :message="form.errors.title" class="mt-2" />
                </div>
                <div>
                    <p class="mb-2 text-sm font-semibold">Produk untuk dijual dalam live</p>
                    <div class="max-h-64 space-y-1 overflow-y-auto">
                        <label v-for="p in products" :key="p.id" class="flex cursor-pointer items-center gap-3 rounded-xl p-2 hover:bg-krim">
                            <input v-model="form.product_ids" type="checkbox" :value="p.id" class="size-4 accent-hijau" />
                            <span class="flex-1 text-sm">{{ p.name }}</span>
                            <span class="text-xs text-kelabu">{{ rm(p.price) }}</span>
                        </label>
                    </div>
                    <p v-if="!products.length" class="text-sm text-kelabu">Tambah produk dahulu.</p>
                    <InputError :message="form.errors.product_ids" class="mt-2" />
                </div>
                <PrimaryButton class="w-full" :disabled="form.processing || !products.length">Cipta & Buka Studio</PrimaryButton>
            </form>
        </div>
    </div>
</template>
