<script>

    import { MoveDownIcon, MoveUpIcon } from "lucide-svelte";
    import { createEventDispatcher } from "svelte";


    const dispatch = createEventDispatcher();
</script>

<div class="flex flex-col justify-center">
    <div
        class="flex flex-col gap-y-2 border border-r-0 border-accent rounded-l-lg p-2 py-4 backdrop-blur backdrop-brightness-[120%]"
    >
        <button
            on:click={()=>dispatch("moveUp")}
            class="opacity-50 hover:opacity-100 transition-opacity duration-300"
            ><MoveUpIcon /></button
        >
        <button
            on:click={()=>dispatch("moveDown")}
            class="opacity-50 hover:opacity-100 transition-opacity duration-300"
            ><MoveDownIcon /></button
        >
    </div>
</div>
