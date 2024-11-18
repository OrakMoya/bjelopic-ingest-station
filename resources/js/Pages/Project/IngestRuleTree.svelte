<svelte:options runes />

<script>
    import IngestRuleTree from "./IngestRuleTree.svelte";
    import { Button } from "$lib/components/ui/button";
    import Combobox from "$lib/components/ui/combobox/combobox.svelte";
    import { TrashIcon } from "lucide-svelte";
    import { createEventDispatcher, onMount } from "svelte";
    import Decider from "./IngestRuleInputs/Decider.svelte";
    import MoveUpDown from "./MoveUpDown.svelte";
    import {newRuleGenerator} from "./utils";
    import { Input } from "$lib/components/ui/input";
    /**
     * @import {IngestRuleObject} from "$lib/types";
     */

    let comboboxValues = [
        { value: "mimetypeIs", label: "Mimetype is" },
        { value: "containsExifTag", label: "Contains EXIF tag" },
        { value: "exifTagIs", label: "EXIF tag is" },
        { value: "filenameContains", label: "Filename contains" },
        { value: "save", label: "Save to" },
    ];

    /**
     * @typedef {Object} Props
     * @property {IngestRuleObject} rule
     * @property {Function} [onMoveUp]
     * @property {Function} [onMoveDown]
     * @property {Function} [onDelete]
     */

    /**
     * @type {Props}
     */
    let {
        rule = $bindable(),
        onMoveUp = () => {},
        onMoveDown = () => {},
        onDelete = () => {},
    } = $props();

    /**
     * @param {number} i
     */
    function moveDown(i) {
        if (i >= rule.next.length - 1) return;

        let thisRule = $state.snapshot(rule);
        [thisRule.next[i], thisRule.next[i + 1]] = [
            thisRule.next[i + 1],
            thisRule.next[i],
        ];
        rule = thisRule;
    }

    /**
     * @param {number} i
     */
    function moveUp(i) {
        if (i == 0) return;

        let thisRule = $state.snapshot(rule);
        [thisRule.next[i], thisRule.next[i - 1]] = [
            thisRule.next[i - 1],
            thisRule.next[i],
        ];
        rule = thisRule;
    }
</script>

<div class="flex align-middle w-fit">
    <MoveUpDown {onMoveUp} {onMoveDown} />
    <div
        class="
    flex flex-row gap-x-2 w-fit justify-center align-middle
    p-2 border border-accent rounded-lg
    backdrop-blur backdrop-brightness-[120%]
    "
        style="--tw-backdrop-blur: blur(2px)"
    >
        <button onclick={() => onDelete()}>
            <TrashIcon class="w-5 h-5 opacity-50" />
        </button>
        <div class="flex flex-col gap-2 justify-center">
            <Decider bind:rule />
            <Combobox
                comboValues={comboboxValues}
                bind:value={rule.operation}
            />
        </div>
        <div class="flex flex-col justify-center gap-y-2">
            {#if rule.next.length}
                {#each rule.next as _, i}
                    <IngestRuleTree
                        onMoveUp={() => moveUp(i)}
                        onMoveDown={() => moveDown(i)}
                        onDelete={() => {
                            rule.next = rule.next.toSpliced(i, 1);
                        }}
                        bind:rule={rule.next[i]}
                    />
                {/each}
            {/if}
            {#if rule.operation != "save" && rule.next[rule.next.length - 1]?.operation != "save"}
                <Button
                    variant="outline"
                    class="w-fit bg-opacity-25"
                    onclick={() => {
                        rule.next.push(newRuleGenerator());
                    }}>+</Button
                >
            {/if}
        </div>
    </div>
</div>
