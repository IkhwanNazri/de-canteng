<script setup>
import { computed, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';
import StampCard from '@/Components/StampCard.vue';

const props = defineProps({
    loyalty: { type: Object, required: true },
    referral: { type: Object, required: true },
    voucherCount: { type: Number, default: 0 },
    commissionFreeOrders: { type: Number, default: null },
});

const user = computed(() => usePage().props.auth.user);
const ROLE_LABELS = { buyer: 'Pembeli', seller: 'Penjual', admin: 'Admin' };

const shortcuts = computed(() => {
    const common = [
        { icon: 'bag', label: 'Pesanan Saya', href: route('orders.index') },
        { icon: 'sparkle', label: `Baucar Saya (${props.voucherCount})`, href: route('vouchers.index') },
        { icon: 'cart', label: 'Troli', href: route('cart.index') },
        { icon: 'heart', label: 'Favorit', href: route('favorites.index') },
        { icon: 'user', label: 'Profil & Keselamatan', href: route('profile.edit') },
    ];
    if (user.value.role === 'seller') {
        return [
            { icon: 'store', label: 'Dashboard Penjual', href: route('seller.dashboard') },
            { icon: 'video', label: 'Studio Live', href: route('seller.live.index') },
            ...common,
        ];
    }
    if (user.value.role === 'admin') {
        return [{ icon: 'shield', label: 'Panel Admin', href: route('admin.dashboard') }, ...common];
    }
    return [{ icon: 'store', label: 'Buka Kedai Sendiri', href: route('shops.create') }, ...common];
});

const copied = ref(false);
const share = async () => {
    const text = `Jom beli batik di DeBatik! Daftar guna kod ${props.referral.code}: ${props.referral.link}`;
    try {
        if (navigator.share) {
            await navigator.share({ title: 'DeBatik', text, url: props.referral.link });
        } else {
            await navigator.clipboard.writeText(text);
            copied.value = true;
            setTimeout(() => (copied.value = false), 2000);
        }
    } catch {
        // pengguna batalkan kongsi
    }
};
const entryDate = (value) => new Date(value).toLocaleDateString('ms-MY', { day: 'numeric', month: 'short' });
</script>

<template>
    <Head title="Akaun Saya" />
    <div class="mx-auto max-w-[1200px] px-4 py-8 md:py-12">
        <div class="mb-6 flex flex-wrap items-center gap-3">
            <h1 class="font-serif text-3xl font-bold text-hijau-gelap">Hai, {{ user.name }}</h1>
            <span class="rounded-full bg-emas px-3 py-1 text-xs font-bold text-hijau-gelap">{{ ROLE_LABELS[user.role] ?? user.role }}</span>
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px]">
            <div class="min-w-0 space-y-6">
                <StampCard v-bind="loyalty" />

                <div class="grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-4">
                    <Link
                        v-for="item in shortcuts"
                        :key="item.label"
                        :href="item.href"
                        class="flex flex-col items-center gap-3 rounded-2xl bg-white p-5 text-center text-sm font-semibold shadow-[0_8px_24px_rgba(0,0,0,0.06)] transition hover:-translate-y-0.5"
                    >
                        <Icon :name="item.icon" :size="28" class="text-hijau" />
                        {{ item.label }}
                    </Link>
                </div>
            </div>

            <aside class="space-y-6">
                <section class="rounded-3xl bg-white p-5 shadow-sm">
                    <h2 class="font-bold">Ajak Kawan</h2>
                    <p class="mt-1 text-sm text-kelabu">
                        Kawan daftar guna kod anda. Selepas pesanan pertama dia dibayar: anda dapat
                        <b class="text-dakwat">+{{ referral.referrerStamps }} cop</b>, kawan dapat <b class="text-dakwat">+{{ referral.refereeStamps }} cop</b>.
                    </p>
                    <div class="mt-4 flex items-center gap-2">
                        <span class="flex-1 rounded-xl border-2 border-dashed border-emas bg-krim py-2.5 text-center font-mono text-lg font-bold tracking-widest">{{ referral.code }}</span>
                        <button class="rounded-xl bg-hijau px-4 py-3 text-sm font-semibold text-white" @click="share">{{ copied ? 'Disalin ✓' : 'Kongsi' }}</button>
                    </div>
                    <p class="mt-2 text-xs text-kelabu">{{ referral.friends }} kawan telah daftar guna kod anda.</p>
                    <p v-if="user.role === 'seller'" class="mt-3 rounded-xl bg-krim p-3 text-xs">
                        <b>Penjual:</b> ajak penjual lain buka kedai guna kod ini — kedua-dua kedai dapat
                        <b>{{ referral.freeOrders }} pesanan bebas caj RM1</b>.
                        <template v-if="commissionFreeOrders"> Baki kedai anda: <b>{{ commissionFreeOrders }}</b>.</template>
                    </p>
                </section>

                <section class="rounded-3xl bg-white p-5 shadow-sm">
                    <h2 class="mb-2 font-bold">Sejarah Cop</h2>
                    <div v-for="e in loyalty.history" :key="e.id" class="flex justify-between gap-3 border-t border-pasir py-2 text-sm first:border-0">
                        <span class="min-w-0 truncate">{{ e.reason }} <span class="text-xs text-kelabu">· {{ entryDate(e.created_at) }}</span></span>
                        <span class="shrink-0 font-bold" :class="e.stamps > 0 ? 'text-hijau' : 'text-kelabu'">{{ e.stamps > 0 ? '+' : '' }}{{ e.stamps }}</span>
                    </div>
                    <p v-if="!loyalty.history.length" class="py-4 text-center text-sm text-kelabu">Belum ada cop. Buat pesanan pertama anda!</p>
                </section>
            </aside>
        </div>
    </div>
</template>
