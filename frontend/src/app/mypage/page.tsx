"use client";

import { useEffect, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import Link from "next/link";
import { api } from "@/lib/api";
import { useAuth } from "@/lib/auth";

type MypageData = {
  user: {
    id: number;
    name: string;
    email: string;
    profile_image_url: string | null;
    postal_code: string | null;
    address: string | null;
    building: string | null;
  };
  average_rating: number | null;
  total_unread_count: number;
  view_type: string;
  products?: { id: number; item_name: string; price: number; is_sold: boolean; image_url: string | null }[];
  orders?: { id: number; product: { id: number; item_name: string; price: number; image_url: string | null }; created_at: string }[];
  transactions?: { id: number; status: string; unread_count: number; product: { id: number; item_name: string; image_url: string | null } }[];
};

export default function MypagePage() {
  const { user, loading: authLoading } = useAuth();
  const router = useRouter();
  const searchParams = useSearchParams();
  const viewType = searchParams.get("page") ?? "sell";
  const [data, setData] = useState<MypageData | null>(null);

  useEffect(() => {
    if (!authLoading && !user) router.push("/login");
  }, [authLoading, user, router]);

  useEffect(() => {
    if (!user) return;
    api
      .get<MypageData>(`/mypage?page=${viewType}`)
      .then(setData)
      .catch(console.error);
  }, [user, viewType]);

  if (authLoading || !data) return <p className="text-center py-12 text-gray-400">読み込み中...</p>;

  const tabs = [
    { key: "sell", label: "出品した商品" },
    { key: "buy", label: "購入した商品" },
    { key: "trading", label: `取引中${data.total_unread_count > 0 ? ` (${data.total_unread_count})` : ""}` },
  ];

  return (
    <div>
      <div className="flex items-center gap-4 mb-6">
        <div className="w-16 h-16 rounded-full bg-gray-200 overflow-hidden">
          {data.user.profile_image_url ? (
            <img src={data.user.profile_image_url} alt="" className="w-full h-full object-cover" />
          ) : (
            <span className="flex items-center justify-center h-full text-2xl">👤</span>
          )}
        </div>
        <div>
          <p className="font-bold text-lg">{data.user.name}</p>
          {data.average_rating && (
            <p className="text-sm text-yellow-500">{"★".repeat(data.average_rating)}{"☆".repeat(5 - data.average_rating)}</p>
          )}
        </div>
        <Link href="/mypage/profile" className="ml-auto text-sm text-indigo-600 hover:underline">
          プロフィール編集
        </Link>
      </div>

      <div className="flex gap-4 border-b mb-6">
        {tabs.map((t) => (
          <button
            key={t.key}
            onClick={() => router.push(`/mypage?page=${t.key}`)}
            className={`pb-2 text-sm font-medium border-b-2 ${
              viewType === t.key ? "border-indigo-600 text-indigo-600" : "border-transparent text-gray-500"
            }`}
          >
            {t.label}
          </button>
        ))}
      </div>

      {viewType === "sell" && data.products && (
        <div className="grid grid-cols-2 sm:grid-cols-3 gap-4">
          {data.products.map((p) => (
            <Link key={p.id} href={`/products/${p.id}`} className="border rounded bg-white overflow-hidden hover:shadow">
              <div className="aspect-square bg-gray-100">
                {p.image_url ? (
                  <img src={p.image_url} alt={p.item_name} className="w-full h-full object-cover" />
                ) : (
                  <div className="w-full h-full flex items-center justify-center text-gray-300 text-4xl">📦</div>
                )}
              </div>
              <div className="p-2">
                <p className="text-sm truncate">{p.item_name}</p>
                <p className="text-sm text-indigo-600 font-bold">¥{p.price.toLocaleString()}</p>
                {p.is_sold && <span className="text-xs text-red-500">SOLD</span>}
              </div>
            </Link>
          ))}
        </div>
      )}

      {viewType === "buy" && data.orders && (
        <div className="space-y-3">
          {data.orders.map((o) => (
            <div key={o.id} className="flex gap-4 bg-white border rounded p-3">
              {o.product.image_url && (
                <img src={o.product.image_url} alt="" className="w-16 h-16 object-cover rounded" />
              )}
              <div>
                <p className="text-sm font-medium">{o.product.item_name}</p>
                <p className="text-sm text-indigo-600 font-bold">¥{o.product.price.toLocaleString()}</p>
                <p className="text-xs text-gray-400">{new Date(o.created_at).toLocaleDateString("ja-JP")}</p>
              </div>
            </div>
          ))}
        </div>
      )}

      {viewType === "trading" && data.transactions && (
        <div className="space-y-3">
          {data.transactions.map((t) => (
            <Link key={t.id} href={`/chat/${t.id}`} className="flex gap-4 bg-white border rounded p-3 hover:shadow">
              {t.product.image_url && (
                <img src={t.product.image_url} alt="" className="w-16 h-16 object-cover rounded" />
              )}
              <div className="flex-1">
                <p className="text-sm font-medium">{t.product.item_name}</p>
              </div>
              {t.unread_count > 0 && (
                <span className="bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center flex-shrink-0">
                  {t.unread_count}
                </span>
              )}
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}
