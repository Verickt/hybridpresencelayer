<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { edit } from '@/routes/appearance';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Darstellungseinstellungen',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Darstellungseinstellungen" />

    <h1 class="sr-only">Darstellungseinstellungen</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Darstellungseinstellungen"
            description="Aktualisieren Sie die Darstellungseinstellungen Ihres Kontos"
        />
        <AppearanceTabs />
    </div>
</template>
