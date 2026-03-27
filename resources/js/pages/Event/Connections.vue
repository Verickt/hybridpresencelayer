<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import Heading from '@/components/Heading.vue';

defineProps<{
    event: { id: number; name: string; slug: string };
    connections: Array<{
        connection_id: number;
        user: { id: number; name: string; company: string };
        context: string;
        is_cross_world: boolean;
        created_at: string;
    }>;
}>();
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
        <Head :title="`${event.name} - Connections`" />

        <Heading title="Your connections" :description="`People you connected with at ${event.name}.`" />

        <p v-if="connections.length === 0" class="text-muted-foreground text-sm">
            No connections yet. Start meeting people!
        </p>

        <Card v-for="connection in connections" :key="connection.connection_id" class="shadow-sm">
            <CardContent class="flex items-center justify-between gap-4 p-4">
                <div class="min-w-0">
                    <p class="truncate font-medium">{{ connection.user.name }}</p>
                    <p class="text-muted-foreground truncate text-sm">{{ connection.user.company }}</p>
                    <p v-if="connection.context" class="text-muted-foreground mt-1 text-xs">
                        {{ connection.context }}
                    </p>
                </div>
                <Badge v-if="connection.is_cross_world" variant="secondary" class="shrink-0">
                    Cross-world
                </Badge>
            </CardContent>
        </Card>
    </div>
</template>
