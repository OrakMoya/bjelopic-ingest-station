<script>
    import SettingsSectionTitle from "./SettingsSectionTitle.svelte";
    import VolumeItem from "./Volumes/VolumeItem.svelte";
    import AddNewVolumeDialog from "./Volumes/AddNewVolumeDialog.svelte";
    import axios from "axios";
    import { toast } from "svelte-sonner";
    import { onMount } from "svelte";

    const csrf_token = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");

    const headers = new Headers();

    headers.append("Content-Type", "application/json");
    headers.append("Accept", "application/json");
    if (csrf_token) {
        headers.append("X-CSRF-TOKEN", csrf_token);
    }

    
    /**
     * @typedef {Object} Props
     * @property {any[]} volumes
     */

    /** @type {Props} */
    let { volumes = $bindable() } = $props();
    refreshVolumes();

    function refreshVolumes() {
        return axios
            .get("/settings/volumes?free_space=1&total_space=1")
            .then((r) => {
                volumes = r.data.volumes;
            })
            .catch((e) => {
                toast.error(e.data.message);
            });
    }

    onMount(() => {
        window.Echo.channel("volumes").listen("VolumesChangedEvent", () => {
            refreshVolumes();
        });
    });
</script>

<SettingsSectionTitle class="mt-0 border-0">Volumes</SettingsSectionTitle>

{#if !volumes}
    LooDung
{:else}
    <div class="flex flex-col gap-y-4">
        <div>
            <AddNewVolumeDialog on:volumeAdded={refreshVolumes} />
        </div>

        <div class="flex flex-col gap-y-4">
            {#each volumes as volume, i}
                <VolumeItem
                    class="{i % 2 ? 'bg-accent' : ''} px-4 py-2 rounded-md"
                    on:volumeDeleted={refreshVolumes}
                    {volume}
                />
            {/each}
        </div>
    </div>
{/if}
