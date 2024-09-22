<script>
    import CreateProjectDialog from "./CreateProjectDialog.svelte";
    import ProjectCard from "./ProjectCard.svelte";

    export let projects;
</script>

<main>
    <div class="text-xl font-bold mb-4">Projects</div>
    <CreateProjectDialog />
    <div class="grid grid-cols-3">
        {#each projects as project}
            <ProjectCard {project} />
        {/each}
    </div>
</main>
