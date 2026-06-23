export const TAB_ORDER = ["umum", "lokasi", "properti", "catatan"];

export const TAB_META = {
    umum: { value: "umum", label: "Umum", icon: "pi-info-circle" },
    lokasi: { value: "lokasi", label: "Lokasi", icon: "pi-map-marker" },
    properti: { value: "properti", label: "Properti", icon: "pi-building" },
    catatan: { value: "catatan", label: "Catatan", icon: "pi-file-edit" },
};

export const REQUIRED_FIELDS_BY_TAB = {
    umum: [
        { key: "jenis_listing_id", label: "Jenis listing" },
        { key: "jenis_objek_id", label: "Jenis objek" },
        { key: "nama_pemberi_informasi", label: "Nama pemberi informasi" },
        { key: "tanggal_data", label: "Tanggal data" },
    ],
    lokasi: [
        { key: "alamat_data", label: "Alamat lengkap" },
        { key: "province_id", label: "Provinsi" },
        { key: "regency_id", label: "Kabupaten/Kota" },
        { key: "district_id", label: "Kecamatan" },
        { key: "village_id", label: "Desa/Kelurahan" },
        { key: "latitude", label: "Latitude" },
        { key: "longitude", label: "Longitude" },
    ],
    properti: [
        { key: "image", label: "Foto properti", skipIf: ({ mode }) => mode !== "create" },
        { key: "luas_tanah", label: "Luas tanah" },
        { key: "luas_bangunan", label: "Luas bangunan", skipIf: ({ isTanah }) => isTanah },
        { key: "lebar_depan", label: "Lebar depan" },
        { key: "lebar_jalan", label: "Lebar jalan" },
        { key: "tahun_bangun", label: "Tahun bangun", skipIf: ({ isTanah }) => isTanah },
        { key: "bentuk_tanah_id", label: "Bentuk tanah" },
        { key: "posisi_tanah_id", label: "Posisi tanah" },
        { key: "kondisi_tanah_id", label: "Kondisi tanah" },
        { key: "topografi_id", label: "Topografi" },
        { key: "dokumen_tanah_id", label: "Dokumen tanah" },
        { key: "peruntukan_id", label: "Peruntukan" },
        { key: "harga", label: "Harga" },
        { key: "jangka_waktu_sewa", label: "Jangka waktu sewa", skipIf: ({ isSewa }) => !isSewa },
        { key: "satuan_waktu_sewa", label: "Satuan waktu sewa", skipIf: ({ isSewa }) => !isSewa },
    ],
    catatan: [],
};

export const isBlankValue = (value) => value === null || value === undefined || (typeof value === "string" && value.trim() === "");

export function isSewaListing(form, options = {}) {
    const listingId = form?.jenis_listing_id;
    if (!listingId || !options?.jenisListings) return false;

    const listing = options.jenisListings.find((item) => Number(item.value) === Number(listingId));

    return listing?.label?.toLowerCase() === "sewa";
}

export function getTabContext(form, options = {}, overrides = {}) {
    return {
        mode: "create",
        isTanah: false,
        isSewa: isSewaListing(form, options),
        ...overrides,
    };
}

export function getFieldsForTab(tab, context = {}) {
    return (REQUIRED_FIELDS_BY_TAB[tab] ?? []).filter((field) => !field.skipIf?.(context));
}

export function getMissingFields(form, context = {}) {
    const result = {};

    for (const tab of TAB_ORDER) {
        result[tab] = getFieldsForTab(tab, context).filter((field) => isBlankValue(form[field.key]));
    }

    return result;
}

export function getErrorCountByTab(formErrors = {}, context = {}) {
    const result = {};

    for (const tab of TAB_ORDER) {
        result[tab] = getFieldsForTab(tab, context).filter((field) => formErrors[field.key]).length;
    }

    return result;
}
