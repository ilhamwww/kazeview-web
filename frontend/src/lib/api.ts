import "server-only";

import type { ApiEnvelope } from "@/lib/types";

const API_ORIGIN = (
  process.env.LARAVEL_API_URL ??
  process.env.NEXT_PUBLIC_LARAVEL_URL ??
  "http://127.0.0.1:8000"
).replace(/\/+$/, "");

export class PublicApiError extends Error {
  constructor(
    public readonly status: number,
    message: string,
  ) {
    super(message);
    this.name = "PublicApiError";
  }
}

export async function getPublicData<T>(
  path: string,
  query?: URLSearchParams,
): Promise<T> {
  const normalizedPath = path.replace(/^\/+/, "");
  const url = new URL(`/api/v1/public/${normalizedPath}`, API_ORIGIN);

  if (query) {
    query.forEach((value, key) => url.searchParams.set(key, value));
  }

  const response = await fetch(url, {
    headers: {
      Accept: "application/json",
    },
    next: {
      revalidate: 30,
    },
    signal: AbortSignal.timeout(15_000),
  });

  if (!response.ok) {
    throw new PublicApiError(
      response.status,
      `Laravel public API returned ${response.status}.`,
    );
  }

  const payload = (await response.json()) as ApiEnvelope<T>;

  if (!payload || typeof payload !== "object" || !("data" in payload)) {
    throw new PublicApiError(502, "Laravel public API response is invalid.");
  }

  return payload.data;
}

export function laravelPublicUrl(path: string): string {
  return new URL(path, `${API_ORIGIN}/`).toString();
}