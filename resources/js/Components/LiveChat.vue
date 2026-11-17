<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { getJson, postJson } from '../http.js';
import Icon from './Icon.vue';

// Komen live — polling setiap 2 saat (tanpa server WebSocket).
const props = defineProps({
    sessionId: { type: Number, required: true },
    canComment: { type: Boolean, default: true },
});
const emit = defineEmits(['state']);

const user = usePage().props.auth.user;
const comments = ref([]);
const body = ref('');
const sending = ref(false);
const error = ref('');
const listEl = ref(null);
let timer = null;

const lastId = () => comments.value.at(-1)?.id ?? 0;

const scrollToBottom = async () => {
    await nextTick();
    listEl.value?.scrollTo({ top: listEl.value.scrollHeight, behavior: 'smooth' });
};

const addComments = (incoming) => {
    const known = new Set(comments.value.map((c) => c.id));
    const fresh = incoming.filter((c) => !known.has(c.id));
    if (!fresh.length) return;
    comments.value = [...comments.value, ...fresh].sort((a, b) => a.id - b.id).slice(-100);
    scrollToBottom();
};

const poll = async () => {
    try {
        const data = await getJson(route('live.comments.index', { liveSession: props.sessionId, after: lastId() }));
        addComments(data.comments);
        emit('state', data);
    } catch {
        // abaikan ralat rangkaian sementara; cuba lagi pada poll seterusnya
    }
};

const send = async () => {
    if (!body.value.trim() || sending.value) return;
    sending.value = true;
    error.value = '';
    try {
        const data = await postJson(route('live.comments.store', props.sessionId), { body: body.value });
        addComments([data.comment]);
        body.value = '';
        setTimeout(poll, 1200); // ambil auto-jawapan dengan cepat
    } catch (e) {
        error.value = e.data?.errors?.body?.[0] ?? e.message;
    } finally {
        sending.value = false;
    }
};

onMounted(() => {
    poll();
    timer = setInterval(poll, 2000);
});
onBeforeUnmount(() => clearInterval(timer));
</script>

<template>
    <div class="flex h-full min-h-80 flex-col rounded-3xl bg-white shadow-[0_8px_24px_rgba(0,0,0,0.06)]">
        <h3 class="border-b border-pasir px-5 py-3 text-sm font-bold">Komen Live</h3>
        <ul ref="listEl" class="flex-1 space-y-2.5 overflow-y-auto px-5 py-3 text-sm" style="max-height: 420px">
            <li v-for="c in comments" :key="c.id" :class="c.is_auto_reply ? 'rounded-xl bg-krim p-2.5' : ''">
                <span class="font-semibold" :class="c.is_auto_reply ? 'text-hijau' : 'text-dakwat'">
                    <Icon v-if="c.is_auto_reply" name="sparkle" :size="13" class="inline -mt-0.5 text-emas" />
                    {{ c.author }}:
                </span>
                {{ c.body }}
            </li>
            <li v-if="!comments.length" class="py-6 text-center text-kelabu">Belum ada komen. Jadi yang pertama!</li>
        </ul>
        <div class="border-t border-pasir p-3">
            <form v-if="user && canComment" class="flex gap-2" @submit.prevent="send">
                <input
                    v-model="body"
                    maxlength="300"
                    placeholder="Tanya harga, stok, pos…"
                    class="min-w-0 flex-1 rounded-full border border-pasir px-4 py-2 text-sm outline-none focus:border-hijau"
                />
                <button :disabled="sending" class="grid size-10 shrink-0 place-items-center rounded-full bg-hijau text-white disabled:opacity-50" aria-label="Hantar">
                    <Icon name="arrow" :size="18" :stroke="2" />
                </button>
            </form>
            <p v-else-if="!user" class="text-center text-sm">
                <Link :href="route('login')" class="font-semibold text-hijau">Log masuk</Link> untuk komen.
            </p>
            <p v-else class="text-center text-sm text-kelabu">Komen dibuka bila live bermula.</p>
            <p v-if="error" class="mt-2 text-xs text-red-600">{{ error }}</p>
        </div>
    </div>
</template>
