import { connection } from "next/server";
import { cache } from "react";
import { defaultInformationContent, normalizeInformationContent, type InformationContent } from "./information-copy";

export const getInformationContent = cache(async (): Promise<InformationContent> => {
  await connection();
  const landingUrl = process.env.LANDING_API_URL ?? "http://127.0.0.1:8000/api/landing-page";
  const url = process.env.INFORMATION_API_URL ?? new URL("information-page", landingUrl).href;
  try {
    const response = await fetch(url, { cache: "no-store", signal: AbortSignal.timeout(5000) });
    if (!response.ok) throw new Error(`Information API returned ${response.status}`);
    const { data } = await response.json() as { data?: unknown };
    return normalizeInformationContent(data);
  } catch (error) {
    console.error("Unable to load information content:", error);
    return defaultInformationContent;
  }
});
