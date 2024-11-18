<script lang="ts">
    import CreateProjectDialog from "$lib/components/ui/CreateProjectDialog.svelte";
    import ProjectCard from "$lib/components/ui/ProjectCard.svelte";
    import Layout from "./Layout.svelte";

    let { projects } = $props();
</script>

<Layout>
    <main>
        <div class="text-xl font-bold mb-4">Projects</div>
        <CreateProjectDialog />
        <div class="grid grid-cols-3 gap-4 mt-4">
            {#each projects as project}
                <ProjectCard {project} />
            {/each}
        </div>
    </main>
</Layout>
