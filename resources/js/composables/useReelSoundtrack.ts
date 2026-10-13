import {
    readonly,
    ref,
    type ComputedRef,
    type InjectionKey,
} from 'vue';

/** What a reel preview tells its video about the soundtrack picked in the composer. */
export interface PreviewSoundtrack {
    playing: ComputedRef<boolean>;
    videoVolume: ComputedRef<number>;
    stop: () => void;
}

export const PREVIEW_SOUNDTRACK: InjectionKey<PreviewSoundtrack> =
    Symbol('previewSoundtrack');

const playingAudioId = ref<string | null>(null);
let player: HTMLAudioElement | null = null;

const stop = (): void => {
    player?.pause();
    player = null;
    playingAudioId.value = null;
};

const play = (audioId: string, url: string, volume: number): void => {
    stop();
    const audio = new Audio(url);
    audio.volume = volume / 100;
    audio.addEventListener('ended', stop);
    player = audio;
    playingAudioId.value = audioId;
    void audio.play().catch(stop);
};

const setVolume = (volume: number): void => {
    if (player) player.volume = volume / 100;
};

/**
 * The one Instagram audio preview playing in the composer. The reel preview
 * plays its video alongside it when the playing track is the post's own.
 */
export const useReelSoundtrack = () => ({
    playingAudioId: readonly(playingAudioId),
    play,
    stop,
    setVolume,
});
