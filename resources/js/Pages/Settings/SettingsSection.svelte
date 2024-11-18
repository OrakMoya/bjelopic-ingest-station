<script lang="ts">
    interface Props {
        children?: import('svelte').Snippet;
    }

    let { children }: Props = $props();
</script>

<section class="p-8 border border-accent rounded-xl">
    {@render children?.()}
</section>
