<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import SellerNav from '@/Components/SellerNav.vue';
import BatikPhoto from '@/Components/BatikPhoto.vue';
import Icon from '@/Components/Icon.vue';
import { rm } from '@/format.js';

defineProps({
    shop: { type: Object, required: true },
    products: { type: Object, required: true },
});

const destroy = (product) => {
    if (confirm(`Padam "${product.name}"?`)) {
        router.delete(route('seller.products.destroy', product.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Produk Saya" />
    <div class="mx-auto max-w-[1200px] px-4 py-8">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm text-kelabu">Pusat Penjual</p>
                <h1 class="font-serif text-3xl font-bold text-hijau-gelap">{{ shop.name }}</h1>
            </div>
            <div class="flex gap-2">
                <Link :href="route('shops.show', shop.slug)" class="rounded-full bg-white px-5 py-2.5 text-sm font-semibold">Lihat Kedai</Link>
                <Link :href="route('seller.products.create')" class="flex items-center gap-1.5 rounded-full bg-hijau px-5 py-2.5 text-sm font-semibold text-white">
                    <Icon name="plus" :size="16" :stroke="2.2" /> Tambah Produk
                </Link>
            </div>
        </div>
        <SellerNav />

        <div class="overflow-hidden rounded-3xl bg-white shadow-sm">
            <div v-for="p in products.data" :key="p.id" class="flex items-center gap-4 border-b border-pasir p-4 last:border-0">
                <BatikPhoto :src="p.image" :alt="p.name" :variant="p.id" :person="false" class="size-16 shrink-0 rounded-xl" />
                <div class="min-w-0 flex-1">
                    <p class="truncate font-semibold">{{ p.name }}</p>
                    <p class="text-sm text-kelabu">{{ p.category?.name ?? 'Tiada kategori' }} · Stok {{ p.stock }}</p>
                </div>
                <p class="hidden font-bold text-hijau-gelap sm:block">{{ rm(p.price) }}</p>
                <Link :href="route('seller.products.edit', p.id)" class="rounded-full bg-krim px-4 py-2 text-sm font-semibold">Edit</Link>
                <button class="rounded-full px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50" @click="destroy(p)">Padam</button>
            </div>
            <p v-if="!products.data.length" class="p-10 text-center text-kelabu">Belum ada produk. Tekan “Tambah Produk”.</p>
        </div>
    </div>
</template>
