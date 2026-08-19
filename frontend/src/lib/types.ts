export interface ApiEnvelope<T> {
  data: T;
}

export interface SiteLink {
  label?: string | null;
  name?: string | null;
  url?: string | null;
  link?: string | null;
}

export interface SiteSeo {
  title: string | null;
  description: string | null;
  keywords: string | null;
  canonical_url: string | null;
  robots: string;
  author: string | null;
  theme_color: string;
  og_title: string | null;
  og_description: string | null;
  twitter_card: string;
  twitter_title: string | null;
  twitter_description: string | null;
  google_verification: string | null;
  bing_verification: string | null;
  same_as: string[];
  og_image_url: string | null;
}

export interface Site {
  name: string;
  description: string | null;
  logo_url: string | null;
  hero_image_url: string | null;
  whatsapp: string | null;
  links: SiteLink[];
  seo: SiteSeo;
}

export interface CollectionItem {
  id: number;
  title: string | null;
  subtitle: string | null;
  category: string | null;
  media_type: string | null;
  image_url: string | null;
  video_url: string | null;
  link: string | null;
  duration: string | null;
  project_year: number | null;
  image_position: string;
  is_active: boolean;
  is_featured: boolean;
  sort_order: number | null;
}

export interface CollectionCategory {
  id: number;
  name: string;
  slug: string;
}

export interface FilterCategory {
  id: number;
  name: string;
  slug: string;
}

export interface PreviewContent {
  id: number;
  title: string | null;
  body: string | null;
  location: string | null;
  event_date: string | null;
  media_type: "FOTO" | "VIDEO";
  preview_type: string;
  film_duration: string | null;
  image_url: string | null;
  image_position: string;
  is_active: boolean;
  is_new: boolean;
  is_price_enabled: boolean;
  ai_photo_search_enabled: boolean;
  ai_photo_search_status: string | null;
  price: number | null;
  redirect_url: string | null;
  categories: FilterCategory[];
}

export interface BootstrapData {
  site: Site;
  routes: Record<string, string>;
}

export interface HomeData {
  site: Site;
  collections: CollectionItem[];
  categories: CollectionCategory[];
}

export interface FilmsData {
  site: Site;
  films: CollectionItem[];
}

export interface AboutCapability {
  title?: string | null;
  description?: string | null;
}

export interface AboutData {
  site: Site;
  about: {
    eyebrow: string;
    headline: string;
    intro: string | null;
    story_title: string;
    story_body: string | null;
    capabilities: AboutCapability[];
    location: string;
    established: string | null;
    cta_title: string;
    cta_label: string;
    cta_url: string | null;
    hero_image_url: string | null;
    story_image_url: string | null;
    stats: unknown[];
  };
}

export interface ContactData {
  site: Site;
  contact: {
    eyebrow: string;
    headline: string;
    intro: string | null;
    email: string | null;
    whatsapp: string | null;
    whatsapp_url: string | null;
    whatsapp_message: string | null;
    location: string;
    availability: string;
    response_time: string;
    cta_label: string;
    social_links: SiteLink[];
    image_url: string | null;
  };
}

export interface PreviewData {
  site: Site;
  categories: FilterCategory[];
  contents: PreviewContent[];
}

export interface GalleryFolder {
  id: number;
  parent_id: number | null;
  name: string;
  relative_path: string;
  depth: number;
  image_count: number;
  total_image_count: number;
}

export interface GalleryPhoto {
  id: number;
  name: string;
  url: string;
  score?: number;
  confidence?: "high" | "medium" | "low";
  folder: {
    id: number;
    name: string;
    relative_path: string;
  } | null;
}

export interface GalleryBrandFilter {
  slug: string;
  label: string;
  count: number;
}

export interface GalleryDetailData {
  type: "gallery";
  site: Site;
  content: PreviewContent;
  folders: GalleryFolder[];
  selected_folder_id: number | null;
  selected_brand: string;
  brand_filters: GalleryBrandFilter[];
  photos: {
    data: GalleryPhoto[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface RedirectDetailData {
  type: "redirect";
  redirect_url: string;
  content: PreviewContent;
}

export interface AiPhotoSearchData {
  content_id: number;
  label: string;
  matches: GalleryPhoto[];
}

export type PreviewDetailData = GalleryDetailData | RedirectDetailData;
