<script setup>
import { usePage } from '@inertiajs/vue3';
import VoucherTicket from './VoucherTicket.vue';

defineProps({
    vouchers: { type: Array, default: () => [] },
    title: { type: String, default: 'Baucar Kedai' },
});

const errors = () => usePage().props.errors;
</script>

<template>
    <section v-if="vouchers.length" :aria-label="title">
        <h2 class="mb-2 text-sm font-bold">{{ title }}</h2>
        <div class="-mx-4 flex gap-3 overflow-x-auto px-4 pb-1 md:mx-0 md:px-0">
            <VoucherTicket v-for="v in vouchers" :key="v.id" :voucher="v" />
        </div>
        <p v-if="errors().voucher" class="mt-2 text-sm text-red-600">{{ errors().voucher }}</p>
    </section>
</template>
