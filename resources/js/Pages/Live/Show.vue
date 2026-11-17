<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import LiveVideo from '@/Components/LiveVideo.vue';
import LiveChat from '@/Components/LiveChat.vue';
import PinnedProduct from '@/Components/PinnedProduct.vue';
import ProductCard from '@/Components/ProductCard.vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
    session: { type: Object, required: true },
    video: { type: Object, default: null },
});

const status = ref(props.session.status);
const pinnedId = ref(props.session.pinned_product_id);
const viewers = ref(props.session.viewers_count);

const pinned = computed(() => props.session.products.find((p) => p.id === pinnedId.value));

const syncState = (state) => {
    status.value = state.status;
    pinnedId.value = state.pinned_product_id;
    viewers.value = state.viewers_count;
};
</script>

<template>
    <Head :title="session.title" />
    <div class="mx-auto max-w-[1200px] px-4 py-6">
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <Link :href="route('shops.show', session.shop.slug)" class="font-serif text-2xl font-bold text-hijau-gelap hover:underline">{{ session.shop.name }}</Link>
            <span v-if="status === 'live'" class="rounded-md bg-red-600 px-2 py-0.5 text-xs font-bold text-white">LIVE</span>
            <span v-else class="rounded-md bg-pasir px-2 py-0.5 text-xs font-bold">{{ status === 'ended' ? 'TAMAT' : 'BELUM MULA' }}</span>
            <span class="flex items-center gap-1 text-sm text-kelabu"><Icon name="user" :size="15" /> {{ viewers }}</span>
        </div>
        <p class="-mt-2 mb-4 text-kelabu">{{ session.title }}</p>

        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_340px]">
            <LiveVideo :video="video" :active="status === 'live'">
                <PinnedProduct v-if="pinned" :product="pinned" class="absolute right-3 bottom-3 left-3 md:right-auto md:w-96" />
            </LiveVideo>
            <LiveChat :session-id="session.id" :can-comment="status === 'live'" @state="syncState" />
        </div>

        <section class="mt-10">
            <h2 class="mb-4 font-serif text-2xl font-bold text-hijau-gelap">Produk dalam live ini</h2>
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-5">
                <ProductCard v-for="p in session.products" :key="p.id" :product="{ ...p, rating: p.rating ?? '—', reviews_count: p.reviews_count ?? 0 }" />
            </div>
        </section>
    </div>
</template>
