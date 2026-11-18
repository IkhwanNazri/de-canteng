<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Icon from '../../Components/Icon.vue';
import BatikPhoto from '../../Components/BatikPhoto.vue';
import ShopCard from '../../Components/ShopCard.vue';
import ProductCard from '../../Components/ProductCard.vue';
import StarRating from '../../Components/StarRating.vue';
import VoucherStrip from '../../Components/VoucherStrip.vue';
import { rm } from '../../format.js';

const props = defineProps({
    product: { type: Object, required: true },
    related: { type: Array, default: () => [] },
    reviews: { type: Array, default: () => [] },
    vouchers: { type: Array, default: () => [] },
});

const isFavorite = computed(() => (usePage().props.favoriteProductIds ?? []).includes(props.product.id));
const toggleFavorite = () =>
    usePage().props.auth.user
        ? router.post(route('favorites.toggle', props.product.id), {}, { preserveScroll: true, preserveState: true })
        : router.visit(route('login'));
const reviewDate = (value) => new Date(value).toLocaleDateString('ms-MY', { dateStyle: 'medium' });

const size = ref(null);
const color = ref(null);
const qty = ref(1);
const adding = ref(false);
const cartError = () => usePage().props.errors.cart;

const addToCart = (buyNow = false) => {
    if (!usePage().props.auth.user) {
        router.visit(route('login'));
        return;
    }
    adding.value = true;
    router.post(
        route('cart.store'),
        { product_id: props.product.id, quantity: qty.value, buy_now: buyNow, size: size.value, color: color.value },
        { preserveScroll: true, onFinish: () => (adding.value = false) },
    );
};
const discount = props.product.compare_price
    ? Math.round((1 - props.product.price / props.product.compare_price) * 100)
    : 0;
</script>

<template>
    <Head :title="product.name" />
    <div class="mx-auto max-w-[1200px] px-4 py-6 md:py-10">
        <Link href="/teroka" class="mb-4 inline-flex items-center gap-1.5 text-sm font-semibold hover:text-hijau">
            <Icon name="back" :size="18" /> Kembali
        </Link>

        <div class="grid gap-6 md:grid-cols-2 md:gap-12">
            <BatikPhoto :src="product.image" :alt="product.name" :variant="product.id" class="aspect-square rounded-3xl" />

            <div>
                <p v-if="product.category" class="text-sm font-semibold text-hijau">{{ product.category.name }}</p>
                <div class="mt-1 flex items-start justify-between gap-3">
                    <h1 class="font-serif text-2xl font-bold md:text-4xl">{{ product.name }}</h1>
                    <button
                        class="grid size-11 shrink-0 place-items-center rounded-full bg-white shadow-sm"
                        :class="isFavorite ? 'text-red-600' : 'text-hijau-gelap'"
                        :aria-label="isFavorite ? 'Buang dari favorit' : 'Tambah ke favorit'"
                        @click="toggleFavorite"
                    >
                        <Icon :name="isFavorite ? 'heart-fill' : 'heart'" :size="22" />
                    </button>
                </div>
                <p class="mt-2 flex items-center gap-1.5 text-sm">
                    <Icon name="star" :size="16" class="text-emas" />
                    <b>{{ product.rating }}</b>
                    <span class="text-kelabu">({{ product.reviews_count }} ulasan) · {{ product.sold_count }} terjual</span>
                </p>

                <div class="mt-4 flex items-center gap-3">
                    <span class="text-3xl font-bold text-red-700">{{ rm(product.price) }}</span>
                    <template v-if="discount">
                        <span class="text-kelabu line-through">{{ rm(product.compare_price) }}</span>
                        <span class="rounded-md bg-red-600 px-2 py-0.5 text-xs font-bold text-white">-{{ discount }}%</span>
                    </template>
                </div>

                <VoucherStrip :vouchers="vouchers" class="mt-5" />

                <template v-if="product.colors?.length">
                    <h2 class="mt-6 text-sm font-semibold">Warna <span class="font-normal text-kelabu">{{ color ?? '— pilih' }}</span></h2>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button
                            v-for="c in product.colors"
                            :key="c"
                            class="rounded-xl border px-4 py-2 text-sm font-semibold"
                            :class="color === c ? 'border-hijau bg-hijau text-white' : 'border-pasir bg-white'"
                            @click="color = c"
                        >
                            {{ c }}
                        </button>
                    </div>
                    <p v-if="usePage().props.errors.color" class="mt-1 text-sm text-red-600">{{ usePage().props.errors.color }}</p>
                </template>

                <template v-if="product.sizes?.length">
                    <h2 class="mt-5 text-sm font-semibold">Saiz</h2>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button
                            v-for="s in product.sizes"
                            :key="s"
                            class="h-10 min-w-12 rounded-xl border px-3 text-sm font-semibold"
                            :class="size === s ? 'border-hijau bg-hijau text-white' : 'border-pasir bg-white'"
                            @click="size = s"
                        >
                            {{ s }}
                        </button>
                    </div>
                    <p v-if="usePage().props.errors.size" class="mt-1 text-sm text-red-600">{{ usePage().props.errors.size }}</p>
                </template>

                <h2 class="mt-5 text-sm font-semibold">Kuantiti <span class="font-normal text-kelabu">(stok {{ product.stock }})</span></h2>
                <div class="mt-2 inline-flex items-center rounded-full bg-white">
                    <button class="grid size-10 place-items-center" aria-label="Kurang" @click="qty = Math.max(1, qty - 1)"><Icon name="minus" :size="16" /></button>
                    <span class="w-8 text-center font-semibold">{{ qty }}</span>
                    <button class="grid size-10 place-items-center" aria-label="Tambah" @click="qty = Math.min(product.stock, qty + 1)"><Icon name="plus" :size="16" /></button>
                </div>

                <div class="mt-6 flex gap-3">
                    <button :disabled="adding || !product.stock" class="grid size-12 place-items-center rounded-full border-2 border-hijau text-hijau disabled:opacity-40" aria-label="Tambah ke troli" @click="addToCart()">
                        <Icon name="cart" :stroke="2" />
                    </button>
                    <button :disabled="adding || !product.stock" class="flex-1 rounded-full bg-hijau py-3 text-center font-semibold text-white hover:bg-hijau-gelap disabled:opacity-40" @click="addToCart(true)">
                        {{ product.stock ? 'Beli Sekarang' : 'Habis Stok' }}
                    </button>
                </div>

                <p v-if="cartError()" class="mt-3 text-sm text-red-600">{{ cartError() }}</p>
                <p class="mt-6 text-sm leading-relaxed text-kelabu">{{ product.description }}</p>

                <ShopCard :shop="product.shop" class="mt-6" />
            </div>
        </div>

        <section class="mt-12">
            <h2 class="mb-4 font-serif text-2xl font-bold text-hijau-gelap">Ulasan ({{ product.reviews_count }})</h2>
            <div v-if="reviews.length" class="space-y-3">
                <article v-for="r in reviews" :key="r.id" class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-semibold">{{ r.user.name }}</span>
                        <span class="text-xs text-kelabu">{{ reviewDate(r.created_at) }}</span>
                    </div>
                    <StarRating :model-value="r.rating" readonly :size="15" />
                    <p v-if="r.body" class="mt-1 text-sm">{{ r.body }}</p>
                </article>
            </div>
            <p v-else class="rounded-2xl bg-white p-6 text-center text-sm text-kelabu">Belum ada ulasan. Pembeli boleh mengulas selepas pesanan diterima.</p>
        </section>

        <section v-if="related.length" class="mt-12">
            <h2 class="mb-4 font-serif text-2xl font-bold text-hijau-gelap">Lagi dari kedai ini</h2>
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-5">
                <ProductCard v-for="p in related" :key="p.id" :product="p" />
            </div>
        </section>
    </div>
</template>
