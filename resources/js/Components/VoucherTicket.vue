<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { rm } from '../format.js';

const props = defineProps({
    voucher: { type: Object, required: true },
    claimable: { type: Boolean, default: true },
});

const page = usePage();
const claim = () => {
    if (!page.props.auth.user) {
        router.visit(route('login'));
        return;
    }
    router.post(route('vouchers.claim', props.voucher.id), {}, { preserveScroll: true });
};
const endDate = (value) => (value ? new Date(value).toLocaleDateString('ms-MY', { day: 'numeric', month: 'short' }) : null);
</script>

<template>
    <div class="relative flex w-72 shrink-0 overflow-hidden rounded-2xl border border-emas/40 bg-white shadow-sm">
        <div class="flex w-20 shrink-0 flex-col items-center justify-center bg-gradient-to-b from-emas to-[#b8932b] px-2 text-center text-hijau-gelap">
            <span class="text-[10px] font-bold tracking-wider uppercase">{{ voucher.scope === 'live' ? 'Live' : voucher.scope === 'follower' ? 'Pengikut' : 'Baucar' }}</span>
            <span class="mt-0.5 text-lg leading-tight font-black">{{ voucher.label.replace('Diskaun ', '') }}</span>
        </div>
        <span class="absolute top-1/2 left-[74px] size-3 -translate-y-1/2 rounded-full bg-krim" aria-hidden="true" />
        <div class="min-w-0 flex-1 p-3 pl-4">
            <p class="truncate text-sm font-bold">{{ voucher.name }}</p>
            <p class="text-xs text-kelabu">
                Min. belanja {{ rm(voucher.min_spend) }}<template v-if="voucher.max_discount"> · maks {{ rm(voucher.max_discount) }}</template>
            </p>
            <p class="text-[11px] text-kelabu">
                {{ voucher.scope_label }}<template v-if="endDate(voucher.ends_at)"> · hingga {{ endDate(voucher.ends_at) }}</template>
                <template v-if="voucher.remaining !== null && voucher.remaining !== undefined"> · tinggal {{ voucher.remaining }}</template>
            </p>
            <div class="mt-2">
                <span v-if="voucher.claimed" class="text-xs font-bold text-hijau">✓ Disimpan</span>
                <button v-else-if="claimable" class="rounded-full bg-hijau px-3.5 py-1 text-xs font-bold text-white" @click="claim">Tuntut</button>
                <Link v-else :href="route('vouchers.index')" class="text-xs font-semibold text-hijau">Lihat</Link>
            </div>
        </div>
    </div>
</template>
