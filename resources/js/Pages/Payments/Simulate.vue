<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { rm } from '@/format.js';

const props = defineProps({ payment: { type: Object, required: true } });
const processing = ref(false);
const pay = (result) => {
    processing.value = true;
    router.post(route('payments.simulate.complete', props.payment.reference), { result }, { onFinish: () => (processing.value = false) });
};
</script>

<template>
    <Head title="Bayaran (Simulasi)" />
    <div class="mx-auto max-w-md px-4 py-12">
        <div class="rounded-3xl bg-white p-8 text-center shadow-sm">
            <p class="inline-block rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">MOD SIMULASI — tiada duit sebenar</p>
            <h1 class="mt-4 font-serif text-2xl font-bold text-hijau-gelap">Bayaran FPX</h1>
            <p class="mt-1 text-sm text-kelabu">Halaman ini akan diganti dengan Bayarcash.</p>
            <p class="mt-6 text-4xl font-bold tabular-nums">{{ rm(payment.amount) }}</p>
            <ul class="mt-4 space-y-1 text-sm text-kelabu">
                <li v-for="o in payment.orders" :key="o.id">{{ o.number }} — {{ rm(o.total) }}</li>
            </ul>
            <div class="mt-8 grid gap-3">
                <button :disabled="processing" class="rounded-full bg-hijau py-3 font-semibold text-white disabled:opacity-50" @click="pay('paid')">Bayaran Berjaya</button>
                <button :disabled="processing" class="rounded-full border border-pasir py-3 font-semibold disabled:opacity-50" @click="pay('failed')">Bayaran Gagal</button>
            </div>
        </div>
    </div>
</template>
