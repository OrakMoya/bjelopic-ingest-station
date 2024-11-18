<script lang="ts">
    import { cn } from "$lib/utils";
    interface Props {
        children?: import('svelte').Snippet;
        [key: string]: any
    }

    let { children, ...rest }: Props = $props();
</script>

<div
    class=" {cn(
        'mt-8  w-full border-t border-slate-800 mb-4',
        rest.class,
    )}"
>
    <span class="text-xl font-bold">
        {@render children?.()}
    </span>
</div>
