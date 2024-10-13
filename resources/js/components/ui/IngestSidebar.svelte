<script>
    import { onMount } from "svelte";
    import axios from "axios";
    import { toast } from "svelte-sonner";
    import { Button } from "$lib/components/ui/button";
    import { active_project, ingest_rules_unsaved } from "$lib/store";
    import * as Dialog from "$lib/components/ui/dialog";
    import {
        LoaderCircle,
        LoaderIcon,
        TriangleAlertIcon,
        ViewIcon,
    } from "lucide-svelte";
    import FileDetailsDialog from "./FileDetailsDialog.svelte";
    import Combobox from "./combobox/combobox.svelte";
    import AddNewVolumeDialog from "$lib/Pages/Settings/Volumes/AddNewVolumeDialog.svelte";
    import { Checkbox } from "$lib/components/ui/checkbox";
    import { Label } from "$lib/components/ui/label";

    /**
     * @type {any[]}
     */
    let ingestData = [];
    let focused = false;
    let ingesting = false;
    let fileCount = 0;
    let filesLeft = 0;
    let ignoredIngestFileCount = 0;
    let selectProjectDialogOpen = false;
    let indexing = false;
    $: selected_project = $active_project;

    /**
     * @type {string}
     */
    let comboboxSelectedProjectIdString;

    /**
     * @type {FileDetailsDialog[]}
     */
    let fileComponents = [];

    /**
     * @type {{ id: string; title: string; }[]}
     */
    let availableProjects;
    /**
     * @type {any[]}
     */
    let comboValues = [];

    let pathPreviewsLoaded = false;

    onMount(() => {
        refreshFiles();
        refreshProjects();

        window.Echo.channel("ingest").listen("IngestIndexedEvent", () => {
            refreshFiles();
        });
        window.Echo.channel("ingest").listen("IngestFileProgressEvent", (e) => {
            updateProgressForFile(e.id, e.status, e.progress);
        });
        window.Echo.channel("volumes").listen("VolumesChangedEvent", () => {
            if (!ingesting) refreshFiles();
        });

        window.Echo.channel("ingest").listen("IndexEvent", (e) => {
            indexing = e.indexing;
        });

        window.Echo.channel("ingest").listen("IngestStartedEvent", (e) => {
            fileCount = e.fileCount;
            ingesting = true;
            button_disabled = false;
            toast.info(e.message);
        });

        window.Echo.channel("ingest").listen("FileIngestedEvent", (e) => {
            ingesting = true;
            fileCount = e.totalFileCount;
            markIngestedFile(e.file.id, e.alreadyExists, e.error);
        });

        window.Echo.channel("ingest").listen(
            "IngestCompleteEvent",
            (/** @type {{ message: string; }} */ e) => {
                toast.success(e.message);
                refreshFiles();
                ingesting = false;
                button_disabled = false;
            },
        );

        window.Echo.channel("ingest").listen(
            "IngestErrorEvent",
            (/** @type {{ message: string;}} */ e) => {
                toast.error(e.message);
                refreshFiles();
                ingesting = false;
            },
        );

        window.Echo.channel("ingest").listen("IngestRulesUpdatedEvent", () => {
            if (pathPreviewsLoaded) {
                getAllTargetPaths();
            }
        });

        window.Echo.channel("projects").listen("ProjectsChangedEvent", () => {
            refreshProjects();
        });
    });

    /**
     * @param {number} id
     */
    function markIngestedFile(id, alreadyExists = false, error = false) {
        if (!ingestData.length) return;
        /*
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
        ignoredIngestFileCount++;
        */

        fileComponents.some((component) => {
            if (component.getFileId() == id) {
                if (alreadyExists) {
                    component.markExists();
                } else if (error) {
                    component.setErrorState(true);
                    component.focus();
                } else {
                    component.markIngested();
                }
                return true;
            }
            return false;
        });
        filesLeft--;
    }

    /**
     * @param {number} id
     * @param {string} status
     * @param {number} progress
     */
    function updateProgressForFile(id, status, progress){
        fileComponents.some((component) => {
            if (component.getFileId() == id) {
                component.setStatus(status);
                component.setProgress(progress);
                return true;
            }
            return false;
        });

    }

    function refreshFiles() {
        button_disabled = true;
        return axios
            .get("/ingest")
            .then((r) => {
                ingestData = r.data.ingest_data;
                fileCount = r.data.ingest_file_count;
                filesLeft = ingestData.length;
                ingesting = r.data.ingesting;
                indexing = r.data.indexing;
                ignoredIngestFileCount = r.data.ignored_ingest_file_count;
            })
            .catch((e) => {
                toast.error(e.response.data.message);
            })
            .finally(() => (button_disabled = false));
    }

    function refreshProjects() {
        return axios
            .get("/projects", {
                headers: {
                    "Content-Type": "application/json",
                },
            })
            .then((r) => {
                comboValues = [];
                availableProjects = r.data.projects;
                availableProjects.forEach(
                    (/** @type {{ id: string; title: string; }} */ project) => {
                        comboValues.push({
                            value: project.id.toString(),
                            label: project.title,
                        });
                    },
                );
                comboValues = comboValues;
            });
    }

    function prepareIngest() {
        if (!pathPreviewsLoaded || errorState) {
            getAllTargetPaths();
            return;
        }
        let id = selected_project?.id;
        if (!id) {
            selectProjectDialogOpen = true;
            return;
        }
        startIngest(id);
    }

    let ingestSettings = {
        check_equality: false,
    };

    let button_disabled = false;

    /**
     * @param {number} id
     */
    function startIngest(id) {
        refreshFiles().then(() => {
            button_disabled = true;
            axios
                .post("/ingest", {
                    id: id,
                    ingest_settings: ingestSettings,
                })
                .catch((e) => {
                    toast.error(e.response.data.message);
                });
        });
    }

    function clearHidden() {
        axios.delete("/ingest").catch((e) => {
            toast.error(e.response.data.message);
        });
    }

    /**
     * @type {Promise<void> | null}
     */
    let pathPreviewsPromise = null;
    let errorState = false;
    let previewsLoadedForProjectId = -1;

    if (ingesting) getAllTargetPaths();

    function getAllTargetPaths() {
        if (!selected_project) return;
        pathPreviewsPromise = axios
            .get("/ingest/dryrun/" + selected_project.id)
            .then((r) => {
                errorState = false;
                // @ts-ignore
                previewsLoadedForProjectId = selected_project.id;
                fileComponents.forEach((component) => {
                    if (component) {
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
                    }
                });
            })
            .catch(() => {
                errorState = true;
                fileComponents.forEach((component) => {
                    if (component) {
                        component.setErrorState(true);
                    }
                });
            })
            .finally(() => {
                pathPreviewsLoaded = true;
                pathPreviewsPromise = null;
            });
    }

    $: handleActiveProjectChange($active_project);
    $: handleComboboxSelectedProject(comboboxSelectedProjectIdString);

    /**
     * @param {{ id: number; title: string; } | null} project
     */
    function handleActiveProjectChange(project) {
        if (!project) {
            fileComponents.forEach((component) => {
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
            comboboxSelectedProjectIdString = project.id.toString();
        }
    }

    function handleComboboxSelectedProject(id) {
        if (!availableProjects) return;
        availableProjects.some((project) => {
            if (project.id == id) {
                selected_project = project;
                return true;
            }
            return false;
        });
        handleActiveProjectChange(selected_project);
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
            <div class="flex items-center gap-2">
                {#if $ingest_rules_unsaved}
                    <TriangleAlertIcon class="text-yellow-500" />
                {/if}
                <span class="text-xl">Ingest</span>
            </div>
            {#if selected_project}
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
            {/if}
        </div>

        <div class="p-2 overflow-x-clip overflow-y-scroll grow max-w-56">
            <div class="min-h-fit transition-all grid grid-cols-1 gap-2">
                {#if !$active_project && comboValues.length && ingestData.length}
                    <Combobox
                        {comboValues}
                        class="w-full"
                        bind:value={comboboxSelectedProjectIdString}
                    />
                {/if}

                {#each ingestData as ingestItem, i (ingestItem.id)}
                    <FileDetailsDialog
                        bind:project={selected_project}
                        bind:this={fileComponents[i]}
                        file={ingestItem}
                    />
                {/each}
                {#if ignoredIngestFileCount}
                    <span class="text-xs opacity-50 italic"
                        >Hiding {ignoredIngestFileCount} ingested files.
                        <button
                            class="italic underline"
                            disabled={indexing || ingesting}
                            on:click={clearHidden}>Unhide</button
                        ></span
                    >
                {/if}
            </div>
        </div>

        <div
            class="grid grid-cols-1 gap-y-2 items-center justify-center align-middle p-2 border-t border-accent"
        >
            <div class="flex w-full gap-x-2 items-center">
                <Checkbox
                    id="check-equality"
                    bind:checked={ingestSettings.check_equality}
                />
                <Label class="text-sm" for="check-equality"
                    >Check equality (slower)</Label
                >
            </div>
            {#if !ingesting}
                <Button
                    disabled={ingesting ||
                        !ingestData.length ||
                        indexing ||
                        errorState ||
                        button_disabled}
                    on:click={prepareIngest}
                >
                    {#if indexing}
                        <span class="flex items-center gap-x-2"
                            ><LoaderCircle class="h-4 w-4 animate-spin" /> Indexing</span
                        >
                    {:else if !pathPreviewsLoaded && selected_project}
                        <span>Check</span>
                    {:else if pathPreviewsLoaded && errorState}
                        <span>Ingest rule errors</span>
                    {:else if pathPreviewsLoaded && selected_project && $ingest_rules_unsaved}
                        <span> Ingest with old rules </span>
                    {:else if pathPreviewsLoaded && selected_project}
                        <span>
                            Ingest into {selected_project.title}
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
                        class="absolute left-0 top-0 h-full bg-primary"
                        style="width: {(1 - filesLeft / fileCount) * 100}%;"
                    ></div>
                    <span class="relative"> Ingesting </span>
                </div>
            {/if}
        </div>
    </section>
</div>
