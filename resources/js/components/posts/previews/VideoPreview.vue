<script setup lang="ts">
import { IconPlayerPlayFilled } from '@tabler/icons-vue';
import { inject, ref, watch } from 'vue';

import { PREVIEW_SOUNDTRACK } from '@/composables/useReelSoundtrack';

const props = withDefaults(
    defineProps<{
        src: string;
        videoClass?: string;
    }>(),
    {
        videoClass: 'w-full h-full object-cover',
    },
);

const videoRef = ref<HTMLVideoElement | null>(null);
const isPlaying = ref(false);

const markPlaying = (): void => {
    isPlaying.value = true;
};

const soundtrack = inject(PREVIEW_SOUNDTRACK, null);

const markPaused = (): void => {
    isPlaying.value = false;
    if (soundtrack?.playing.value) soundtrack.stop();
};

watch(
    () => soundtrack?.playing.value ?? false,
    (playing) => {
        const el = videoRef.value;
        if (!el) return;
        if (playing) {
            el.currentTime = 0;
            el.volume = soundtrack?.videoVolume.value ?? 1;
            void el.play().catch(() => soundtrack?.stop());
        } else {
            el.pause();
        }
    },
);

watch(
    () => soundtrack?.videoVolume.value,
    (volume) => {
        if (videoRef.value && volume !== undefined) {
            videoRef.value.volume = volume;
        }
    },
);

const toggle = () => {
    const el = videoRef.value;
    if (!el) return;
    if (el.paused) {
        void el.play();
    } else {
        el.pause();
    }
};
</script>

<template>
    <div class="relative h-full w-full" @click="toggle">
        <video
            ref="videoRef"
            :src="props.src"
            :class="props.videoClass"
            playsinline
            preload="metadata"
            @play="markPlaying"
            @pause="markPaused"
            @ended="markPaused"
        />
        <button
            v-show="!isPlaying"
            type="button"
            class="absolute inset-0 flex cursor-pointer items-center justify-center"
            :aria-label="$t('common.play')"
        >
            <span
                class="flex size-12 items-center justify-center rounded-full bg-white/90 transition-transform hover:scale-105"
            >
                <IconPlayerPlayFilled class="size-6 text-black" />
            </span>
        </button>
    </div>
</template>
