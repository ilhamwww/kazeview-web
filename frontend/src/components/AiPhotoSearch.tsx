"use client";

import { FormEvent, useEffect, useRef, useState } from "react";
import PhotoGallery from "@/components/PhotoGallery";
import type { AiPhotoSearchData, ApiEnvelope } from "@/lib/types";

type ErrorPayload = {
  message?: string;
  errors?: Record<string, string[]>;
  reference?: string;
};

export default function AiPhotoSearch({ contentId }: { contentId: number }) {
  const [photo, setPhoto] = useState<File | null>(null);
  const [preview, setPreview] = useState<string | null>(null);
  const [result, setResult] = useState<AiPhotoSearchData | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const previewRef = useRef<string | null>(null);

  useEffect(
    () => () => {
      if (previewRef.current) URL.revokeObjectURL(previewRef.current);
    },
    [],
  );

  function selectPhoto(file: File | null) {
    if (previewRef.current) URL.revokeObjectURL(previewRef.current);

    const url = file ? URL.createObjectURL(file) : null;
    previewRef.current = url;
    setPhoto(file);
    setPreview(url);
    setError(null);
    setResult(null);
  }

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);
    setResult(null);

    if (!photo) {
      setError("Choose a motorcycle photo first.");
      return;
    }

    if (photo.size > 8 * 1024 * 1024) {
      setError("The photo must be smaller than 8 MB.");
      return;
    }

    if (!["image/jpeg", "image/png", "image/webp"].includes(photo.type)) {
      setError("Use a JPEG, PNG, or WebP image.");
      return;
    }

    const form = new FormData();
    form.append("photo", photo);

    setLoading(true);

    try {
      const response = await fetch(
        `/api/ai-photo-search/${contentId}`,
        {
          method: "POST",
          headers: { Accept: "application/json" },
          body: form,
          signal: AbortSignal.timeout(90_000),
        },
      );

      const payload = (await response.json()) as
        | ApiEnvelope<AiPhotoSearchData>
        | ErrorPayload;

      if (!response.ok || !("data" in payload)) {
        const validationMessage =
          "errors" in payload
            ? Object.values(payload.errors ?? {}).flat()[0]
            : undefined;
        const responseMessage =
          "message" in payload ? payload.message : undefined;

        throw new Error(
          validationMessage ||
            responseMessage ||
            "Photo search failed. Please try again.",
        );
      }

      setResult(payload.data);
    } catch (reason) {
      setError(
        reason instanceof Error
          ? reason.message
          : "Photo search failed. Please try again.",
      );
    } finally {
      setLoading(false);
    }
  }

  return (
    <section className="ai-photo-search" aria-labelledby="ai-search-title">
      <div className="ai-photo-search__intro">
        <p className="preview-eyebrow">AI PHOTO SEARCH</p>
        <h2 id="ai-search-title">FIND YOUR MOTORCYCLE</h2>
        <p>
          Upload a clear photo of your motorcycle. We will show possible
          matches from this event; some unrelated photos may appear.
        </p>
      </div>

      <form className="ai-photo-search__form" onSubmit={submit}>
        <label className="ai-photo-search__picker">
          <span>{photo ? photo.name : "CHOOSE MOTORCYCLE PHOTO"}</span>
          <input
            type="file"
            accept="image/jpeg,image/png,image/webp"
            disabled={loading}
            onChange={(event) =>
              selectPhoto(event.target.files?.[0] ?? null)
            }
          />
        </label>

        {preview && (
          // eslint-disable-next-line @next/next/no-img-element
          <img
            className="ai-photo-search__preview"
            src={preview}
            alt="Selected motorcycle"
          />
        )}

        <button
          className="ai-photo-search__submit"
          type="submit"
          disabled={!photo || loading}
        >
          {loading ? "SEARCHING…" : "FIND POSSIBLE MATCHES"}
        </button>
      </form>

      <p className="ai-photo-search__privacy">
        The uploaded file is processed temporarily and is not added to the
        event gallery.
      </p>

      <div aria-live="polite">
        {error && <p className="ai-photo-search__error">{error}</p>}
        {loading && (
          <p className="ai-photo-search__status">
            Analyzing the motorcycle and comparing event photos…
          </p>
        )}
      </div>

      {result && (
        <div className="ai-photo-search__results">
          <div className="ai-photo-search__results-heading">
            <h3>{result.label.toUpperCase()}</h3>
            <span>{result.matches.length} PHOTOS</span>
          </div>
          {result.matches.length > 0 ? (
            <PhotoGallery photos={result.matches} />
          ) : (
            <p className="preview-gallery-empty">NO POSSIBLE MATCHES FOUND.</p>
          )}
        </div>
      )}
    </section>
  );
}