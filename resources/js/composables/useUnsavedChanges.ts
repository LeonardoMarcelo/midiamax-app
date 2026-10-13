import { router } from '@inertiajs/vue3';
import { onScopeDispose, ref } from 'vue';

/**
 * Asks before unsaved work is lost: closing what holds it (through `guard`),
 * an Inertia visit to another page, or reloading and closing the tab (the
 * browser's own prompt, which no page can word). Writes (POST, PUT, ...) and
 * partial reloads of the current page are never held.
 */
export const useUnsavedChanges = (isDirty: () => boolean) => {
    const confirming = ref(false);
    let pending: (() => void) | null = null;
    let leaving = false;

    const leave = (action: () => void): void => {
        leaving = true;
        try {
            action();
        } finally {
            leaving = false;
        }
    };

    const guard = (action: () => void): void => {
        if (leaving || !isDirty()) {
            action();
            return;
        }
        pending = action;
        confirming.value = true;
    };

    const discard = (): void => {
        const action = pending;
        pending = null;
        confirming.value = false;
        if (action) leave(action);
    };

    const keepEditing = (): void => {
        pending = null;
        confirming.value = false;
    };

    const stopVisits = router.on('before', (event) => {
        const visit = event.detail.visit;

        if (
            leaving ||
            visit.method !== 'get' ||
            visit.only.length > 0 ||
            visit.except.length > 0 ||
            visit.prefetch ||
            visit.url.href === window.location.href ||
            !isDirty()
        ) {
            return;
        }

        event.preventDefault();
        guard(() =>
            router.visit(visit.url.href, {
                preserveScroll: visit.preserveScroll,
                preserveState: visit.preserveState,
                replace: visit.replace,
            }),
        );
    });

    const onBeforeUnload = (event: BeforeUnloadEvent): void => {
        if (leaving || !isDirty()) return;
        event.preventDefault();
        event.returnValue = '';
    };

    window.addEventListener('beforeunload', onBeforeUnload);

    onScopeDispose(() => {
        stopVisits();
        window.removeEventListener('beforeunload', onBeforeUnload);
    });

    return { confirming, guard, discard, keepEditing, leave };
};
