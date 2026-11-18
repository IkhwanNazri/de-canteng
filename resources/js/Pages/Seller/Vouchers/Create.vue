<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import VoucherTicket from '@/Components/VoucherTicket.vue';
import { rm } from '@/format.js';

const props = defineProps({
    products: { type: Array, default: () => [] },
    scopes: { type: Array, required: true },
});

const inDays = (days) => {
    const d = new Date(Date.now() + days * 86400000);
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
    return d.toISOString().slice(0, 16);
};

const form = useForm({
    name: '',
    code: '',
    scope: 'shop',
    discount_type: 'fixed',
    discount_value: 5,
    max_discount: '',
    min_spend: 50,
    quantity: 100,
    starts_at: '',
    ends_at: inDays(14),
    product_ids: [],
});

const SCOPE_HELP = {
    shop: 'Boleh digunakan untuk semua produk kedai.',
    product: 'Hanya untuk produk yang anda pilih di bawah.',
    follower: 'Pembeli mesti ikut kedai anda untuk tuntut — tarik lebih ramai pengikut.',
    live: 'Hanya boleh dituntut semasa anda sedang live — galakkan penonton beli segera.',
};

const preview = computed(() => ({
    id: 0,
    name: form.name || 'Nama baucar',
    label: form.discount_type === 'percent' ? `Diskaun ${form.discount_value || 0}%` : `Diskaun RM${form.discount_value || 0}`,
    scope: form.scope,
    scope_label: props.scopes.find((s) => s.value === form.scope)?.label,
    min_spend: Number(form.min_spend || 0),
    max_discount: form.discount_type === 'percent' && form.max_discount ? Number(form.max_discount) : null,
    ends_at: form.ends_at,
    remaining: form.quantity ? Number(form.quantity) : null,
    claimed: false,
}));

const presets = [
    { label: 'RM5 (min RM50)', apply: { discount_type: 'fixed', discount_value: 5, min_spend: 50 } },
    { label: 'RM10 (min RM100)', apply: { discount_type: 'fixed', discount_value: 10, min_spend: 100 } },
    { label: '10% (maks RM20)', apply: { discount_type: 'percent', discount_value: 10, max_discount: 20, min_spend: 30 } },
];

const submit = () => form.post(route('seller.vouchers.store'));
</script>

<template>
    <Head title="Cipta Baucar" />
    <div class="mx-auto max-w-4xl px-4 py-8">
        <Link :href="route('seller.vouchers.index')" class="text-sm font-semibold hover:text-hijau">← Baucar Kedai</Link>
        <h1 class="mt-2 mb-6 font-serif text-3xl font-bold text-hijau-gelap">Cipta Baucar</h1>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_300px]">
            <form class="space-y-5 rounded-3xl bg-white p-6 shadow-sm md:p-8" @submit.prevent="submit">
                <div>
                    <InputLabel value="Jenis baucar" />
                    <div class="mt-2 grid gap-2 sm:grid-cols-2">
                        <label
                            v-for="s in scopes"
                            :key="s.value"
                            class="cursor-pointer rounded-2xl border-2 p-3 text-sm"
                            :class="form.scope === s.value ? 'border-hijau bg-krim' : 'border-pasir'"
                        >
                            <input v-model="form.scope" type="radio" :value="s.value" class="sr-only" />
                            <span class="block font-bold">{{ s.label }}</span>
                            <span class="text-xs text-kelabu">{{ SCOPE_HELP[s.value] }}</span>
                        </label>
                    </div>
                </div>

                <div v-if="form.scope === 'product'">
                    <InputLabel value="Pilih produk" />
                    <div class="mt-2 max-h-56 space-y-1 overflow-y-auto rounded-2xl border border-pasir p-2">
                        <label v-for="p in products" :key="p.id" class="flex cursor-pointer items-center gap-3 rounded-xl p-2 hover:bg-krim">
                            <input v-model="form.product_ids" type="checkbox" :value="p.id" class="size-4 accent-hijau" />
                            <span class="flex-1 text-sm">{{ p.name }}</span>
                            <span class="text-xs text-kelabu">{{ rm(p.price) }}</span>
                        </label>
                    </div>
                    <InputError :message="form.errors.product_ids" class="mt-2" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="name" value="Nama baucar" />
                        <TextInput id="name" v-model="form.name" placeholder="cth: Diskaun Raya" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="code" value="Kod (pilihan)" />
                        <TextInput id="code" v-model="form.code" placeholder="Auto jika kosong" class="mt-1 block w-full uppercase" maxlength="15" />
                        <InputError :message="form.errors.code" class="mt-1" />
                    </div>
                </div>

                <div>
                    <InputLabel value="Diskaun" />
                    <div class="mt-2 mb-3 flex flex-wrap gap-2">
                        <button v-for="p in presets" :key="p.label" type="button" class="rounded-full border border-pasir px-3 py-1 text-xs font-semibold hover:border-hijau" @click="Object.assign(form, p.apply)">
                            {{ p.label }}
                        </button>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <select v-model="form.discount_type" class="rounded-xl border border-pasir bg-white px-4 py-2.5 text-sm">
                            <option value="fixed">Jumlah (RM)</option>
                            <option value="percent">Peratus (%)</option>
                        </select>
                        <TextInput v-model="form.discount_value" type="number" step="0.01" min="1" class="block w-full" required />
                        <TextInput v-if="form.discount_type === 'percent'" v-model="form.max_discount" type="number" step="0.01" placeholder="Maks RM" class="block w-full" />
                    </div>
                    <InputError :message="form.errors.discount_value || form.errors.max_discount" class="mt-1" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="min_spend" value="Belanja minimum (RM)" />
                        <TextInput id="min_spend" v-model="form.min_spend" type="number" step="0.01" min="0" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.min_spend" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="quantity" value="Kuantiti (kosong = tiada had)" />
                        <TextInput id="quantity" v-model="form.quantity" type="number" min="1" class="mt-1 block w-full" />
                        <InputError :message="form.errors.quantity" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="starts_at" value="Mula (pilihan)" />
                        <input id="starts_at" v-model="form.starts_at" type="datetime-local" class="mt-1 block w-full rounded-xl border border-pasir px-4 py-2.5 text-sm" />
                    </div>
                    <div>
                        <InputLabel for="ends_at" value="Tamat" />
                        <input id="ends_at" v-model="form.ends_at" type="datetime-local" required class="mt-1 block w-full rounded-xl border border-pasir px-4 py-2.5 text-sm" />
                        <InputError :message="form.errors.ends_at" class="mt-1" />
                    </div>
                </div>

                <PrimaryButton :disabled="form.processing">Cipta Baucar</PrimaryButton>
            </form>

            <aside class="lg:sticky lg:top-24 lg:self-start">
                <p class="mb-2 text-sm font-semibold">Pratonton</p>
                <VoucherTicket :voucher="preview" :claimable="false" />
            </aside>
        </div>
    </div>
</template>
