"use client";

import {
  faArrowUpRightFromSquare,
  faPlay,
  faXmark,
} from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import Link from "next/link";
import { useEffect, useMemo, useRef, useState } from "react";
import type {
  FilterCategory,
  PreviewContent,
  Site,
} from "@/lib/types";

function formatDate(value: string | null): string {
  if (!value) return "";
  return new Intl.DateTimeFormat("en-GB", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  })
    .format(new Date(`${value}T00:00:00`))
    .toUpperCase();
}

function formatPrice(value: number | null): string {
  if (value === null) return "FREE DOWNLOAD";
  return `Rp ${new Intl.NumberFormat("id-ID").format(value)} / PHOTO`;
}

function whatsapp(value: string | null): string {
  let number = (value ?? "").replace(/\D+/g, "");
  if (number.startsWith("0")) number = `62${number.slice(1)}`;
  else if (number && !number.startsWith("62")) number = `62${number}`;
  return number ? `https://wa.me/${number}` : "#contact";
}

export default function PreviewBrowser({
  contents,
  categories,
  site,
}: {
  contents: PreviewContent[];
  categories: FilterCategory[];
  site: Site;
}) {
  const [filter, setFilter] = useState("all");
  const [sort, setSort] = useState<"newest" | "oldest">("newest");
  const [payment, setPayment] = useState<PreviewContent | null>(null);
  const closeRef = useRef<HTMLButtonElement>(null);
  const openerRef = useRef<HTMLElement | null>(null);

  const filters = [
    ["all", "ALL EVENTS"],
    ["photo", "FOTO"],
    ["video", "VIDEO"],
    ["latest", "LATEST"],
    ...categories.map(
      (category) =>
        [`category:${category.slug}`, category.name.toUpperCase()] as const,
    ),
  ] as const;

  const events = useMemo(() => {
    const visible = contents.filter((item) => {
      if (filter === "all") return true;
      if (filter === "photo") return item.media_type === "FOTO";
      if (filter === "video") return item.media_type === "VIDEO";
      if (filter === "latest") return item.is_new;
      if (filter.startsWith("category:")) {
        const slug = filter.slice("category:".length);
        return item.categories.some((category) => category.slug === slug);
      }
      return true;
    });

    return [...visible].sort((a, b) => {
      const left = a.event_date ? Date.parse(a.event_date) : 0;
      const right = b.event_date ? Date.parse(b.event_date) : 0;
      return sort === "newest" ? right - left : left - right;
    });
  }, [contents, filter, sort]);

  useEffect(() => {
    if (!payment) return;
    const overflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    closeRef.current?.focus();

    const keydown = (event: KeyboardEvent) => {
      if (event.key === "Escape") setPayment(null);
      if (event.key === "Tab") {
        const dialog = closeRef.current?.closest<HTMLElement>(
          ".payment-modal__dialog",
        );
        const focusable = dialog?.querySelectorAll<HTMLElement>(
          'button, a[href], [tabindex]:not([tabindex="-1"])',
        );
        if (!focusable?.length) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
          event.preventDefault();
          last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
          event.preventDefault();
          first.focus();
        }
      }
    };

    document.addEventListener("keydown", keydown);
    return () => {
      document.body.style.overflow = overflow;
      document.removeEventListener("keydown", keydown);
      openerRef.current?.focus();
    };
  }, [payment]);

  const card = (item: PreviewContent, index: number) => {
    const title = (item.title || "UNTITLED EVENT").toUpperCase();
    const isExternal = item.media_type === "VIDEO";
    const href = isExternal ? item.redirect_url || "#" : `/preview/${item.id}`;
    const paid = item.is_price_enabled && item.price !== null;

    const content = (
      <>
        <div className="event-card__media">
          {item.image_url && (
            // eslint-disable-next-line @next/next/no-img-element
            <img
              src={item.image_url}
              alt={`${title} — KAZEVIEW`}
              style={{ objectPosition: item.image_position || "50% 50%" }}
              loading={index < 4 ? "eager" : "lazy"}
            />
          )}
          <span className="event-card__type">{item.media_type}</span>
          {item.is_new && <span className="event-card__new">NEW</span>}
          {!item.is_active && (
            <span className="event-card__unavailable">COMING SOON</span>
          )}
          {item.media_type === "VIDEO" && (
            <span className="event-card__play" aria-hidden="true">
              <FontAwesomeIcon icon={faPlay} />
            </span>
          )}
        </div>
        <div className="event-card__content">
          <p className="event-card__date">{formatDate(item.event_date)}</p>
          <h2>{title}</h2>
          <div className="event-card__details">
            <span>{(item.location || "LOCATION TBA").toUpperCase()}</span>
            {item.film_duration && <span>{item.film_duration}</span>}
          </div>
          <div className="event-card__footer">
            <span>{formatPrice(paid ? item.price : null)}</span>
            <FontAwesomeIcon
              icon={faArrowUpRightFromSquare}
              aria-hidden="true"
            />
          </div>
        </div>
      </>
    );

    if (!item.is_active) {
      return (
        <article className="event-card event-card--inactive" key={item.id}>
          {content}
        </article>
      );
    }

    if (paid) {
      return (
        <button
          className="event-card event-card--button"
          type="button"
          key={item.id}
          onClick={(event) => {
            openerRef.current = event.currentTarget;
            setPayment(item);
          }}
        >
          {content}
        </button>
      );
    }

    return isExternal ? (
      <a
        className="event-card"
        href={href}
        target="_blank"
        rel="noopener noreferrer"
        key={item.id}
      >
        {content}
      </a>
    ) : (
      <Link className="event-card" href={href} key={item.id}>
        {content}
      </Link>
    );
  };

  return (
    <>
      <section className="discovery-bar" aria-label="Find events">
        <div className="discovery-filters">
          {filters.map(([value, label]) => (
            <button
              className={`discovery-filter${filter === value ? " is-active" : ""}`}
              type="button"
              key={value}
              aria-pressed={filter === value}
              onClick={() => setFilter(value)}
            >
              {label}
            </button>
          ))}
        </div>
        <label className="discovery-sort">
          <span>SORT</span>
          <select
            value={sort}
            onChange={(event) =>
              setSort(event.target.value as "newest" | "oldest")
            }
          >
            <option value="newest">NEWEST FIRST</option>
            <option value="oldest">OLDEST FIRST</option>
          </select>
        </label>
      </section>

      <section className="event-grid" aria-label="KAZEVIEW event galleries">
        {events.map(card)}
      </section>

      {events.length === 0 && (
        <p className="event-empty" role="status">
          NO EVENTS IN THIS CATEGORY.
        </p>
      )}

      {payment && (
        <div
          className="payment-modal is-open"
          role="dialog"
          aria-modal="true"
          aria-labelledby="payment-title"
          onMouseDown={(event) => {
            if (event.target === event.currentTarget) setPayment(null);
          }}
        >
          <div className="payment-modal__dialog">
            <button
              ref={closeRef}
              className="payment-modal__close"
              type="button"
              aria-label="Close payment information"
              onClick={() => setPayment(null)}
            >
              <FontAwesomeIcon icon={faXmark} aria-hidden="true" />
            </button>
            <p className="preview-eyebrow">PHOTO ACCESS</p>
            <h2 id="payment-title">{payment.title}</h2>
            <p>
              Harga akses {formatPrice(payment.price)}. Hubungi KAZEVIEW untuk
              konfirmasi pembayaran QRIS dan menerima tautan galeri.
            </p>
            <a
              className="payment-modal__action"
              href={`${whatsapp(site.whatsapp)}?text=${encodeURIComponent(
                `Halo KAZEVIEW, saya ingin mengakses foto ${payment.title || ""}.`,
              )}`}
              target="_blank"
              rel="noopener noreferrer"
            >
              CONTINUE VIA WHATSAPP{" "}
              <FontAwesomeIcon
                icon={faArrowUpRightFromSquare}
                aria-hidden="true"
              />
            </a>
          </div>
        </div>
      )}
    </>
  );
}