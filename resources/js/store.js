import { writable } from "svelte/store";

/**
 * @type {import('svelte/store').Writable<null|{id: Number; title: String}>}
 */
export const active_project = writable(null);

export const ingest_rules_unsaved = writable(false);
