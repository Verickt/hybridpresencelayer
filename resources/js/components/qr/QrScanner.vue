<script setup lang="ts">
import QrScannerLib from 'qr-scanner';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { Button } from '@/components/ui/button';

const emit = defineEmits<{
    scan: [result: string];
}>();

const videoEl = ref<HTMLVideoElement>();
const scanning = ref(false);
const error = ref<string | null>(null);
let scanner: QrScannerLib | null = null;

async function startScanner() {
    if (!videoEl.value) {
        return;
    }

    error.value = null;

    try {
        scanner = new QrScannerLib(
            videoEl.value,
            (result) => {
                emit('scan', result.data);
            },
            {
                preferredCamera: 'environment',
                highlightScanRegion: true,
                highlightCodeOutline: true,
            },
        );

        await scanner.start();
        scanning.value = true;
    } catch {
        error.value = 'Camera access denied or unavailable.';
        scanning.value = false;
    }
}

function stopScanner() {
    scanner?.stop();
    scanner?.destroy();
    scanner = null;
    scanning.value = false;
}

onMounted(startScanner);

onBeforeUnmount(stopScanner);
</script>

<template>
    <div class="space-y-3">
        <div
            class="relative overflow-hidden rounded-2xl border border-border/70 bg-black"
        >
            <video
                ref="videoEl"
                class="aspect-square w-full object-cover"
            />
        </div>

        <p v-if="error" class="text-center text-sm text-destructive">
            {{ error }}
        </p>

        <div class="flex justify-center">
            <Button
                v-if="!scanning"
                size="sm"
                @click="startScanner"
            >
                Start scanning
            </Button>
            <Button
                v-else
                size="sm"
                variant="outline"
                @click="stopScanner"
            >
                Stop scanning
            </Button>
        </div>
    </div>
</template>
