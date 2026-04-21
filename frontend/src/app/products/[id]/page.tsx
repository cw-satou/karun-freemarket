"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { api, type ProductDetail } from "@/lib/api";
import { useAuth } from "@/lib/auth";

export default function ProductPage() {
  const { id } = useParams<{ id: string }>();
  const router = useRouter();
  const { user } = useAuth();
  const [product, setProduct] = useState<ProductDetail | null>(null);
  const [comment, setComment] = useState("");
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);

  const fetchProduct = () =>
    api
      .get<{ product: ProductDetail }>(`/products/${id}`)
      .then((d) => setProduct(d.product))
      .catch(console.error)
      .finally(() => setLoading(false));

  useEffect(() => {
    fetchProduct();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [id]);

  const toggleFavorite = async () => {
    if (!user) { router.push("/login"); return; }
    const d = await api.post<{ favorited: boolean; favorites_count: number }>(
      `/products/${id}/favorite`,
      {}
    );
    setProduct((p) =>
      p ? { ...p, is_favorited: d.favorited, favorites_count: d.favorites_count } : p
    );
  };

  const postComment = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!user) { router.push("/login"); return; }
    setSubmitting(true);
    try {
      await api.post(`/products/${id}/comments`, { comment });
      setComment("");
      fetchProduct();
    } catch (err) {
      alert((err as Error).message);
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) return <p className="text-center py-12 text-gray-400">読み込み中...</p>;
  if (!product) return <p className="text-center py-12">商品が見つかりません</p>;

  return (
    <div className="max-w-3xl mx-auto">
      <div className="grid md:grid-cols-2 gap-6 mb-8">
        <div className="aspect-square bg-gray-100 rounded overflow-hidden">
          {product.image_url ? (
            <img src={product.image_url} alt={product.item_name} className="w-full h-full object-cover" />
          ) : (
            <div className="w-full h-full flex items-center justify-center text-gray-300 text-6xl">📦</div>
          )}
        </div>
        <div className="flex flex-col gap-3">
          <h1 className="text-xl font-bold">{product.item_name}</h1>
          <p className="text-2xl text-indigo-600 font-bold">¥{product.price.toLocaleString()}</p>

          <div className="flex items-center gap-3 text-sm text-gray-500">
            <button onClick={toggleFavorite} className="flex items-center gap-1 hover:text-red-500">
              <span>{product.is_favorited ? "❤️" : "🤍"}</span>
              <span>{product.favorites_count}</span>
            </button>
            <span>💬 {product.comments.length}</span>
          </div>

          {product.condition && (
            <p className="text-sm text-gray-600">状態: {product.condition.condition}</p>
          )}
          {product.brand && (
            <p className="text-sm text-gray-600">ブランド: {product.brand}</p>
          )}
          {product.categories.length > 0 && (
            <div className="flex flex-wrap gap-1">
              {product.categories.map((c) => (
                <span key={c.id} className="text-xs bg-gray-100 rounded px-2 py-1">
                  {c.category}
                </span>
              ))}
            </div>
          )}

          <p className="text-sm text-gray-700 whitespace-pre-wrap">{product.description}</p>

          {!product.is_sold ? (
            <button
              onClick={() => router.push(`/checkout?product_id=${product.id}`)}
              className="mt-auto bg-orange-500 text-white py-2 rounded font-bold hover:bg-orange-600"
            >
              購入する
            </button>
          ) : (
            <button disabled className="mt-auto bg-gray-300 text-white py-2 rounded font-bold cursor-not-allowed">
              SOLD OUT
            </button>
          )}

          <div className="flex items-center gap-2 text-sm text-gray-600 border-t pt-3">
            {product.seller.profile_image_url ? (
              <img src={product.seller.profile_image_url} alt="" className="w-8 h-8 rounded-full object-cover" />
            ) : (
              <div className="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs">👤</div>
            )}
            <span>{product.seller.name}</span>
          </div>
        </div>
      </div>

      <section>
        <h2 className="text-base font-bold mb-4">コメント ({product.comments.length})</h2>
        <div className="space-y-3 mb-6">
          {product.comments.map((c) => (
            <div key={c.id} className="flex gap-3">
              <div className="w-8 h-8 rounded-full bg-gray-200 flex-shrink-0 overflow-hidden">
                {c.user.profile_image_url ? (
                  <img src={c.user.profile_image_url} alt="" className="w-full h-full object-cover" />
                ) : (
                  <span className="flex items-center justify-center h-full text-xs">👤</span>
                )}
              </div>
              <div>
                <p className="text-xs text-gray-500 font-medium">{c.user.name}</p>
                <p className="text-sm">{c.comment}</p>
              </div>
            </div>
          ))}
        </div>

        {user && (
          <form onSubmit={postComment} className="flex gap-2">
            <input
              value={comment}
              onChange={(e) => setComment(e.target.value)}
              placeholder="コメントを入力..."
              className="flex-1 border rounded px-3 py-2 text-sm"
              required
            />
            <button
              type="submit"
              disabled={submitting}
              className="bg-indigo-600 text-white px-4 py-2 rounded text-sm hover:bg-indigo-700 disabled:opacity-50"
            >
              送信
            </button>
          </form>
        )}
      </section>
    </div>
  );
}
