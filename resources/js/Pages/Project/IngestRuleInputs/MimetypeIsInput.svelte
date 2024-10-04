<script>
    import Combobox from "$lib/components/ui/combobox/combobox.svelte";
    export let value;

    let comboboxValues = [
        {value: 'video/*', label: 'video/*'},
        {value: 'video/quicktime', label: 'video/quicktime'},
        {value: 'video/mp4', label: 'video/mp4'},

        {value: 'image/*', label: 'image/*'},
        {value: 'image/png', label: 'image/png'},
        {value: 'image/webp', label: 'image/webp'},
        {value: 'image/jpeg', label: 'image/jpeg'},

        {value: 'application/*', label: 'application/*'},
        {value: 'application/pdf', label: 'application/pdf'},

        {value: 'text/*', label: 'text/*'},
        {value: 'text/plain', label: 'text/plain'},
    ];
</script>


<Combobox bind:value={value} comboValues={comboboxValues} />
