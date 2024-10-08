<script>
    import { Link } from "@inertiajs/svelte";
    import { portal } from "svelte-portal";
    import {active_project} from "$lib/store";


    let project = $active_project;

</script>

<nav use:portal={"#global-sidebar"} class="min-w-fit flex flex-col gap-y-2">
    <div class="text-xl w-full text-nowrap border-b border-accent p-4">
        {project?.title ?? 'Unknown'}
    </div>
    <div class="px-4 flex flex-col">
        <Link href="/projects/{project?.id}">Overview</Link>
        <Link href="/projects/{project?.id}/ingestrules">Ingest rules</Link>
    </div>
</nav>

