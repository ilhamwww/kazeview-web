"use client";

import { useState } from "react";
import type { CollectionItem } from "@/lib/types";

const filters = [
  ["all", "ALL"],
  ["photography", "PHOTOGRAPHY"],
  ["films", "FILMS"],
  ["automotive", "AUTOMOTIVE"],
  ["portraits", "PORTRAITS"],
  ["events", "EVENTS"],
] as const;

function category(item: CollectionItem): string {
  const value = (item.category || "PHOTOGRAPHY").toUpperCase();
  return ["PHOTOGRAPHY", "AUTOMOTIVE", "PORTRAITS", "EVENTS"].includes(value)
    ? value
    : "PHOTOGRAPHY";
}

export default function HomePortfolio({
  items,
}: {
  items: CollectionItem[];
}) {
  const [filter, setFilter] = useState("all");
  const portfolio = items.filter((item) => item.is_active);

  return (
    <>
      <nav className="home-filter-bar" aria-label="Filter portfolio">
        <ul className="home-filter-list">
          {filters.map(([value, label]) => (
            <li key={value}>
              <button
                className={`filter-button${filter === value ? " is-active" : ""}`}
                type="button"
                aria-pressed={filter === value}
                onClick={() => setFilter(value)}
              >
                {label}
              </button>
            </li>
          ))}
        </ul>
      </nav>

      <section
        className="home-portfolio-grid"
        id="work"
        aria-label="KAZEVIEW portfolio"
      >
        {portfolio.map((item, index) => {
          const isFilm = item.media_type?.toUpperCase() === "FILM";
          const itemCategory = category(item);
          const tags = [
            isFilm ? "films" : "photography",
            itemCategory.toLowerCase(),
          ];
          const hidden = filter !== "all" && !tags.includes(filter);

          return (
            <a
              key={item.id}
              className={`home-portfolio-card${
                isFilm ? " home-portfolio-card--film" : ""
              }${hidden ? " is-hidden" : ""}`}
              href={item.link || "#work"}
              aria-label={`${isFilm ? "FILM" : "PHOTOGRAPHY"}, ${
                item.title || "KAZEVIEW"
              }, ${itemCategory}`}
            >
              {item.video_url ? (
                <video
                  className="portfolio-card__video"
                  autoPlay
                  muted
                  loop
                  playsInline
                  poster={item.image_url || undefined}
                  aria-label={`${item.title || "KAZEVIEW"} — ${itemCategory.toLowerCase()} by KAZEVIEW`}
                >
                  <source src={item.video_url} />
                </video>
              ) : item.image_url ? (
                // eslint-disable-next-line @next/next/no-img-element
                <img
                  src={item.image_url}
                  alt={`${item.title || "KAZEVIEW"} — ${itemCategory.toLowerCase()} by KAZEVIEW`}
                  style={{ objectPosition: item.image_position || "50% 50%" }}
                  loading={index < 4 ? "eager" : "lazy"}
                />
              ) : null}

              {isFilm && (
                <>
                  <span className="portfolio-play" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                      <path d="M7 4.8v14.4L19 12 7 4.8Z" />
                    </svg>
                  </span>
                  {item.duration && (
                    <span className="portfolio-duration" aria-hidden="true">
                      {item.duration}
                    </span>
                  )}
                </>
              )}

              <span className="portfolio-card__meta" aria-hidden="true">
                <span className="portfolio-card__category">{itemCategory}</span>
                <span className="portfolio-card__title">
                  {(item.title || "KAZEVIEW").toUpperCase()}
                </span>
              </span>
            </a>
          );
        })}
      </section>
    </>
  );
}