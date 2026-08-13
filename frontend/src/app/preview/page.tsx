import type { Metadata } from "next";
import PreviewBrowser from "@/components/PreviewBrowser";
import { getPublicData } from "@/lib/api";
import type { PreviewData } from "@/lib/types";

export const metadata: Metadata = {
  title: "Preview",
  description:
    "Find, preview, and access KAZEVIEW event photography and films.",
};

export default async function PreviewPage() {
  const data = await getPublicData<PreviewData>("preview");

  return (
    <article className="preview-page">
      <section className="preview-intro">
        <div className="preview-intro__heading">
          <p className="preview-eyebrow">KAZEVIEW EVENT ARCHIVE</p>
          <h1>
            FIND YOUR
            <br />
            MOMENT<span className="accent">.</span>
          </h1>
          <p>
            Browse recent events, preview your photographs, and access the
            frames made for you.
          </p>
        </div>

        <div className="preview-process" aria-label="How event access works">
          {[
            ["01", "SELECT EVENT"],
            ["02", "PREVIEW PHOTOS"],
            ["03", "PAY & DOWNLOAD"],
          ].map(([number, label]) => (
            <div className="process-step" key={number}>
              <span className="process-step__number">{number}</span>
              <span className="process-step__label">{label}</span>
            </div>
          ))}
        </div>
      </section>

      <PreviewBrowser
        contents={data.contents}
        categories={data.categories}
        site={data.site}
      />
    </article>
  );
}