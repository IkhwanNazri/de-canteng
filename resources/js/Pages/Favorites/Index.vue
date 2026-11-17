<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ProductCard from '@/Components/ProductCard.vue';

defineProps({ products: { type: Array, default: () => [] } });
</script>

<template>
    <Head title="Favorit" />
    <div class="mx-auto max-w-[1200px] px-4 py-8">
        <h1 class="mb-6 font-serif text-3xl font-bold text-hijau-gelap">Favorit Saya</h1>
        <div v-if="products.length" class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-5">
            <ProductCard v-for="p in products" :key="p.id" :product="p" />
        </div>
        <div v-else class="rounded-3xl bg-white p-12 text-center text-kelabu shadow-sm">
            Tekan ♡ pada produk untuk simpan di sini.
            <Link :href="route('products.index')" class="mt-4 block font-semibold text-hijau">Teroka batik →</Link>
        </div>
    </div>
</template>
