import { connection } from "next/server";
import defaults from "./about-defaults.json";
import { layoutOrganization, type OrganizationNode } from "./organization";

export type AboutContent = {
  profile: { hook: string; description: string; photo_urls: string[] };
  vision: { hook: string; vision: string; mission: string };
  organization: OrganizationNode[];
  certificates: { id: number; title: string; description: string; image_url: string | null; fallback_image: string | null }[];
  updated_at: string | null;
};

const fallback: AboutContent = {
  profile: { hook: defaults.profile.hook, description: defaults.profile.description, photo_urls: [] },
  vision: defaults.vision,
  organization: defaults.organization,
  certificates: defaults.certificates.map(item => ({ ...item, image_url: null })),
  updated_at: null,
};

export async function getAboutContent(): Promise<AboutContent> {
  await connection();
  const landingUrl = process.env.LANDING_API_URL ?? "http://127.0.0.1:8000/api/landing-page";
  const url = process.env.PROFILE_API_URL ?? new URL("profile-page", landingUrl).href;
  try {
    const response = await fetch(url, { cache: "no-store", signal: AbortSignal.timeout(5000) });
    if (!response.ok) throw new Error(`Profile API returned ${response.status}`);
    const { data } = await response.json() as { data?: AboutContent };
    if (!data || typeof data.profile?.hook !== "string" || typeof data.profile.description !== "string" ||
      !Array.isArray(data.profile.photo_urls) || !data.profile.photo_urls.every(src => typeof src === "string" && /^https?:\/\//.test(src)) ||
      !data.vision || ![data.vision.hook, data.vision.vision, data.vision.mission].every(value => typeof value === "string") ||
      !Array.isArray(data.organization) || !Array.isArray(data.certificates) ||
      !data.certificates.every(item => typeof item.title === "string" && typeof item.description === "string" &&
        (item.image_url === null || (typeof item.image_url === "string" && /^https?:\/\//.test(item.image_url))) &&
        (item.fallback_image === null || ["license-certificate.png", "remote-license.png", "bssn-registration.png"].includes(item.fallback_image)))) {
      throw new Error("Profile API returned invalid content");
    }
    layoutOrganization(data.organization);
    return data;
  } catch (error) {
    console.error("Unable to load profile content:", error);
    return fallback;
  }
}
