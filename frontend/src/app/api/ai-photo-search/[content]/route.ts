import { NextRequest, NextResponse } from "next/server";

export const runtime = "nodejs";

const LARAVEL_ORIGIN = (
  process.env.LARAVEL_API_URL ??
  process.env.NEXT_PUBLIC_LARAVEL_URL ??
  "http://127.0.0.1:8000"
).replace(/\/+$/, "");

const MAX_REQUEST_BYTES = 9 * 1024 * 1024;

type Context = {
  params: Promise<{ content: string }>;
};

export async function POST(request: NextRequest, { params }: Context) {
  const { content } = await params;

  if (!/^\d+$/.test(content)) {
    return NextResponse.json({ message: "Invalid content ID." }, { status: 400 });
  }

  const contentLength = Number(request.headers.get("content-length") ?? "0");

  if (Number.isFinite(contentLength) && contentLength > MAX_REQUEST_BYTES) {
    return NextResponse.json(
      { message: "The photo must be smaller than 8 MB." },
      { status: 413 },
    );
  }

  let form: FormData;

  try {
    form = await request.formData();
  } catch {
    return NextResponse.json(
      { message: "The uploaded form is invalid." },
      { status: 400 },
    );
  }

  const photo = form.get("photo");

  if (!(photo instanceof File)) {
    return NextResponse.json(
      { message: "Choose a motorcycle photo first." },
      { status: 422 },
    );
  }

  if (photo.size > 8 * 1024 * 1024) {
    return NextResponse.json(
      { message: "The photo must be smaller than 8 MB." },
      { status: 422 },
    );
  }

  if (!["image/jpeg", "image/png", "image/webp"].includes(photo.type)) {
    return NextResponse.json(
      { message: "Use a JPEG, PNG, or WebP image." },
      { status: 422 },
    );
  }

  const upstreamForm = new FormData();
  upstreamForm.append("photo", photo, photo.name);

  try {
    const response = await fetch(
      `${LARAVEL_ORIGIN}/api/v1/public/preview/${content}/ai-photo-search`,
      {
        method: "POST",
        headers: { Accept: "application/json" },
        body: upstreamForm,
        cache: "no-store",
        signal: AbortSignal.timeout(90_000),
      },
    );

    const body = await response.text();

    return new NextResponse(body, {
      status: response.status,
      headers: {
        "Content-Type":
          response.headers.get("content-type") ?? "application/json",
        "Cache-Control": "no-store",
      },
    });
  } catch {
    return NextResponse.json(
      { message: "Photo search service is unavailable. Please try again." },
      {
        status: 503,
        headers: { "Cache-Control": "no-store" },
      },
    );
  }
}