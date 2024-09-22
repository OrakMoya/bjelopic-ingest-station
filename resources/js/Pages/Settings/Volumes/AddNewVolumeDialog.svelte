<script>
    import { Button } from "$lib/components/ui/button";
    import * as Dialog from "$lib/components/ui/dialog";
    import { Input } from "$lib/components/ui/input";
    import { createEventDispatcher } from "svelte";
    import { useForm } from "@inertiajs/svelte";
    import { Label } from "$lib/components/ui/label";
    import axios from "axios";
    import { toast } from "svelte-sonner";
    import * as Tabs from "$lib/components/ui/tabs";

    const dispatch = createEventDispatcher();
    const csrf_token = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");

    let open = false;

    async function processSubmit() {
        axios
            .post("/settings/volumes", formData)
            .then(() => {
                open = false;
                dispatch("volumeAdded");
            })
            .catch((error) => {
                toast.error(error.response.data.message);
            });
    }

    let formData = {
        _token: csrf_token,
        display_name: null,
        absolute_path: null,
        type: "storage",
    };
</script>

<Dialog.Root bind:open>
    <Dialog.Trigger asChild let:builder>
        <Button builders={[builder]}>Add</Button>
    </Dialog.Trigger>
    <Dialog.Content>
        <form
            on:submit|preventDefault={processSubmit}
            class="flex flex-col gap-y-2"
        >
            <Dialog.Header>
                <Dialog.Title>Add new volume</Dialog.Title>
            </Dialog.Header>

            <Label for="display-name">Display name</Label>
            <Input id="display-name" bind:value={formData.display_name} />

            <Label for="path">Path</Label>
            <Input id="path" bind:value={formData.absolute_path} />

            <Tabs.Root
                class="w-full"
                bind:value={formData.type}
            >
                <Tabs.List>
                    <Tabs.Trigger value="storage">Storage</Tabs.Trigger>
                    <Tabs.Trigger value="ingest">Ingest</Tabs.Trigger>
                </Tabs.List>
            </Tabs.Root>

            <Dialog.Footer>
                <Button type="submit">Submit</Button>
            </Dialog.Footer>
        </form>
    </Dialog.Content>
</Dialog.Root>
