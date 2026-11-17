<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SellerNav from '@/Components/SellerNav.vue';
import StatTile from '@/Components/StatTile.vue';
import SalesChart from '@/Components/SalesChart.vue';
import OrderStatusBadge from '@/Components/OrderStatusBadge.vue';
import { rm } from '@/format.js';

defineProps({
    shop: { type: Object, required: true },
    stats: { type: Object, required: true },
    dailySales: { type: Array, required: true },
    recentOrders: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Dashboard Penjual" />
    <div class="mx-auto max-w-[1200px] px-4 py-8">
        <p class="text-sm text-kelabu">Pusat Penjual</p>
        <h1 class="mb-4 font-serif text-3xl font-bold text-hijau-gelap">{{ shop.name }}</h1>
        <SellerNav />

        <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-4">
            <StatTile label="Baki boleh dikeluarkan" :value="rm(stats.balance)" icon="bag" highlight />
            <StatTile label="Jualan bulan ini" :value="rm(stats.salesThisMonth)" icon="star" />
            <StatTile label="Perlu dihantar" :value="stats.ordersToShip" icon="truck" />
            <StatTile label="Caj platform dibayar" :value="rm(stats.commissionPaid)" icon="shield" />
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_400px]">
            <SalesChart :data="dailySales" />

            <section class="rounded-3xl bg-white p-5 shadow-sm">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="font-bold">Pesanan terkini</h2>
                    <Link :href="route('seller.orders.index')" class="text-sm font-semibold text-hijau">Semua</Link>
                </div>
                <div v-for="o in recentOrders" :key="o.id" class="flex items-center justify-between gap-3 border-t border-pasir py-2.5 text-sm">
                    <div class="min-w-0">
                        <p class="font-semibold">{{ o.number }}</p>
                        <p class="truncate text-kelabu">{{ o.buyer.name }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold tabular-nums">{{ rm(o.total) }}</p>
                        <OrderStatusBadge :status="o.status" :label="o.status_label" />
                    </div>
                </div>
                <p v-if="!recentOrders.length" class="py-6 text-center text-sm text-kelabu">Belum ada pesanan.</p>
            </section>
        </div>
        <p class="mt-4 text-xs text-kelabu">Caj platform RM1 dipotong untuk setiap pesanan yang dibayar. Ciri pengeluaran (withdraw) akan datang.</p>
    </div>
</template>
