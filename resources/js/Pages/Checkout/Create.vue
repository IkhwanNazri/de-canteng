<script setup>
import { computed, reactive } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { rm, variantLabel } from '@/format.js';

const props = defineProps({
    items: { type: Array, required: true },
    summary: { type: Object, required: true },
    defaults: { type: Object, required: true },
    vouchers: { type: Array, default: () => [] },
});

// Baucar: satu untuk setiap kedai + satu baucar DeBatik.
const shopVouchers = computed(() => props.vouchers.filter((v) => !v.platform));
const platformVouchers = computed(() => props.vouchers.filter((v) => v.platform));
const shopsWithVouchers = computed(() => [...new Map(shopVouchers.value.map((v) => [v.shop_id, v.shop_name])).entries()]);
const selected = reactive({ shops: {}, platform: null });

const bestFirst = (list) => [...list].sort((a, b) => b.discount - a.discount);
for (const [shopId] of shopsWithVouchers.value) {
    const best = bestFirst(shopVouchers.value.filter((v) => v.shop_id === shopId && v.discount > 0))[0];
    if (best) selected.shops[shopId] = best.id;
}
selected.platform = bestFirst(platformVouchers.value.filter((v) => v.discount > 0))[0]?.id ?? null;

const discount = computed(() => {
    const ids = [...Object.values(selected.shops), selected.platform].filter(Boolean);
    return props.vouchers.filter((v) => ids.includes(v.id)).reduce((sum, v) => sum + v.discount, 0);
});
const voucherIds = () => [...Object.values(selected.shops), selected.platform].filter(Boolean);

const STATES = ['Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan', 'Pahang', 'Perak', 'Perlis', 'Pulau Pinang', 'Sabah', 'Sarawak', 'Selangor', 'Terengganu', 'W.P. Kuala Lumpur', 'W.P. Labuan', 'W.P. Putrajaya'];
const form = useForm({ ...props.defaults });
const voucherError = () => usePage().props.errors.voucher;
const submit = () => form.transform((data) => ({ ...data, voucher_ids: voucherIds() })).post(route('checkout.store'));
const cartError = () => usePage().props.errors.cart;
</script>

<template>
    <Head title="Checkout" />
    <div class="mx-auto max-w-[1100px] px-4 py-8">
        <h1 class="mb-6 font-serif text-3xl font-bold text-hijau-gelap">Checkout</h1>
        <p v-if="cartError()" class="mb-4 rounded-xl bg-red-50 p-3 text-sm text-red-700">{{ cartError() }}</p>

        <form class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]" @submit.prevent="submit">
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
                        <span class="line-clamp-1">{{ item.quantity }}× {{ item.product.name }}<template v-if="variantLabel(item)"> ({{ variantLabel(item) }})</template></span>
                        <span class="tabular-nums">{{ rm(item.product.price * item.quantity) }}</span>
                    </li>
                </ul>
                <div v-if="vouchers.length" class="mb-4 space-y-3 border-t border-pasir pt-3 text-sm">
                    <p class="font-bold">Baucar</p>
                    <label v-for="[shopId, shopName] in shopsWithVouchers" :key="shopId" class="block">
                        <span class="text-xs text-kelabu">{{ shopName }}</span>
                        <select v-model="selected.shops[shopId]" class="mt-1 block w-full rounded-xl border border-pasir bg-white px-3 py-2 text-sm">
                            <option :value="undefined">Tiada baucar</option>
                            <option v-for="v in shopVouchers.filter((x) => x.shop_id === shopId)" :key="v.id" :value="v.id" :disabled="v.discount <= 0">
                                {{ v.name }} — {{ v.discount > 0 ? `−${rm(v.discount)}` : `min ${rm(v.min_spend)}` }}
                            </option>
                        </select>
                    </label>
                    <label v-if="platformVouchers.length" class="block">
                        <span class="text-xs text-kelabu">Baucar DeBatik (kad cop)</span>
                        <select v-model="selected.platform" class="mt-1 block w-full rounded-xl border border-pasir bg-white px-3 py-2 text-sm">
                            <option :value="null">Tiada baucar</option>
                            <option v-for="v in platformVouchers" :key="v.id" :value="v.id" :disabled="v.discount <= 0">
                                {{ v.name }} — {{ v.discount > 0 ? `−${rm(v.discount)}` : `min ${rm(v.min_spend)}` }}
                            </option>
                        </select>
                    </label>
                    <p v-if="voucherError()" class="text-xs text-red-600">{{ voucherError() }}</p>
                </div>
                <dl class="space-y-2 border-t border-pasir pt-3 text-sm">
                    <div class="flex justify-between"><dt>Subjumlah</dt><dd class="tabular-nums">{{ rm(summary.subtotal) }}</dd></div>
                    <div class="flex justify-between"><dt>Pos ({{ summary.shops }} kedai)</dt><dd class="tabular-nums">{{ rm(summary.shipping) }}</dd></div>
                    <div v-if="discount > 0" class="flex justify-between text-hijau"><dt>Diskaun baucar</dt><dd class="tabular-nums">−{{ rm(discount) }}</dd></div>
                    <div class="flex justify-between text-base font-bold"><dt>Jumlah Bayar</dt><dd class="tabular-nums text-hijau-gelap">{{ rm(summary.total - discount) }}</dd></div>
                </dl>
                <PrimaryButton class="mt-5 w-full" :disabled="form.processing">Bayar Sekarang</PrimaryButton>
                <p class="mt-3 text-center text-xs text-kelabu">FPX / DuitNow melalui Bayarcash</p>
            </aside>
        </form>
    </div>
</template>
