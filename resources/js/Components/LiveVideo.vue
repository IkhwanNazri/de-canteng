<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { Room, RoomEvent, Track } from 'livekit-client';
import Icon from './Icon.vue';

// Video live melalui LiveKit. `publish` = penjual hantar kamera, selain itu tonton sahaja.
const props = defineProps({
    video: { type: Object, default: null }, // { url, token } atau null jika LiveKit belum dikonfigurasi
    publish: { type: Boolean, default: false },
    active: { type: Boolean, default: true },
});

const videoEl = ref(null);
const audioEl = ref(null);
const state = ref(props.video ? 'connecting' : 'unconfigured');
const errorMessage = ref('');
const needsTap = ref(false); // iOS/Safari: bunyi hanya boleh dimainkan selepas pengguna tekan
let room = null;

const isLocalAddress = (url) => /\/\/(localhost|127\.0\.0\.1|\[::1\])(:|\/|$)/.test(url);
const viewerIsRemote = () => !['localhost', '127.0.0.1', '::1'].includes(window.location.hostname);

const cameraErrorMessage = (error) => {
    if (!window.isSecureContext) return 'Kamera hanya boleh dibuka melalui https atau http://127.0.0.1 / localhost.';
    return (
        {
            NotAllowedError: 'Akses kamera ditolak. Klik ikon kamera/kunci di sebelah alamat laman, pilih "Allow", kemudian refresh.',
            NotFoundError: 'Tiada kamera dijumpai pada komputer ini.',
            NotReadableError: 'Kamera sedang digunakan oleh aplikasi lain (Zoom, Teams, OBS, kamera Windows). Tutup aplikasi itu dan refresh.',
            OverconstrainedError: 'Kamera tidak menyokong tetapan video yang diminta.',
        }[error?.name] ?? `Tidak dapat buka kamera: ${error?.message ?? error}`
    );
};

const enableSound = async () => {
    needsTap.value = false;
    if (videoEl.value) videoEl.value.muted = false;
    try {
        await room?.startAudio();
        await videoEl.value?.play();
    } catch {
        needsTap.value = true;
    }
};

const attachTrack = (track) => {
    if (track.kind === Track.Kind.Video) {
        track.attach(videoEl.value);
        state.value = 'playing';
    } else if (track.kind === Track.Kind.Audio && !props.publish) {
        track.attach(audioEl.value);
    }
};

onMounted(async () => {
    if (!props.video || !props.active) return;

    // Server LiveKit di laptop (localhost) tidak boleh dicapai dari telefon/komputer lain.
    if (isLocalAddress(props.video.url) && viewerIsRemote()) {
        state.value = 'unreachable';
        return;
    }

    room = new Room({ adaptiveStream: true, dynacast: true });
    room.on(RoomEvent.AudioPlaybackStatusChanged, () => (needsTap.value = !room.canPlaybackAudio));
    room.on(RoomEvent.TrackSubscribed, attachTrack);
    room.on(RoomEvent.TrackUnsubscribed, (track) => track.detach());
    room.on(RoomEvent.Disconnected, () => (state.value = 'ended'));

    try {
        await room.connect(props.video.url, props.video.token);
    } catch (error) {
        state.value = 'error';
        errorMessage.value = `Tidak dapat sambung ke server video (${props.video.url}). Semak LIVEKIT_URL, API key & secret dalam .env, kemudian php artisan config:clear. Ralat: ${error?.message ?? error}`;
        console.error(error);
        return;
    }

    try {
        if (props.publish) {
            await room.localParticipant.setCameraEnabled(true);
            const camera = room.localParticipant.getTrackPublication(Track.Source.Camera);
            camera?.videoTrack?.attach(videoEl.value);
            state.value = 'playing';
            // Mikrofon tidak wajib — live tetap berjalan tanpa bunyi jika gagal.
            room.localParticipant.setMicrophoneEnabled(true).catch((error) => console.warn('Mikrofon gagal:', error));
        } else {
            room.remoteParticipants.forEach((participant) =>
                participant.trackPublications.forEach((publication) => publication.track && attachTrack(publication.track)),
            );
            if (state.value !== 'playing') state.value = 'waiting';
            needsTap.value = !room.canPlaybackAudio;
        }
    } catch (error) {
        state.value = 'error';
        errorMessage.value = cameraErrorMessage(error);
        console.error(error);
    }
});

onBeforeUnmount(() => room?.disconnect());
</script>

<template>
    <div class="relative aspect-[9/16] w-full overflow-hidden rounded-3xl bg-hijau-gelap md:aspect-video">
        <video ref="videoEl" autoplay playsinline muted class="absolute inset-0 size-full object-cover" :class="{ '-scale-x-100': publish }" />
        <audio ref="audioEl" autoplay />

        <div v-if="state !== 'playing'" class="absolute inset-0 grid place-items-center p-6 text-center text-white">
            <div>
                <Icon name="video" :size="44" class="mx-auto text-emas" />
                <p class="mt-3 text-sm font-semibold">
                    <template v-if="state === 'unconfigured'">Video belum aktif — tetapkan LIVEKIT_URL dalam .env</template>
                    <template v-else-if="state === 'connecting'">Menyambung…</template>
                    <template v-else-if="state === 'waiting'">Menunggu penjual mula kamera…</template>
                    <template v-else-if="state === 'ended'">Live tamat.</template>
                    <template v-else-if="state === 'unreachable'">Video live belum tersedia di pautan ini. Server video perlu di-deploy dahulu — komen & produk masih boleh digunakan.</template>
                    <template v-else>{{ errorMessage }}</template>
                </p>
            </div>
        </div>
        <button
            v-if="needsTap && !publish && state === 'playing'"
            class="absolute top-3 left-1/2 z-10 -translate-x-1/2 rounded-full bg-black/60 px-4 py-2 text-sm font-semibold text-white backdrop-blur"
            @click="enableSound"
        >
            🔇 Tekan untuk bunyi
        </button>
        <slot />
    </div>
</template>
