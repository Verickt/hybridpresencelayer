<script setup lang="ts">
import { Head, useHttp } from '@inertiajs/vue3';
import { Camera } from 'lucide-vue-next';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import QrScanner from '@/components/qr/QrScanner.vue';
import { Badge } from '@/components/ui/badge';
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
    <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
        <Head :title="`${event.name} - Profile`" />

        <Heading
            title="Your profile"
            :description="`How others see you at ${event.name}.`"
        />

        <Card class="shadow-sm">
            <CardContent class="space-y-3 p-4">
                <div>
                    <p class="text-lg font-semibold">{{ user.name }}</p>
                    <p class="text-sm text-muted-foreground">{{ user.email }}</p>
                </div>

                <div class="grid gap-2 text-sm sm:grid-cols-2">
                    <div>
                        <span class="text-muted-foreground">Company</span>
                        <p>{{ user.company }}</p>
                    </div>
                    <div>
                        <span class="text-muted-foreground">Role</span>
                        <p>{{ user.role_title }}</p>
                    </div>
                    <div>
                        <span class="text-muted-foreground">Intent</span>
                        <p>{{ user.intent }}</p>
                    </div>
                    <div>
                        <span class="text-muted-foreground">Participation</span>
                        <p class="capitalize">{{ user.participant_type }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Badge v-for="tag in interestTags" :key="tag" variant="secondary">
                        {{ tag }}
                    </Badge>
                </div>
            </CardContent>
        </Card>

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
