"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { api } from "@/lib/api";
import { useAuth } from "@/lib/auth";

type FormData = {
  categories: { id: number; category: string }[];
  conditions: { id: number; condition: string }[];
};

export default function SellPage() {
  const { user, loading: authLoading } = useAuth();
  const router = useRouter();
  const [formData, setFormData] = useState<FormData | null>(null);
  const [form, setForm] = useState({
    item_name: "",
    description: "",
    price: "",
    condition_id: "",
    brand: "",
    categories: [] as number[],
  });
  const [imageFile, setImageFile] = useState<File | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!authLoading && !user) router.push("/login");
  }, [authLoading, user, router]);

  useEffect(() => {
    api.get<FormData>("/sell/form-data").then(setFormData).catch(console.error);
  }, []);

  const set = (field: string) => (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) =>
    setForm((f) => ({ ...f, [field]: e.target.value }));

  const toggleCategory = (id: number) =>
    setForm((f) => ({
      ...f,
      categories: f.categories.includes(id)
        ? f.categories.filter((c) => c !== id)
        : [...f.categories, id],
    }));

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      const body = new FormData();
      Object.entries(form).forEach(([k, v]) => {
        if (k === "categories") {
          (v as number[]).forEach((id) => body.append("categories[]", String(id)));
        } else {
          body.append(k, String(v));
        }
      });
      if (imageFile) body.append("image", imageFile);
      await api.post("/sell", body);
      router.push("/");
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setSubmitting(false);
    }
  };

  if (authLoading || !formData) return <p className="text-center py-12 text-gray-400">読み込み中...</p>;

  return (
    <div className="max-w-lg mx-auto">
      <h1 className="text-xl font-bold mb-6">商品を出品する</h1>
      <form onSubmit={handleSubmit} className="bg-white rounded shadow p-6 space-y-4">
        {error && <p className="text-red-500 text-sm">{error}</p>}

        <div>
          <label className="text-sm font-medium block mb-1">商品画像</label>
          <label className="block w-full aspect-video bg-gray-100 rounded cursor-pointer flex items-center justify-center text-gray-400 hover:bg-gray-200 overflow-hidden">
            {imageFile ? (
              <img src={URL.createObjectURL(imageFile)} alt="" className="w-full h-full object-cover" />
            ) : (
              <span className="text-4xl">📷</span>
            )}
            <input type="file" accept="image/*" className="hidden" onChange={(e) => setImageFile(e.target.files?.[0] ?? null)} />
          </label>
        </div>

        <div>
          <label className="text-sm font-medium block mb-1">商品名 *</label>
          <input value={form.item_name} onChange={set("item_name")} required className="w-full border rounded px-3 py-2 text-sm" />
        </div>

        <div>
          <label className="text-sm font-medium block mb-1">ブランド</label>
          <input value={form.brand} onChange={set("brand")} className="w-full border rounded px-3 py-2 text-sm" />
        </div>

        <div>
          <label className="text-sm font-medium block mb-1">説明 *</label>
          <textarea value={form.description} onChange={set("description")} required rows={4} className="w-full border rounded px-3 py-2 text-sm" />
        </div>

        <div>
          <label className="text-sm font-medium block mb-1">状態 *</label>
          <select value={form.condition_id} onChange={set("condition_id")} required className="w-full border rounded px-3 py-2 text-sm">
            <option value="">選択してください</option>
            {formData.conditions.map((c) => (
              <option key={c.id} value={c.id}>{c.condition}</option>
            ))}
          </select>
        </div>

        <div>
          <label className="text-sm font-medium block mb-2">カテゴリー</label>
          <div className="flex flex-wrap gap-2">
            {formData.categories.map((c) => (
              <button
                key={c.id}
                type="button"
                onClick={() => toggleCategory(c.id)}
                className={`px-3 py-1 rounded text-xs border transition ${
                  form.categories.includes(c.id)
                    ? "bg-indigo-600 text-white border-indigo-600"
                    : "bg-white text-gray-600 border-gray-300"
                }`}
              >
                {c.category}
              </button>
            ))}
          </div>
        </div>

        <div>
          <label className="text-sm font-medium block mb-1">価格 (円) *</label>
          <input type="number" value={form.price} onChange={set("price")} required min={0} className="w-full border rounded px-3 py-2 text-sm" />
        </div>

        <button
          type="submit"
          disabled={submitting}
          className="w-full bg-orange-500 text-white py-2 rounded font-bold hover:bg-orange-600 disabled:opacity-50"
        >
          {submitting ? "出品中..." : "出品する"}
        </button>
      </form>
    </div>
  );
}
