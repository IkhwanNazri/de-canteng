<script setup>
import { Head, Link } from '@inertiajs/vue3';
import StatTile from '@/Components/StatTile.vue';
import SalesChart from '@/Components/SalesChart.vue';
import OrderStatusBadge from '@/Components/OrderStatusBadge.vue';
import { rm } from '@/format.js';

defineProps({
    stats: { type: Object, required: true },
    dailySales: { type: Array, required: true },
    recentOrders: { type: Array, default: () => [] },
    topShops: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Panel Admin" />
    <div class="mx-auto max-w-[1200px] px-4 py-8">
        <p class="text-sm text-kelabu">Panel Admin</p>
        <h1 class="mb-6 font-serif text-3xl font-bold text-hijau-gelap">Ringkasan DeBatik</h1>

        <div class="grid grid-cols-2 gap-3 md:grid-cols-5 md:gap-4">
            <StatTile label="Komisen terkumpul (RM1/pesanan)" :value="rm(stats.commission)" icon="bag" highlight />
            <StatTile label="Jumlah jualan (GMV)" :value="rm(stats.gmv)" icon="star" />
            <StatTile label="Pesanan dibayar" :value="stats.orders" icon="truck" />
            <StatTile label="Kedai" :value="stats.shops" icon="store" />
            <StatTile label="Pembeli" :value="stats.buyers" icon="user" />
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_380px]">
            <SalesChart :data="dailySales" title="Jualan platform 14 hari" />

            <section class="rounded-3xl bg-white p-5 shadow-sm">
                <h2 class="mb-3 font-bold">Kedai teratas</h2>
                <ol class="space-y-1">
                    <li v-for="(s, i) in topShops" :key="s.id" class="flex items-center gap-3 border-t border-pasir py-2.5 text-sm first:border-0">
                        <span class="grid size-6 place-items-center rounded-full bg-krim text-xs font-bold">{{ i + 1 }}</span>
                        <Link :href="route('shops.show', s.slug)" class="flex-1 font-semibold hover:text-hijau">{{ s.name }}</Link>
                        <span class="text-kelabu">{{ s.orders_count }} pesanan</span>
                        <span class="font-bold tabular-nums">{{ rm(s.sales ?? 0) }}</span>
                    </li>
                </ol>
            </section>
        </div>

        <section class="mt-6 overflow-x-auto rounded-3xl bg-white p-5 shadow-sm">
            <h2 class="mb-3 font-bold">Pesanan terkini</h2>
            <table class="w-full text-left text-sm">
                <thead class="text-kelabu">
                    <tr><th class="py-2 font-medium">No.</th><th class="font-medium">Kedai</th><th class="font-medium">Pembeli</th><th class="font-medium">Status</th><th class="text-right font-medium">Jumlah</th><th class="text-right font-medium">Komisen</th></tr>
                </thead>
                <tbody>
                    <tr v-for="o in recentOrders" :key="o.id" class="border-t border-pasir">
                        <td class="py-2.5 font-semibold">{{ o.number }}</td>
                        <td>{{ o.shop.name }}</td>
                        <td>{{ o.buyer.name }}</td>
                        <td><OrderStatusBadge :status="o.status" :label="o.status_label" /></td>
                        <td class="text-right tabular-nums">{{ rm(o.total) }}</td>
                        <td class="text-right tabular-nums">{{ rm(o.commission) }}</td>
                    </tr>
                    <tr v-if="!recentOrders.length"><td colspan="6" class="py-6 text-center text-kelabu">Belum ada pesanan.</td></tr>
                </tbody>
            </table>
        </section>
    </div>
</template>
