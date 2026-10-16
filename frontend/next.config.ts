import type { NextConfig } from "next";

const mediaOrigin = new URL(process.env.LANDING_MEDIA_ORIGIN ?? "http://localhost:8000");
const adminOrigin = new URL(process.env.ADMIN_PREVIEW_ORIGIN ?? "http://localhost:8000");
if (!["http:", "https:"].includes(adminOrigin.protocol) || adminOrigin.username || adminOrigin.password) {
  throw new Error("ADMIN_PREVIEW_ORIGIN must be an HTTP(S) origin without credentials");
}

const nextConfig: NextConfig = {
  devIndicators: false,
  async headers() {
    return [{
      source: "/:path*",
      headers: [
        { key: "X-Content-Type-Options", value: "nosniff" },
        { key: "Referrer-Policy", value: "strict-origin-when-cross-origin" },
        { key: "Permissions-Policy", value: "camera=(), microphone=(), geolocation=()" },
        { key: "Content-Security-Policy", value: `object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self' ${adminOrigin.origin}` },
      ],
    }];
  },
  images: {
    remotePatterns: [{
      protocol: mediaOrigin.protocol === "https:" ? "https" : "http",
      hostname: mediaOrigin.hostname,
      port: mediaOrigin.port,
      pathname: "/storage/**",
    }],
  },
};

export default nextConfig;
