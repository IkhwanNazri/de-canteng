<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import SellerNav from '@/Components/SellerNav.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { rm } from '@/format.js';

const props = defineProps({
    balance: { type: Number, required: true },
    minimum: { type: Number, required: true },
    bank: { type: Object, default: null },
    withdrawals: { type: Array, default: () => [] },
    transactions: { type: Array, default: () => [] },
});

const form = useForm({ amount: '' });
const submit = () => form.post(route('seller.withdrawals.store'), { preserveScroll: true, onSuccess: () => form.reset() });

const STATUS = { pending: 'bg-amber-100 text-amber-800', paid: 'bg-green-100 text-green-800', rejected: 'bg-gray-200 text-gray-600' };
const date = (value) => new Date(value).toLocaleDateString('ms-MY', { dateStyle: 'medium' });
</script>

<template>
    <Head title="Pengeluaran" />
    <div class="mx-auto max-w-[1200px] px-4 py-8">
        <p class="text-sm text-kelabu">Pusat Penjual</p>
        <h1 class="mb-4 font-serif text-3xl font-bold text-hijau-gelap">Pengeluaran</h1>
        <SellerNav />

        <div class="grid gap-6 lg:grid-cols-[380px_1fr]">
            <section class="h-fit space-y-4 rounded-3xl bg-white p-6 shadow-sm">
                <div class="rounded-2xl bg-gradient-to-br from-hijau-gelap to-hijau p-5 text-white">
                    <p class="text-sm text-white/75">Baki boleh dikeluarkan</p>
                    <p class="mt-1 text-3xl font-bold tabular-nums">{{ rm(balance) }}</p>
                </div>

                <div v-if="bank" class="text-sm">
                    <p class="text-kelabu">Dibayar ke</p>
                    <p class="font-semibold">{{ bank.name }} · {{ bank.account }}</p>
                    <Link :href="route('seller.settings.edit')" class="text-xs font-semibold text-hijau">Tukar bank</Link>
                </div>
                <p v-else class="rounded-xl bg-amber-50 p-3 text-sm text-amber-800">
                    Isi maklumat bank dalam <Link :href="route('seller.settings.edit')" class="font-semibold underline">Tetapan Kedai</Link> dahulu.
                </p>

                <form class="space-y-3" @submit.prevent="submit">
                    <label for="amount" class="block text-sm font-semibold">Jumlah (RM)</label>
                    <div class="flex gap-2">
                        <input
                            id="amount"
                            v-model="form.amount"
                            type="number"
                            step="0.01"
                            :min="minimum"
                            :max="balance"
                            inputmode="decimal"
                            class="min-w-0 flex-1 rounded-xl border border-pasir px-4 py-2.5 text-sm outline-none focus:border-hijau"
                            required
                        />
                        <button type="button" class="rounded-xl bg-krim px-3 text-sm font-semibold" @click="form.amount = balance.toFixed(2)">Semua</button>
                    </div>
                    <InputError :message="form.errors.amount" />
                    <PrimaryButton class="w-full" :disabled="form.processing || !bank || balance < minimum">Mohon Pengeluaran</PrimaryButton>
                    <p class="text-xs text-kelabu">Minimum {{ rm(minimum) }}. Diproses oleh admin dalam 1–3 hari bekerja.</p>
                </form>
            </section>

            <div class="space-y-6">
                <section class="rounded-3xl bg-white p-5 shadow-sm">
                    <h2 class="mb-3 font-bold">Sejarah Pengeluaran</h2>
                    <div v-for="w in withdrawals" :key="w.id" class="flex flex-wrap items-center justify-between gap-2 border-t border-pasir py-3 text-sm first:border-0">
                        <div>
                            <p class="font-bold tabular-nums">{{ rm(w.amount) }}</p>
                            <p class="text-kelabu">{{ date(w.created_at) }} · {{ w.bank_name }}</p>
                            <p v-if="w.reference" class="text-xs text-kelabu">Rujukan: {{ w.reference }}</p>
                            <p v-if="w.admin_note" class="text-xs text-red-600">Sebab: {{ w.admin_note }}</p>
                        </div>
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="STATUS[w.status]">{{ w.status_label }}</span>
                    </div>
                    <p v-if="!withdrawals.length" class="py-6 text-center text-sm text-kelabu">Belum ada pengeluaran.</p>
                </section>

                <section class="rounded-3xl bg-white p-5 shadow-sm">
                    <h2 class="mb-3 font-bold">Transaksi Terkini</h2>
                    <div v-for="t in transactions" :key="t.id" class="flex justify-between gap-3 border-t border-pasir py-2 text-sm first:border-0">
                        <span>{{ t.description }}</span>
                        <span class="shrink-0 font-semibold tabular-nums" :class="t.amount < 0 ? 'text-red-600' : 'text-hijau'">{{ t.amount < 0 ? '−' : '+' }}{{ rm(Math.abs(t.amount)) }}</span>
                    </div>
                    <p v-if="!transactions.length" class="py-6 text-center text-sm text-kelabu">Belum ada transaksi.</p>
                </section>
            </div>
        </div>
    </div>
</template>
