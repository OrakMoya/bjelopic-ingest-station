<script>
    import { Input } from "$lib/components/ui/input";
    import MimetypeIsInput from "./MimetypeIsInput.svelte";

    export let operation = "save";
    export let value = "";

</script>

{#if operation == "mimetypeIs"}
    <MimetypeIsInput bind:value={value} />
{:else}
    <Input class="bg-background" bind:value={value} />
{/if}
