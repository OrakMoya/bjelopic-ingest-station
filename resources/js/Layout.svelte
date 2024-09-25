<script>
    import { ModeWatcher } from "mode-watcher";
    import { Link } from "@inertiajs/svelte";
    import { Toaster } from "$lib/components/ui/sonner";
    import IngestSidebar from "$lib/components/ui/IngestSidebar.svelte";
    import { Settings } from "lucide-svelte";
    import { ChevronUp } from "svelte-radix";
    import { toast } from "svelte-sonner";

    window.Echo.channel("messages").listen("MessageSentEvent", (e) => {
        toast.success(e.message);
    });
</script>

<ModeWatcher />
<Toaster richColors position="bottom-center" />

<div class="dark w-screen h-screen">
    <div class="flex w-full h-full">
        <div
            class="border-r border-accent min-w-40 flex flex-col justify-between"
        >
            <div class="flex flex-col w-full" id="global-sidebar"></div>

            <div class="flex justify-end p-4 border-t border-accent">
                <Link href="/settings"><Settings /></Link>
            </div>
        </div>

        <div class="flex flex-col justify-between grow">
            <div class="flex w-full overflow-y-scroll">
                <div
                    class="h-full grow max-w-screen-xl mx-auto px-4 py-4 box-border"
                >
                    <slot />
                </div>
            </div>
            <div
                class="p-4 border-t border-accent flex flex-row gap-x-2 items-center"
            >
                <div class="relative top-[1px]">
                    <ChevronUp />
                </div>
                <div>Jobs</div>
            </div>
        </div>

        <IngestSidebar />
    </div>
</div>
