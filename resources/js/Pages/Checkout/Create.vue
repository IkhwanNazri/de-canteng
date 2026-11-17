<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { rm } from '@/format.js';

const props = defineProps({
    items: { type: Array, required: true },
    summary: { type: Object, required: true },
    defaults: { type: Object, required: true },
});

const STATES = ['Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan', 'Pahang', 'Perak', 'Perlis', 'Pulau Pinang', 'Sabah', 'Sarawak', 'Selangor', 'Terengganu', 'W.P. Kuala Lumpur', 'W.P. Labuan', 'W.P. Putrajaya'];
const form = useForm({ ...props.defaults });
const cartError = () => usePage().props.errors.cart;
</script>

<template>
    <Head title="Checkout" />
    <div class="mx-auto max-w-[1100px] px-4 py-8">
        <h1 class="mb-6 font-serif text-3xl font-bold text-hijau-gelap">Checkout</h1>
        <p v-if="cartError()" class="mb-4 rounded-xl bg-red-50 p-3 text-sm text-red-700">{{ cartError() }}</p>

        <form class="grid gap-6 lg:grid-cols-[1fr_360px]" @submit.prevent="form.post(route('checkout.store'))">
            <section class="space-y-4 rounded-3xl bg-white p-6 shadow-sm">
                <h2 class="font-bold">Alamat Penghantaran</h2>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel for="recipient_name" value="Nama Penerima" />
                        <TextInput id="recipient_name" v-model="form.recipient_name" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.recipient_name" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="phone" value="No. Telefon" />
                        <TextInput id="phone" v-model="form.phone" type="tel" placeholder="012-345 6789" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.phone" class="mt-1" />
                    </div>
                </div>
                <div>
                    <InputLabel for="address" value="Alamat" />
                    <TextInput id="address" v-model="form.address" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.address" class="mt-1" />
                </div>
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <InputLabel for="postcode" value="Poskod" />
                        <TextInput id="postcode" v-model="form.postcode" inputmode="numeric" maxlength="5" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.postcode" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="city" value="Bandar" />
                        <TextInput id="city" v-model="form.city" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.city" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="state" value="Negeri" />
                        <select id="state" v-model="form.state" required class="mt-1 block w-full rounded-xl border border-pasir bg-white px-4 py-2.5 text-sm">
                            <option value="" disabled>Pilih</option>
                            <option v-for="s in STATES" :key="s">{{ s }}</option>
                        </select>
                        <InputError :message="form.errors.state" class="mt-1" />
                    </div>
                </div>
            </section>

            <aside class="h-fit rounded-3xl bg-white p-6 shadow-sm">
                <h2 class="mb-3 font-bold">Pesanan ({{ items.length }} item)</h2>
                <ul class="mb-4 space-y-2 text-sm">
                    <li v-for="item in items" :key="item.id" class="flex justify-between gap-3">
                        <span class="line-clamp-1">{{ item.quantity }}× {{ item.product.name }}</span>
                        <span class="tabular-nums">{{ rm(item.product.price * item.quantity) }}</span>
                    </li>
                </ul>
                <dl class="space-y-2 border-t border-pasir pt-3 text-sm">
                    <div class="flex justify-between"><dt>Subjumlah</dt><dd class="tabular-nums">{{ rm(summary.subtotal) }}</dd></div>
                    <div class="flex justify-between"><dt>Pos ({{ summary.shops }} kedai)</dt><dd class="tabular-nums">{{ rm(summary.shipping) }}</dd></div>
                    <div class="flex justify-between text-base font-bold"><dt>Jumlah Bayar</dt><dd class="tabular-nums text-hijau-gelap">{{ rm(summary.total) }}</dd></div>
                </dl>
                <PrimaryButton class="mt-5 w-full" :disabled="form.processing">Bayar Sekarang</PrimaryButton>
                <p class="mt-3 text-center text-xs text-kelabu">FPX / DuitNow melalui Bayarcash</p>
            </aside>
        </form>
    </div>
</template>
