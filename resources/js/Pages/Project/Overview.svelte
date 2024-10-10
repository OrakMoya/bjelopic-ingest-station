<script>
    import Nav from "./Nav.svelte";
    import { active_project } from "$lib/store";
    import { Button } from "$lib/components/ui/button";
    import { TrashIcon } from "lucide-svelte";
    import { router } from "@inertiajs/svelte";
    export let project;
    active_project.set(project);


    function deleteThisProject() {
        router.delete("/projects/" + project.id);
    }
</script>

<Nav />

<div class="flex items-center justify-between">
    <span>Gas</span>

    <div>
        <Button variant="destructive" on:click={deleteThisProject}><TrashIcon class="w-4 h-4" /></Button>
    </div>
</div>
