import { ref, watch } from "vue";

export const parseDateValue = (value) => {
    if (!value) return null;

    if (typeof value === "string") {
        const match = value.match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (match) {
            const year = Number(match[1]);
            const month = Number(match[2]) - 1;
            const day = Number(match[3]);

            return new Date(year, month, day);
        }
    }

    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
};

export const formatDateValue = (value) => {
    if (!value) return null;

    if (value instanceof Date && !Number.isNaN(value.getTime())) {
        const year = value.getFullYear();
        const month = String(value.getMonth() + 1).padStart(2, "0");
        const day = String(value.getDate()).padStart(2, "0");

        return `${year}-${month}-${day}`;
    }

    if (typeof value === "string") {
        const match = value.match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (match) {
            return `${match[1]}-${match[2]}-${match[3]}`;
        }

        const parsed = parseDateValue(value);
        return parsed ? formatDateValue(parsed) : null;
    }

    const parsed = parseDateValue(value);
    return parsed ? formatDateValue(parsed) : null;
};

export const useDateRangeBridge = (target, config = {}) => {
    const fromKey = config.fromKey ?? "dari_tanggal";
    const toKey = config.toKey ?? "sampai_tanggal";

    const initialFrom = parseDateValue(target[fromKey]);
    const initialTo = parseDateValue(target[toKey]);

    const dateRange = ref(
        initialFrom || initialTo
            ? [initialFrom ?? null, initialTo ?? null]
            : null,
    );

    watch(
        dateRange,
        (value) => {
            if (!Array.isArray(value) || (!value[0] && !value[1])) {
                target[fromKey] = null;
                target[toKey] = null;
                return;
            }

            target[fromKey] = formatDateValue(value[0] ?? null);
            target[toKey] = formatDateValue(value[1] ?? null);
        },
        { deep: true },
    );

    const clearDateRange = () => {
        dateRange.value = null;
    };

    return {
        dateRange,
        clearDateRange,
    };
};
