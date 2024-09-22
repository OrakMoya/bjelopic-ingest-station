<script>
    import { cn } from "$lib/utils";
</script>

<div
    class=" {cn(
        'mt-8  w-full border-t border-slate-800 mb-4',
        $$restProps.class,
    )}"
>
    <span class="text-xl font-bold">
        <slot />
    </span>
</div>
