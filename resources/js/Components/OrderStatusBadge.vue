<script setup>
defineProps({ status: { type: String, required: true }, label: { type: String, required: true } });

const STYLES = {
    pending_payment: 'bg-amber-100 text-amber-800',
    paid: 'bg-blue-100 text-blue-800',
    shipped: 'bg-indigo-100 text-indigo-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-gray-200 text-gray-600',
};
</script>

<template>
    <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap" :class="STYLES[status]">{{ label }}</span>
</template>
