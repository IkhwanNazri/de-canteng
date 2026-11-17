<script setup>
import Icon from './Icon.vue';

defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    icon: { type: String, default: null },
    highlight: { type: Boolean, default: false },
});
</script>

<template>
    <div class="rounded-2xl p-5 shadow-sm" :class="highlight ? 'bg-gradient-to-br from-hijau-gelap to-hijau text-white' : 'bg-white'">
        <div class="flex items-center justify-between">
            <p class="text-sm" :class="highlight ? 'text-white/75' : 'text-kelabu'">{{ label }}</p>
            <Icon v-if="icon" :name="icon" :size="20" :class="highlight ? 'text-emas' : 'text-hijau'" />
        </div>
        <p class="mt-2 text-2xl font-bold tabular-nums">{{ value }}</p>
    </div>
</template>
