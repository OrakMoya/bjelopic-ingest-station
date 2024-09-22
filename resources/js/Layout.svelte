<script>
    import { ModeWatcher } from "mode-watcher";
    import * as Resizable from "$lib/components/ui/resizable";
    import { Link } from "@inertiajs/svelte";
    import { onMount } from "svelte";
    import "$lib/echo";
    import { Toaster } from "./components/ui/sonner";

    onMount(() => {
        window.Echo.channel("messages").listen("MessageSentEvent", (e) => {
            console.log(e);
        });
    });
</script>

<ModeWatcher />
<Toaster richColors />

<div class="dark w-screen h-screen overflow-hidden">
    <div class="flex w-full h-full">
        <div class="border-r border-r-slate-800 p-4 min-w-40 w-fit flex  flex-col">
            <Link href="/projects">Projects</Link>
            <Link href="/settings">Settings</Link>
        </div>
        <div class="w-full h-full max-w-screen-xl mx-auto px-4 py-4 box-content">
            <slot />
        </div>
    </div>
</div>
