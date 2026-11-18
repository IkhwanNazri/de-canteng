<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { rm } from '@/format.js';

defineProps({
    usable: { type: Array, default: () => [] },
    used: { type: Array, default: () => [] },
});

const date = (value) => (value ? new Date(value).toLocaleDateString('ms-MY', { dateStyle: 'medium' }) : '—');
</script>

<template>
    <Head title="Baucar Saya" />
    <div class="mx-auto max-w-3xl px-4 py-8">
        <h1 class="mb-1 font-serif text-3xl font-bold text-hijau-gelap">Baucar Saya</h1>
        <p class="mb-6 text-sm text-kelabu">Pilih baucar semasa checkout. Satu baucar kedai untuk setiap kedai, dan satu baucar DeBatik.</p>

        <div class="space-y-3">
            <article v-for="v in usable" :key="v.id" class="flex overflow-hidden rounded-2xl border border-emas/40 bg-white shadow-sm">
                <div class="flex w-24 shrink-0 flex-col items-center justify-center bg-gradient-to-b from-emas to-[#b8932b] p-2 text-center text-hijau-gelap">
                    <span class="text-[10px] font-bold uppercase">{{ v.shop ? 'Kedai' : 'DeBatik' }}</span>
                    <span class="text-xl font-black">{{ v.voucher.label.replace('Diskaun ', '') }}</span>
                </div>
                <div class="min-w-0 flex-1 p-4">
                    <p class="font-bold">{{ v.voucher.name }} <span class="font-mono text-xs text-kelabu">{{ v.voucher.code }}</span></p>
                    <p class="text-sm text-kelabu">
                        <Link v-if="v.shop" :href="route('shops.show', v.shop.slug)" class="font-semibold text-hijau">{{ v.shop.name }}</Link>
                        <template v-else>Semua kedai</template>
                        · Min. {{ rm(v.voucher.min_spend) }} · {{ v.voucher.scope_label }}
                    </p>
                    <p class="text-xs text-kelabu">Sah hingga {{ date(v.voucher.ends_at) }}</p>
                </div>
            </article>
            <p v-if="!usable.length" class="rounded-2xl bg-white p-10 text-center text-kelabu">
                Tiada baucar. Tuntut baucar di halaman produk, kedai atau semasa live — atau kumpul cop!
            </p>
        </div>

        <template v-if="used.length">
            <h2 class="mt-10 mb-3 font-bold text-kelabu">Sudah digunakan</h2>
            <ul class="space-y-2 text-sm text-kelabu">
                <li v-for="v in used" :key="v.id" class="rounded-xl bg-white/60 px-4 py-2">{{ v.voucher.name }} ({{ v.voucher.label }}) · {{ date(v.used_at) }}</li>
            </ul>
        </template>
    </div>
</template>
