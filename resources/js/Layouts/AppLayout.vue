<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import Logo from '../Components/Logo.vue';
import Icon from '../Components/Icon.vue';
import Dropdown from '../Components/Dropdown.vue';
import DropdownLink from '../Components/DropdownLink.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const cartCount = computed(() => page.props.cartCount ?? 0);

const menuOpen = ref(false);
const stopListening = router.on('navigate', () => (menuOpen.value = false));
onBeforeUnmount(stopListening);

const accountLinks = computed(() => {
    if (!user.value) return [];
    const links = [
        { label: 'Akaun Saya', href: route('dashboard'), icon: 'user' },
        { label: 'Pesanan Saya', href: route('orders.index'), icon: 'bag' },
        { label: 'Favorit', href: route('favorites.index'), icon: 'heart' },
    ];
    if (user.value.role === 'seller') links.push({ label: 'Pusat Penjual', href: route('seller.dashboard'), icon: 'store' });
    if (user.value.role === 'admin') links.push({ label: 'Panel Admin', href: route('admin.dashboard'), icon: 'shield' });
    if (user.value.role === 'buyer') links.push({ label: 'Buka Kedai', href: route('shops.create'), icon: 'store' });
    return links;
});

const NAV = [
    { label: 'Utama', href: '/' },
    { label: 'Teroka', href: '/teroka' },
    { label: 'Kedai', href: '/kedai' },
    { label: 'Live', href: '/live' },
    { label: 'AI DeBatik', href: '/ai' },
];

const isActive = (href) => (href === '/' ? page.url === '/' : page.url.startsWith(href));

const TRUST = [
    { icon: 'shield', text: ['Pembayaran Selamat', '& Dipercayai'] },
    { icon: 'truck', text: ['Penghantaran', 'Seluruh Malaysia'] },
    { icon: 'headset', text: ['Sokongan', 'Pelanggan'] },
    { icon: 'shield', text: ['Jaminan', 'Kualiti Produk'] },
];

const FOOTER = [
    { title: 'Tentang DeBatik', links: ['Mengenai Kami', 'Pengusaha Tempatan', 'Kerjaya'] },
    { title: 'Bantuan', links: ['Soalan Lazim', 'Hubungi Kami', 'Panduan Pembeli'] },
    { title: 'Penjual', links: ['Daftar Sebagai Penjual', 'Pusat Penjual', 'Polisi Penjual'] },
];
</script>

<template>
    <div class="min-h-screen pb-20 md:pb-0">
        <!-- Header -->
        <header class="sticky top-0 z-40 bg-hijau-gelap">
            <div class="mx-auto flex max-w-[1440px] items-center gap-4 px-4 py-3 md:gap-11 md:px-12 md:py-4">
                <button class="text-white md:hidden" aria-label="Buka menu" :aria-expanded="menuOpen" @click="menuOpen = true"><Icon name="menu" :stroke="2" /></button>
                <Logo class="max-md:hidden" />
                <Logo size="sm" class="md:hidden" />

                <nav class="hidden items-center gap-8 md:flex" aria-label="Navigasi utama">
                    <Link
                        v-for="item in NAV"
                        :key="item.href"
                        :href="item.href"
                        prefetch
                        class="relative pb-1.5 text-[15px] text-white transition hover:text-emas"
                        :class="isActive(item.href) ? 'font-semibold' : 'font-medium'"
                    >
                        {{ item.label }}
                        <span v-if="isActive(item.href)" class="absolute inset-x-0 bottom-0 mx-auto h-0.5 w-10 bg-emas" />
                    </Link>
                </nav>

                <div class="ml-auto flex items-center gap-4 md:gap-5">
                    <Link href="/teroka" class="text-white hover:text-emas" aria-label="Cari"><Icon name="search" :size="22" :stroke="2" /></Link>
                    <Link :href="user ? route('cart.index') : route('login')" class="relative text-white hover:text-emas" aria-label="Troli">
                        <Icon name="cart" :stroke="2" />
                        <span
                            v-if="cartCount"
                            class="absolute -top-1.5 -right-2 rounded-full bg-emas px-1.5 text-[10px] font-bold text-hijau-gelap"
                            >{{ cartCount }}</span
                        >
                    </Link>
                    <template v-if="!user">
                        <Link
                            :href="route('login')"
                            class="hidden rounded-full border border-white px-[18px] py-2 text-sm font-semibold text-white hover:bg-white/10 md:block"
                            >Log Masuk</Link
                        >
                        <Link
                            :href="route('register')"
                            class="hidden rounded-full bg-emas px-[22px] py-2 text-sm font-semibold text-hijau-gelap hover:brightness-105 md:block"
                            >Daftar</Link
                        >
                    </template>
                    <Dropdown v-else align="right" width="48" class="hidden md:block">
                        <template #trigger>
                            <button class="flex items-center gap-2 rounded-full border border-white/40 py-1.5 pr-4 pl-1.5 text-sm font-semibold text-white hover:bg-white/10">
                                <span class="grid size-7 place-items-center rounded-full bg-emas text-xs font-bold text-hijau-gelap">
                                    {{ user.name.charAt(0).toUpperCase() }}
                                </span>
                                {{ user.name }}
                            </button>
                        </template>
                        <template #content>
                            <DropdownLink :href="route('dashboard')">Akaun Saya</DropdownLink>
                            <DropdownLink :href="route('orders.index')">Pesanan Saya</DropdownLink>
                            <DropdownLink v-if="user.role === 'seller'" :href="route('seller.dashboard')">Dashboard Penjual</DropdownLink>
                            <DropdownLink v-if="user.role === 'admin'" :href="route('admin.dashboard')">Panel Admin</DropdownLink>
                            <DropdownLink :href="route('profile.edit')">Profil</DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button">Log Keluar</DropdownLink>
                        </template>
                    </Dropdown>
                </div>
            </div>
        </header>

        <div
            v-if="page.props.flash?.success"
            :key="page.props.flash.success"
            class="mx-auto mt-4 flex max-w-[1200px] items-center gap-2 rounded-2xl bg-hijau px-5 py-3 text-sm font-semibold text-white shadow"
            role="status"
        >
            <Icon name="check" :size="18" :stroke="2.4" /> {{ page.props.flash.success }}
        </div>

        <!-- Menu laci (mobile) -->
        <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" enter-active-class="transition-opacity duration-200" leave-active-class="transition-opacity duration-150">
            <div v-if="menuOpen" class="fixed inset-0 z-50 bg-black/40 md:hidden" @click="menuOpen = false" />
        </Transition>
        <Transition enter-from-class="-translate-x-full" leave-to-class="-translate-x-full" enter-active-class="transition-transform duration-200 ease-out" leave-active-class="transition-transform duration-150 ease-in">
            <aside v-if="menuOpen" class="fixed inset-y-0 left-0 z-50 flex w-[82%] max-w-xs flex-col overflow-y-auto bg-hijau-gelap p-5 text-white md:hidden" aria-label="Menu">
                <div class="mb-6 flex items-center justify-between">
                    <Logo size="sm" />
                    <button aria-label="Tutup menu" class="grid size-9 place-items-center rounded-full bg-white/10" @click="menuOpen = false">✕</button>
                </div>

                <div v-if="user" class="mb-5 flex items-center gap-3 rounded-2xl bg-white/10 p-3">
                    <span class="grid size-10 place-items-center rounded-full bg-emas font-bold text-hijau-gelap">{{ user.name.charAt(0).toUpperCase() }}</span>
                    <span class="min-w-0">
                        <span class="block truncate font-semibold">{{ user.name }}</span>
                        <span class="block truncate text-xs text-white/60">{{ user.email }}</span>
                    </span>
                </div>

                <nav class="space-y-1">
                    <Link
                        v-for="item in NAV"
                        :key="item.href"
                        :href="item.href"
                        class="block rounded-xl px-3 py-2.5 font-semibold"
                        :class="isActive(item.href) ? 'bg-white/15 text-emas' : 'hover:bg-white/10'"
                    >
                        {{ item.label }}
                    </Link>
                </nav>

                <template v-if="user">
                    <p class="mt-6 mb-2 px-3 text-xs font-semibold tracking-wider text-white/50 uppercase">Akaun</p>
                    <nav class="space-y-1">
                        <Link v-for="item in accountLinks" :key="item.href" :href="item.href" class="flex items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-white/10">
                            <Icon :name="item.icon" :size="18" class="text-emas" /> {{ item.label }}
                        </Link>
                        <Link :href="route('profile.edit')" class="flex items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-white/10">
                            <Icon name="shield" :size="18" class="text-emas" /> Profil
                        </Link>
                    </nav>
                    <Link :href="route('logout')" method="post" as="button" class="mt-auto w-full rounded-full border border-white/30 py-2.5 text-sm font-semibold">Log Keluar</Link>
                </template>
                <div v-else class="mt-auto grid gap-2 pt-6">
                    <Link :href="route('login')" class="rounded-full border border-white py-2.5 text-center text-sm font-semibold">Log Masuk</Link>
                    <Link :href="route('register')" class="rounded-full bg-emas py-2.5 text-center text-sm font-semibold text-hijau-gelap">Daftar</Link>
                </div>
            </aside>
        </Transition>

        <main>
            <slot />
        </main>

        <!-- Jaminan -->
        <section class="bg-pasir">
            <div class="mx-auto grid max-w-[1440px] grid-cols-2 gap-5 px-4 py-6 md:flex md:justify-between md:px-[120px] md:py-7">
                <div v-for="item in TRUST" :key="item.text[0]" class="flex items-center gap-3">
                    <Icon :name="item.icon" :size="32" :stroke="1.6" class="shrink-0 text-hijau" />
                    <p class="text-[13px] leading-snug font-semibold md:text-[15px]">{{ item.text[0] }}<br />{{ item.text[1] }}</p>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-hijau-gelap text-white">
            <div class="mx-auto max-w-[1440px] px-4 py-10 md:px-[120px] md:py-14">
                <div class="flex flex-col justify-between gap-10 md:flex-row">
                    <Logo size="lg" />
                    <div class="grid grid-cols-2 gap-8 md:flex md:gap-[72px]">
                        <div v-for="col in FOOTER" :key="col.title">
                            <h3 class="mb-2.5 text-[15px] font-semibold">{{ col.title }}</h3>
                            <ul class="space-y-2.5 text-[13px] text-white/70">
                                <li v-for="link in col.links" :key="link"><a href="#" class="hover:text-emas">{{ link }}</a></li>
                            </ul>
                        </div>
                        <div>
                            <h3 class="mb-3 text-[15px] font-semibold">Ikuti Kami</h3>
                            <div class="flex gap-2.5">
                                <a
                                    v-for="s in ['f', 'IG', 'TT', 'YT']"
                                    :key="s"
                                    href="#"
                                    class="grid size-[30px] place-items-center rounded-full bg-white text-[10px] font-bold text-hijau-gelap hover:bg-emas"
                                    >{{ s }}</a
                                >
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="my-8 border-white/15" />
                <p class="text-center text-[13px] text-white/60">© {{ new Date().getFullYear() }} DeBatik. Semua Hak Cipta Terpelihara.</p>
            </div>
        </footer>

        <!-- Bottom nav (mobile) -->
        <nav class="fixed inset-x-0 bottom-0 z-40 flex items-center justify-between bg-hijau-gelap px-5 py-2 md:hidden" aria-label="Navigasi bawah">
            <Link
                v-for="item in [
                    { label: 'Utama', href: '/', icon: 'home' },
                    { label: 'Kedai', href: '/kedai', icon: 'store' },
                ]"
                :key="item.href"
                :href="item.href"
                class="flex w-14 flex-col items-center gap-1 text-[11px] font-semibold"
                :class="isActive(item.href) ? 'text-emas' : 'text-white'"
            >
                <Icon :name="item.icon" />{{ item.label }}
            </Link>
            <Link
                href="/live"
                class="-mt-7 grid size-16 place-items-center rounded-full border-[3px] border-emas bg-hijau-gelap text-emas"
                aria-label="Live"
            >
                <span class="flex flex-col items-center text-[10px] font-bold"><Icon name="video" :size="22" />LIVE</span>
            </Link>
            <Link
                v-for="item in [
                    { label: 'Favorit', href: user ? route('favorites.index') : route('login'), icon: 'heart' },
                    { label: 'Akaun', href: user ? route('dashboard') : route('login'), icon: 'user' },
                ]"
                :key="item.href"
                :href="item.href"
                class="flex w-14 flex-col items-center gap-1 text-[11px] font-semibold"
                :class="isActive(item.href) ? 'text-emas' : 'text-white'"
            >
                <Icon :name="item.icon" />{{ item.label }}
            </Link>
        </nav>
    </div>
</template>
