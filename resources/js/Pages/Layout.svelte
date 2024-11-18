<script lang="ts">
    import {portal} from "svelte-portal";
    import { Link } from "@inertiajs/svelte";
    import {active_project} from "$lib/store";
    interface Props {
        children?: import('svelte').Snippet;
    }

    let { children }: Props = $props();

    active_project.set(null);
</script>

<nav use:portal={"#global-sidebar"} class="text-xl p-4">
    <Link href="/projects">Projects</Link>
</nav>

{@render children?.()}
