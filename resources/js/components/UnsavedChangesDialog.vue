<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';

const open = defineModel<boolean>('open', { required: true });

const emit = defineEmits<{ discard: []; keep: [] }>();

const onOpenChange = (value: boolean): void => {
    if (!value) emit('keep');
};

const discard = (): void => {
    emit('discard');
};
</script>

<template>
    <AlertDialog :open="open" @update:open="onOpenChange">
        <AlertDialogContent
            class="gap-0 px-0 pt-6 pb-0 sm:max-w-[512px]"
            data-testid="unsaved-changes-dialog"
        >
            <AlertDialogHeader class="gap-3 px-6 pb-4 text-left">
                <AlertDialogTitle class="font-sans text-base font-medium">
                    {{ $t('common.unsaved_changes.title') }}
                </AlertDialogTitle>
                <AlertDialogDescription class="text-sm text-foreground">
                    {{ $t('common.unsaved_changes.description') }}
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter class="mx-2 mb-2">
                <AlertDialogCancel data-testid="unsaved-changes-keep">
                    {{ $t('common.cancel') }}
                </AlertDialogCancel>
                <Button
                    variant="destructive"
                    size="lg"
                    data-testid="unsaved-changes-discard"
                    @click="discard"
                >
                    {{ $t('common.unsaved_changes.discard') }}
                </Button>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
