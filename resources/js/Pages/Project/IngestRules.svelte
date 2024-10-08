<script>
    import Nav from "./Nav.svelte";
    import { active_project } from "$lib/store";
    import IngestRuleTree from "./IngestRuleTree.svelte";
    import { Button } from "$lib/components/ui/button";
    import axios from "axios";
    import { useForm, page } from "@inertiajs/svelte";
    import { onDestroy, onMount } from "svelte";
    import { RefreshCcw } from "lucide-svelte";
    import MoveUpDown from "./MoveUpDown.svelte";
    import { fade } from "svelte/transition";

    /**
     * @type {{ id: number; title: string; } }
     */
    export let project;
    active_project.set(project);

    /**
     * @type {any[]}
     */
    export let rules;
    /**
     * @type {Array<IngestRuleTree>}
     */
    let children = [];


    let form = useForm({
        rules: rules,
    });

    function save() {
        $form.post("/projects/" + project.id + "/ingestrules");
    }

    /**
     * @param {number} i
     */
    function addRootChild(i) {
        $form.rules = [
            ...$form.rules.slice(0, i + 1),
            {
                operation: "save",
                criteria: "Other",
                next: [],
            },
            ...$form.rules.slice(i + 1),
        ];
    }

    /**
     * @type {number}
     */
    let frame = 0;
    (function update() {
        frame = requestAnimationFrame(update);
    })();

    onDestroy(() => {
        cancelAnimationFrame(frame);
    });

    /**
     * @param {number} i
     */
    function moveRuleUp(i) {
        if (i === 0) return;
        let previousRule = $form.rules.at(i - 1);
        let thisRule = $form.rules.at(i);
        $form.rules[i - 1] = thisRule;
        $form.rules[i] = previousRule;
    }
    /**
     * @param {number} i
     */
    function moveRuleDown(i) {
        if (i === $form.rules.length - 1) return;
        let previousRule = $form.rules.at(i + 1);
        let thisRule = $form.rules.at(i);
        $form.rules[i + 1] = thisRule;
        $form.rules[i] = previousRule;
    }
</script>

<Nav />

<div class="flex flex-col w-full h-full">
    <div class="flex gap-x-2 items-center mb-2">
        <Button on:click={save} >Save</Button>
        <Button on:click={() => ($form.rules = rules)}
            ><RefreshCcw class="w-4 h-4" /></Button
        >
        {#if JSON.stringify($page.props.errors) !== "{}"}
            <span
                class="text-sm px-4 py-2 box-content outline outline-1 rounded-md bg-destructive/50 outline-destructive"
                >Rule {$page.props.errors.ingestOperation ??
                    $page.props.errors.ingestCriteria}</span
            >
        {/if}
    </div>
    <div class="w-full grow overflow-scroll flex flex-col">
        <div
            class="flex gap-x-2 min-w-fit w-full grow min-h-fit"
            style="background-image: url('/ingestRuleEditorBackground.png'); background-attachment: scroll;"
        >
            <div
                class="min-w-10 min-h-full self-stretch flex flex-col justify-center bg-contain bg-repeat-y opacity-45"
                style="
                background-image: url('/ingestRuleEditorStart.png');
                background-position-y: {frame / 8}px ;
            "
            ></div>
            <div
                class="flex flex-col gap-4 w-fit h-fit transition-all duration-500"
            >
                {#if !$form.rules.length}
                    <Button
                        on:click={() => addRootChild(0)}
                        variant="ghost"
                        class="text-lg ">+</Button
                    >
                {/if}

                {#each $form.rules as rule, i }
                    <div class="flex flex-col w-fit gap-4">
                        <div class="flex">
                            <MoveUpDown
                                on:moveUp={() => moveRuleUp(i)}
                                on:moveDown={() => moveRuleDown(i)}
                            />
                            <IngestRuleTree
                                label={(i + 1).toString()}
                                bind:rule={$form.rules[i]}
                                bind:this={children[i]}
                                on:deleteThis={() => {
                                    $form.rules = $form.rules.toSpliced(i, 1);
                                }}
                            />
                        </div>
                        {#if rule.operation !== "save"}
                            <div
                                class="flex flex-col justify-center transition-all duration-500"
                            >
                                <Button
                                    on:click={() => addRootChild(i)}
                                    variant="ghost"
                                    class="text-lg ">+</Button
                                >
                            </div>
                        {/if}
                    </div>
                {/each}
            </div>
        </div>
    </div>
</div>
