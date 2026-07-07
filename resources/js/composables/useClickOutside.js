import { onBeforeUnmount, onMounted, unref } from "vue";

/**
 * Run a callback when the user clicks outside one or more target refs/selectors.
 *
 * @param {import("vue").Ref|Element|string|Array<import("vue").Ref|Element|string>} targets
 * @param {Function} callback
 * @param {{ eventName?: string, enabled?: Function|boolean }} options
 * @returns {{ handler: Function }}
 */
export function useClickOutside(targets, callback, options = {}) {
    const eventName = options.eventName ?? "click";
    const targetList = Array.isArray(targets) ? targets : [targets];
    const isEnabled = () => {
        if (typeof options.enabled === "function") return Boolean(options.enabled());
        return options.enabled ?? true;
    };

    const matchesTarget = (event, target) => {
        const resolved = unref(target);
        if (!resolved) return false;

        if (typeof resolved === "string") {
            return Boolean(event.target?.closest?.(resolved));
        }

        return resolved === event.target || Boolean(resolved.contains?.(event.target));
    };

    const handler = (event) => {
        if (!isEnabled()) return;
        if (targetList.some((target) => matchesTarget(event, target))) return;

        callback(event);
    };

    onMounted(() => {
        if (typeof document !== "undefined") {
            document.addEventListener(eventName, handler);
        }
    });

    onBeforeUnmount(() => {
        if (typeof document !== "undefined") {
            document.removeEventListener(eventName, handler);
        }
    });

    return { handler };
}
