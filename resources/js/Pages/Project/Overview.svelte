<script>
    import Nav from "./Nav.svelte";
    import { active_project } from "$lib/store";
    export let project;
    active_project.set(project);
</script>

<Nav />

Gas
