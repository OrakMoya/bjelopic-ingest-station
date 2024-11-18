<script lang="ts">
    import Combobox from "$lib/components/ui/combobox/combobox.svelte";
    let { value = $bindable(), onValueChange = ()=>{} } = $props();

    let comboboxValues = [
        {value: 'video/*', label: 'video/*'},
        {value: 'video/quicktime', label: 'video/quicktime'},
        {value: 'video/mp4', label: 'video/mp4'},

        {value: 'image/*', label: 'image/*'},
        {value: 'image/png', label: 'image/png'},
        {value: 'image/webp', label: 'image/webp'},
        {value: 'image/jpeg', label: 'image/jpeg'},

        {value: 'audio/*', label: 'audio/*'},
        {value: 'audio/mpeg', label: 'audio/mpeg'},
        {value: 'audio/wav', label: 'audio/wav'},
        {value: 'audio/flac', label: 'audio/flac'},

        {value: 'application/*', label: 'application/*'},
        {value: 'application/pdf', label: 'application/pdf'},

        {value: 'text/*', label: 'text/*'},
        {value: 'text/plain', label: 'text/plain'},
    ];

    $effect(()=>{
        onValueChange(value);
    })
</script>


<Combobox bind:value={value} comboValues={comboboxValues} />
