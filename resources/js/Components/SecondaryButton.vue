<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center justify-center rounded-full border border-pasir bg-white px-6 py-2.5 text-sm font-semibold text-dakwat transition hover:bg-krim focus:ring-2 focus:ring-hijau focus:ring-offset-2 focus:outline-none disabled:opacity-50"
    >
        <slot />
    </button>
</template>
