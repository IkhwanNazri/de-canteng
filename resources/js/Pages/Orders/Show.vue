<script setup>
import { reactive } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import StarRating from '@/Components/StarRating.vue';
import InputError from '@/Components/InputError.vue';
import OrderStatusBadge from '@/Components/OrderStatusBadge.vue';
import { rm, variantLabel } from '@/format.js';

const props = defineProps({
    order: { type: Object, required: true },
    reviewedProductIds: { type: Array, default: () => [] },
});

const canReview = ['shipped', 'completed'].includes(props.order.status);
const forms = reactive(
    Object.fromEntries(props.order.items.filter((i) => i.product_id).map((i) => [i.product_id, useForm({ product_id: i.product_id, rating: 5, body: '' })])),
);
const submitReview = (productId) => forms[productId].post(route('reviews.store', props.order.number), { preserveScroll: true });
const complete = () => router.post(route('orders.complete', props.order.number), {}, { preserveScroll: true });
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
                    <span>{{ item.quantity }}× {{ item.product_name }}<span v-if="variantLabel(item)" class="text-kelabu"> ({{ variantLabel(item) }})</span></span>
                    <span class="tabular-nums">{{ rm(item.line_total) }}</span>
                </div>
                <dl class="mt-2 space-y-1 border-t border-pasir pt-3 text-sm">
                    <div class="flex justify-between"><dt>Pos</dt><dd class="tabular-nums">{{ rm(order.shipping_fee) }}</dd></div>
                    <div class="flex justify-between font-bold"><dt>Jumlah</dt><dd class="tabular-nums">{{ rm(order.total) }}</dd></div>
                </dl>
            </section>
            <section v-if="order.status === 'shipped'" class="flex flex-wrap items-center justify-between gap-3 rounded-3xl bg-krim p-5">
                <p class="text-sm">Dah terima barang? Sahkan supaya penjual tahu.</p>
                <button class="rounded-full bg-hijau px-5 py-2 text-sm font-semibold text-white" @click="complete">Saya Dah Terima</button>
            </section>

            <section v-if="canReview" class="space-y-4 rounded-3xl bg-white p-6 shadow-sm">
                <h2 class="font-bold">Ulasan Produk</h2>
                <template v-for="item in order.items.filter((i) => i.product_id)" :key="item.id">
                    <p v-if="reviewedProductIds.includes(item.product_id)" class="text-sm text-kelabu">✓ {{ item.product_name }} — sudah diulas</p>
                    <form v-else class="space-y-2 border-t border-pasir pt-4 first:border-0 first:pt-0" @submit.prevent="submitReview(item.product_id)">
                        <p class="text-sm font-semibold">{{ item.product_name }}</p>
                        <StarRating v-model="forms[item.product_id].rating" :size="24" />
                        <textarea
                            v-model="forms[item.product_id].body"
                            rows="2"
                            maxlength="1000"
                            placeholder="Kongsi pengalaman anda (pilihan)"
                            class="block w-full rounded-xl border border-pasir px-4 py-2.5 text-sm outline-none focus:border-hijau"
                        />
                        <InputError :message="forms[item.product_id].errors.product_id || forms[item.product_id].errors.rating" />
                        <button :disabled="forms[item.product_id].processing" class="rounded-full bg-hijau px-5 py-2 text-sm font-semibold text-white disabled:opacity-50">Hantar Ulasan</button>
                    </form>
                </template>
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
