import type { Metadata } from "next";
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

export const metadata: Metadata = {
  title: "LSP TIK Global",
  description: "Lembaga Sertifikasi Profesi LSP TIK Global",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html
      lang="id"
      className={`${figtree.variable} ${inter.variable} ${jakarta.variable} antialiased`}
    >
      <body>{children}</body>
    </html>
  );
}
