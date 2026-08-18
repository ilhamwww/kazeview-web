import {
  faArrowLeft,
  faArrowRight,
} from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import type { Metadata } from "next";
import Link from "next/link";
import { notFound, redirect } from "next/navigation";
import AiPhotoSearch from "@/components/AiPhotoSearch";
import PhotoGallery from "@/components/PhotoGallery";
import { getPublicData, PublicApiError } from "@/lib/api";
import type { PreviewDetailData } from "@/lib/types";

type Props = {
  params: Promise<{ content: string }>;
  searchParams: Promise<{
    folder?: string | string[];
    brand?: string | string[];
    page?: string | string[];
  }>;
};

async function detail(
  content: string,
  search: Awaited<Props["searchParams"]>,
): Promise<PreviewDetailData> {
  const query = new URLSearchParams();
  const folder = Array.isArray(search.folder) ? search.folder[0] : search.folder;
  const brand = Array.isArray(search.brand) ? search.brand[0] : search.brand;
  const page = Array.isArray(search.page) ? search.page[0] : search.page;
  if (folder && /^\d+$/.test(folder)) query.set("folder", folder);
  if (brand && /^[a-z0-9-]+$/i.test(brand)) query.set("brand", brand);
  if (page && /^\d+$/.test(page)) query.set("page", page);

  try {
    return await getPublicData<PreviewDetailData>(
      `preview/${encodeURIComponent(content)}`,
      query,
    );
  } catch (error) {
    if (error instanceof PublicApiError && error.status === 404) notFound();
    throw error;
  }
}

export async function generateMetadata({ params, searchParams }: Props) {
  const { content } = await params;
  const data = await detail(content, await searchParams);
  return {
    title: data.content.title || "Event Preview",
    description:
      data.content.body ||
      `Preview ${data.content.title || "KAZEVIEW event"} photography.`,
  } satisfies Metadata;
}

function href(
  content: string,
  folder: number | null,
  brand = "all",
  page?: number,
): string {
  const query = new URLSearchParams();
  if (folder) query.set("folder", String(folder));
  if (brand !== "all") query.set("brand", brand);
  if (page && page > 1) query.set("page", String(page));
  return `/preview/${content}${query.size ? `?${query}` : ""}`;
}

export default async function PreviewDetailPage({
  params,
  searchParams,
}: Props) {
  const { content: contentId } = await params;
  const data = await detail(contentId, await searchParams);

  if (data.type === "redirect") redirect(data.redirect_url);

  const event = data.content;
  const current = data.photos.current_page;
  const last = data.photos.last_page;

  return (
    <article className="preview-detail-page">
      <header className="preview-detail-hero">
        {event.image_url && (
          // eslint-disable-next-line @next/next/no-img-element
          <img
            src={event.image_url}
            alt={`${event.title || "KAZEVIEW event"} cover`}
            style={{ objectPosition: event.image_position || "50% 50%" }}
            fetchPriority="high"
          />
        )}
        <span className="preview-detail-hero__overlay" aria-hidden="true" />
        <div className="preview-detail-hero__content">
          <Link href="/preview" className="preview-detail-back">
            <FontAwesomeIcon icon={faArrowLeft} aria-hidden="true" /> ALL EVENTS
          </Link>
          <p className="preview-eyebrow">
            {event.preview_type} / {event.event_date || "KAZEVIEW"}
          </p>
          <h1>{(event.title || "UNTITLED EVENT").toUpperCase()}</h1>
          <div className="preview-detail-meta">
            {event.location && <span>{event.location.toUpperCase()}</span>}
            <span>{data.photos.total} PHOTOS</span>
          </div>
        </div>
      </header>

      {event.ai_photo_search_enabled && (
        <AiPhotoSearch contentId={event.id} />
      )}

      {data.folders.length > 0 && (
        <nav className="preview-folder-nav" aria-label="Gallery folders">
          <Link
            className={data.selected_folder_id === null ? "is-active" : ""}
            href={href(contentId, null, data.selected_brand)}
          >
            ALL PHOTOS
          </Link>
          {data.folders
            .filter((folder) => folder.depth === 1)
            .map((folder) => (
              <Link
                key={folder.id}
                className={
                  data.selected_folder_id === folder.id ? "is-active" : ""
                }
                href={href(contentId, folder.id, data.selected_brand)}
              >
                {folder.name.toUpperCase()} ({folder.total_image_count})
              </Link>
            ))}
        </nav>
      )}

      <nav className="preview-brand-nav" aria-label="Motorcycle brands">
        {data.brand_filters.map((brand) => (
          <Link
            key={brand.slug}
            className={data.selected_brand === brand.slug ? "is-active" : ""}
            href={href(contentId, data.selected_folder_id, brand.slug)}
            aria-current={
              data.selected_brand === brand.slug ? "page" : undefined
            }
          >
            <span>{brand.label}</span>
            <strong>{brand.count}</strong>
          </Link>
        ))}
      </nav>

      <section className="preview-gallery" aria-label="Event photos">
        {data.photos.data.length ? (
          <PhotoGallery photos={data.photos.data} />
        ) : (
          <p className="preview-gallery-empty">NO PHOTOS FOUND.</p>
        )}
      </section>

      {last > 1 && (
        <nav className="preview-pagination" aria-label="Photo pages">
          {current > 1 && (
            <Link
              href={href(
                contentId,
                data.selected_folder_id,
                data.selected_brand,
                current - 1,
              )}
            >
              <FontAwesomeIcon icon={faArrowLeft} aria-hidden="true" /> PREVIOUS
            </Link>
          )}
          <span>
            PAGE {current} / {last}
          </span>
          {current < last && (
            <Link
              href={href(
                contentId,
                data.selected_folder_id,
                data.selected_brand,
                current + 1,
              )}
            >
              NEXT <FontAwesomeIcon icon={faArrowRight} aria-hidden="true" />
            </Link>
          )}
        </nav>
      )}
    </article>
  );
}