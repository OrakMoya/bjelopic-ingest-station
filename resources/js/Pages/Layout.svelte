<script>
    import {portal} from "svelte-portal";
    import { Link } from "@inertiajs/svelte";
</script>

<nav use:portal={"#global-sidebar"} class="text-xl p-4">
    <Link href="/projects">Projects</Link>
</nav>

<slot />
