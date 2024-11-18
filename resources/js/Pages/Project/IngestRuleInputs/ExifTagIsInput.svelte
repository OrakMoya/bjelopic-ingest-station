<script>
    import { Input } from "$lib/components/ui/input";

    /**
     * @typedef {Object} Props
     * @property {string} value
     * @property {any | { tag: string; }} opts
     */

    /** @type {Props} */
    let { value = $bindable(), opts = $bindable() } = $props();

    if (!opts?.tag) opts = { tag: "" };
</script>

<Input bind:value={opts.tag} placeholder="Tag" />
<Input bind:value placeholder="Criteria" />
