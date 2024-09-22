<script>
    import { Button } from "$lib/components/ui/button";
    import { onMount } from "svelte";
    import SettingsSectionTitle from "./SettingsSectionTitle.svelte";
    import VolumeItem from "./Volumes/VolumeItem.svelte";
    import AddNewVolumeDialog from "./Volumes/AddNewVolumeDialog.svelte";

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
     * @type {any[]}
     */
    export let volumes = [];
    let refresh_disabled = false;

    async function refreshVolumes() {
        refresh_disabled = true;
        let response = await fetch("/settings/volumes", {
            method: "GET",
            headers: headers,
        });
        let json = await response.json();
        volumes = json.volumes;
        refresh_disabled = false;
    }

    onMount(() => {
        if (volumes.length) return;
        refreshVolumes();
    });
</script>

<SettingsSectionTitle>Volumes</SettingsSectionTitle>

<div>
    <Button disabled={refresh_disabled} on:click={refreshVolumes}
        >Refresh</Button
    >
    <AddNewVolumeDialog on:volumeAdded={refreshVolumes} />

    <div class="flex flex-col gap-y-4">
        {#each volumes as volume}
            <VolumeItem on:volumeDeleted={refreshVolumes} {volume} />
        {/each}
    </div>
</div>
