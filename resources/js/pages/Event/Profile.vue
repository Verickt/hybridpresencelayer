<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import Heading from '@/components/Heading.vue';

defineProps<{
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
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
        <Head :title="`${event.name} - Profile`" />

        <Heading title="Your profile" :description="`How others see you at ${event.name}.`" />

        <Card class="shadow-sm">
            <CardContent class="space-y-3 p-4">
                <div>
                    <p class="text-lg font-semibold">{{ user.name }}</p>
                    <p class="text-muted-foreground text-sm">{{ user.email }}</p>
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
    </div>
</template>
