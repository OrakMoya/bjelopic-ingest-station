<script>
    import { Input } from "$lib/components/ui/input";

    export let value = "";
    /**
     * @type {any | { tag: string; }}
     */
    export let opts;

    if (!opts?.tag) opts = { tag: "" };
</script>

<Input bind:value={opts.tag} placeholder="Tag" />
<Input bind:value placeholder="Criteria" />
