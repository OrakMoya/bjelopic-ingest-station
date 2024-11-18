<script>
    import * as Dialog from "$lib/components/ui/dialog";
    import { Button } from "$lib/components/ui/button";
    import { useForm, page } from "@inertiajs/svelte";
    import axios from "axios";
    import { Input } from "$lib/components/ui/input";
    import { Label } from "$lib/components/ui/label";
    import Combobox from "$lib/components/ui/combobox/combobox.svelte";
    import { onMount } from "svelte";
    import { toast } from "svelte-sonner";

    let volumes = $state([]);
    let open = $state(false);

    function refreshVolumes() {
        volumes = [];
        axios.get("/settings/volumes?type=storage").then((response) => {
            response.data.volumes.forEach(
                (
                    /** @type {{ id: number; display_name: string; }} */ volume,
                ) => {
                    volumes.push({
                        value: volume.id.toString(),
                        label: volume.display_name,
                    });
                },
            );
        });
    }

    onMount(() => {
        refreshVolumes();
    });

    let form = useForm({
        title: null,
        volume_id: null,
    });

    /**
     * @argument {SubmitEvent} e
     */
    function processSubmit(e) {
        e.preventDefault();

        $form.post("/projects", {
            onSuccess: () => {
                open = false;
                toast.success("Project created!");
            },
            onError: () => {
                if (JSON.stringify($page.props.errors) !== "{}") {
                    toast.error(
                        $page.props.errors[Object.keys($page.props.errors)[0]],
                    );
                    return;
                }
            },
        });
    }
</script>

<Dialog.Root bind:open>
    <Dialog.Trigger>
        {#snippet child({ props })}
            <Button {...props}>+</Button>
        {/snippet}
    </Dialog.Trigger>
    <Dialog.Content>
        <form onsubmit={processSubmit} class="grid gap-4">
            <Dialog.Header>
                <Dialog.Title>Create new project</Dialog.Title>
            </Dialog.Header>

            <div class="flex flex-col gap-2">
                <Label for="project-title">Title</Label>
                <Input id="project-title" bind:value={$form.title} />
                <Label>Volume</Label>
                <Combobox bind:value={$form.volume_id} comboValues={volumes} />
            </div>

            <Dialog.Footer>
                <Button on:click={(open = false)}>Cancel</Button>
                <Button type="submit">Submit</Button>
            </Dialog.Footer>
        </form>
    </Dialog.Content>
</Dialog.Root>
