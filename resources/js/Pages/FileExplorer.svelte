<script>
    import { run } from 'svelte/legacy';


    import * as Breadcrumb from "$lib/components/ui/breadcrumb";
    import { Link, page } from "@inertiajs/svelte";
    let { files, directories, parent } = $props();

    let entries = $state([]);
    let breadcrumb_trail = $state([]);

    /**
     * @param {string} parent
     * @param {string[]} directories
     * @param {string[]} files
     */
    function processEntries(parent, directories, files) {
        let entries = [];
        if (parent) {
            entries.push({ label: "..", href: "/files/" + parent });
        }
        directories.forEach((/** @type {string} */ directory) => {
            entries.push({
                label: directory,
                href: window.location + "/" + directory,
            });
        });
        files.forEach((/** @type {string} */ file) => {
            entries.push({ label: file, href: "#" });
        });
        return entries;
    }
    function processBreadcrumbTrail(url) {
        let exploded = url.split("/");
        exploded.splice(0, 1);
        let trail = [];
        exploded.reduce((previous, current) => {
            trail.push({
                label: current,
                href: previous + "/" + current,
            });
            return previous + "/" + current;
        }, "");
        trail.splice(0, 1);
        return trail;
    }

    run(() => {
        entries = processEntries(parent, directories, files);
    });
    run(() => {
        breadcrumb_trail = processBreadcrumbTrail($page.url);
    });

</script>

<main class="m-4 max-w-screen-md mx-auto border rounded-lg overflow-clip">
    <div class="py-2 px-4 border-b border-slate-800">
        <Breadcrumb.Root>
            <Breadcrumb.List>
                {#each breadcrumb_trail as breadcrumb, i}
                    <Breadcrumb.Item>
                        <Link
                            class="hover:text-foreground transition-colors"
                            href={breadcrumb.href}
                        >
                            {breadcrumb.label}
                        </Link>
                    </Breadcrumb.Item>
                    {#if i !== breadcrumb_trail.length - 1}
                        <Breadcrumb.Separator />
                    {/if}
                {/each}
            </Breadcrumb.List>
        </Breadcrumb.Root>
    </div>

    <div class="flex flex-col">
        {#each entries as entry, i}
            <Link
                class="{i !== entries.length - 1
                    ? 'border-b'
                    : ''} px-4 py-2 border-slate-800 hover:bg-slate-800 transition"
                href={entry.href}
            >
                {entry.label}
            </Link>
        {/each}
    </div>
</main>
