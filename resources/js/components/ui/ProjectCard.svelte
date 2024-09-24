<script>
    export let project;
    import { Link } from "@inertiajs/svelte";
</script>

<Link
    href="/projects/{project.id}"
    class="bg-card rounded-lg transition-colors border hover:bg-accent"
>
    <div class="p-4">
        <div class="text-lg">{project.title}</div>
        <div>{project.volume.display_name}</div>
    </div>
</Link>
