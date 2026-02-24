<script setup>
import { computed } from 'vue';
import { RouterView, useRoute } from 'vue-router';
import AppShell from './components/AppShell.vue';

const route = useRoute();
const useShell = computed(() => Boolean(route.meta?.requiresAuth));
</script>

<template>
    <RouterView v-slot="{ Component }">
        <AppShell v-if="useShell">
            <component :is="Component" />
        </AppShell>
        <component :is="Component" v-else />
    </RouterView>
</template>
