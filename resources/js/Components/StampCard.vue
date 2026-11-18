<script setup>
import { computed } from 'vue';
import Logo from './Logo.vue';
import { rm } from '../format.js';

const props = defineProps({
    stamps: { type: Number, required: true },
    perCard: { type: Number, required: true },
    reward: { type: Number, required: true },
    minOrder: { type: Number, required: true },
});

const remaining = computed(() => props.perCard - props.stamps);
</script>

<template>
    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-hijau-gelap to-hijau p-5 text-white shadow-lg md:p-6" aria-label="Kad cop">
        <span class="absolute -top-8 -right-10 h-24 w-48 -rotate-[25deg] rounded-full bg-emas/25" />
        <div class="relative flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold tracking-[0.2em] text-emas">KAD COP</p>
                <p class="mt-1 font-serif text-xl font-bold sm:text-2xl">Kumpul {{ perCard }} cop, dapat {{ rm(reward) }}</p>
            </div>
            <Logo size="sm" class="pointer-events-none shrink-0 opacity-90 max-sm:hidden" />
        </div>

        <div class="relative mt-5 grid max-w-md grid-cols-5 gap-2.5 sm:gap-3">
            <div
                v-for="n in perCard"
                :key="n"
                class="grid aspect-square place-items-center rounded-full border-2 text-sm font-bold transition"
                :class="n <= stamps ? 'border-emas bg-emas text-hijau-gelap shadow-inner' : 'border-dashed border-white/35 text-white/40'"
                :aria-label="n <= stamps ? `Cop ${n} dikumpul` : `Cop ${n} belum`"
            >
                <svg v-if="n <= stamps" viewBox="0 0 40 40" class="size-3/5" aria-hidden="true">
                    <polygon points="20,2 24,14 37,14 26.5,22 30.5,35 20,27 9.5,35 13.5,22 3,14 16,14" fill="currentColor" />
                </svg>
                <span v-else>{{ n }}</span>
            </div>
        </div>

        <p class="relative mt-4 text-sm text-white/85">
            <template v-if="remaining > 0">Lagi <b class="text-emas">{{ remaining }} cop</b> untuk baucar {{ rm(reward) }}.</template>
            1 cop untuk setiap pesanan dibayar (min. {{ rm(minOrder) }}).
        </p>
    </section>
</template>
