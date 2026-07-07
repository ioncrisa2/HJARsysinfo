import { onBeforeUnmount, watch } from "vue";

/**
 * Watch a reactive source and run the callback after a debounce delay.
 *
 * @param {import("vue").WatchSource|import("vue").WatchSource[]|Function} source
 * @param {Function} callback
 * @param {{ delay?: number, watch?: import("vue").WatchOptions }} options
 * @returns {{ stop: Function, cancel: Function, flush: Function }}
 */
export function useDebouncedWatch(source, callback, options = {}) {
    const delay = options.delay ?? 300;
    let timeoutId = null;
    let lastArgs = null;

    const cancel = () => {
        if (timeoutId === null) return;

        clearTimeout(timeoutId);
        timeoutId = null;
    };

    const flush = () => {
        if (!lastArgs) return;

        cancel();
        callback(...lastArgs);
        lastArgs = null;
    };

    const stop = watch(
        source,
        (...args) => {
            lastArgs = args;
            cancel();
            timeoutId = setTimeout(() => {
                timeoutId = null;
                callback(...lastArgs);
                lastArgs = null;
            }, delay);
        },
        options.watch ?? {},
    );

    onBeforeUnmount(() => {
        cancel();
        stop();
    });

    return { stop, cancel, flush };
}
