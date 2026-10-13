import { connection } from "next/server";
import { cache } from "react";
import type { Tuk } from "./tuks";
import type { DirectoryEntry } from "./directory";
import type { Scheme } from "./schemes";
import type { GalleryActivity } from "./gallery";

export type PublicContent = {
  tuks: Tuk[];
  unavailable?: boolean;
  assessors: DirectoryEntry[];
  schemes: Scheme[];
  gallery: GalleryActivity[];
};

export const getPublicContent = cache(async (): Promise<PublicContent> => {
  // Konten CMS dibaca saat request, sehingga perubahan admin tidak membutuhkan build ulang.
  await connection();
  try {
    const url = process.env.CONTENT_API_URL ?? "http://127.0.0.1:8000/api/content-directory";
    const response = await fetch(url, {
      cache: "no-store",
      signal: AbortSignal.timeout(5000),
    });
    if (!response.ok) throw new Error(`Content API returned ${response.status}`);
    const { data } = await response.json() as { data: PublicContent };
    if (![data?.tuks, data?.assessors, data?.schemes, data?.gallery].every(Array.isArray)) {
      throw new Error("Invalid content payload");
    }
    return data;
  } catch (error) {
    console.error("Unable to load public content:", error);
    return { unavailable: true, tuks: [], assessors: [], schemes: [], gallery: [] };
  }
});
