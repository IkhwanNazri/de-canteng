<script setup>
import { computed, ref } from 'vue';
import { rm } from '../format.js';

// Carta bar satu siri (jualan harian). Satu warna, hover tooltip, jadual untuk pembaca skrin.
const props = defineProps({
    data: { type: Array, required: true }, // [{ date: 'YYYY-MM-DD', total: number }]
    title: { type: String, default: 'Jualan 14 hari' },
});

const hovered = ref(null);
const max = computed(() => Math.max(...props.data.map((d) => d.total), 1));
const total = computed(() => props.data.reduce((sum, d) => sum + d.total, 0));
const dayLabel = (date) => new Date(date + 'T00:00:00').toLocaleDateString('ms-MY', { day: 'numeric', month: 'short' });
</script>

<template>
    <figure class="rounded-3xl bg-white p-5 shadow-sm md:p-6">
        <figcaption class="mb-4 flex items-baseline justify-between">
            <span class="font-bold">{{ title }}</span>
            <span class="text-sm text-kelabu">Jumlah <b class="text-dakwat">{{ rm(total) }}</b></span>
        </figcaption>

        <div class="relative">
            <span class="absolute -top-1 left-0 text-[11px] text-kelabu">{{ rm(max) }}</span>
            <div class="flex h-40 items-end gap-0.5 border-b border-pasir pt-5" @mouseleave="hovered = null">
                <div
                    v-for="(d, i) in data"
                    :key="d.date"
                    class="relative flex h-full flex-1 items-end"
                    @mouseenter="hovered = i"
                >
                    <div
                        class="mx-auto w-full max-w-7 rounded-t-[4px] transition-colors"
                        :class="hovered === i ? 'bg-hijau-gelap' : 'bg-hijau'"
                        :style="{ height: d.total ? `${Math.max((d.total / max) * 100, 3)}%` : '2px', opacity: d.total ? 1 : 0.25 }"
                    />
                    <div
                        v-if="hovered === i"
                        class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 -translate-x-1/2 rounded-lg bg-dakwat px-2.5 py-1.5 text-xs whitespace-nowrap text-white shadow"
                    >
                        {{ dayLabel(d.date) }} · <b>{{ rm(d.total) }}</b>
                    </div>
                </div>
            </div>
            <div class="mt-1.5 flex justify-between text-[11px] text-kelabu">
                <span>{{ dayLabel(data[0].date) }}</span>
                <span>{{ dayLabel(data.at(-1).date) }}</span>
            </div>
        </div>

        <table class="sr-only">
            <caption>{{ title }}</caption>
            <tr v-for="d in data" :key="d.date"><th>{{ d.date }}</th><td>{{ rm(d.total) }}</td></tr>
        </table>
    </figure>
</template>
