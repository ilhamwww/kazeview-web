import { faArrowUpRightFromSquare } from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import type { Metadata } from "next";
import Link from "next/link";
import FilmsShowcase from "@/components/FilmsShowcase";
import { getPublicData } from "@/lib/api";
import type { FilmsData } from "@/lib/types";

export const metadata: Metadata = {
  title: "Films",
  description:
    "Explore KAZEVIEW films — cinematic automotive, portrait, and event stories captured in motion.",
};

export default async function FilmsPage() {
  const data = await getPublicData<FilmsData>("films");
  const films = data.films;
  const featured = films[0];
  const fallback = data.site.hero_image_url || "/KAZE_icon.png";

  return (
    <article className="film-page">
      {featured ? (
        <>
          <header className="film-hero">
            <div className="film-hero__media">
              {featured.video_url ? (
                <video
                  autoPlay
                  muted
                  loop
                  playsInline
                  poster={featured.image_url || fallback}
                  aria-label={`${featured.title || "KAZEVIEW"} film`}
                >
                  <source src={featured.video_url} />
                </video>
              ) : (
                // eslint-disable-next-line @next/next/no-img-element
                <img
                  src={featured.image_url || fallback}
                  alt={`${featured.title || "KAZEVIEW"} film by KAZEVIEW`}
                  style={{
                    objectPosition: featured.image_position || "50% 50%",
                  }}
                  fetchPriority="high"
                />
              )}
              <span className="film-hero__overlay" aria-hidden="true" />
            </div>
            <div className="film-hero__content">
              <p className="film-eyebrow">
                {(featured.category || "KAZEVIEW FILM").toUpperCase()} /{" "}
                {featured.project_year || new Date().getFullYear()}
              </p>
              <h1>
                {(featured.title || "STORIES IN MOTION").toUpperCase()}
                <span className="accent">.</span>
              </h1>
              <div className="film-hero__meta">
                {featured.duration && <span>{featured.duration}</span>}
                {featured.link && (
                  <a
                    className="film-watch-link"
                    href={featured.link}
                    target={
                      featured.link.startsWith("http") ? "_blank" : undefined
                    }
                    rel={
                      featured.link.startsWith("http")
                        ? "noopener noreferrer"
                        : undefined
                    }
                  >
                    WATCH FILM{" "}
                    <FontAwesomeIcon
                      icon={faArrowUpRightFromSquare}
                      aria-hidden="true"
                    />
                  </a>
                )}
              </div>
            </div>
            <span className="film-hero__index" aria-hidden="true">
              01 / {String(films.length).padStart(2, "0")}
            </span>
          </header>

          <section className="film-archive" aria-labelledby="film-archive-title">
            <div className="film-section-heading">
              <p className="film-eyebrow">ARCHIVE / {new Date().getFullYear()}</p>
              <h2 id="film-archive-title">
                FILMOGRAPHY<span className="accent">.</span>
              </h2>
            </div>
            <FilmsShowcase films={films} fallbackImage={fallback} />
          </section>
        </>
      ) : (
        <section className="film-empty-state">
          <p className="film-eyebrow">KAZEVIEW FILMS</p>
          <h1>
            STORIES IN
            <br />
            MOTION<span className="accent">.</span>
          </h1>
          <p>New films are currently in production.</p>
          <Link href="/contact">
            START A PROJECT{" "}
            <FontAwesomeIcon
              icon={faArrowUpRightFromSquare}
              aria-hidden="true"
            />
          </Link>
        </section>
      )}
    </article>
  );
}