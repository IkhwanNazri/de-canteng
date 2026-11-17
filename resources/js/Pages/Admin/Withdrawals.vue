<script setup>
import { reactive } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminNav from '@/Components/AdminNav.vue';
import { rm } from '@/format.js';

defineProps({
    withdrawals: { type: Object, required: true },
    status: { type: String, required: true },
    pendingTotal: { type: Number, required: true },
});

const TABS = [
    { value: 'pending', label: 'Menunggu' },
    { value: 'paid', label: 'Sudah Dibayar' },
    { value: 'rejected', label: 'Ditolak' },
];
const inputs = reactive({});
const errors = () => usePage().props.errors;

const pay = (w) => router.post(route('admin.withdrawals.pay', w.id), { reference: inputs[w.id] ?? '' }, { preserveScroll: true });
const reject = (w) => {
    const note = prompt('Sebab tolak?');
    if (note) router.post(route('admin.withdrawals.reject', w.id), { admin_note: note }, { preserveScroll: true });
};
const date = (value) => new Date(value).toLocaleDateString('ms-MY', { dateStyle: 'medium' });
</script>

<template>
    <Head title="Pengeluaran Penjual" />
    <div class="mx-auto max-w-[1200px] px-4 py-8">
        <p class="text-sm text-kelabu">Panel Admin</p>
        <h1 class="mb-4 font-serif text-3xl font-bold text-hijau-gelap">Pengeluaran Penjual</h1>
        <AdminNav />

        <p class="mb-4 rounded-2xl bg-white p-4 text-sm shadow-sm">
            Jumlah menunggu: <b class="tabular-nums">{{ rm(pendingTotal) }}</b>. Pindahkan wang ke akaun bank penjual secara manual (online banking), kemudian masukkan rujukan pindahan.
        </p>

        <div class="mb-4 flex gap-2 overflow-x-auto">
            <Link
                v-for="t in TABS"
                :key="t.value"
                :href="route('admin.withdrawals.index', { status: t.value })"
                class="shrink-0 rounded-full px-4 py-1.5 text-sm font-semibold"
                :class="status === t.value ? 'bg-emas text-hijau-gelap' : 'bg-white'"
            >
                {{ t.label }}
            </Link>
        </div>
        <p v-if="errors().reference || errors().withdrawal" class="mb-3 rounded-xl bg-red-50 p-3 text-sm text-red-700">{{ errors().reference || errors().withdrawal }}</p>

        <div class="space-y-3">
            <article v-for="w in withdrawals.data" :key="w.id" class="rounded-2xl bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-2xl font-bold tabular-nums">{{ rm(w.amount) }}</p>
                        <p class="font-semibold">{{ w.shop.name }}</p>
                        <p class="text-sm text-kelabu">{{ w.bank_name }} · <span class="font-mono">{{ w.bank_account }}</span> · {{ date(w.created_at) }}</p>
                        <p v-if="w.reference" class="text-sm">Rujukan: {{ w.reference }}</p>
                        <p v-if="w.admin_note" class="text-sm text-red-600">Sebab: {{ w.admin_note }}</p>
                    </div>
                    <form v-if="w.status === 'pending'" class="flex flex-wrap gap-2" @submit.prevent="pay(w)">
                        <input v-model="inputs[w.id]" placeholder="Rujukan pindahan" required class="w-48 rounded-full border border-pasir px-4 py-2 text-sm outline-none focus:border-hijau" />
                        <button class="rounded-full bg-hijau px-4 py-2 text-sm font-semibold text-white">Tanda Dibayar</button>
                        <button type="button" class="rounded-full px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50" @click="reject(w)">Tolak</button>
                    </form>
                </div>
            </article>
            <p v-if="!withdrawals.data.length" class="rounded-2xl bg-white p-10 text-center text-kelabu">Tiada permintaan.</p>
        </div>
    </div>
</template>
