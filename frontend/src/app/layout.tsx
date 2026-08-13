import type { Metadata } from "next";
import type { ReactNode } from "react";
import SiteChrome from "@/components/SiteChrome";
import { getPublicData } from "@/lib/api";
import type { BootstrapData } from "@/lib/types";
import "./kazeview.css";

export const revalidate = 30;

async function bootstrap(): Promise<BootstrapData> {
  return getPublicData<BootstrapData>("bootstrap");
}

export async function generateMetadata(): Promise<Metadata> {
  const { site } = await bootstrap();
  const title = site.seo.title || site.name || "KAZEVIEW";
  const description =
    site.seo.description ||
    site.description ||
    "KAZEVIEW — Automotive, portrait, and event photography and films.";
  const canonical = site.seo.canonical_url || undefined;

  return {
    metadataBase: canonical ? new URL(canonical) : undefined,
    title: {
      default: title,
      template: `%s — ${site.name || "KAZEVIEW"}`,
    },
    description,
    keywords: site.seo.keywords || undefined,
    authors: site.seo.author ? [{ name: site.seo.author }] : undefined,
    robots: site.seo.robots,
    alternates: canonical ? { canonical } : undefined,
    openGraph: {
      type: "website",
      siteName: site.name,
      title: site.seo.og_title || title,
      description: site.seo.og_description || description,
      images: site.seo.og_image_url ? [site.seo.og_image_url] : undefined,
    },
    twitter: {
      card:
        site.seo.twitter_card === "summary"
          ? "summary"
          : "summary_large_image",
      title: site.seo.twitter_title || site.seo.og_title || title,
      description:
        site.seo.twitter_description ||
        site.seo.og_description ||
        description,
      images: site.seo.og_image_url ? [site.seo.og_image_url] : undefined,
    },
    verification: {
      google: site.seo.google_verification || undefined,
      other: site.seo.bing_verification
        ? { "msvalidate.01": site.seo.bing_verification }
        : undefined,
    },
    icons: {
      icon: "/KAZE_icon.png",
    },
  };
}

export default async function RootLayout({
  children,
}: Readonly<{ children: ReactNode }>) {
  const { site } = await bootstrap();

  return (
    <html lang="id">
      <head>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link
          rel="preconnect"
          href="https://fonts.gstatic.com"
          crossOrigin="anonymous"
        />
        <link
          href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Inter+Tight:wght@600;700;800&display=swap"
          rel="stylesheet"
        />
        <meta name="theme-color" content={site.seo.theme_color || "#050506"} />
      </head>
      <body>
        <SiteChrome site={site}>{children}</SiteChrome>
      </body>
    </html>
  );
}