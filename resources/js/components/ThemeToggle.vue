<script setup lang="ts">
import { Moon, Sun } from '@lucide/vue';
import { computed } from 'vue';
import { useAppearance } from '@/composables/useAppearance';

const { appearance, resolvedAppearance, updateAppearance } = useAppearance();

const isDark = computed(() => resolvedAppearance.value === 'dark');

function toggleTheme() {
    if (appearance.value === 'dark') {
        updateAppearance('light');
    } else if (appearance.value === 'light') {
        updateAppearance('dark');
    } else {
        // If system, toggle to dark or light based on current resolved state
        updateAppearance(isDark.value ? 'light' : 'dark');
    }
}
</script>

<template>
    <button
        @click="toggleTheme"
        class="relative inline-flex h-9 w-9 items-center justify-center rounded-md transition-colors hover:bg-accent focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
        :class="isDark ? 'text-foreground' : 'text-foreground'"
        aria-label="Toggle theme"
    >
        <Sun
            v-if="isDark"
            class="h-5 w-5 transition-all"
        />
        <Moon
            v-else
            class="h-5 w-5 transition-all"
        />
    </button>
</template>
