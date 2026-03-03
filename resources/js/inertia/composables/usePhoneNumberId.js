import { watch } from "vue";

export const formatPhoneNumberId = (value) => {
    const digitsOnly = String(value ?? "").replace(/\D/g, "");

    if (!digitsOnly) return "";

    let normalized = digitsOnly;
    if (normalized.startsWith("0")) {
        normalized = `62${normalized.slice(1)}`;
    } else if (!normalized.startsWith("62")) {
        normalized = `62${normalized}`;
    }

    const localNumber = normalized.slice(2, 17);
    if (!localNumber) return "(+62)";

    const groups = [];
    groups.push(localNumber.slice(0, 3));

    for (let index = 3; index < localNumber.length; index += 4) {
        groups.push(localNumber.slice(index, index + 4));
    }

    return `(+62) ${groups.filter(Boolean).join(" ")}`.trim();
};

export const sanitizePhoneNumberId = (value) => {
    const formatted = formatPhoneNumberId(value);
    return formatted === "(+62)" ? "" : formatted;
};

export const usePhoneNumberField = (source, key = "nomer_telepon_pemberi_informasi", config = {}) => {
    const formatOnInit = config.formatOnInit ?? true;

    if (formatOnInit) {
        source[key] = formatPhoneNumberId(source[key]);
    }

    watch(
        () => source[key],
        (value) => {
            const formatted = formatPhoneNumberId(value);
            if (value !== formatted) {
                source[key] = formatted;
            }
        },
    );

    return {
        formatPhoneNumberId,
        sanitizePhoneNumberId,
    };
};
