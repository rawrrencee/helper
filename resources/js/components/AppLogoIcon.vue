<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 64 64"
        :class="className"
        v-bind="$attrs"
    >
        <!-- Head -->
        <circle cx="32" cy="14" r="8" fill="currentColor" />
        <!-- Body -->
        <path
            fill="currentColor"
            d="M22 26a10 10 0 0 1 20 0v10h-6v16h-8V36h-6V26Z"
        />
        <!-- Left hand reaching out -->
        <path
            fill="currentColor"
            d="M22 26c-2 0-6 2-10 8l-4 6 5 3 4-6 1-2v-5c0-2 2-4 4-4Z"
        />
        <!-- Right hand reaching out -->
        <path
            fill="currentColor"
            d="M42 26c2 0 6 2 10 8l4 6-5 3-4-6-1-2v-5c0-2-2-4-4-4Z"
        />
        <!-- Heart in chest -->
        <path
            fill="oklch(0.95 0 0)"
            d="M32 33c-1.5-2-4-2.5-5-1s0 3.5 5 6.5c5-3 6-5 5-6.5s-3.5-1-5 1Z"
        />
    </svg>
</template>
