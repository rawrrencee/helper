<script setup lang="ts">
import { Moon, Sun, Monitor } from 'lucide-vue-next';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useAppearance } from '@/composables/useAppearance';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const { appearance, updateAppearance } = useAppearance();

function cycleAppearance() {
    const modes = ['light', 'dark', 'system'] as const;
    const next = modes[(modes.indexOf(appearance.value) + 1) % modes.length];
    updateAppearance(next);
}
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <div class="ml-auto">
            <TooltipProvider :delay-duration="0">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="h-9 w-9 cursor-pointer"
                            @click="cycleAppearance"
                        >
                            <Sun v-if="appearance === 'light'" class="size-5" />
                            <Moon v-else-if="appearance === 'dark'" class="size-5" />
                            <Monitor v-else class="size-5" />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>
                        <p class="capitalize">{{ appearance }} mode</p>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>
        </div>
    </header>
</template>
