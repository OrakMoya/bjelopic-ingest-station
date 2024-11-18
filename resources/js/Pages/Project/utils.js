

/**
 * @returns {import("$lib/types").IngestRuleObject}
 */
export function newRuleGenerator(){
    return {
        operation: "save",
        criteria: "Other",
        opts: [],
        next: []
    };
}
