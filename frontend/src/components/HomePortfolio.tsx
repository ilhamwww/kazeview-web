"use client";

import { faPlay } from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { useMemo, useState } from "react";
import type {
  CollectionCategory,
  CollectionItem,
} from "@/lib/types";

function category(item: CollectionItem): string {
  return (item.category || "PHOTOGRAPHY").trim().toUpperCase();
}

function categorySlug(value: string): string {
  return value
    .toLowerCase()
    .normalize("NFKD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
}

export default function HomePortfolio({
  items,
  categories,
}: {
  items: CollectionItem[];
  categories: CollectionCategory[];
}) {
  const [filter, setFilter] = useState("all");
  const portfolio = items.filter((item) => item.is_active);
  const filters = useMemo(
    () => [
      ["all", "ALL"],
      ["photography", "PHOTOGRAPHY"],
      ["films", "FILMS"],
      ...categories
        .filter(
          (item) =>
            !["photography", "films"].includes(item.slug),
        )
        .map((item) => [item.slug, item.name.toUpperCase()]),
    ],
    [categories],
  );

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
            categorySlug(itemCategory),
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
                    <FontAwesomeIcon icon={faPlay} />
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