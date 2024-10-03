<script>
    import Nav from "./Nav.svelte";
    import { active_project } from "$lib/store";
    import IngestRuleTree from "./IngestRuleTree.svelte";
    import { Button } from "$lib/components/ui/button";
    import axios from "axios";
    import { useForm, page } from "@inertiajs/svelte";
    import { onDestroy, onMount } from "svelte";
    import { RefreshCcw } from "lucide-svelte";

    /**
     * @type {{ id: number; title: string; } }
     */
    export let project;
    active_project.set(project);

    /**
     * @type {any[]}
     */
    export let rules;
    let initialRules = rules;
    let children = [];

    function compile() {
        /**
         * @type {any[]}
         */
        let compiledRules = [];
        children = children.reduce((prev, cur) => {
            if (cur) prev.push(cur);
            return prev;
        }, []);
        console.log(children);
        children.forEach((element) => {
            if (element) {
                compiledRules.push(element.compileRules());
            }
        });
        return compiledRules;
    }
    let form = useForm({
        rules: null,
    });

    function save() {
        $form.rules = compile();
        $form.post("/projects/" + project.id + "/ingestrules", {
            onError: (e) => {
                rules = $form.rules;
            },
        });
    }

    function addRootChild(i) {
        rules = [
            ...rules.slice(0, i + 1),
            {
                operation: "save",
                criteria: "Other",
                next: [],
            },
            ...rules.slice(i + 1),
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

    onMount(() => {
        children.forEach((element) => {
            console.log(element);
        });
    });
</script>

<Nav />

<div class="flex flex-col w-full h-full">
    <div class="flex gap-x-2 items-center mb-2">
        <Button on:click={save}>Save</Button>
        <Button on:click={() => (rules = initialRules)}><RefreshCcw class="w-4 h-4" /></Button>
        {#if JSON.stringify($page.props.errors) !== "{}"}
            <span
                class="text-sm px-4 py-2 box-content outline outline-1 rounded-md bg-destructive/50 outline-destructive"
                >{$page.props.errors.ingestOperation ??
                    $page.props.errors.ingestCriteria}</span
            >
        {/if}
    </div>
    <div class="w-full grow overflow-scroll">
        <div
            class="flex gap-x-2"
            style="background-image: url('/ingestRuleEditorBackground.png') ;
background-attachment: scroll;"
        >
            <div
                class="min-w-10 min-h-full self-stretch flex flex-col justify-center bg-contain bg-repeat-y opacity-45"
                style="
                background-image: url('/ingestRuleEditorStart.png');
                background-position-y: {frame / 8}px ;
            "
            ></div>
            <div class="flex flex-col gap-4 w-fit transition-all duration-500">
                {#if !rules.length}
                    <Button
                        on:click={() => addRootChild(0)}
                        variant="ghost"
                        class="text-lg ">+</Button
                    >
                {/if}

                {#each rules as rule, i}
                    <div class="flex flex-col w-fit gap-4">
                        <IngestRuleTree
                            label={i + 1}
                            class="transition-all duration-500"
                            bind:rule={rules[i]}
                            bind:this={children[i]}
                            on:deleteThis={() => {
                                rules = rules.toSpliced(i, 1);
                            }}
                        />
                        {#if rule.operation !== "save"}
                            <div
                                class="flex flex-col justify-center transition-all duration-500"
                            >
                                <!-- content here -->
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
