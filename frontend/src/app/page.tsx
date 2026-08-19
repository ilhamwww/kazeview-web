import type { Metadata } from "next";
import HomePortfolio from "@/components/HomePortfolio";
import { getPublicData } from "@/lib/api";
import type { CollectionItem, HomeData } from "@/lib/types";

export const metadata: Metadata = {
  title: "KAZEVIEW — Photo + Film",
  description:
    "KAZEVIEW captures motion in every frame through automotive, portrait, and event photography and film.",
};

function Media({
  item,
  featured = false,
}: {
  item: CollectionItem;
  featured?: boolean;
}) {
  if (item.video_url) {
    return (
      <video
        className="media-tile__image"
        autoPlay
        muted
        loop
        playsInline
        poster={item.image_url || undefined}
        aria-label={`${item.title || "KAZEVIEW"} by KAZEVIEW`}
      >
        <source src={item.video_url} />
      </video>
    );
  }

  return item.image_url ? (
    // eslint-disable-next-line @next/next/no-img-element
    <img
      className="media-tile__image"
      src={item.image_url}
      alt={`${item.title || "KAZEVIEW"} by KAZEVIEW`}
      style={{ objectPosition: item.image_position || "50% 50%" }}
      loading="eager"
      fetchPriority={featured ? "high" : undefined}
    />
  ) : null;
}

export default async function HomePage() {
  const data = await getPublicData<HomeData>("home");
  const fallback: CollectionItem = {
    id: -1,
    title: "KAZEVIEW",
    subtitle: null,
    category: "PHOTOGRAPHY",
    media_type: "PHOTOGRAPHY",
    image_url: data.site.hero_image_url || "/KAZE_icon.png",
    video_url: null,
    link: "#work",
    duration: null,
    project_year: new Date().getFullYear(),
    image_position: "50% 50%",
    is_active: true,
    is_featured: false,
    sort_order: 0,
  };
  const media = data.collections.filter(
    (item) => item.is_active && (item.image_url || item.video_url),
  );
  const source = media.length ? media : [fallback];
  const featured = [
    ...source.filter((item) => item.is_featured).slice(0, 5),
  ];
  const selected = new Set(featured.map((item) => item.id));

  for (const item of source) {
    if (featured.length >= 5) break;
    if (!selected.has(item.id)) {
      featured.push(item);
      selected.add(item.id);
    }
  }

  while (featured.length < 5) featured.push(fallback);

  const hero = featured[0];
  const secondary = featured.slice(1, 5);
  const portfolio = source.filter((item) => item.id !== hero.id);

  return (
    <>
      <section
        className="home-featured-grid"
        id="films"
        aria-label="Featured KAZEVIEW work"
      >
        <a
          className="media-tile featured-film"
          href={hero.link || "#work"}
          aria-label={`View ${hero.title || "KAZEVIEW"} project`}
        >
          <Media item={hero} featured />
          <span className="featured-film__statement" aria-hidden="true">
            <h1>
              MOTION IN
              <br />
              EVERY FRAME<span className="accent">.</span>
            </h1>
            <p>Automotive · Portrait · Event</p>
          </span>
          <span className="scroll-indicator" aria-hidden="true">
            SCROLL TO EXPLORE
          </span>
        </a>

        <div className="home-featured-grid__secondary">
          {secondary.map((item, index) => (
            <a
              className="media-tile secondary-tile"
              href={item.link || "#work"}
              key={`${item.id}-${index}`}
              aria-label={`View ${(item.category || "photography").toLowerCase()} ${(item.media_type || "photography").toLowerCase()}`}
            >
              <Media item={item} />
              <span className="secondary-tile__label" aria-hidden="true">
                <span className="secondary-tile__category">
                  {(item.category || "PHOTOGRAPHY").toUpperCase()}
                </span>
                <span className="secondary-tile__year">
                  {item.project_year || new Date().getFullYear()}
                </span>
              </span>
            </a>
          ))}
        </div>
      </section>

      <HomePortfolio items={portfolio} categories={data.categories || []} />

      <section id="about" className="sr-only" aria-label="About KAZEVIEW">
        <h2>About KAZEVIEW</h2>
        <p>Automotive, portrait, and event photography and films.</p>
      </section>
    </>
  );
}