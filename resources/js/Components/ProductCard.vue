<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import BatikPhoto from './BatikPhoto.vue';
import Icon from './Icon.vue';
import { rm } from '../format.js';

const props = defineProps({ product: { type: Object, required: true } });

const page = usePage();
const isFavorite = computed(() => (page.props.favoriteProductIds ?? []).includes(props.product.id));

const toggleFavorite = () => {
    if (!page.props.auth.user) {
        router.visit(route('login'));
        return;
    }
    router.post(route('favorites.toggle', props.product.id), {}, { preserveScroll: true, preserveState: true });
};
</script>

<template>
    <Link :href="`/produk/${product.slug}`" prefetch class="group block overflow-hidden rounded-2xl bg-white shadow-[0_8px_24px_rgba(0,0,0,0.06)]">
        <BatikPhoto :src="product.image" :alt="product.name" :variant="product.id" class="aspect-square">
            <button
                type="button"
                class="absolute top-2.5 right-2.5 grid size-8 place-items-center rounded-full bg-white/90 transition active:scale-90"
                :class="isFavorite ? 'text-red-600' : 'text-hijau-gelap'"
                :aria-label="isFavorite ? 'Buang dari favorit' : 'Tambah ke favorit'"
                @click.prevent.stop="toggleFavorite"
            >
                <Icon :name="isFavorite ? 'heart-fill' : 'heart'" :size="17" />
            </button>
            <span
                v-if="product.compare_price"
                class="absolute top-2.5 left-2.5 rounded-md bg-red-600 px-1.5 py-0.5 text-[11px] font-bold text-white"
            >
                -{{ Math.round((1 - product.price / product.compare_price) * 100) }}%
            </span>
        </BatikPhoto>
        <div class="p-3">
            <h3 class="line-clamp-2 min-h-10 text-sm font-semibold group-hover:text-hijau">{{ product.name }}</h3>
            <p class="mt-1 font-bold text-hijau-gelap">{{ rm(product.price) }}</p>
            <p class="mt-1 flex items-center gap-1 text-xs text-kelabu">
                <Icon name="star" :size="13" class="text-emas" /> {{ product.rating }} ({{ product.reviews_count }})
            </p>
        </div>
    </Link>
</template>
