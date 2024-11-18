<script>
    import Nav from "./Nav.svelte";
    import { active_project, ingest_rules_unsaved } from "$lib/store";
    import { Button } from "$lib/components/ui/button";
    import { useForm, page } from "@inertiajs/svelte";
    import { onDestroy, onMount } from "svelte";
    import { RefreshCcw, VariableIcon } from "lucide-svelte";
    import MoveUpDown from "./MoveUpDown.svelte";
    import { fade } from "svelte/transition";
    import IngestRuleTree from "./IngestRuleTree.svelte";
    import ResizablePaneGroup from "$lib/components/ui/resizable/resizable-pane-group.svelte";
    import IngestRuleEditorStart from "./IngestRuleEditorStart.svelte";
    import { newRuleGenerator } from "./utils";

    /**
     * @import {IngestRuleObject} from "$lib/types.js"
     */

    /**
     * @typedef {Object} Props
     * @property {{ id: number; title: string; } } project
     * @property {IngestRuleObject[]} rules
     */

    /** @type {Props} */
    let { project, rules } = $props();
    active_project.set(project);

    let reactiveRules = $state(structuredClone(rules));

    let form = useForm({
        rules: rules,
    });

    $effect(() => {
        console.log(unsaved(rules, $state.snapshot(reactiveRules)));
        console.log(rules);
        console.log($state.snapshot(rules));
        $ingest_rules_unsaved = unsaved(rules, $state.snapshot(reactiveRules));
    });

    function save() {
        $form.rules = $state.snapshot(reactiveRules);
        $form.post("/projects/" + project.id + "/ingestrules");
    }

    /**
     * @param {number} i
     */
    function moveUp(i) {
        if (i == 0) return;

        // Prevents double reactive update
        rules = $state.snapshot(reactiveRules);
        [rules[i], rules[i - 1]] = [rules[i - 1], rules[i]];
        reactiveRules = rules;
    }

    /**
     * @param {number} i
     */
    function moveDown(i) {
        if (i >= reactiveRules.length - 1) return;

        // Prevents double reactive update
        rules = $state.snapshot(reactiveRules);
        [rules[i], rules[i + 1]] = [rules[i + 1], rules[i]];
        reactiveRules = rules;
    }

    /**
     * @param {IngestRuleObject} rule1
     * @param {IngestRuleObject} rule2
     * @returns {boolean}
     */
    function compare(rule1, rule2) {
        if (
            rule1.operation !== rule2.operation ||
            rule1.criteria !== rule2.criteria ||
            JSON.stringify(rule1.opts) !== JSON.stringify(rule2.opts) // jbg inace ne radi
        ) {
            return false;
        }
        if (rule1.next.length !== rule2.next.length) {
            return false;
        }
        for (let i = 0; i < rule1.next.length; i++) {
            if (!compare(rule1.next[i], rule2.next[i])) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param {IngestRuleObject[]} rules1
     * @param {IngestRuleObject[]} rules2
     * @returns {boolean}
     */
    function unsaved(rules1, rules2) {
        if (rules1.length != rules2.length) return true;
        for (let i = 0; i < rules1.length; i++) {
            if (!compare(rules1[i], rules2[i])) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param {number} index
     */
    function addNewRuleBefore(index) {
        reactiveRules.splice(index, 0, newRuleGenerator());
    }

    /**
     * @param {number} index
     */
    function addNewRuleAfter(index) {
        reactiveRules.splice(index + 1, 0, newRuleGenerator());
    }
</script>

<Nav />

<div class="flex flex-col w-full h-full">
    <div class="flex gap-x-2 items-center mb-2">
        <Button onclick={save} disabled={!$ingest_rules_unsaved}>Save</Button>
        <Button onclick={() => (reactiveRules = rules)}
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
    <div
        class="flex w-full h-full grow overflow-scroll"
        style="
        background-image: url('/ingestRuleEditorBackground.png');
    background-attachment: scroll;"
    >
        <IngestRuleEditorStart />
        <div class="flex flex-col gap-y-2 w-full h-full grow">
            {#each reactiveRules as _, i}
                <div class="flex flex-col gap-y-2 w-fit">
                    {#if i == 0}
                        <Button
                            variant="ghost"
                            onclick={() => addNewRuleBefore(i)}>+</Button
                        >
                    {/if}
                    <IngestRuleTree
                        onMoveDown={() => moveDown(i)}
                        onMoveUp={() => moveUp(i)}
                        onDelete={() =>
                            (reactiveRules = reactiveRules.toSpliced(i, 1))}
                        bind:rule={reactiveRules[i]}
                    />
                    <Button variant="ghost" onclick={() => addNewRuleAfter(i)}
                        >+</Button
                    >
                </div>
            {/each}
        </div>
    </div>
</div>
