<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import SellerNav from '@/Components/SellerNav.vue';
import Icon from '@/Components/Icon.vue';
import { rm } from '@/format.js';

defineProps({ vouchers: { type: Array, default: () => [] } });

const toggle = (v) => router.patch(route('seller.vouchers.toggle', v.id), {}, { preserveScroll: true });
const date = (value) => (value ? new Date(value).toLocaleDateString('ms-MY', { day: 'numeric', month: 'short', year: 'numeric' }) : '—');
const expired = (v) => v.ends_at && new Date(v.ends_at) < new Date();
</script>

<template>
    <Head title="Baucar Kedai" />
    <div class="mx-auto max-w-[1200px] px-4 py-8">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm text-kelabu">Pusat Penjual</p>
                <h1 class="font-serif text-3xl font-bold text-hijau-gelap">Baucar Kedai</h1>
            </div>
            <Link :href="route('seller.vouchers.create')" class="flex items-center gap-1.5 rounded-full bg-hijau px-5 py-2.5 text-sm font-semibold text-white">
                <Icon name="plus" :size="16" :stroke="2.2" /> Cipta Baucar
            </Link>
        </div>
        <SellerNav />

        <p class="mb-4 rounded-2xl bg-white p-4 text-sm shadow-sm">
            Diskaun baucar kedai ditanggung oleh kedai anda. Jenis: <b>Semua produk</b>, <b>Produk terpilih</b>,
            <b>Pengikut sahaja</b> (pembeli mesti ikut kedai), <b>Masa live sahaja</b> (boleh dituntut semasa anda live).
        </p>

        <div class="space-y-3">
            <article v-for="v in vouchers" :key="v.id" class="flex flex-wrap items-center gap-4 rounded-2xl bg-white p-4 shadow-sm" :class="{ 'opacity-60': !v.is_active || expired(v) }">
                <div class="grid size-16 shrink-0 place-items-center rounded-xl bg-emas text-center text-sm leading-tight font-black text-hijau-gelap">
                    {{ v.label.replace('Diskaun ', '') }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-bold">{{ v.name }} <span class="font-mono text-xs text-kelabu">{{ v.code }}</span></p>
                    <p class="text-sm text-kelabu">
                        {{ v.scope_label }}<template v-if="v.scope === 'product'"> ({{ v.products_count }} produk)</template>
                        · Min. {{ rm(v.min_spend) }}<template v-if="v.max_discount"> · maks {{ rm(v.max_discount) }}</template>
                    </p>
                    <p class="text-xs text-kelabu">
                        Dituntut {{ v.claimed_count }}<template v-if="v.quantity"> / {{ v.quantity }}</template> · hingga {{ date(v.ends_at) }}
                        <span v-if="expired(v)" class="font-semibold text-red-600"> · Tamat</span>
                    </p>
                </div>
                <button
                    class="rounded-full px-4 py-2 text-sm font-semibold"
                    :class="v.is_active ? 'bg-krim' : 'bg-hijau text-white'"
                    @click="toggle(v)"
                >
                    {{ v.is_active ? 'Hentikan' : 'Aktifkan' }}
                </button>
            </article>
            <p v-if="!vouchers.length" class="rounded-2xl bg-white p-10 text-center text-kelabu">Belum ada baucar. Cipta baucar untuk tarik pembeli!</p>
        </div>
    </div>
</template>
