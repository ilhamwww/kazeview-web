"use client";

import {
  faChevronLeft,
  faChevronRight,
  faXmark,
} from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { useCallback, useEffect, useRef, useState } from "react";
import type { GalleryPhoto } from "@/lib/types";

export default function PhotoGallery({ photos }: { photos: GalleryPhoto[] }) {
  const [active, setActive] = useState<number | null>(null);
  const closeRef = useRef<HTMLButtonElement>(null);
  const openerRef = useRef<HTMLButtonElement | null>(null);

  const close = useCallback(() => setActive(null), []);
  const previous = useCallback(
    () =>
      setActive((value) =>
        value === null ? null : (value - 1 + photos.length) % photos.length,
      ),
    [photos.length],
  );
  const next = useCallback(
    () =>
      setActive((value) =>
        value === null ? null : (value + 1) % photos.length,
      ),
    [photos.length],
  );

  useEffect(() => {
    if (active === null) return;
    const overflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    closeRef.current?.focus();

    const keydown = (event: KeyboardEvent) => {
      if (event.key === "Escape") close();
      if (event.key === "ArrowLeft") previous();
      if (event.key === "ArrowRight") next();
    };

    document.addEventListener("keydown", keydown);
    return () => {
      document.body.style.overflow = overflow;
      document.removeEventListener("keydown", keydown);
      openerRef.current?.focus();
    };
  }, [active, close, next, previous]);

  const grouped = photos.reduce<Record<string, GalleryPhoto[]>>(
    (result, photo) => {
      const key = photo.folder?.relative_path || "";
      (result[key] ||= []).push(photo);
      return result;
    },
    {},
  );

  return (
    <>
      {Object.entries(grouped).map(([folder, items]) => (
        <section className="preview-gallery-group" key={folder || "root"}>
          {folder && <h2>{folder}</h2>}
          <div className="preview-photo-grid">
            {items.map((photo) => {
              const index = photos.findIndex((item) => item.id === photo.id);
              return (
                <button
                  className="preview-photo"
                  type="button"
                  key={photo.id}
                  onClick={(event) => {
                    openerRef.current = event.currentTarget;
                    setActive(index);
                  }}
                  aria-label={`Open ${photo.name}`}
                >
                  {/* eslint-disable-next-line @next/next/no-img-element */}
                  <img src={photo.url} alt={photo.name} loading="lazy" />
                </button>
              );
            })}
          </div>
        </section>
      ))}

      {active !== null && photos[active] && (
        <div
          className="photo-lightbox is-open"
          role="dialog"
          aria-modal="true"
          aria-label={`Photo ${active + 1} of ${photos.length}`}
          onMouseDown={(event) => {
            if (event.target === event.currentTarget) close();
          }}
        >
          <button
            ref={closeRef}
            className="photo-lightbox__close"
            type="button"
            aria-label="Close photo"
            onClick={close}
          >
            <FontAwesomeIcon icon={faXmark} aria-hidden="true" />
          </button>
          {photos.length > 1 && (
            <button
              className="photo-lightbox__nav photo-lightbox__nav--prev"
              type="button"
              aria-label="Previous photo"
              onClick={previous}
            >
              <FontAwesomeIcon icon={faChevronLeft} aria-hidden="true" />
            </button>
          )}
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src={photos[active].url} alt={photos[active].name} />
          {photos.length > 1 && (
            <button
              className="photo-lightbox__nav photo-lightbox__nav--next"
              type="button"
              aria-label="Next photo"
              onClick={next}
            >
              <FontAwesomeIcon icon={faChevronRight} aria-hidden="true" />
            </button>
          )}
          <p className="photo-lightbox__count">
            {active + 1} / {photos.length}
          </p>
        </div>
      )}
    </>
  );
}