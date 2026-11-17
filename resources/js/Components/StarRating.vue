<script setup>
import Icon from './Icon.vue';

const model = defineModel({ type: Number, default: 0 });
defineProps({ readonly: { type: Boolean, default: false }, size: { type: Number, default: 18 } });
</script>

<template>
    <span class="inline-flex gap-0.5" role="radiogroup" aria-label="Penilaian">
        <button
            v-for="n in 5"
            :key="n"
            type="button"
            :disabled="readonly"
            :aria-label="`${n} bintang`"
            :class="n <= model ? 'text-emas' : 'text-pasir'"
            class="disabled:cursor-default"
            @click="model = n"
        >
            <Icon name="star" :size="size" />
        </button>
    </span>
</template>
