<script setup>
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const TABS = [
    { label: 'Ringkasan', name: 'admin.dashboard', match: (url) => url === '/admin' },
    { label: 'Pengeluaran Penjual', name: 'admin.withdrawals.index', match: (url) => url.startsWith('/admin/pengeluaran') },
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
