<script setup>
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const TABS = [
    { label: 'Produk', name: 'seller.products.index', prefix: '/penjual/produk' },
    { label: 'Live', name: 'seller.live.index', prefix: '/penjual/live' },
];
</script>

<template>
    <nav class="mb-6 flex gap-2">
        <Link
            v-for="tab in TABS"
            :key="tab.name"
            :href="route(tab.name)"
            class="rounded-full px-5 py-2 text-sm font-semibold"
            :class="page.url.startsWith(tab.prefix) ? 'bg-hijau-gelap text-white' : 'bg-white hover:bg-pasir'"
        >
            {{ tab.label }}
        </Link>
    </nav>
</template>
