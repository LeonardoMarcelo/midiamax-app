<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

withDefaults(
    defineProps<{
        /** `full` is the mark plus the wordmark, `mark` the round mark alone. */
        variant?: 'full' | 'mark';
    }>(),
    { variant: 'full' },
);

// MidiaMax: o nome vem do prop compartilhado `name` (APP_NAME), nunca cravado —
// assim trocar a marca e so mudar a variavel de ambiente.
const page = usePage();
const appName = computed(() => (page.props.name as string | undefined) ?? 'MidiaMax');
</script>

<template>
    <span
        class="inline-flex shrink-0 items-center gap-[0.32em] font-logo leading-none font-bold tracking-[-0.01em] text-foreground"
        role="img"
        :aria-label="appName"
        data-testid="app-logo"
    >
        <svg
            viewBox="0 0 410 410"
            class="size-[1.15em] shrink-0 [clip-path:circle(50%)]"
            aria-hidden="true"
        >
            <defs>
                <linearGradient id="mm-mark" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#8b5cf6" />
                    <stop offset="100%" stop-color="#4c1d95" />
                </linearGradient>
            </defs>
            <rect width="410" height="410" fill="url(#mm-mark)" />
            <!-- tres barras ascendentes: alcance crescente. Legivel a 16px. -->
            <rect x="92" y="244" width="56" height="92" rx="28" fill="#ffffff" />
            <rect x="177" y="182" width="56" height="154" rx="28" fill="#ffffff" />
            <rect x="262" y="120" width="56" height="216" rx="28" fill="#ffffff" />
            <circle cx="290" cy="72" r="30" fill="#fcd34d" />
        </svg>
        <span v-if="variant === 'full'" aria-hidden="true">{{ appName }}</span>
    </span>
</template>
