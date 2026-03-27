<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, Copy, ExternalLink, QrCode } from 'lucide-vue-next';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import Heading from '@/components/Heading.vue';
import { show } from '@/routes/event/sessions';

const props = defineProps<{
    event: { id: number; name: string; slug: string };
    session: {
        id: number;
        title: string;
        room: string | null;
        starts_at: string;
        ends_at: string;
    };
    qr: {
        payload: string;
        svg: string;
        expires_at: string;
        remote_join_url: string;
    };
}>();

const copyMessage = ref<string | null>(null);
const copyError = ref<string | null>(null);

function formatTimeRange(startsAt: string, endsAt: string): string {
    const formatter = new Intl.DateTimeFormat('en-GB', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    });

    return `${formatter.format(new Date(startsAt))} - ${formatter.format(new Date(endsAt))}`;
}

function formatTimestamp(timestamp: string): string {
    return new Intl.DateTimeFormat('en-GB', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    }).format(new Date(timestamp));
}

async function copyToClipboard(value: string, label: string): Promise<void> {
    copyMessage.value = null;
    copyError.value = null;

    if (typeof navigator === 'undefined' || ! navigator.clipboard) {
        copyError.value = 'Clipboard access is not available in this browser.';

        return;
    }

    try {
        await navigator.clipboard.writeText(value);
        copyMessage.value = `${label} copied.`;
    } catch {
        copyError.value = `Unable to copy the ${label.toLowerCase()}.`;
    }
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">
        <Head :title="`${session.title} Room QR`" />

        <Card
            class="border-border/70 bg-gradient-to-br from-primary/8 via-card to-card py-0 shadow-sm"
        >
            <CardContent class="space-y-4 p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <Heading
                        :title="`${session.title} Room QR`"
                        description="Display this on the room screen for in-app scanning. Remote attendees use the normal session page."
                    />

                    <Button as-child variant="outline">
                        <Link
                            :href="
                                show({
                                    event: event.slug,
                                    session: session.id,
                                })
                            "
                        >
                            Back to session
                        </Link>
                    </Button>
                </div>

                <div
                    class="grid gap-3 text-sm text-muted-foreground md:grid-cols-3"
                >
                    <div class="flex items-center gap-2">
                        <CalendarDays class="size-4" />
                        {{
                            formatTimeRange(session.starts_at, session.ends_at)
                        }}
                    </div>
                    <div class="flex items-center gap-2">
                        <QrCode class="size-4" />
                        {{ session.room || 'Room to be announced' }}
                    </div>
                    <div class="flex items-center gap-2">
                        <ExternalLink class="size-4" />
                        Expires {{ formatTimestamp(qr.expires_at) }}
                    </div>
                </div>
            </CardContent>
        </Card>

        <Alert v-if="copyError" variant="destructive">
            <AlertTitle>Copy failed</AlertTitle>
            <AlertDescription>{{ copyError }}</AlertDescription>
        </Alert>

        <Alert v-else-if="copyMessage">
            <AlertTitle>Copied</AlertTitle>
            <AlertDescription>{{ copyMessage }}</AlertDescription>
        </Alert>

        <div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
            <Card class="border-border/70 py-0 shadow-sm">
                <CardContent class="flex flex-col items-center gap-4 p-6">
                    <Badge
                        class="rounded-full px-2.5 py-1 text-[11px] font-semibold tracking-[0.12em] uppercase"
                    >
                        Room QR
                    </Badge>
                    <div
                        class="flex w-full justify-center rounded-3xl border border-border/70 bg-white p-4 shadow-xs"
                    >
                        <div
                            class="h-80 w-80 max-w-full"
                            v-html="qr.svg"
                        />
                    </div>
                    <p class="text-center text-sm text-muted-foreground">
                        Participants scan this code in the app to check into the
                        live room experience.
                    </p>
                </CardContent>
            </Card>

            <Card class="border-border/70 py-0 shadow-sm">
                <CardContent class="space-y-5 p-6">
                    <div class="space-y-2">
                        <h2 class="text-lg font-semibold">Share options</h2>
                        <p class="text-sm text-muted-foreground">
                            The QR payload stays relative so the in-app scanner
                            can resolve it safely against the current event.
                        </p>
                    </div>

                    <div class="space-y-3">
                        <div class="space-y-2">
                            <p class="text-sm font-medium">Room QR payload</p>
                            <code
                                class="block overflow-x-auto rounded-2xl border border-border/70 bg-muted/60 p-3 text-xs"
                            >
                                {{ qr.payload }}
                            </code>
                            <Button
                                dusk="copy-room-qr-link-button"
                                variant="outline"
                                @click="
                                    copyToClipboard(qr.payload, 'Room QR link')
                                "
                            >
                                <Copy class="size-4" />
                                Copy room QR link
                            </Button>
                        </div>

                        <div class="space-y-2">
                            <p class="text-sm font-medium">Remote join link</p>
                            <code
                                class="block overflow-x-auto rounded-2xl border border-border/70 bg-muted/60 p-3 text-xs"
                            >
                                {{ qr.remote_join_url }}
                            </code>
                            <Button
                                dusk="copy-remote-join-link-button"
                                variant="outline"
                                @click="
                                    copyToClipboard(
                                        qr.remote_join_url,
                                        'Remote join link',
                                    )
                                "
                            >
                                <Copy class="size-4" />
                                Copy remote join link
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
