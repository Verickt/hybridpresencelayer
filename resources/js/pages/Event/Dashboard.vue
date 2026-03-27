<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Activity, Handshake, LayoutGrid, Megaphone, Sparkles, Users } from 'lucide-vue-next';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';

defineOptions({ layout: AppLayout });
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import Heading from '@/components/Heading.vue';

const props = defineProps<{
    event: { id: number; name: string; slug: string };
    overview: Record<string, number>;
    sessionAnalytics: Array<Record<string, unknown>>;
    boothPerformance: Array<Record<string, unknown>>;
    sessions: Array<{ id: number; title: string; speaker: string; room: string; starts_at: string; ends_at: string; qa_enabled: boolean }>;
    booths: Array<{ id: number; name: string; company: string; description: string; staff: string[] }>;
    interestTags: Record<number, string>;
    icebreakers: Array<{ id: number; question: string }>;
}>();

const showAddSession = ref(false);
const showAddBooth = ref(false);
const newSession = ref({ title: '', speaker: '', room: '', starts_at: '', ends_at: '', description: '' });
const newBooth = ref({ name: '', company: '', description: '' });

function addSession() {
    fetch(`/event/${props.event.slug}/sessions`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': getCsrfToken(), 'Accept': 'text/html' },
        body: JSON.stringify(newSession.value),
        credentials: 'same-origin',
    }).then(() => {
        newSession.value = { title: '', speaker: '', room: '', starts_at: '', ends_at: '', description: '' };
        showAddSession.value = false;
        router.reload();
    });
}

function deleteSession(id: number) {
    fetch(`/event/${props.event.slug}/sessions/${id}`, {
        method: 'DELETE',
        headers: { 'X-XSRF-TOKEN': getCsrfToken(), 'Accept': 'text/html' },
        credentials: 'same-origin',
    }).then(() => router.reload());
}

function addBooth() {
    fetch(`/event/${props.event.slug}/booths`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': getCsrfToken(), 'Accept': 'text/html' },
        body: JSON.stringify(newBooth.value),
        credentials: 'same-origin',
    }).then(() => {
        newBooth.value = { name: '', company: '', description: '' };
        showAddBooth.value = false;
        router.reload();
    });
}

function deleteBooth(id: number) {
    fetch(`/event/${props.event.slug}/booths/${id}`, {
        method: 'DELETE',
        headers: { 'X-XSRF-TOKEN': getCsrfToken(), 'Accept': 'text/html' },
        credentials: 'same-origin',
    }).then(() => router.reload());
}

const announcementText = ref('');
const announcementSent = ref(false);
const waveSent = ref(false);

function sendAnnouncement() {
    if (!announcementText.value.trim()) return;
    fetch(`/event/${props.event.slug}/actions/announce`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': getCsrfToken() },
        body: JSON.stringify({ message: announcementText.value }),
        credentials: 'same-origin',
    }).then(() => {
        announcementSent.value = true;
        announcementText.value = '';
        setTimeout(() => { announcementSent.value = false; }, 3000);
    });
}

function triggerSerendipityWave() {
    fetch(`/event/${props.event.slug}/actions/serendipity-wave`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': getCsrfToken() },
        credentials: 'same-origin',
    }).then(() => {
        waveSent.value = true;
        setTimeout(() => { waveSent.value = false; }, 3000);
    });
}

function getCsrfToken(): string {
    return document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN='))?.split('=')[1]?.replace('%3D', '=') ?? '';
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">
        <Head :title="`${event.name} Dashboard`" />

        <Card
            class="border-border/70 bg-gradient-to-br from-primary/8 via-card to-card py-0 shadow-sm"
        >
            <CardContent class="space-y-3 p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <Heading
                        title="Organizer overview"
                        :description="`Live event metrics for ${event.name}.`"
                    />
                    <Badge variant="secondary" class="rounded-full px-3 py-1">
                        {{ event.slug }}
                    </Badge>
                </div>
            </CardContent>
        </Card>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <Card class="border-border/70 py-0 shadow-sm">
                <CardContent class="space-y-3 p-6">
                    <div
                        class="flex items-center gap-2 text-sm text-muted-foreground"
                    >
                        <Users class="size-4" />
                        Total active
                    </div>
                    <p class="text-3xl font-semibold tracking-tight">
                        {{ overview.total_active }}
                    </p>
                </CardContent>
            </Card>

            <Card class="border-border/70 py-0 shadow-sm">
                <CardContent class="space-y-3 p-6">
                    <div
                        class="flex items-center gap-2 text-sm text-muted-foreground"
                    >
                        <Handshake class="size-4" />
                        Connections
                    </div>
                    <p class="text-3xl font-semibold tracking-tight">
                        {{ overview.total_connections }}
                    </p>
                </CardContent>
            </Card>

            <Card class="border-border/70 py-0 shadow-sm">
                <CardContent class="space-y-3 p-6">
                    <div
                        class="flex items-center gap-2 text-sm text-muted-foreground"
                    >
                        <Activity class="size-4" />
                        Interaction rate
                    </div>
                    <p class="text-3xl font-semibold tracking-tight">
                        {{ overview.interaction_rate }}%
                    </p>
                </CardContent>
            </Card>

            <Card class="border-border/70 py-0 shadow-sm">
                <CardContent class="space-y-3 p-6">
                    <div
                        class="flex items-center gap-2 text-sm text-muted-foreground"
                    >
                        <LayoutGrid class="size-4" />
                        Cross-pollination
                    </div>
                    <p class="text-3xl font-semibold tracking-tight">
                        {{ overview.cross_pollination_rate }}%
                    </p>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <Card class="border-border/70 py-0 shadow-sm">
                <CardContent class="space-y-4 p-6">
                    <h2 class="text-lg font-semibold flex items-center gap-2">
                        <Megaphone class="size-5" />
                        Send Announcement
                    </h2>
                    <div class="flex gap-2">
                        <input
                            v-model="announcementText"
                            type="text"
                            maxlength="500"
                            placeholder="Type an event-wide announcement..."
                            class="flex-1 rounded-md border border-border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                        />
                        <Button @click="sendAnnouncement" :disabled="!announcementText.trim()">
                            Send
                        </Button>
                    </div>
                    <p v-if="announcementSent" class="text-sm text-green-500">Announcement sent to all participants!</p>
                </CardContent>
            </Card>

            <Card class="border-border/70 py-0 shadow-sm">
                <CardContent class="space-y-4 p-6">
                    <h2 class="text-lg font-semibold flex items-center gap-2">
                        <Sparkles class="size-5" />
                        Serendipity Wave
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Push match suggestions to all active participants simultaneously. Creates event-wide networking energy.
                    </p>
                    <Button @click="triggerSerendipityWave" variant="outline" class="w-full">
                        Trigger Connection Wave
                    </Button>
                    <p v-if="waveSent" class="text-sm text-green-500">Serendipity wave triggered!</p>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <Card class="border-border/70 py-0 shadow-sm">
                <CardContent class="space-y-4 p-6">
                    <h2 class="text-lg font-semibold">Session analytics</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-border/50 text-left text-muted-foreground">
                                    <th class="pb-2 font-medium">Session</th>
                                    <th class="pb-2 font-medium text-center">Check-ins</th>
                                    <th class="pb-2 font-medium text-center">Reactions</th>
                                    <th class="pb-2 font-medium text-center">Questions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="session in sessionAnalytics" :key="session.id" class="border-b border-border/30">
                                    <td class="py-3 font-medium">{{ session.title }}</td>
                                    <td class="py-3 text-center">{{ session.check_ins_count }}</td>
                                    <td class="py-3 text-center">{{ session.reactions_count }}</td>
                                    <td class="py-3 text-center">{{ session.questions_count }}</td>
                                </tr>
                                <tr v-if="sessionAnalytics.length === 0">
                                    <td colspan="4" class="py-6 text-center text-muted-foreground">No sessions yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <Card class="border-border/70 py-0 shadow-sm">
                <CardContent class="space-y-4 p-6">
                    <h2 class="text-lg font-semibold">Booth performance</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-border/50 text-left text-muted-foreground">
                                    <th class="pb-2 font-medium">Booth</th>
                                    <th class="pb-2 font-medium">Company</th>
                                    <th class="pb-2 font-medium text-center">Visitors</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="booth in boothPerformance" :key="booth.id" class="border-b border-border/30">
                                    <td class="py-3 font-medium">{{ booth.name }}</td>
                                    <td class="py-3 text-muted-foreground">{{ booth.company }}</td>
                                    <td class="py-3 text-center">{{ booth.visitor_count }}</td>
                                </tr>
                                <tr v-if="boothPerformance.length === 0">
                                    <td colspan="3" class="py-6 text-center text-muted-foreground">No booths yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Session Management -->
        <Card class="border-border/70 py-0 shadow-sm">
            <CardContent class="space-y-4 p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Manage Sessions</h2>
                    <Button @click="showAddSession = !showAddSession" size="sm">
                        {{ showAddSession ? 'Cancel' : '+ Add Session' }}
                    </Button>
                </div>

                <div v-if="showAddSession" class="grid gap-3 rounded-lg border border-border/50 p-4">
                    <div class="grid gap-3 md:grid-cols-2">
                        <input v-model="newSession.title" placeholder="Session title" class="rounded-md border border-border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground" />
                        <input v-model="newSession.speaker" placeholder="Speaker name" class="rounded-md border border-border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground" />
                        <input v-model="newSession.room" placeholder="Room" class="rounded-md border border-border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground" />
                        <input v-model="newSession.description" placeholder="Description" class="rounded-md border border-border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground" />
                        <input v-model="newSession.starts_at" type="datetime-local" class="rounded-md border border-border bg-background px-3 py-2 text-sm" />
                        <input v-model="newSession.ends_at" type="datetime-local" class="rounded-md border border-border bg-background px-3 py-2 text-sm" />
                    </div>
                    <Button @click="addSession" :disabled="!newSession.title || !newSession.starts_at || !newSession.ends_at">
                        Create Session
                    </Button>
                </div>

                <div class="space-y-2">
                    <div v-for="session in sessions" :key="session.id" class="flex items-center justify-between rounded-lg border border-border/30 px-4 py-3">
                        <div>
                            <p class="font-medium">{{ session.title }}</p>
                            <p class="text-xs text-muted-foreground">{{ session.speaker }} &middot; {{ session.room }}</p>
                        </div>
                        <Button variant="ghost" size="sm" class="text-destructive hover:text-destructive" @click="deleteSession(session.id)">
                            Delete
                        </Button>
                    </div>
                    <p v-if="sessions.length === 0" class="text-sm text-muted-foreground">No sessions yet.</p>
                </div>
            </CardContent>
        </Card>

        <!-- Booth Management -->
        <Card class="border-border/70 py-0 shadow-sm">
            <CardContent class="space-y-4 p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Manage Booths</h2>
                    <Button @click="showAddBooth = !showAddBooth" size="sm">
                        {{ showAddBooth ? 'Cancel' : '+ Add Booth' }}
                    </Button>
                </div>

                <div v-if="showAddBooth" class="grid gap-3 rounded-lg border border-border/50 p-4">
                    <div class="grid gap-3 md:grid-cols-2">
                        <input v-model="newBooth.name" placeholder="Booth name" class="rounded-md border border-border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground" />
                        <input v-model="newBooth.company" placeholder="Company name" class="rounded-md border border-border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground" />
                    </div>
                    <input v-model="newBooth.description" placeholder="Description" class="rounded-md border border-border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground" />
                    <Button @click="addBooth" :disabled="!newBooth.name || !newBooth.company">
                        Create Booth
                    </Button>
                </div>

                <div class="space-y-2">
                    <div v-for="booth in booths" :key="booth.id" class="flex items-center justify-between rounded-lg border border-border/30 px-4 py-3">
                        <div>
                            <p class="font-medium">{{ booth.name }}</p>
                            <p class="text-xs text-muted-foreground">{{ booth.company }} <span v-if="booth.staff.length"> &middot; Staff: {{ booth.staff.join(', ') }}</span></p>
                        </div>
                        <Button variant="ghost" size="sm" class="text-destructive hover:text-destructive" @click="deleteBooth(booth.id)">
                            Delete
                        </Button>
                    </div>
                    <p v-if="booths.length === 0" class="text-sm text-muted-foreground">No booths yet.</p>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
