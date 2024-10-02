<script>
    import Nav from "./Nav.svelte";
    import { active_project } from "$lib/store";
    import IngestRuleTree from "./IngestRuleTree.svelte";
    import { Button } from "$lib/components/ui/button";
    import axios from "axios";
    import { useForm, page } from "@inertiajs/svelte";
    import { onMount } from "svelte";

    /**
     * @type {{ id: number; title: string; } }
     */
    export let project;
    active_project.set(project);

    /**
     * @type {any[]}
     */
    export let rules;
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

    onMount(() => {
        children.forEach((element) => {
            console.log(element);
        });
    });
</script>

<Nav />

<div class="w-full h-full overflow-scroll">
    <div class="flex gap-x-2 items-center mb-2">
        <Button on:click={save}>Save</Button>
        {#if JSON.stringify($page.props.errors)!=="{}"}
            <span
                class="text-sm px-4 py-2 border rounded-md bg-destructive/50 border-destructive"
                >{$page.props.errors.ingestOperation ?? $page.props.errors.ingestCriteria}</span
            >
        {/if}
    </div>
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
