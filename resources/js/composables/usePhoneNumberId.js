import { watch } from "vue";

const getLocalDigits = (value) => {
    const digitsOnly = String(value ?? "").replace(/\D/g, "");

    if (!digitsOnly) return "";

    if (digitsOnly.startsWith("0")) {
        return digitsOnly.slice(1, 16);
    }

    if (digitsOnly.startsWith("62")) {
        return digitsOnly.slice(2, 17);
    }

    return digitsOnly.slice(0, 15);
};

const groupLocalNumber = (value) => {
    const localNumber = getLocalDigits(value);

    if (!localNumber) return "";

    const groups = [];
    groups.push(localNumber.slice(0, 3));

    for (let index = 3; index < localNumber.length; index += 4) {
        groups.push(localNumber.slice(index, index + 4));
    }

    return groups.filter(Boolean).join(" ").trim();
};

export const formatPhoneNumberId = (value) => {
    const grouped = groupLocalNumber(value);

    return grouped ? `(+62) ${grouped}` : "";
};

export const sanitizePhoneNumberId = (value) => {
    return groupLocalNumber(value);
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
