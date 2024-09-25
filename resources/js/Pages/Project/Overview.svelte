<script>
    import { Link } from "@inertiajs/svelte";
    import { portal } from "svelte-portal";
    import {active_project} from "$lib/store";

    export let project;

    active_project.set(project);

</script>

<nav use:portal={"#global-sidebar"} class="min-w-fit flex flex-col gap-y-2">
    <div class="text-xl w-full text-nowrap border-b border-accent p-4">
        {project.title}
    </div>
    <div class="px-4">
        <Link>Ingest rules</Link>
    </div>
</nav>
