import type { Metadata } from "next";
import { getLandingContent } from "@/lib/landing";
import { Figtree, Inter, Plus_Jakarta_Sans } from "next/font/google";
import "./globals.css";

const figtree = Figtree({
  variable: "--font-figtree",
  subsets: ["latin"],
  style: ["normal", "italic"],
  display: "swap",
});

const inter = Inter({
  variable: "--font-inter",
  subsets: ["latin"],
});

const jakarta = Plus_Jakarta_Sans({
  variable: "--font-jakarta",
  subsets: ["latin"],
});

export async function generateMetadata(): Promise<Metadata> {
  const { identity } = await getLandingContent();
  return {
    title: identity.brand_name,
    description: identity.footer_description,
    icons: {
      icon: identity.favicon_url ?? identity.icon_url ?? "/favicon.ico",
      ...(identity.icon_url ? { apple: identity.icon_url } : {}),
    },
  };
}

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html
      lang="id"
      data-scroll-behavior="smooth"
      className={`${figtree.variable} ${inter.variable} ${jakarta.variable} antialiased`}
    >
      <body>{children}</body>
    </html>
  );
}
