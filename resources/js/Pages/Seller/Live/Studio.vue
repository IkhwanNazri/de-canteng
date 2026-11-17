<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import LiveVideo from '@/Components/LiveVideo.vue';
import LiveChat from '@/Components/LiveChat.vue';
import PinnedProduct from '@/Components/PinnedProduct.vue';
import BatikPhoto from '@/Components/BatikPhoto.vue';
import Icon from '@/Components/Icon.vue';
import { rm } from '@/format.js';

const props = defineProps({
    session: { type: Object, required: true },
    video: { type: Object, default: null },
});

const viewers = ref(props.session.viewers_count);
const isLive = computed(() => props.session.status === 'live');
const pinned = computed(() => props.session.products.find((p) => p.id === props.session.pinned_product_id));

const update = (data) => router.patch(route('seller.live.update', props.session.id), data, { preserveScroll: true, preserveState: true });
const start = () => router.post(route('seller.live.start', props.session.id), {}, { preserveScroll: true });
const end = () => confirm('Tamatkan live?') && router.post(route('seller.live.end', props.session.id), {}, { preserveScroll: true });
</script>

<template>
    <Head :title="`Studio: ${session.title}`" />
    <div class="mx-auto max-w-[1200px] px-4 py-6">
        <Link :href="route('seller.live.index')" class="text-sm font-semibold hover:text-hijau">← Live Saya</Link>
        <div class="mt-2 mb-5 flex flex-wrap items-center gap-3">
            <h1 class="font-serif text-2xl font-bold text-hijau-gelap md:text-3xl">{{ session.title }}</h1>
            <span v-if="isLive" class="rounded-md bg-red-600 px-2 py-0.5 text-xs font-bold text-white">LIVE</span>
            <span class="flex items-center gap-1 text-sm text-kelabu"><Icon name="user" :size="15" /> {{ viewers }} tontonan</span>
            <div class="ml-auto flex gap-2">
                <a v-if="isLive" :href="route('live.show', session.id)" target="_blank" class="rounded-full bg-white px-4 py-2 text-sm font-semibold">Lihat sebagai penonton</a>
                <button v-if="session.status !== 'live'" class="rounded-full bg-red-600 px-5 py-2 text-sm font-bold text-white" @click="start">● Mula Live</button>
                <button v-else class="rounded-full bg-dakwat px-5 py-2 text-sm font-bold text-white" @click="end">Tamat Live</button>
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-[1fr_340px]">
            <div class="space-y-5">
                <LiveVideo :key="session.status" :video="video" publish :active="isLive">
                    <PinnedProduct v-if="pinned" :product="pinned" class="absolute right-3 bottom-3 left-3 md:right-auto md:w-96" />
                </LiveVideo>
                <p v-if="!isLive" class="text-sm text-kelabu">Tekan “Mula Live” untuk hidupkan kamera dan buka komen kepada penonton.</p>

                <div class="rounded-3xl bg-white p-5 shadow-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="font-bold">Produk dalam live — tekan untuk pin</h2>
                        <label class="flex items-center gap-2 text-sm font-semibold">
                            <input type="checkbox" class="size-4 accent-hijau" :checked="session.auto_reply" @change="update({ auto_reply: $event.target.checked })" />
                            Auto-jawab komen
                        </label>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <button
                            v-for="p in session.products"
                            :key="p.id"
                            class="flex items-center gap-3 rounded-2xl border-2 p-2 text-left transition"
                            :class="p.id === session.pinned_product_id ? 'border-emas bg-krim' : 'border-transparent bg-krim/50 hover:border-pasir'"
                            @click="update({ pinned_product_id: p.id })"
                        >
                            <BatikPhoto :src="p.image" :variant="p.id" :person="false" class="size-12 shrink-0 rounded-xl" />
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold">{{ p.name }}</span>
                                <span class="text-xs text-kelabu">{{ rm(p.price) }} · stok {{ p.stock }}</span>
                            </span>
                            <span v-if="p.id === session.pinned_product_id" class="text-xs font-bold text-emas">DIPIN</span>
                        </button>
                    </div>
                </div>
            </div>

            <LiveChat :session-id="session.id" :can-comment="isLive" @state="viewers = $event.viewers_count" />
        </div>
    </div>
</template>
