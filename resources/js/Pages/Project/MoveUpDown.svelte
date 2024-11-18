<script>

    import { MoveDownIcon, MoveUpIcon, TrashIcon } from "lucide-svelte";

    let {onMoveUp = () => {}, onMoveDown = () => {}} = $props();

</script>

<div class="flex flex-col justify-center">
    <div
        class="flex flex-col items-center gap-y-2 border border-r-0 border-accent rounded-l-lg p-2 py-4 backdrop-blur backdrop-brightness-[120%]"
    >
        <button
            onclick={()=>onMoveUp()}
            class="opacity-50 hover:opacity-100 transition-opacity duration-300"
            ><MoveUpIcon /></button
        >
        <button
            onclick={()=>onMoveDown()}
            class="opacity-50 hover:opacity-100 transition-opacity duration-300"
            ><MoveDownIcon /></button
        >
    </div>
</div>
