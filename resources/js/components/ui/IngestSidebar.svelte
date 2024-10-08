<script>
    import { onMount } from "svelte";
    import axios from "axios";
    import { toast } from "svelte-sonner";
    import { Button } from "$lib/components/ui/button";
    import { active_project } from "$lib/store";
    import * as Dialog from "$lib/components/ui/dialog";
    import { LoaderCircle, LoaderIcon, ViewIcon } from "lucide-svelte";
    import FileDetailsDialog from "./FileDetailsDialog.svelte";

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
    /**
     * @type {any[]}
     */
    let components = [];

    let pathPreviewsLoaded = false;

    onMount(() => {
        refresh();

        window.Echo.channel("ingest").listen("IngestIndexedEvent", () => {
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

        window.Echo.channel("ingest").listen("IngestRulesUpdatedEvent", () => {
            if (pathPreviewsLoaded) {
                getAllTargetPaths();
            }
        });
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

    /**
     * @type {Promise<void> | null}
     */
    let pathPreviewsPromise = null;
    let errorState = false;
    let previewsLoadedForProjectId = -1;

    function getAllTargetPaths() {
        if (!$active_project) return;
        pathPreviewsPromise = axios
            .get("/ingest/dryrun/" + $active_project.id)
            .then((r) => {
                errorState = false;
                previewsLoadedForProjectId = $active_project.id;
                components.forEach((component) => {
                    r.data.some(
                        (
                            /** @type {{ file_id: number; targetDirectory: string; }} */ pair,
                        ) => {
                            if (component.getFileId() == pair.file_id) {
                                component.setTargetDirectory(
                                    pair.targetDirectory,
                                );
                                component.setErrorState(false);
                                return true;
                            }
                        },
                    );
                });
            })
            .catch(() => {
                errorState = true;
                components.forEach((component) => {
                    component.setErrorState(true);
                });
            })
            .finally(() => {
                pathPreviewsLoaded = true;
                pathPreviewsPromise = null;
            });
    }

    $: handleActiveProjectChange($active_project);

    function handleActiveProjectChange(project) {
        if (!project) {
            components.forEach((component) => {
                component.setErrorState(false);
                component.setTargetDirectory("");
            });
            previewsLoadedForProjectId = -1;
        }
        if (
            project &&
            pathPreviewsLoaded &&
            project.id != previewsLoadedForProjectId
        ) {
            getAllTargetPaths();
        }
    }
</script>

<Dialog.Root bind:open={selectProjectDialogOpen}>
    <Dialog.Content></Dialog.Content>
</Dialog.Root>

<div
    class="min-w-56 w-56 border-l border-accent"
    on:focusin={() => (focused = true)}
    on:focusout={() => (focused = false)}
>
    <section class="w-full h-full flex flex-col">
        <div
            class="p-4 border-b border-accent flex items-center justify-between"
        >
            <span class="text-xl">Ingest</span>
            <button
                class="{pathPreviewsPromise
                    ? 'opacity-100'
                    : 'opacity-50'} hover:opacity-100 transition-opacity"
                on:click={getAllTargetPaths}
            >
                {#if pathPreviewsPromise}
                    <LoaderIcon class="animate-spin" />
                {:else}
                    <ViewIcon />
                {/if}
            </button>
        </div>

        <div class="p-2 overflow-x-clip overflow-y-scroll grow max-w-56">
            <div class="min-h-fit transition-all grid grid-cols-1 gap-2">
                {#each ingestData as ingestItem, i (ingestItem.id)}
                    <FileDetailsDialog
                        bind:this={components[i]}
                        file={ingestItem}
                    />
                {/each}
            </div>
        </div>

        <div
            class="flex items-center align-middle min-h-[57px] p-2 border-t border-accent"
        >
            {#if !ingesting}
                <Button
                    class="grow"
                    disabled={ingesting ||
                        !ingestData.length ||
                        indexing ||
                        errorState}
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
