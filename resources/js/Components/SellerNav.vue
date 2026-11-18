<script setup>
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const TABS = [
    { label: 'Dashboard', name: 'seller.dashboard', match: (url) => url === '/penjual' },
    { label: 'Pesanan', name: 'seller.orders.index', match: (url) => url.startsWith('/penjual/pesanan') },
    { label: 'Produk', name: 'seller.products.index', match: (url) => url.startsWith('/penjual/produk') },
    { label: 'Baucar', name: 'seller.vouchers.index', match: (url) => url.startsWith('/penjual/baucar') },
    { label: 'Live', name: 'seller.live.index', match: (url) => url.startsWith('/penjual/live') },
    { label: 'Pengeluaran', name: 'seller.withdrawals.index', match: (url) => url.startsWith('/penjual/pengeluaran') },
    { label: 'Tetapan Kedai', name: 'seller.settings.edit', match: (url) => url.startsWith('/penjual/tetapan') },
];
</script>

<template>
    <nav class="mb-6 flex gap-2 overflow-x-auto">
        <Link
            v-for="tab in TABS"
            :key="tab.name"
            :href="route(tab.name)"
            prefetch
            class="shrink-0 rounded-full px-5 py-2 text-sm font-semibold"
            :class="tab.match(page.url.split('?')[0]) ? 'bg-hijau-gelap text-white' : 'bg-white hover:bg-pasir'"
        >
            {{ tab.label }}
        </Link>
    </nav>
</template>
