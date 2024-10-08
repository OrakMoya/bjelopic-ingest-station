<script>
    import * as Dialog from "$lib/components/ui/dialog";
    import * as AlertDialog from "$lib/components/ui/alert-dialog";
    import axios from "axios";
    import { InfoIcon, LoaderIcon, ViewIcon } from "lucide-svelte";
    import { toast } from "svelte-sonner";
    import { active_project } from "$lib/store";
    import { onMount } from "svelte";

    /**
     * @type {{ id: number; filename: string; mimetype: string; }}
     */
    export let file;
    let open = false;

    /**
     * @type {{ exif: { raw: { [x: string]: any; }; }; }}
     */
    let details;
    /**
     * @type {Promise<void>|null}
     */
    let detailsPromise;
    /**
     * @type {Promise<void>|null}
     */
    let dryRunPromise;

    function getDetails() {
        return (detailsPromise = axios
            .get("/ingest/" + file.id)
            .then((r) => {
                details = r.data;
            })
            .catch((e) => {
                toast.error(e.response.statusText);
                detailsPromise = null;
            }));
    }

    /**
     * @param {boolean} state
     */
    export function setErrorState(state) {
        errorState = state;
    }

    async function showDetails() {
        if (!details) {
            await getDetails();
        }
        open = true;
    }

    export let targetDirectory = "";
    let errorState = false;

    export function getFileId() {
        return file.id;
    }

    function dryRunIngest() {
        if ($active_project) {
            dryRunPromise = axios
                .get("/ingest/dryrun/" + $active_project.id + "/" + file.id)
                .then((r) => {
                    targetDirectory = r.data.destination;
                    errorState = false;
                })
                .catch(() => {
                    errorState = true;
                })
                .finally(() => {
                    dryRunPromise = null;
                });
        }
    }

    /**
     * @param {string} directory
     */
    export function setTargetDirectory(directory) {
        targetDirectory = directory;
        targetDirectorySetExternally = true;
    }
    let targetDirectorySetExternally = false;

    onMount(() => {
        window.Echo.channel("ingest").listen("IngestRulesUpdatedEvent", () => {
            if (targetDirectory && !targetDirectorySetExternally) {
                dryRunIngest();
            }
        });
    });
</script>

<Dialog.Root bind:open>
    <Dialog.Content class="w-fit md:w-fit">
        <Dialog.Header>
            <Dialog.Title>{details.file.filename}</Dialog.Title>
            <Dialog.Description>{details.file.mimetype}</Dialog.Description>
        </Dialog.Header>
        <div class="overflow-scroll max-h-[75vh] w-fit">
            {#if details}
                <div class="grid grid-cols-2">
                    {#each Object.keys(details.exif.raw) as exifkey}
                        <div class="truncate">
                            {exifkey}
                        </div>
                        <div class="truncate">
                            {details.exif.raw[exifkey]}
                        </div>
                    {/each}
                </div>
            {:else}
                Loading...
            {/if}
        </div>
    </Dialog.Content>
</Dialog.Root>

<div class="flex flex-col items-start w-full">
    <div
        class="flex gap-2 items-center {errorState
            ? 'bg-destructive'
            : 'bg-accent'} text-white px-2 py-1 rounded-md {targetDirectory &&
        !errorState
            ? 'rounded-bl-none'
            : ''} w-full drop-shadow-md"
    >
        <button
            class="{!details && !detailsPromise
                ? 'opacity-30'
                : ''} hover:opacity-100 transition-opacity"
            on:click={() => showDetails()}
        >
            {#if detailsPromise && !details}
                <LoaderIcon class="min-w-4 min-h-4 w-4 h-4 animate-spin" />
            {:else}
                <InfoIcon class="min-w-4 min-h-4 w-4 h-4" />
            {/if}
        </button>

        {#if $active_project}
            <button
                on:click={() => dryRunIngest()}
                class="opacity-30 hover:opacity-100 transition-opacity"
            >
                {#if dryRunPromise}
                    <LoaderIcon class="min-w-4 min-h-4 w-4 h-4 animate-spin" />
                {:else}
                    <ViewIcon class="min-w-4 min-h-4 w-4 h-4" />
                {/if}
            </button>
        {/if}

        <span class="truncate transition-all duration-500">
            {file.filename}
        </span>
    </div>
    {#if targetDirectory && !errorState}
        <div class="w-5/6 bg-accent opacity-50 rounded-b-md px-2 py-1 text-xs">
            {targetDirectory}
        </div>
    {/if}
</div>
