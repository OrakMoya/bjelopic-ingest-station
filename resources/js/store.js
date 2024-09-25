import { writable } from "svelte/store";

/**
 * @type {import('svelte/store').Writable<null|{id: Number; title: String}>}
 */
export const active_project = writable(null);
