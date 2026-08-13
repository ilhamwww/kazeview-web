"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useEffect, useMemo, useState } from "react";
import type { Site } from "@/lib/types";

function whatsappUrl(value: string | null): string {
  let number = (value ?? "").replace(/\D+/g, "");

  if (number.startsWith("0")) {
    number = `62${number.slice(1)}`;
  } else if (number && !number.startsWith("62")) {
    number = `62${number}`;
  }

  return number ? `https://wa.me/${number}` : "#contact";
}

export default function SiteChrome({
  site,
  children,
}: {
  site: Site;
  children: React.ReactNode;
}) {
  const pathname = usePathname();
  const [menuOpen, setMenuOpen] = useState(false);
  const logo = site.logo_url ?? "/KAZE_logo.png";
  const pageClass = useMemo(() => {
    if (pathname === "/") return "page-home";
    if (pathname === "/films") return "page-films";
    if (pathname.startsWith("/preview")) return "page-preview";
    if (pathname === "/about") return "page-about";
    if (pathname === "/contact") return "page-contact";
    return "";
  }, [pathname]);

  useEffect(() => {
    document.body.className = `${pageClass}${menuOpen ? " menu-open" : ""}`;
    return () => {
      document.body.className = "";
    };
  }, [pageClass, menuOpen]);

  useEffect(() => {
    const groups = [
      {
        selector:
          ".home-featured-grid, .film-hero, .preview-intro, .about-hero, .contact-hero",
        className: "reveal-cinematic",
      },
      {
        selector:
          ".home-filter-bar, .film-discovery, .film-section-heading, .discovery-bar, .about-story, .about-section-heading, .contact-details__heading",
        className: "reveal-up",
      },
      {
        selector:
          ".home-portfolio-card, .film-card, .event-card, .about-capability-list > li, .contact-details__list > div, .contact-socials li",
        className: "reveal-up",
        stagger: true,
      },
      {
        selector:
          ".about-story__media, .about-hero__media, .contact-hero__media",
        className: "reveal-media",
      },
      {
        selector:
          ".about-story__content, .about-cta, .contact-details, .contact-socials",
        className: "reveal-up",
      },
    ];
    const elements: HTMLElement[] = [];

    groups.forEach((group) => {
      document.querySelectorAll<HTMLElement>(group.selector).forEach(
        (element, index) => {
          element.classList.add("reveal", group.className);
          if (group.stagger) {
            element.style.setProperty(
              "--reveal-delay",
              `${Math.min(index % 8, 7) * 65}ms`,
            );
          }
          elements.push(element);
        },
      );
    });

    const reduceMotion = window.matchMedia(
      "(prefers-reduced-motion: reduce)",
    ).matches;

    if (reduceMotion || !("IntersectionObserver" in window)) {
      elements.forEach((element) => element.classList.add("is-revealed"));
      return;
    }

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          entry.target.classList.add("is-revealed");
          observer.unobserve(entry.target);
        });
      },
      { rootMargin: "0px 0px -8% 0px", threshold: 0.08 },
    );

    elements.forEach((element) => observer.observe(element));
    return () => observer.disconnect();
  }, [pathname]);

  useEffect(() => {
    const close = (event: KeyboardEvent) => {
      if (event.key === "Escape") setMenuOpen(false);
    };
    document.addEventListener("keydown", close);
    return () => document.removeEventListener("keydown", close);
  }, []);

  const links = [
    ["/", "WORK"],
    ["/films", "FILMS"],
    ["/preview", "PREVIEW"],
    ["/about", "ABOUT"],
    ["/contact", "CONTACT"],
  ] as const;
  const isPreview = pathname.startsWith("/preview");
  const wa = whatsappUrl(site.whatsapp);

  return (
    <>
      <a className="skip-link" href="#main-content">
        Skip to content
      </a>

      <header
        className={`site-header ${
          isPreview ? "site-header--preview" : "site-header--home"
        }`}
      >
        <Link
          className={`site-logo${site.logo_url ? " site-logo--custom" : ""}`}
          href="/"
          aria-label="KAZEVIEW home"
        >
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src={logo} alt="KAZEVIEW" width="174" height="24" />
        </Link>

        <button
          className="menu-toggle"
          type="button"
          aria-expanded={menuOpen}
          aria-controls="primary-navigation"
          onClick={() => setMenuOpen((open) => !open)}
        >
          <span className="sr-only">Toggle navigation</span>
          <span />
          <span />
        </button>

        <nav
          className="site-nav"
          id="primary-navigation"
          aria-label="Primary navigation"
        >
          {links.map(([href, label]) => {
            const active =
              href === "/"
                ? pathname === "/"
                : href === "/preview"
                  ? pathname.startsWith("/preview")
                  : pathname === href;

            return (
              <Link
                key={href}
                href={href}
                className={active ? "is-active" : undefined}
                aria-current={active ? "page" : undefined}
                onClick={() => setMenuOpen(false)}
              >
                {label}
              </Link>
            );
          })}
          <a
            className="site-nav__book"
            href={wa}
            target={wa.startsWith("http") ? "_blank" : undefined}
            rel={wa.startsWith("http") ? "noopener noreferrer" : undefined}
          >
            BOOK A SHOOT
          </a>
        </nav>
      </header>

      <main id="main-content">{children}</main>

      <footer className="site-footer" id="contact">
        <span>
          © {new Date().getFullYear()} {(site.name || "KAZEVIEW").toUpperCase()}
        </span>
        <span>PHOTO + FILM / YOGYAKARTA</span>
      </footer>
    </>
  );
}