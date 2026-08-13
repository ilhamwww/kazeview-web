import type { Metadata } from "next";
import { getPublicData } from "@/lib/api";
import type { ContactData, SiteLink } from "@/lib/types";

export const metadata: Metadata = {
  title: "Contact",
  description: "Contact KAZEVIEW for photography and film projects.",
};

function linkUrl(item: SiteLink): string | null {
  const value = item.url || item.link;
  return value?.startsWith("https://") || value?.startsWith("http://")
    ? value
    : null;
}

export default async function ContactPage() {
  const data = await getPublicData<ContactData>("contact");
  const { contact } = data;
  const emailUrl = contact.email ? `mailto:${contact.email}` : null;
  const primary = contact.whatsapp_url || emailUrl || "#contact";
  const external = primary.startsWith("http");
  const socials = contact.social_links
    .map((item) => ({ ...item, resolved: linkUrl(item) }))
    .filter((item) => item.resolved);

  return (
    <article className="contact-page">
      <section className="contact-hero" aria-labelledby="contact-title">
        <div className="contact-hero__content">
          <p className="contact-eyebrow">{contact.eyebrow}</p>
          <h1 id="contact-title">{contact.headline}</h1>
          {contact.intro && (
            <p className="contact-hero__intro">{contact.intro}</p>
          )}
          <a
            className="contact-primary-action"
            href={primary}
            target={external ? "_blank" : undefined}
            rel={external ? "noopener noreferrer" : undefined}
          >
            {contact.cta_label} <span aria-hidden="true">↗</span>
          </a>
        </div>
        <figure className="contact-hero__media">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img
            src={contact.image_url || "/KAZE_icon.png"}
            alt={contact.headline}
            fetchPriority="high"
          />
          <figcaption>
            <span className="contact-status-dot" aria-hidden="true" />
            {contact.availability}
          </figcaption>
        </figure>
      </section>

      <section className="contact-details" aria-labelledby="contact-details-title">
        <div className="contact-details__heading">
          <p className="contact-eyebrow">DIRECT CONTACT</p>
          <h2 id="contact-details-title">
            GET IN TOUCH<span className="accent">.</span>
          </h2>
        </div>
        <dl className="contact-details__list">
          {contact.email && (
            <div>
              <dt>EMAIL</dt>
              <dd>
                <a href={emailUrl || undefined}>{contact.email}</a>
              </dd>
            </div>
          )}
          {contact.whatsapp && (
            <div>
              <dt>WHATSAPP</dt>
              <dd>
                <a
                  href={contact.whatsapp_url || "#contact"}
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  {contact.whatsapp}
                </a>
              </dd>
            </div>
          )}
          <div>
            <dt>BASE</dt>
            <dd>{contact.location}</dd>
          </div>
          <div>
            <dt>RESPONSE</dt>
            <dd>{contact.response_time}</dd>
          </div>
        </dl>
      </section>

      {socials.length > 0 && (
        <section className="contact-socials" aria-labelledby="socials-title">
          <p className="contact-eyebrow">FOLLOW KAZEVIEW</p>
          <h2 id="socials-title" className="sr-only">
            KAZEVIEW social links
          </h2>
          <ul>
            {socials.map((item, index) => (
              <li key={`${index}-${item.resolved}`}>
                <a
                  href={item.resolved || undefined}
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  {(item.label || item.name || "SOCIAL").toUpperCase()}
                  <span aria-hidden="true">↗</span>
                </a>
              </li>
            ))}
          </ul>
        </section>
      )}
    </article>
  );
}