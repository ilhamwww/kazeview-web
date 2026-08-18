"use client";

import { faPlay, faXmark } from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { useEffect, useMemo, useRef, useState } from "react";
import type { CollectionItem } from "@/lib/types";

function slug(value: string): string {
  return value
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-|-$/g, "") || "film";
}

export default function FilmsShowcase({
  films,
  fallbackImage,
}: {
  films: CollectionItem[];
  fallbackImage: string;
}) {
  const [filter, setFilter] = useState("all");
  const [activeFilm, setActiveFilm] = useState<CollectionItem | null>(null);
  const closeRef = useRef<HTMLButtonElement>(null);
  const openerRef = useRef<HTMLElement | null>(null);

  const categories = useMemo(
    () =>
      Array.from(
        new Map(
          films.map((film) => {
            const label = (film.category || "FILM").toUpperCase();
            return [slug(label), label];
          }),
        ).entries(),
      ),
    [films],
  );

  useEffect(() => {
    if (!activeFilm) return;

    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    closeRef.current?.focus();

    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") setActiveFilm(null);
    };

    document.addEventListener("keydown", onKeyDown);
    return () => {
      document.body.style.overflow = previousOverflow;
      document.removeEventListener("keydown", onKeyDown);
      openerRef.current?.focus();
    };
  }, [activeFilm]);

  const open = (
    film: CollectionItem,
    event: React.MouseEvent<HTMLElement>,
  ) => {
    if (!film.video_url && film.link) return;
    event.preventDefault();
    openerRef.current = event.currentTarget;
    setActiveFilm(film);
  };

  return (
    <>
      <nav className="film-discovery" aria-label="Filter films">
        <button
          className={`film-filter${filter === "all" ? " is-active" : ""}`}
          type="button"
          aria-pressed={filter === "all"}
          onClick={() => setFilter("all")}
        >
          ALL FILMS
        </button>
        {categories.map(([value, label]) => (
          <button
            key={value}
            className={`film-filter${filter === value ? " is-active" : ""}`}
            type="button"
            aria-pressed={filter === value}
            onClick={() => setFilter(value)}
          >
            {label}
          </button>
        ))}
      </nav>

      <section className="film-grid" aria-label="KAZEVIEW films">
        {films.map((film, index) => {
          const category = (film.category || "FILM").toUpperCase();
          const categorySlug = slug(category);
          const hidden = filter !== "all" && filter !== categorySlug;
          const href = film.link || film.video_url || "#";
          const title = (film.title || "UNTITLED FILM").toUpperCase();

          return (
            <a
              key={film.id}
              className={`film-card${film.is_featured ? " film-card--featured" : ""}${hidden ? " is-hidden" : ""}`}
              href={href}
              target={film.link?.startsWith("http") ? "_blank" : undefined}
              rel={
                film.link?.startsWith("http")
                  ? "noopener noreferrer"
                  : undefined
              }
              onClick={(event) => open(film, event)}
              aria-label={`Watch ${title}`}
            >
              <span className="film-card__media">
                {film.video_url ? (
                  <video
                    muted
                    loop
                    playsInline
                    preload="metadata"
                    poster={film.image_url || fallbackImage}
                    aria-label={`${title} by KAZEVIEW`}
                    onMouseEnter={(event) => {
                      void event.currentTarget.play().catch(() => undefined);
                    }}
                    onMouseLeave={(event) => {
                      event.currentTarget.pause();
                      event.currentTarget.currentTime = 0;
                    }}
                  >
                    <source src={film.video_url} />
                  </video>
                ) : (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img
                    src={film.image_url || fallbackImage}
                    alt={`${title} by KAZEVIEW`}
                    style={{
                      objectPosition: film.image_position || "50% 50%",
                    }}
                    loading={index < 3 ? "eager" : "lazy"}
                  />
                )}
                <span className="film-card__play" aria-hidden="true">
                  <FontAwesomeIcon icon={faPlay} />
                </span>
              </span>
              <span className="film-card__meta">
                <span>
                  <strong>{title}</strong>
                  <small>{category}</small>
                </span>
                <span>
                  {film.project_year || new Date().getFullYear()}
                  {film.duration ? ` · ${film.duration}` : ""}
                </span>
              </span>
            </a>
          );
        })}
      </section>

      {activeFilm?.video_url && (
        <div
          className="film-modal is-open"
          role="dialog"
          aria-modal="true"
          aria-label={`Playing ${activeFilm.title || "film"}`}
          onMouseDown={(event) => {
            if (event.target === event.currentTarget) setActiveFilm(null);
          }}
        >
          <div className="film-modal__dialog">
            <button
              ref={closeRef}
              className="film-modal__close"
              type="button"
              aria-label="Close film"
              onClick={() => setActiveFilm(null)}
            >
              <FontAwesomeIcon icon={faXmark} aria-hidden="true" />
            </button>
            <video
              controls
              autoPlay
              playsInline
              poster={activeFilm.image_url || fallbackImage}
            >
              <source src={activeFilm.video_url} />
            </video>
          </div>
        </div>
      )}
    </>
  );
}