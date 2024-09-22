<script>
    import { Button } from "$lib/components/ui/button";
    import * as Dialog from "$lib/components/ui/dialog";
    import axios from "axios";
    import { toast } from "svelte-sonner";
    import { createEventDispatcher } from "svelte";

    const dispatch = createEventDispatcher();

    /**
     * @type {{ id: number; display_name: any; absolute_path: any; }}
     */
    export let volume;
    let open = false;

    function deleteThisVolume() {
        axios
            .delete("/settings/volumes/" + volume.id)
            .then(() => {
                open = false;
                toast.success("Volume deleted");
                dispatch("volumeDeleted");
            })
            .catch((error) => {
                toast.error(error.response.data.message);
            });
    }
</script>

<div class="flex gap-x-4 justify-between items-center">
    <div class="flex gap-x-4 items-center">
        <div>
            {volume.id}
        </div>
        <div>
            {volume.display_name}
        </div>
        <div>
            {volume.absolute_path}
        </div>
    </div>
    <div>
        <Dialog.Root bind:open>
            <Dialog.Trigger asChild let:builder>
                <Button builders={[builder]} variant="destructive"
                    >Delete</Button
                >
            </Dialog.Trigger>
            <Dialog.Content>
                <Dialog.Header>
                    <Dialog.Title>Are you absolutely sure?</Dialog.Title>
                    <Dialog.Description>
                        This will not delete any files.
                    </Dialog.Description>
                </Dialog.Header>
                <Dialog.Footer>
                    <Button on:click={() => (open = false)}>Cancel</Button>
                    <Button on:click={deleteThisVolume} variant="destructive">Delete</Button>
                </Dialog.Footer>
            </Dialog.Content>
        </Dialog.Root>
    </div>
</div>
