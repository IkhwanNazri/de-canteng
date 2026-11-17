<script setup>
import { Head } from '@inertiajs/vue3';
import LiveCard from '@/Components/LiveCard.vue';
import SectionHeader from '@/Components/SectionHeader.vue';

defineProps({
    lives: { type: Array, default: () => [] },
    upcoming: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Live Shopping" />
    <div class="mx-auto max-w-[1440px] space-y-10 px-4 py-8 md:px-[120px] md:py-12">
        <section>
            <SectionHeader title="Sedang LIVE Sekarang" icon="radio" :line="false" />
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-5">
                <LiveCard v-for="(l, i) in lives" :key="l.id" :live="l" :index="i" />
            </div>
            <p v-if="!lives.length" class="rounded-2xl bg-white p-10 text-center text-kelabu">Tiada live buat masa ini. Jom tengok semula nanti!</p>
        </section>

        <section v-if="upcoming.length">
            <SectionHeader title="Akan Datang" :line="false" />
            <ul class="grid gap-3 md:grid-cols-3">
                <li v-for="u in upcoming" :key="u.id" class="rounded-2xl bg-white p-4 shadow-sm">
                    <p class="font-semibold">{{ u.title }}</p>
                    <p class="text-sm text-kelabu">{{ u.shop.name }}</p>
                </li>
            </ul>
        </section>
    </div>
</template>
