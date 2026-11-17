<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';

const user = computed(() => usePage().props.auth.user);

const ROLE_LABELS = { buyer: 'Pembeli', seller: 'Penjual', admin: 'Admin' };

const shortcuts = computed(() => {
    const common = [
        { icon: 'bag', label: 'Pesanan Saya', href: route('orders.index') },
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
</script>

<template>
    <Head title="Akaun Saya" />
    <div class="mx-auto max-w-[1200px] px-4 py-8 md:py-12">
        <div class="rounded-3xl bg-gradient-to-br from-hijau-gelap to-hijau p-6 text-white md:p-8">
            <p class="text-sm text-white/70">Selamat datang,</p>
            <h1 class="font-serif text-3xl font-bold">{{ user.name }}</h1>
            <span class="mt-3 inline-block rounded-full bg-emas px-3 py-1 text-xs font-bold text-hijau-gelap">
                {{ ROLE_LABELS[user.role] ?? user.role }}
            </span>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-5">
            <Link
                v-for="item in shortcuts"
                :key="item.label"
                :href="item.href"
                class="flex flex-col items-center gap-3 rounded-2xl bg-white p-5 text-center text-sm font-semibold shadow-[0_8px_24px_rgba(0,0,0,0.06)] transition hover:-translate-y-0.5"
            >
                <Icon :name="item.icon" :size="30" class="text-hijau" />
                {{ item.label }}
            </Link>
        </div>
    </div>
</template>
