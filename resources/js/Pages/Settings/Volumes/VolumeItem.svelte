<script>
    import { Button } from "$lib/components/ui/button";
    import * as Dialog from "$lib/components/ui/dialog";
    import axios from "axios";
    import { toast } from "svelte-sonner";
    import { createEventDispatcher } from "svelte";
    import { RefreshCw } from "lucide-svelte";

    const dispatch = createEventDispatcher();

    /**
     * @type {{ id: number; display_name: string; absolute_path: string; type: string; free_space: number; total_space: number; is_alive: boolean;  }}
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
            .catch((e) => {
                toast.error(e.response.data.message);
            });
    }

    function refreshVolume() {
        axios
            .post("/settings/volumes/" + volume.id + "/refresh")
            .then((r) => {
                volume = r.data.volume;
            })
            .catch((e) => {
                toast.error(e.response.data.message);
                if (e.response.data.volume) {
                    volume = e.response.data.volume;
                }
            });
    }
</script>

<div class="flex gap-x-4 justify-between items-center {$$restProps.class}">
    <div class="flex flex-wrap gap-x-4 items-center">
        <div>
            {volume.id}
        </div>
        <div>
            {volume.display_name}
        </div>
        <div class="truncate">
            {volume.absolute_path}
        </div>
        <div>
            {volume.type}
        </div>
        <div>
            {(100 - (volume.free_space / volume.total_space) * 100).toFixed(1)}%
        </div>
        <div>
            {(volume.free_space / Math.pow(1024, 3)).toFixed(2)} GiB free
        </div>
    </div>
    <div class="flex items-center gap-2">
        <Button
            on:click={refreshVolume}
            variant={volume.is_alive ? "outline" : "default"}
            ><RefreshCw class="w-4 h-4" />
        </Button>
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
                    <Button on:click={deleteThisVolume} variant="destructive"
                        >Delete</Button
                    >
                </Dialog.Footer>
            </Dialog.Content>
        </Dialog.Root>
    </div>
</div>
