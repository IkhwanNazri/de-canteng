<script setup>
import { Head, Link } from '@inertiajs/vue3';
import OrderStatusBadge from '@/Components/OrderStatusBadge.vue';
import { rm } from '@/format.js';

defineProps({ order: { type: Object, required: true } });
const date = (value) => (value ? new Date(value).toLocaleString('ms-MY', { dateStyle: 'medium', timeStyle: 'short' }) : '—');
</script>

<template>
    <Head :title="order.number" />
    <div class="mx-auto max-w-3xl px-4 py-8">
        <Link :href="route('orders.index')" class="text-sm font-semibold hover:text-hijau">← Pesanan Saya</Link>
        <div class="mt-2 mb-6 flex flex-wrap items-center gap-3">
            <h1 class="font-serif text-2xl font-bold text-hijau-gelap">{{ order.number }}</h1>
            <OrderStatusBadge :status="order.status" :label="order.status_label" />
        </div>

        <div class="space-y-4">
            <section class="rounded-3xl bg-white p-6 shadow-sm">
                <h2 class="mb-3 font-bold">{{ order.shop.name }}</h2>
                <div v-for="item in order.items" :key="item.id" class="flex justify-between border-t border-pasir py-2 text-sm">
                    <span>{{ item.quantity }}× {{ item.product_name }}</span>
                    <span class="tabular-nums">{{ rm(item.line_total) }}</span>
                </div>
                <dl class="mt-2 space-y-1 border-t border-pasir pt-3 text-sm">
                    <div class="flex justify-between"><dt>Pos</dt><dd class="tabular-nums">{{ rm(order.shipping_fee) }}</dd></div>
                    <div class="flex justify-between font-bold"><dt>Jumlah</dt><dd class="tabular-nums">{{ rm(order.total) }}</dd></div>
                </dl>
            </section>
            <section class="grid gap-4 rounded-3xl bg-white p-6 text-sm shadow-sm md:grid-cols-2">
                <div>
                    <h2 class="mb-2 font-bold">Penghantaran</h2>
                    <p>{{ order.recipient_name }} · {{ order.phone }}</p>
                    <p class="text-kelabu">{{ order.address }}, {{ order.postcode }} {{ order.city }}, {{ order.state }}</p>
                    <p v-if="order.tracking_number" class="mt-2">No. tracking: <b>{{ order.tracking_number }}</b></p>
                </div>
                <div>
                    <h2 class="mb-2 font-bold">Masa</h2>
                    <p>Dibuat: {{ date(order.created_at) }}</p>
                    <p>Dibayar: {{ date(order.paid_at) }}</p>
                    <p>Dihantar: {{ date(order.shipped_at) }}</p>
                </div>
            </section>
        </div>
    </div>
</template>
