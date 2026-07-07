const CSRF_SELECTOR = 'meta[name="csrf-token"]';
const XSRF_COOKIE = "XSRF-TOKEN";

const readCookie = (name) => {
    if (typeof document === "undefined") return null;

    const value = document.cookie
        .split("; ")
        .find((row) => row.startsWith(`${name}=`))
        ?.split("=")
        .slice(1)
        .join("=");

    if (!value) return null;

    try {
        return decodeURIComponent(value);
    } catch {
        return value;
    }
};

export const getMetaCsrfToken = () => {
    if (typeof document === "undefined") return null;

    return document.head?.querySelector(CSRF_SELECTOR)?.getAttribute("content") ?? null;
};

export const appendCsrfHeader = (headers) => {
    if (headers.has("X-CSRF-TOKEN") || headers.has("X-XSRF-TOKEN")) {
        return headers;
    }

    const xsrfToken = readCookie(XSRF_COOKIE);

    if (xsrfToken) {
        headers.set("X-XSRF-TOKEN", xsrfToken);
        return headers;
    }

    const csrfToken = getMetaCsrfToken();

    if (csrfToken) {
        headers.set("X-CSRF-TOKEN", csrfToken);
    }

    return headers;
};
