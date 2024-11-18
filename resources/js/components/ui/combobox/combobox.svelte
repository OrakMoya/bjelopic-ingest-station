<script>
    import Check from "lucide-svelte/icons/check";
    import ChevronsUpDown from "lucide-svelte/icons/chevrons-up-down";
    import * as Command from "$lib/components/ui/command";
    import * as Popover from "$lib/components/ui/popover";
    import { Button } from "$lib/components/ui/button";
    import { tick } from "svelte";
    import { cn } from "$lib/utils";

    let {
        comboValues = [],
        value = $bindable(""),
        valueSelected = () => {},
    } = $props();

    let open = $state(false);
    let selectedValue = $state(null);
    /**
     *  @type {HTMLButtonElement|null}
     */
    let triggerRef = $state(null);

    $effect(() => {
        selectedValue =
            comboValues.find((f) => f.value === value)?.label ?? "Select...";
        valueSelected(selectedValue, value);
    });

    // We want to refocus the trigger button when the user selects
    // an item from the list so users can continue navigating the
    // rest of the form with the keyboard.
    function closeAndFocusTrigger() {
        tick().then(() => {
            triggerRef?.focus();
        });
    }
</script>

<Popover.Root bind:open>
    <Popover.Trigger bind:ref={triggerRef}>
        {#snippet child({ props })}
            <Button
                variant="outline"
                class="w-[200px] justify-between"
                {...props}
                role="combobox"
                aria-expanded={open}
            >
                {selectedValue || "Select a framework..."}
                <ChevronsUpDown class="ml-2 size-4 shrink-0 opacity-50" />
            </Button>
        {/snippet}
    </Popover.Trigger>
    <Popover.Content class="w-[200px] p-0">
        <Command.Root>
            <Command.Input placeholder="Search framework..." />
            <Command.List>
                <Command.Empty>No framework found.</Command.Empty>
                <Command.Group>
                    {#each comboValues as comboValue}
                        <Command.Item
                            value={comboValue.value}
                            onSelect={() => {
                                value = comboValue.value;
                                closeAndFocusTrigger();
                            }}
                        >
                            <Check
                                class={cn(
                                    "mr-2 size-4",
                                    value !== comboValue.value &&
                                        "text-transparent",
                                )}
                            />
                            {comboValue.label}
                        </Command.Item>
                    {/each}
                </Command.Group>
            </Command.List>
        </Command.Root>
    </Popover.Content>
</Popover.Root>
