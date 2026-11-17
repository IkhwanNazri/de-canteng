<script setup>
import Logo from '@/Components/Logo.vue';

defineProps({
    title: { type: String, default: null },
    subtitle: { type: String, default: null },
});
</script>

<template>
    <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-gradient-to-br from-hijau-gelap to-hijau px-4 py-10">
        <span class="absolute -top-10 -left-16 h-28 w-64 rotate-[30deg] rounded-full bg-emas/25" />
        <span class="absolute -right-16 bottom-10 h-28 w-72 -rotate-[25deg] rounded-full bg-emas/30" />

        <Logo size="lg" class="relative" />

        <div class="relative mt-8 w-full max-w-md rounded-3xl bg-white px-6 py-8 shadow-2xl sm:px-8">
            <h1 v-if="title" class="font-serif text-3xl font-bold text-hijau-gelap">{{ title }}</h1>
            <p v-if="subtitle" class="mt-1 mb-6 text-sm text-kelabu">{{ subtitle }}</p>
            <slot />
        </div>
    </div>
</template>
