<script>
    import { onDestroy, onMount } from "svelte";

    let frame = $state(0);

    function update() {
        frame = requestAnimationFrame(update);
    }
    update();

    onDestroy(() => {
        cancelAnimationFrame(frame);
    });
</script>

<div
    class="min-w-10 min-h-full h-full self-stretch flex flex-col justify-center bg-contain bg-repeat-y opacity-45 grow"
    style="
            background-image: url('/ingestRuleEditorStart.png');
            background-position-y: {frame / 8}px;
            "
></div>
