<script setup>
import { Link } from '@inertiajs/vue3';
import BatikPhoto from './BatikPhoto.vue';
import { rm } from '../format.js';

defineProps({ product: { type: Object, required: true } });
</script>

<template>
    <div class="flex items-center gap-3 rounded-2xl bg-white/95 p-2.5 pr-3 shadow-lg backdrop-blur">
        <BatikPhoto :src="product.image" :alt="product.name" :variant="product.id" :person="false" class="size-14 shrink-0 rounded-xl" />
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold">{{ product.name }}</p>
            <p class="text-sm font-bold text-red-700">
                {{ rm(product.price) }}
                <span v-if="product.compare_price" class="ml-1 text-xs font-normal text-kelabu line-through">{{ rm(product.compare_price) }}</span>
            </p>
            <p class="text-xs text-kelabu">Stok {{ product.stock }}</p>
        </div>
        <Link v-if="product.slug" :href="route('products.show', product.slug)" class="rounded-full bg-emas px-4 py-2 text-sm font-bold text-hijau-gelap">Beli</Link>
    </div>
</template>
