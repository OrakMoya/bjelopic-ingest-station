<script>
    import {portal} from "svelte-portal";
    import { Link } from "@inertiajs/svelte";
    import {active_project} from "$lib/store";

    active_project.set(null);
</script>

<nav use:portal={"#global-sidebar"} class="text-xl p-4">
    <Link href="/projects">Projects</Link>
</nav>

<slot />
