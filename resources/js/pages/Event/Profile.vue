<script setup lang="ts">
import { Head, useHttp } from '@inertiajs/vue3';
import { Camera } from 'lucide-vue-next';
import { ref } from 'vue';
import QrScanner from '@/components/qr/QrScanner.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { resolve as qrResolve } from '@/routes/event/qr';

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
        scanError.value = 'Invalid or expired QR code.';
    }
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <Head :title="`${event.name} - Profile`" />

        <!-- Profile header — centered -->
        <div class="flex flex-col items-center pt-4">
            <div class="flex size-20 items-center justify-center rounded-full bg-indigo-100 text-2xl font-bold text-indigo-700">
                {{ user.name.split(' ').map(n => n[0]).join('') }}
            </div>
            <h1 class="mt-3 text-xl font-bold text-neutral-900">{{ user.name }}</h1>
            <p class="text-sm text-neutral-500">{{ user.role_title }} · {{ user.company }}</p>
            <p class="mt-1 text-sm text-neutral-400">
                📍 {{ user.participant_type === 'physical' ? 'Physical' : 'Remote' }} · {{ user.status === 'available' ? 'Available' : user.status.replace('_', ' ') }}
            </p>

            <div class="mt-3 flex flex-wrap justify-center gap-2">
                <span
                    v-for="tag in interestTags"
                    :key="tag"
                    class="rounded-full bg-indigo-600 px-3 py-1 text-xs font-medium text-white"
                >
                    {{ tag }}
                </span>
            </div>

            <button class="mt-4 rounded-full border border-neutral-200 px-6 py-2 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50">
                Edit Profile
            </button>
        </div>

        <!-- Availability section -->
        <div class="space-y-1">
            <p class="text-xs font-semibold tracking-wider text-neutral-400 uppercase">Availability</p>
            <div class="divide-y divide-neutral-100">
                <div class="flex items-center justify-between py-3">
                    <span class="text-sm text-neutral-700">Serendipity Mode</span>
                    <div class="relative h-6 w-11 rounded-full bg-indigo-600">
                        <div class="absolute right-0.5 top-0.5 size-5 rounded-full bg-white shadow transition" />
                    </div>
                </div>
                <div class="flex items-center justify-between py-3">
                    <span class="text-sm text-neutral-700">Invisible Mode</span>
                    <div class="relative h-6 w-11 rounded-full bg-neutral-200">
                        <div class="absolute left-0.5 top-0.5 size-5 rounded-full bg-white shadow transition" />
                    </div>
                </div>
                <div class="flex items-center justify-between py-3">
                    <span class="text-sm text-neutral-700">Attending as</span>
                    <span class="text-sm text-neutral-500">📍 {{ user.participant_type === 'physical' ? 'Physical' : 'Remote' }} ›</span>
                </div>
            </div>
        </div>

        <!-- Notifications section -->
        <div class="space-y-1">
            <p class="text-xs font-semibold tracking-wider text-neutral-400 uppercase">Notifications</p>
            <div class="divide-y divide-neutral-100">
                <div class="flex items-center justify-between py-3">
                    <span class="text-sm text-neutral-700">Do Not Disturb</span>
                    <div class="relative h-6 w-11 rounded-full bg-neutral-200">
                        <div class="absolute left-0.5 top-0.5 size-5 rounded-full bg-white shadow transition" />
                    </div>
                </div>
                <div class="flex items-center justify-between py-3">
                    <span class="text-sm text-neutral-700">Notification Preferences</span>
                    <span class="text-sm text-neutral-400">›</span>
                </div>
            </div>
        </div>

        <!-- QR Scanner section -->
        <Card class="shadow-sm">
            <CardContent class="space-y-3 p-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold">QR Scanner</h3>
                    <Button
                        size="sm"
                        :variant="showScanner ? 'outline' : 'default'"
                        @click="showScanner = !showScanner"
                    >
                        <Camera class="mr-1 size-4" />
                        {{ showScanner ? 'Close' : 'Scan QR' }}
                    </Button>
                </div>

                <p class="text-sm text-muted-foreground">
                    Scan session or booth QR codes to check in instantly.
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
