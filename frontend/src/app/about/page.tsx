import type { Metadata } from "next";
import { getPublicData } from "@/lib/api";
import type { AboutData } from "@/lib/types";

export const metadata: Metadata = {
  title: "About",
  description:
    "About KAZEVIEW — independent photography and film studio in Yogyakarta.",
};

function whatsappUrl(value: string | null): string | null {
  let number = (value ?? "").replace(/\D+/g, "");
  if (!number) return null;
  if (number.startsWith("0")) number = `62${number.slice(1)}`;
  else if (!number.startsWith("62")) number = `62${number}`;
  return `https://wa.me/${number}`;
}

export default async function AboutPage() {
  const data = await getPublicData<AboutData>("about");
  const { about } = data;
  const hero = about.hero_image_url || "/KAZE_icon.png";
  const story = about.story_image_url || hero;
  const capabilities = about.capabilities.filter((item) =>
    item.title?.trim(),
  );
  const cta = about.cta_url || whatsappUrl(data.site.whatsapp) || "#contact";
  const external = cta.startsWith("http");

  return (
    <article className="about-page">
      <section className="about-hero" aria-labelledby="about-title">
        <div className="about-hero__content">
          <p className="about-eyebrow">{about.eyebrow}</p>
          <h1 id="about-title">{about.headline}</h1>
          {about.intro && <p className="about-hero__intro">{about.intro}</p>}
          <dl className="about-hero__facts">
            <div>
              <dt>BASE</dt>
              <dd>{about.location}</dd>
            </div>
            {about.established && (
              <div>
                <dt>STUDIO</dt>
                <dd>{about.established}</dd>
              </div>
            )}
          </dl>
        </div>
        <figure className="about-hero__media">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src={hero} alt={about.headline} fetchPriority="high" />
        </figure>
      </section>

      <section className="about-story" aria-labelledby="about-story-title">
        <figure className="about-story__media">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src={story} alt={about.story_title} loading="lazy" />
        </figure>
        <div className="about-story__content">
          <p className="about-eyebrow">OUR STORY</p>
          <h2 id="about-story-title">{about.story_title}</h2>
          {about.story_body && (
            <div className="about-story__body">
              {about.story_body
                .split(/\r\n|\r|\n/)
                .map((paragraph) => paragraph.trim())
                .filter(Boolean)
                .map((paragraph, index) => (
                  <p key={`${index}-${paragraph}`}>{paragraph}</p>
                ))}
            </div>
          )}
        </div>
      </section>

      <section className="about-capabilities" aria-labelledby="capabilities-title">
        <div className="about-section-heading">
          <p className="about-eyebrow">WHAT WE DO</p>
          <h2 id="capabilities-title">
            CAPABILITIES<span className="accent">.</span>
          </h2>
        </div>
        <ol className="about-capability-list">
          {capabilities.map((capability, index) => (
            <li key={`${index}-${capability.title}`}>
              <span>{String(index + 1).padStart(2, "0")}</span>
              <h3>{capability.title}</h3>
              {capability.description && <p>{capability.description}</p>}
            </li>
          ))}
        </ol>
      </section>

      <section className="about-cta" aria-labelledby="about-cta-title">
        <p className="about-eyebrow">START A PROJECT</p>
        <h2 id="about-cta-title">{about.cta_title}</h2>
        <a
          href={cta}
          target={external ? "_blank" : undefined}
          rel={external ? "noopener noreferrer" : undefined}
        >
          {about.cta_label} <span aria-hidden="true">↗</span>
        </a>
      </section>
    </article>
  );
}