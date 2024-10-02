<svelte:options accessors />

<script>
    import { Button } from "$lib/components/ui/button";
    import { Input } from "$lib/components/ui/input";
    import { TrashIcon } from "lucide-svelte";
    import { createEventDispatcher } from "svelte";

    /**
     * @type {{ next: any[]; operation: string; criteria: string; }}
     */
    export let rule;


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
         * @type {{ next: any[]; operation: string; criteria: string; }[]}
         */
        let nextRules = [];
        children.forEach((element) => {
            if (element) nextRules.push(element.compileRules());
        });
        return {
            operation: rule.operation,
            criteria: rule.criteria,
            next: rule.operation !== "save" ? nextRules : [],
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

    function operationChanged() {
        dispatch("operationChanged", rule.operation);
    }
</script>

<div class="flex p-4 border border-accent rounded-xl gap-4 w-fit bg-background">
    <div class="flex gap-2 items-center">
        <div class="flex gap-2">
            <div class="flex flex-col w-full gap-2">
                <div class="flex items-center gap-x-2">
                    <span class="font-bold">Operation: </span>
                    <Input
                        class="w-fit"
                        bind:value={rule.operation}
                        on:input={operationChanged}
                    />
                </div>
                <div class="flex items-center gap-x-2">
                    <span class="font-bold"
                        >{rule.operation == "save" ? "Location" : "Criteria"}:
                    </span><Input bind:value={rule.criteria} />
                </div>
            </div>
            <div class="flex flex-col">
                <Button
                    on:click={deleteThis}
                    class="h-full"
                    variant="destructive"
                    ><TrashIcon class="w-4 h-4" />
                </Button>
            </div>
        </div>
    </div>
    {#if rule.operation !== "save"}
        <div class="flex flex-col justify-center gap-2">
            {#if Array.isArray(rule.next) && rule.next.length > 0}
                {#each rule.next as nextRule, i}
                    <svelte:self
                        bind:this={children[i]}
                        on:deleteThis={() => {
                            rule.next = rule.next.toSpliced(i, 1);
                            children = children.toSpliced(i, 1);
                        }}
                        bind:rule={nextRule}
                    />
                {/each}
            {/if}
            {#if !(rule.next.at(rule.next.length - 1)?.operation === "save")}
                <!-- content here -->
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
