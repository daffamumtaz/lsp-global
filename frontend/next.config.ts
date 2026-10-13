import type { NextConfig } from "next";

const mediaOrigin = new URL(process.env.LANDING_MEDIA_ORIGIN ?? "http://localhost:8000");

const nextConfig: NextConfig = {
  devIndicators: false,
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
