const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api";

function getToken(): string | null {
  if (typeof window === "undefined") return null;
  return localStorage.getItem("token");
}

async function request<T>(
  path: string,
  options: RequestInit = {}
): Promise<T> {
  const token = getToken();
  const headers: Record<string, string> = {
    Accept: "application/json",
    ...(options.headers as Record<string, string>),
  };
  if (token) headers["Authorization"] = `Bearer ${token}`;
  if (!(options.body instanceof FormData)) {
    headers["Content-Type"] = "application/json";
  }

  const res = await fetch(`${API_URL}${path}`, { ...options, headers });
  if (!res.ok) {
    const err = await res.json().catch(() => ({ message: res.statusText }));
    throw new Error(err.message ?? res.statusText);
  }
  return res.json() as Promise<T>;
}

export const api = {
  get: <T>(path: string) => request<T>(path),
  post: <T>(path: string, body: unknown) =>
    request<T>(path, {
      method: "POST",
      body: body instanceof FormData ? body : JSON.stringify(body),
    }),
  patch: <T>(path: string, body: unknown) =>
    request<T>(path, { method: "PATCH", body: JSON.stringify(body) }),
  delete: <T>(path: string) => request<T>(path, { method: "DELETE" }),
};

export type Product = {
  id: number;
  item_name: string;
  price: number;
  image_url: string | null;
  is_sold: boolean;
};

export type ProductDetail = Product & {
  description: string;
  brand: string | null;
  condition: { id: number; condition: string } | null;
  categories: { id: number; category: string }[];
  comments: Comment[];
  favorites_count: number;
  is_favorited: boolean;
  seller: { id: number; name: string; profile_image_url: string | null };
};

export type Comment = {
  id: number;
  comment: string;
  created_at: string;
  user: { id: number; name: string; profile_image_url: string | null };
};

export type User = {
  id: number;
  name: string;
  email: string;
  email_verified_at: string | null;
  profile: {
    postal_code: string;
    address: string;
    building: string;
    profile_image_url: string | null;
  } | null;
};

export type Message = {
  id: number;
  content: string | null;
  image_url: string | null;
  edited_at: string | null;
  created_at: string;
  user_id: number;
  user: { id: number; name: string; profile_image_url: string | null };
};
