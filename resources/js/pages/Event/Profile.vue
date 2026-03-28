<script setup lang="ts">
import { Head, router, useHttp } from '@inertiajs/vue3';
import { Camera } from 'lucide-vue-next';
import { ref } from 'vue';
import QrScanner from '@/components/qr/QrScanner.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { resolve as qrResolve } from '@/routes/event/qr';
import { notificationPrefs } from '@/routes/event';
import { invisible } from '@/routes/event/status';

const props = defineProps<{
    event: { id: number; name: string; slug: string };
    user: {
        id: number;
        name: string;
        email: string;
        company: string;
        role_title: string;
        intent: string;
        participant_type: string;
        status: string;
        icebreaker_answer: string | null;
        notification_mode: string;
        is_invisible: boolean;
    };
    interestTags: string[];
}>();

const isInvisible = ref(props.user.is_invisible);
const notificationMode = ref(props.user.notification_mode);
const invisibleRequest = useHttp();
const notifRequest = useHttp();

async function toggleInvisible() {
    isInvisible.value = !isInvisible.value;
    try {
        await invisibleRequest.submit(
            invisible(props.event.slug),
        );
        router.reload({ only: ['user'] });
    } catch {
        isInvisible.value = !isInvisible.value;
    }
}

async function toggleDnd() {
    const newMode = notificationMode.value === 'dnd' ? 'normal' : 'dnd';
    notificationMode.value = newMode;
    try {
        await notifRequest.submit({
            url: notificationPrefs(props.event.slug).url,
            method: 'patch' as const,
            data: { notification_mode: newMode },
        });
        router.reload({ only: ['user'] });
    } catch {
        notificationMode.value = notificationMode.value === 'dnd' ? 'normal' : 'dnd';
    }
}

const showScanner = ref(false);
const scanResult = ref<string | null>(null);
const scanError = ref<string | null>(null);
const scanRequest = useHttp();

async function handleScan(data: string) {
    scanResult.value = null;
    scanError.value = null;

    try {
        // Extract relative path from scanned URL
        const url = new URL(data, window.location.origin);
        const payload = url.pathname + url.search;

        const response = (await scanRequest.submit({
            url: qrResolve(props.event.slug).url,
            method: 'post' as const,
            data: { payload },
        })) as { message: string };

        scanResult.value = response.message;
        showScanner.value = false;
    } catch {
        scanError.value = 'Ungültiger oder abgelaufener QR-Code.';
    }
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <Head :title="`${event.name} - Profile`" />

        <!-- Profile header — centered -->
        <div class="flex flex-col items-center pt-4">
            <div class="flex size-20 items-center justify-center rounded-full bg-orange-100 text-2xl font-bold text-orange-700">
                {{ user.name.split(' ').map(n => n[0]).join('') }}
            </div>
            <h1 class="mt-3 text-xl font-bold text-neutral-900">{{ user.name }}</h1>
            <p class="text-sm text-neutral-500">{{ user.role_title }} · {{ user.company }}</p>
            <p class="mt-1 text-sm text-neutral-400">
                📍 {{ user.participant_type === 'physical' ? 'Vor Ort' : 'Remote' }} · {{ user.status === 'available' ? 'Verfügbar' : user.status.replace('_', ' ') }}
            </p>

            <div class="mt-3 flex flex-wrap justify-center gap-2">
                <span
                    v-for="tag in interestTags"
                    :key="tag"
                    class="rounded-full bg-orange-600 px-3 py-1 text-xs font-medium text-white"
                >
                    {{ tag }}
                </span>
            </div>

            <button
                class="mt-4 rounded-full border border-neutral-200 px-6 py-2 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50"
                @click="router.visit('/settings/profile')"
            >
                Profil bearbeiten
            </button>
        </div>

        <!-- Availability section -->
        <div class="space-y-1">
            <p class="text-xs font-semibold tracking-wider text-neutral-400 uppercase">Verfügbarkeit</p>
            <div class="divide-y divide-neutral-100">
                <div class="flex items-center justify-between py-3">
                    <span class="text-sm text-neutral-700">Unsichtbarer Modus</span>
                    <button
                        class="relative h-6 w-11 rounded-full transition"
                        :class="isInvisible ? 'bg-orange-600' : 'bg-neutral-200'"
                        @click="toggleInvisible"
                    >
                        <div
                            class="absolute top-0.5 size-5 rounded-full bg-white shadow transition"
                            :class="isInvisible ? 'right-0.5' : 'left-0.5'"
                        />
                    </button>
                </div>
                <button
                    class="flex w-full items-center justify-between py-3"
                    @click="router.visit(`/event/${event.slug}/onboarding/type`)"
                >
                    <span class="text-sm text-neutral-700">Teilnahme als</span>
                    <span class="text-sm text-neutral-500">📍 {{ user.participant_type === 'physical' ? 'Vor Ort' : 'Remote' }} ›</span>
                </button>
            </div>
        </div>

        <!-- Notifications section -->
        <div class="space-y-1">
            <p class="text-xs font-semibold tracking-wider text-neutral-400 uppercase">Benachrichtigungen</p>
            <div class="divide-y divide-neutral-100">
                <div class="flex items-center justify-between py-3">
                    <span class="text-sm text-neutral-700">Bitte nicht stören</span>
                    <button
                        class="relative h-6 w-11 rounded-full transition"
                        :class="notificationMode === 'dnd' ? 'bg-orange-600' : 'bg-neutral-200'"
                        @click="toggleDnd"
                    >
                        <div
                            class="absolute top-0.5 size-5 rounded-full bg-white shadow transition"
                            :class="notificationMode === 'dnd' ? 'right-0.5' : 'left-0.5'"
                        />
                    </button>
                </div>
            </div>
        </div>

        <!-- QR Scanner section -->
        <Card class="shadow-sm">
            <CardContent class="space-y-3 p-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold">QR-Scanner</h3>
                    <Button
                        size="sm"
                        :variant="showScanner ? 'outline' : 'default'"
                        @click="showScanner = !showScanner"
                    >
                        <Camera class="mr-1 size-4" />
                        {{ showScanner ? 'Schliessen' : 'QR scannen' }}
                    </Button>
                </div>

                <p class="text-sm text-muted-foreground">
                    Scannen Sie Session- oder Stand-QR-Codes, um sich sofort einzuchecken.
                </p>

                <QrScanner
                    v-if="showScanner"
                    @scan="handleScan"
                />

                <p v-if="scanResult" class="text-sm font-medium text-primary">
                    {{ scanResult }}
                </p>
                <p v-if="scanError" class="text-sm text-destructive">
                    {{ scanError }}
                </p>
            </CardContent>
        </Card>
    </div>
</template>
