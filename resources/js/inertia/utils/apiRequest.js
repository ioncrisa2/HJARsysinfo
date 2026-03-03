const CSRF_SELECTOR = 'meta[name="csrf-token"]';

const getCsrfToken = () => {
    if (typeof document === "undefined") return null;
    return document.head?.querySelector(CSRF_SELECTOR)?.getAttribute("content") ?? null;
};

const firstValidationMessage = (errors) => {
    if (!errors || typeof errors !== "object") return null;

    for (const value of Object.values(errors)) {
        if (Array.isArray(value) && value.length > 0) return String(value[0]);
        if (typeof value === "string" && value.trim() !== "") return value;
    }

    return null;
};

const parseBody = async (response) => {
    const contentType = response.headers.get("content-type") ?? "";

    if (contentType.includes("application/json")) {
        try {
            return {
                isJson: true,
                payload: await response.json(),
            };
        } catch {
            return { isJson: true, payload: null };
        }
    }

    return {
        isJson: false,
        payload: await response.text(),
    };
};

const buildErrorMessage = (response, parsed) => {
    if (response.status === 419) {
        return "Sesi berakhir. Muat ulang halaman lalu coba lagi.";
    }

    if (parsed.isJson && parsed.payload && typeof parsed.payload === "object") {
        if (typeof parsed.payload.message === "string" && parsed.payload.message.trim() !== "") {
            return parsed.payload.message;
        }

        const validationMessage = firstValidationMessage(parsed.payload.errors);
        if (validationMessage) return validationMessage;
    }

    return `Permintaan gagal (HTTP ${response.status}).`;
};

export class ApiRequestError extends Error {
    constructor(message, { status = 0, payload = null } = {}) {
        super(message);
        this.name = "ApiRequestError";
        this.status = status;
        this.payload = payload;
    }
}

export const apiRequest = async (url, options = {}) => {
    const { body, headers, ...rest } = options;
    const finalHeaders = new Headers(headers ?? {});

    if (!finalHeaders.has("Accept")) {
        finalHeaders.set("Accept", "application/json");
    }

    if (!finalHeaders.has("X-Requested-With")) {
        finalHeaders.set("X-Requested-With", "XMLHttpRequest");
    }

    const csrfToken = getCsrfToken();
    if (csrfToken && !finalHeaders.has("X-CSRF-TOKEN")) {
        finalHeaders.set("X-CSRF-TOKEN", csrfToken);
    }

    let finalBody = body;
    const hasBody = body !== undefined && body !== null;
    const isFormData = typeof FormData !== "undefined" && body instanceof FormData;

    if (hasBody && !isFormData && typeof body === "object" && !finalHeaders.has("Content-Type")) {
        finalHeaders.set("Content-Type", "application/json");
        finalBody = JSON.stringify(body);
    }

    // ─── Wrap fetch() itself to normalize network-level errors ───────────────
    let response;
    try {
        response = await fetch(url, {
            credentials: "same-origin",
            ...rest,
            headers: finalHeaders,
            body: hasBody ? finalBody : undefined,
        });
    } catch (networkError) {
        // TypeError from fetch = offline, DNS failure, timeout, etc.
        throw new ApiRequestError("Tidak ada koneksi. Periksa jaringan lalu coba lagi.", {
            status: 0,
            payload: null,
        });
    }
    // ─────────────────────────────────────────────────────────────────────────

    const parsed = await parseBody(response);

    if (!response.ok) {
        throw new ApiRequestError(buildErrorMessage(response, parsed), {
            status: response.status,
            payload: parsed.payload,
        });
    }

    return parsed.payload;
};
