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
let room = null;

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

    room = new Room({ adaptiveStream: true, dynacast: true });
    room.on(RoomEvent.TrackSubscribed, attachTrack);
    room.on(RoomEvent.TrackUnsubscribed, (track) => track.detach());
    room.on(RoomEvent.Disconnected, () => (state.value = 'ended'));

    try {
        await room.connect(props.video.url, props.video.token);

        if (props.publish) {
            await room.localParticipant.enableCameraAndMicrophone();
            const camera = room.localParticipant.getTrackPublication(Track.Source.Camera);
            camera?.videoTrack?.attach(videoEl.value);
            state.value = 'playing';
        } else {
            room.remoteParticipants.forEach((participant) =>
                participant.trackPublications.forEach((publication) => publication.track && attachTrack(publication.track)),
            );
            if (state.value !== 'playing') state.value = 'waiting';
        }
    } catch (error) {
        state.value = 'error';
        errorMessage.value = props.publish
            ? 'Tidak dapat buka kamera/mikrofon. Benarkan akses kamera, dan buka laman melalui https atau localhost.'
            : 'Tidak dapat sambung ke video live.';
        console.error(error);
    }
});

onBeforeUnmount(() => room?.disconnect());
</script>

<template>
    <div class="relative aspect-[9/16] w-full overflow-hidden rounded-3xl bg-hijau-gelap md:aspect-video">
        <video ref="videoEl" autoplay playsinline :muted="publish" class="absolute inset-0 size-full object-cover" :class="{ '-scale-x-100': publish }" />
        <audio ref="audioEl" autoplay />

        <div v-if="state !== 'playing'" class="absolute inset-0 grid place-items-center p-6 text-center text-white">
            <div>
                <Icon name="video" :size="44" class="mx-auto text-emas" />
                <p class="mt-3 text-sm font-semibold">
                    <template v-if="state === 'unconfigured'">Video belum aktif — tetapkan LIVEKIT_URL dalam .env</template>
                    <template v-else-if="state === 'connecting'">Menyambung…</template>
                    <template v-else-if="state === 'waiting'">Menunggu penjual mula kamera…</template>
                    <template v-else-if="state === 'ended'">Live tamat.</template>
                    <template v-else>{{ errorMessage }}</template>
                </p>
            </div>
        </div>
        <slot />
    </div>
</template>
