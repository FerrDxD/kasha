<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const name = usePage().props.name;
</script>

<template>
    <div class="flex items-center gap-2">
        <AppLogoIcon class="size-8 shrink-0" />
        <span class="text-base font-bold tracking-tight text-slate-900 dark:text-white flex items-center">
            KASHA<span class="text-emerald-600 font-extrabold text-lg leading-none">.</span>
        </span>
    </div>
</template>
