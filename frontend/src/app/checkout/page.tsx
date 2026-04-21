"use client";

import { Suspense, useEffect, useState } from "react";
import { useSearchParams, useRouter } from "next/navigation";
import { api, type ProductDetail } from "@/lib/api";
import { useAuth } from "@/lib/auth";

function CheckoutContent() {
  const searchParams = useSearchParams();
  const productId = searchParams.get("product_id");
  const router = useRouter();
  const { user, loading: authLoading } = useAuth();

  const [product, setProduct] = useState<ProductDetail | null>(null);
  const [paymentMethod, setPaymentMethod] = useState<"card" | "konbini">("card");
  const [address, setAddress] = useState({
    sending_postcode: "",
    sending_address: "",
    sending_building: "",
  });
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!authLoading && !user) router.push("/login");
  }, [authLoading, user, router]);

  useEffect(() => {
    if (!productId) return;
    api
      .get<{ product: ProductDetail }>(`/products/${productId}`)
      .then((d) => setProduct(d.product))
      .catch(() => router.push("/"));
  }, [productId, router]);

  useEffect(() => {
    if (user?.profile) {
      setAddress({
        sending_postcode: user.profile.postal_code ?? "",
        sending_address: user.profile.address ?? "",
        sending_building: user.profile.building ?? "",
      });
    }
  }, [user]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      const data = await api.post<{ checkout_url: string }>("/orders", {
        product_id: Number(productId),
        payment_method: paymentMethod,
        ...address,
      });
      window.location.href = data.checkout_url;
    } catch (err) {
      setError((err as Error).message);
      setSubmitting(false);
    }
  };

  if (authLoading || !product) {
    return <p className="text-center py-12 text-gray-400">読み込み中...</p>;
  }

  return (
    <div className="max-w-lg mx-auto">
      <h1 className="text-xl font-bold mb-6">購入確認</h1>

      <div className="bg-white rounded shadow p-4 mb-6 flex gap-4 items-center">
        {product.image_url && (
          <img src={product.image_url} alt="" className="w-20 h-20 object-cover rounded" />
        )}
        <div>
          <p className="font-medium">{product.item_name}</p>
          <p className="text-indigo-600 font-bold">¥{product.price.toLocaleString()}</p>
        </div>
      </div>

      <form onSubmit={handleSubmit} className="bg-white rounded shadow p-6 space-y-4">
        {error && <p className="text-red-500 text-sm">{error}</p>}

        <div>
          <label className="text-sm font-medium block mb-2">支払い方法</label>
          <div className="flex gap-4">
            {(["card", "konbini"] as const).map((m) => (
              <label key={m} className="flex items-center gap-2 text-sm cursor-pointer">
                <input
                  type="radio"
                  value={m}
                  checked={paymentMethod === m}
                  onChange={() => setPaymentMethod(m)}
                />
                {m === "card" ? "クレジットカード" : "コンビニ払い"}
              </label>
            ))}
          </div>
        </div>

        <div>
          <label className="text-sm font-medium block mb-1">郵便番号</label>
          <input
            value={address.sending_postcode}
            onChange={(e) => setAddress((a) => ({ ...a, sending_postcode: e.target.value }))}
            required
            className="w-full border rounded px-3 py-2 text-sm"
            placeholder="123-4567"
          />
        </div>
        <div>
          <label className="text-sm font-medium block mb-1">住所</label>
          <input
            value={address.sending_address}
            onChange={(e) => setAddress((a) => ({ ...a, sending_address: e.target.value }))}
            required
            className="w-full border rounded px-3 py-2 text-sm"
          />
        </div>
        <div>
          <label className="text-sm font-medium block mb-1">建物名・部屋番号（任意）</label>
          <input
            value={address.sending_building}
            onChange={(e) => setAddress((a) => ({ ...a, sending_building: e.target.value }))}
            className="w-full border rounded px-3 py-2 text-sm"
          />
        </div>

        <button
          type="submit"
          disabled={submitting}
          className="w-full bg-orange-500 text-white py-2 rounded font-bold hover:bg-orange-600 disabled:opacity-50"
        >
          {submitting ? "処理中..." : "Stripeで支払う"}
        </button>
      </form>
    </div>
  );
}

export default function CheckoutPage() {
  return (
    <Suspense fallback={<p className="text-center py-12 text-gray-400">読み込み中...</p>}>
      <CheckoutContent />
    </Suspense>
  );
}
