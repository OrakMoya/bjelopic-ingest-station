<svelte:options accessors />

<script>
    import { Button } from "$lib/components/ui/button";
    import Combobox from "$lib/components/ui/combobox/combobox.svelte";
    import { TrashIcon } from "lucide-svelte";
    import { createEventDispatcher } from "svelte";
    import Decider from "./IngestRuleInputs/Decider.svelte";
    import MoveUpDown from "./MoveUpDown.svelte";

    /**
     * @type {{ next: any[]; operation: string; criteria: string; opts: any[]; }}
     */
    export let rule;

    export let label = "1";

    /**
     * @type {any[]}
     */
    let children = [];
    if (rule.next) {
        children = new Array(rule.next.length);
    }

    const dispatch = createEventDispatcher();

    export const compileRules = () => {
        /**
         * @type {{ next: any[]; operation: string; criteria: string; opts: any[]; }[]}
         */
        let nextRules = [];
        children.forEach((element) => {
            if (element) nextRules.push(element.compileRules());
        });
        return {
            operation: rule.operation,
            criteria: rule.criteria,
            next: rule.operation !== "save" ? nextRules : [],
            label: label,
        };
    };

    function addChild() {
        rule.next.push({
            operation: "save",
            criteria: "Other",
            next: [],
        });
        rule.next = rule.next;
    }

    function deleteThis() {
        dispatch("deleteThis");
    }

    let comboboxValues = [
        { value: "mimetypeIs", label: "Mimetype is" },
        { value: "containsExifTag", label: "Contains EXIF tag" },
        { value: "exifTagIs", label: "EXIF tag is" },
        { value: "filenameContains", label: "Filename contains" },
        { value: "save", label: "Save to" },
    ];

    /**
     * @param {number} i
     */
    function moveRuleUp(i) {
        if (i === 0) return;
        let previousRule = rule.next.at(i - 1);
        let thisRule = rule.next.at(i);
        rule.next[i - 1] = thisRule;
        rule.next[i] = previousRule;
    }
    /**
     * @param {number} i
     */
    function moveRuleDown(i) {
        if (i === rule.next.length - 1) return;
        let previousRule = rule.next.at(i + 1);
        let thisRule = rule.next.at(i);
        rule.next[i + 1] = thisRule;
        rule.next[i] = previousRule;
    }
</script>

<div
    class="flex px-4 py-6 pr-1 border border-accent rounded-xl gap-4 w-fit backdrop-blur backdrop-brightness-[120%] relative"
    style="--tw-backdrop-blur: blur(2px);"
>
    <div class="absolute top-[2px] left-[6px] opacity-50 italic text-sm">
        {label}
    </div>
    <div class="flex gap-2 items-center">
        <div class="flex gap-2">
            <div class="flex flex-col">
                <Button
                    on:click={deleteThis}
                    class="h-full"
                    variant="destructive"
                    ><TrashIcon class="w-4 h-4" />
                </Button>
            </div>
            <div class="flex flex-col w-full gap-2">
                <Combobox
                    bind:value={rule.operation}
                    comboValues={comboboxValues}
                    on:valueSelected={() => (rule.criteria = "")}
                />
                <Decider
                    bind:value={rule.criteria}
                    bind:operation={rule.operation}
                    bind:opts={rule.opts}
                />
            </div>
        </div>
    </div>
    {#if rule.operation !== "save"}
        <div class="flex flex-col justify-center gap-2">
            {#if Array.isArray(rule.next) && rule.next.length > 0}
                {#each rule.next as nextRule, i}
                    <div class="flex">
                        <MoveUpDown
                            on:moveUp={() => moveRuleUp(i)}
                            on:moveDown={() => moveRuleDown(i)}
                        />
                        <svelte:self
                            bind:this={children[i]}
                            on:deleteThis={() => {
                                rule.next = rule.next.toSpliced(i, 1);
                                children = children.toSpliced(i, 1);
                            }}
                            label={label + ":" + (i + 1)}
                            bind:rule={nextRule}
                        />
                    </div>
                {/each}
            {/if}
            {#if !(rule.next.at(rule.next.length - 1)?.operation === "save")}
                <div class="flex justify-center">
                    <Button
                        class="text-xl grow"
                        variant="ghost"
                        on:click={addChild}>+</Button
                    >
                </div>
            {/if}
        </div>
    {/if}
</div>
