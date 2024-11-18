<script>
    import { Input } from "$lib/components/ui/input";
    import MimetypeIsInput from "./MimetypeIsInput.svelte";
    import ExifTagIsInput from "./ExifTagIsInput.svelte";
    /** @import IngestRuleObject from "$lib/types" */

    /**
     * @typedef Props
     * @property {IngestRuleObject}  rule
     */

    /** @type {Props} */
    let {
        rule = $bindable()
    } = $props();

</script>

{#if rule.operation == "mimetypeIs"}
    <MimetypeIsInput bind:value={rule.criteria} />
{:else if rule.operation == "exifTagIs"}
    <ExifTagIsInput bind:value={rule.criteria} bind:opts={rule.opts} />
{:else}
    <Input class="bg-background" bind:value={rule.criteria} />
{/if}
