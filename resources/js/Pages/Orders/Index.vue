<script setup>
import { Head, Link } from '@inertiajs/vue3';
import OrderStatusBadge from '@/Components/OrderStatusBadge.vue';
import { rm } from '@/format.js';

defineProps({ orders: { type: Object, required: true } });
</script>

<template>
    <Head title="Pesanan Saya" />
    <div class="mx-auto max-w-3xl px-4 py-8">
        <h1 class="mb-6 font-serif text-3xl font-bold text-hijau-gelap">Pesanan Saya</h1>
        <div class="space-y-3">
            <Link
                v-for="o in orders.data"
                :key="o.id"
                :href="route('orders.show', o.number)"
                prefetch
                class="block rounded-2xl bg-white p-5 shadow-sm transition hover:-translate-y-0.5"
            >
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="font-bold">{{ o.shop.name }}</span>
                    <OrderStatusBadge :status="o.status" :label="o.status_label" />
                </div>
                <p class="mt-1 line-clamp-1 text-sm text-kelabu">{{ o.items.map((i) => `${i.quantity}× ${i.product_name}`).join(', ') }}</p>
                <div class="mt-2 flex justify-between text-sm">
                    <span class="text-kelabu">{{ o.number }}</span>
                    <span class="font-bold tabular-nums">{{ rm(o.total) }}</span>
                </div>
            </Link>
            <p v-if="!orders.data.length" class="rounded-2xl bg-white p-10 text-center text-kelabu">Belum ada pesanan.</p>
        </div>
    </div>
</template>
