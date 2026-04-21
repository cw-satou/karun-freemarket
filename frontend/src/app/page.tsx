"use client";

import { Suspense, useEffect, useState } from "react";
import { useSearchParams, useRouter } from "next/navigation";
import Link from "next/link";
import { api, type Product } from "@/lib/api";
import { useAuth } from "@/lib/auth";

function HomeContent() {
  const { user } = useAuth();
  const searchParams = useSearchParams();
  const router = useRouter();
  const tab = searchParams.get("tab") ?? "recommend";
  const keyword = searchParams.get("keyword") ?? "";

  const [products, setProducts] = useState<Product[]>([]);
  const [search, setSearch] = useState(keyword);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    setLoading(true);
    api
      .get<{ products: Product[] }>(
        `/products?tab=${tab}&keyword=${encodeURIComponent(keyword)}`
      )
      .then((d) => setProducts(d.products))
      .catch(console.error)
      .finally(() => setLoading(false));
  }, [tab, keyword]);

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    const params = new URLSearchParams({ tab, keyword: search });
    router.push(`/?${params}`);
  };

  return (
    <div>
      <form onSubmit={handleSearch} className="flex gap-2 mb-6">
        <input
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          placeholder="商品を検索..."
          className="flex-1 border rounded px-3 py-2 text-sm"
        />
        <button className="bg-indigo-600 text-white px-4 py-2 rounded text-sm hover:bg-indigo-700">
          検索
        </button>
      </form>

      {user && (
        <div className="flex gap-4 mb-6 border-b">
          {["recommend", "mylist"].map((t) => (
            <button
              key={t}
              onClick={() => router.push(`/?tab=${t}`)}
              className={`pb-2 text-sm font-medium border-b-2 ${
                tab === t
                  ? "border-indigo-600 text-indigo-600"
                  : "border-transparent text-gray-500"
              }`}
            >
              {t === "recommend" ? "おすすめ" : "マイリスト"}
            </button>
          ))}
        </div>
      )}

      {loading ? (
        <p className="text-center text-gray-400 py-12">読み込み中...</p>
      ) : products.length === 0 ? (
        <p className="text-center text-gray-400 py-12">商品がありません</p>
      ) : (
        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
          {products.map((p) => (
            <Link
              key={p.id}
              href={`/products/${p.id}`}
              className="group block rounded overflow-hidden border bg-white hover:shadow-md transition"
            >
              <div className="relative aspect-square bg-gray-100">
                {p.image_url ? (
                  <img
                    src={p.image_url}
                    alt={p.item_name}
                    className="w-full h-full object-cover"
                  />
                ) : (
                  <div className="w-full h-full flex items-center justify-center text-gray-300 text-4xl">
                    📦
                  </div>
                )}
                {p.is_sold && (
                  <div className="absolute inset-0 bg-black/50 flex items-center justify-center">
                    <span className="text-white font-bold text-lg">SOLD</span>
                  </div>
                )}
              </div>
              <div className="p-2">
                <p className="text-sm font-medium truncate">{p.item_name}</p>
                <p className="text-sm text-indigo-600 font-bold">
                  ¥{p.price.toLocaleString()}
                </p>
              </div>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}

export default function HomePage() {
  return (
    <Suspense fallback={<p className="text-center text-gray-400 py-12">読み込み中...</p>}>
      <HomeContent />
    </Suspense>
  );
}
