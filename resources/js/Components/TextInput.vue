<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: [String, Number],
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="rounded-xl border border-pasir bg-white px-4 py-2.5 text-sm shadow-sm outline-none transition focus:border-hijau focus:ring-2 focus:ring-hijau/20"
        v-model="model"
        ref="input"
    />
</template>
