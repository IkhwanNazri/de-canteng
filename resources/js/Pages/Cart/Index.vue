<script setup>
import { computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import BatikPhoto from '@/Components/BatikPhoto.vue';
import Icon from '@/Components/Icon.vue';
import { rm, variantLabel } from '@/format.js';

const props = defineProps({
    groups: { type: Array, default: () => [] },
    shippingFee: { type: Number, required: true },
});

const errors = computed(() => usePage().props.errors);
const subtotal = computed(() => props.groups.flatMap((g) => g.items).reduce((sum, i) => sum + i.product.price * i.quantity, 0));
const shipping = computed(() => props.groups.length * props.shippingFee);

const setQuantity = (item, quantity) => {
    if (quantity < 1 || quantity > item.product.stock) return;
    router.patch(route('cart.update', item.id), { quantity }, { preserveScroll: true });
};
const remove = (item) => router.delete(route('cart.destroy', item.id), { preserveScroll: true });
</script>

<template>
    <Head title="Troli" />
    <div class="mx-auto max-w-[1200px] px-4 py-8">
        <h1 class="mb-6 font-serif text-3xl font-bold text-hijau-gelap">Troli Saya</h1>
        <p v-if="errors.cart" class="mb-4 rounded-xl bg-red-50 p-3 text-sm text-red-700">{{ errors.cart }}</p>

        <div v-if="groups.length" class="grid gap-6 lg:grid-cols-[1fr_340px]">
            <div class="space-y-4">
                <section v-for="group in groups" :key="group.shop.id" class="rounded-3xl bg-white p-5 shadow-sm">
                    <Link :href="route('shops.show', group.shop.slug)" class="mb-3 flex items-center gap-2 font-bold hover:text-hijau">
                        <Icon name="store" :size="18" /> {{ group.shop.name }}
                    </Link>
                    <div v-for="item in group.items" :key="item.id" class="flex items-center gap-4 border-t border-pasir py-3">
                        <BatikPhoto :src="item.product.image" :variant="item.product.id" :person="false" class="size-16 shrink-0 rounded-xl" />
                        <div class="min-w-0 flex-1">
                            <Link :href="route('products.show', item.product.slug)" class="line-clamp-1 font-semibold hover:text-hijau">{{ item.product.name }}</Link>
                            <p v-if="variantLabel(item)" class="text-xs text-kelabu">{{ variantLabel(item) }}</p>
                            <p class="text-sm font-bold text-hijau-gelap">{{ rm(item.product.price) }}</p>
                            <p v-if="item.quantity >= item.product.stock" class="text-xs text-amber-700">Stok maksimum ({{ item.product.stock }})</p>
                        </div>
                        <div class="inline-flex items-center rounded-full bg-krim">
                            <button class="grid size-8 place-items-center" aria-label="Kurang" @click="setQuantity(item, item.quantity - 1)"><Icon name="minus" :size="14" /></button>
                            <span class="w-7 text-center text-sm font-semibold">{{ item.quantity }}</span>
                            <button class="grid size-8 place-items-center" aria-label="Tambah" @click="setQuantity(item, item.quantity + 1)"><Icon name="plus" :size="14" /></button>
                        </div>
                        <button class="text-sm font-semibold text-red-600" @click="remove(item)">Buang</button>
                    </div>
                </section>
            </div>

            <aside class="h-fit rounded-3xl bg-white p-6 shadow-sm lg:sticky lg:top-24">
                <h2 class="mb-4 font-bold">Ringkasan</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt>Subjumlah</dt><dd class="tabular-nums">{{ rm(subtotal) }}</dd></div>
                    <div class="flex justify-between"><dt>Pos ({{ groups.length }} kedai × {{ rm(shippingFee) }})</dt><dd class="tabular-nums">{{ rm(shipping) }}</dd></div>
                    <div class="flex justify-between border-t border-pasir pt-3 text-base font-bold"><dt>Jumlah</dt><dd class="tabular-nums text-hijau-gelap">{{ rm(subtotal + shipping) }}</dd></div>
                </dl>
                <Link :href="route('checkout.create')" class="mt-5 block rounded-full bg-hijau py-3 text-center font-semibold text-white hover:bg-hijau-gelap">Teruskan Pembayaran</Link>
            </aside>
        </div>

        <div v-else class="rounded-3xl bg-white p-12 text-center shadow-sm">
            <Icon name="cart" :size="44" class="mx-auto text-kelabu" />
            <p class="mt-3 text-kelabu">Troli anda kosong.</p>
            <Link :href="route('products.index')" class="mt-5 inline-block rounded-full bg-emas px-6 py-3 font-semibold text-hijau-gelap">Teroka Batik</Link>
        </div>
    </div>
</template>
