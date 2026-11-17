<script setup>
import { reactive } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import SellerNav from '@/Components/SellerNav.vue';
import OrderStatusBadge from '@/Components/OrderStatusBadge.vue';
import { rm } from '@/format.js';

const props = defineProps({
    orders: { type: Object, required: true },
    status: { type: String, default: null },
    statuses: { type: Array, required: true },
});

const tracking = reactive({});
const ship = (order) =>
    router.patch(route('seller.orders.ship', order.id), { tracking_number: tracking[order.id] ?? '' }, { preserveScroll: true });
const errors = () => usePage().props.errors;
</script>

<template>
    <Head title="Pesanan Kedai" />
    <div class="mx-auto max-w-[1200px] px-4 py-8">
        <p class="text-sm text-kelabu">Pusat Penjual</p>
        <h1 class="mb-4 font-serif text-3xl font-bold text-hijau-gelap">Pesanan</h1>
        <SellerNav />

        <div class="mb-4 flex gap-2 overflow-x-auto">
            <Link :href="route('seller.orders.index')" class="shrink-0 rounded-full px-4 py-1.5 text-sm font-semibold" :class="!status ? 'bg-emas text-hijau-gelap' : 'bg-white'">Semua</Link>
            <Link
                v-for="s in statuses"
                :key="s.value"
                :href="route('seller.orders.index', { status: s.value })"
                class="shrink-0 rounded-full px-4 py-1.5 text-sm font-semibold"
                :class="status === s.value ? 'bg-emas text-hijau-gelap' : 'bg-white'"
            >
                {{ s.label }}
            </Link>
        </div>
        <p v-if="errors().tracking_number" class="mb-3 rounded-xl bg-red-50 p-3 text-sm text-red-700">{{ errors().tracking_number }}</p>

        <div class="space-y-3">
            <article v-for="o in orders.data" :key="o.id" class="rounded-2xl bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="font-bold">{{ o.number }} <span class="font-normal text-kelabu">· {{ o.buyer.name }}</span></span>
                    <OrderStatusBadge :status="o.status" :label="o.status_label" />
                </div>
                <p class="mt-1 text-sm">{{ o.items.map((i) => `${i.quantity}× ${i.product_name}`).join(', ') }}</p>
                <p class="mt-1 text-sm text-kelabu">{{ o.recipient_name }}, {{ o.phone }} — {{ o.address }}, {{ o.postcode }} {{ o.city }}, {{ o.state }}</p>
                <div class="mt-3 flex flex-wrap items-center justify-between gap-3 border-t border-pasir pt-3">
                    <span class="text-sm">
                        Jumlah <b class="tabular-nums">{{ rm(o.total) }}</b>
                        <span v-if="o.seller_amount" class="text-kelabu"> · Anda terima {{ rm(o.seller_amount) }}</span>
                    </span>
                    <form v-if="o.status === 'paid'" class="flex gap-2" @submit.prevent="ship(o)">
                        <input v-model="tracking[o.id]" placeholder="No. tracking" required class="w-44 rounded-full border border-pasir px-4 py-2 text-sm outline-none focus:border-hijau" />
                        <button class="rounded-full bg-hijau px-4 py-2 text-sm font-semibold text-white">Tanda Dihantar</button>
                    </form>
                    <span v-else-if="o.tracking_number" class="text-sm text-kelabu">Tracking: {{ o.tracking_number }}</span>
                </div>
            </article>
            <p v-if="!orders.data.length" class="rounded-2xl bg-white p-10 text-center text-kelabu">Tiada pesanan.</p>
        </div>
    </div>
</template>
