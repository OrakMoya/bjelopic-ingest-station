<script>
    import { Input } from "$lib/components/ui/input";
    import MimetypeIsInput from "./MimetypeIsInput.svelte";
    import ExifTagIsInput from "./ExifTagIsInput.svelte";

    export let operation = "save";
    export let value = "";
    /**
     * @type any
     */
    export let opts;
</script>

{#if operation == "mimetypeIs"}
    <MimetypeIsInput bind:value />
{:else if operation == "exifTagIs"}
    <ExifTagIsInput bind:value bind:opts />
{:else}
    <Input class="bg-background" bind:value />
{/if}
