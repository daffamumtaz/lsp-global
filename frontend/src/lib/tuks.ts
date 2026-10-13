import type { DirectoryEntry } from "./directory";

export type Tuk = {
  id: string; name: string; url: string; logo_url: string; code: string;
  city: string; province: string; address: string; maps_url: string;
  latitude: number | null; longitude: number | null;
};

export function tukDirectoryEntry(tuk: Tuk): DirectoryEntry {
  return { id: tuk.id, name: tuk.name, code: tuk.code, city: tuk.city, province: tuk.province, address: tuk.address, maps_url: tuk.maps_url, url: tuk.url, logo_url: tuk.logo_url };
}

export function hasCoordinates(tuk: Tuk): tuk is Tuk & { latitude: number; longitude: number } {
  return typeof tuk.latitude === "number" && Number.isFinite(tuk.latitude) && Math.abs(tuk.latitude) <= 90 &&
    typeof tuk.longitude === "number" && Number.isFinite(tuk.longitude) && Math.abs(tuk.longitude) <= 180;
}
