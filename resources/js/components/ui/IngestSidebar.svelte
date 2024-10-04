<script>
    import { onMount } from "svelte";
    import axios from "axios";
    import { toast } from "svelte-sonner";
    import { Button } from "$lib/components/ui/button";
    import { active_project } from "$lib/store";
    import * as Dialog from "$lib/components/ui/dialog";
    import { scale } from "svelte/transition";
    import { LoaderCircle } from "lucide-svelte";
    import {echo} from "$lib/echo";

    /**
     * @type {any[]}
     */
    let ingestData = [];
    let focused = false;
    let ingesting = false;
    let fileCount = 0;
    let filesLeft = 0;
    let selectProjectDialogOpen = false;
    let indexing = false;

    onMount(() => {
        refresh();

        window.Echo.channel("ingest").listen("IngestIndexedEvent", (e) => {
            refresh();
        });
        window.Echo.channel("ingest").listen("IndexEvent", (e) => {
            indexing = e.indexing;
        });

        window.Echo.channel("ingest").listen("IngestStartedEvent", (e) => {
            toast.info(e.message);
            fileCount = e.fileCount;
            filesLeft = e.fileCount;
            ingesting = true;
        });

        window.Echo.channel("ingest").listen("FileIngestedEvent", (e) => {
            ingesting = true;
            fileCount = e.totalFileCount;
            removeIngestedFile(e.file.id);
        });

        window.Echo.channel("ingest").listen(
            "IngestCompleteEvent",
            (/** @type {{ message: string; }} */ e) => {
                toast.success(e.message);
                refresh();
                ingesting = false;
            },
        );

        window.Echo.channel("ingest").listen(
            "IngestErrorEvent",
            (/** @type {{ message: string;}} */ e) => {
                toast.error(e.message);
                refresh();
                ingesting = false;
            },
        );
    });

    /**
     * @param {number} id
     */
    function removeIngestedFile(id) {
        let index = -1;
        ingestData.some((file, i) => {
            if (file.id === id) {
                index = i;

                return true;
            }
            return false;
        });

        if (index === -1) {
            return;
        }

        ingestData.splice(index, 1);
        ingestData = ingestData;
        filesLeft = ingestData.length;
    }

    function refresh() {
        return axios
            .get("/ingest")
            .then((r) => {
                ingestData = r.data.ingest_data;
                fileCount = r.data.ingest_file_count;
                filesLeft = ingestData.length;
                ingesting = r.data.ingesting;
                indexing = r.data.indexing;
            })
            .catch((e) => {
                toast.error(e.response.data.message);
            });
    }

    function prepareIngest() {
        let id = $active_project?.id;
        if (!id) {
            selectProjectDialogOpen = true;
            return;
        }
        startIngest(id);
    }

    /**
     * @param {number} id
     */
    function startIngest(id) {
        refresh().then(() => {
            axios
                .post("/ingest", {
                    id: id,
                })
                .catch((e) => {
                    toast.error(e.response.data.message);
                });
        });
    }
</script>

<div
    class="{focused || selectProjectDialogOpen
        ? 'min-w-56'
        : 'min-w-48 w-48'} transition-all border-l border-accent"
    on:focusin={() => (focused = true)}
    on:focusout={() => (focused = false)}
>
    <Dialog.Root bind:open={selectProjectDialogOpen}>
        <Dialog.Content></Dialog.Content>
    </Dialog.Root>
    <section class="w-full h-full flex flex-col">
        <div class="p-4 border-b border-accent">
            <span class="text-xl">Ingest</span>
        </div>

        <div class="p-4 overflow-x-clip overflow-y-scroll grow max-w-56">
            <div class="min-h-fit transition-all grid grid-cols-1" >
                {#each ingestData as ingestItem (ingestItem.id)}
                    <div class="truncate transition-all duration-500" >
                        {ingestItem.filename}
                    </div>
                {/each}
            </div>
        </div>

        <div
            class="flex items-center align-middle min-h-[57px] p-2 border-t border-accent"
        >
            {#if !ingesting}
                <Button
                    class="grow"
                    disabled={ingesting || !ingestData.length || indexing}
                    on:click={prepareIngest}
                >
                    {#if indexing}
                        <span class="flex items-center gap-x-2"
                            ><LoaderCircle class="h-4 w-4 animate-spin" /> Indexing</span
                        >
                    {:else if $active_project}
                        <span>
                            Ingest into {$active_project.title}
                        </span>
                    {:else}
                        <span> Ingest </span>
                    {/if}
                </Button>
            {:else}
                <div
                    class="grow relative text-sm bg-muted-foreground px-4 py-2 shadow rounded-md text-primary-foreground font-medium text-center overflow-clip"
                >
                    <div
                        class="absolute left-0 top-0 h-full bg-primary transition-all duration-500"
                        style="width: {(1 - filesLeft / fileCount) * 100}%;"
                    ></div>
                    <span class="relative"> Ingesting </span>
                </div>
            {/if}
        </div>
    </section>
</div>
