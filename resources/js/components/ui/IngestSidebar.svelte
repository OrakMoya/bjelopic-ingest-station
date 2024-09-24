<script>
    import { onMount } from "svelte";
    import axios from "axios";
    import { toast } from "svelte-sonner";
    import { Button } from "$lib/components/ui/button";

    /**
     * @type {any[]}
     */
    let ingestData = [];

    let focused = false;

    onMount(() => {
        refresh();
        window.Echo.channel("ingest").listen("IngestEvent", (e) => {
            if (e.data.status === "added") {
                refresh();
            }

            if (e.data.status === "ingested") {
                removeIngestedFile(e.data.id);
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

        if(index === -1){
            refresh()
            return;
        }

        ingestData.splice(index, 1);
        ingestData = ingestData;
    }
    function refresh() {
        axios
            .get("/ingest")
            .then((r) => {
                ingestData = r.data.ingest_data;
            })
            .catch((e) => {
                toast.error(e.response.data.message);
            });
    }
</script>

<div
    class="{focused
        ? 'min-w-56'
        : 'max-w-56 min-w-40'} transition-all border-l border-accent"
    on:focusin={() => (focused = true)}
    on:focusout={() => (focused = false)}
>
    <section class="w-full h-full overflow-x-hidden overflow-y-scroll">
        <div class="p-4 border-b border-accent mb-2">
            <span class="text-xl">Ingest</span>
        </div>

        <div class="p-4">
            {#each ingestData as ingestItem}
                <div>
                    {ingestItem.filename}
                </div>
            {/each}
        </div>
    </section>
</div>
