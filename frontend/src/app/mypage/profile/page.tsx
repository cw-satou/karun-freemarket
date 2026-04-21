"use client";

import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import { api } from "@/lib/api";
import { useAuth } from "@/lib/auth";

export default function ProfileEditPage() {
  const { user, loading: authLoading, refresh } = useAuth();
  const router = useRouter();
  const [form, setForm] = useState({
    name: "",
    postal_code: "",
    address: "",
    building: "",
  });
  const [imageFile, setImageFile] = useState<File | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [message, setMessage] = useState<string | null>(null);

  useEffect(() => {
    if (!authLoading && !user) router.push("/login");
  }, [authLoading, user, router]);

  useEffect(() => {
    if (user) {
      setForm({
        name: user.name,
        postal_code: user.profile?.postal_code ?? "",
        address: user.profile?.address ?? "",
        building: user.profile?.building ?? "",
      });
    }
  }, [user]);

  const set = (field: string) => (e: React.ChangeEvent<HTMLInputElement>) =>
    setForm((f) => ({ ...f, [field]: e.target.value }));

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    setMessage(null);
    try {
      const body = new FormData();
      Object.entries(form).forEach(([k, v]) => body.append(k, v));
      if (imageFile) body.append("profile_image", imageFile);
      await api.post("/mypage/profile", body);
      await refresh();
      setMessage("プロフィールを更新しました。");
    } catch (err) {
      setMessage((err as Error).message);
    } finally {
      setSubmitting(false);
    }
  };

  if (authLoading) return <p className="text-center py-12 text-gray-400">読み込み中...</p>;

  return (
    <div className="max-w-md mx-auto">
      <h1 className="text-xl font-bold mb-6">プロフィール編集</h1>
      <form onSubmit={handleSubmit} className="bg-white rounded shadow p-6 space-y-4">
        {message && <p className="text-sm text-center text-indigo-600">{message}</p>}

        <div className="flex flex-col items-center gap-2">
          <div className="w-20 h-20 rounded-full bg-gray-200 overflow-hidden">
            {imageFile ? (
              <img src={URL.createObjectURL(imageFile)} alt="" className="w-full h-full object-cover" />
            ) : user?.profile?.profile_image_url ? (
              <img src={user.profile.profile_image_url} alt="" className="w-full h-full object-cover" />
            ) : (
              <span className="flex items-center justify-center h-full text-3xl">👤</span>
            )}
          </div>
          <label className="text-sm text-indigo-600 cursor-pointer hover:underline">
            画像を変更
            <input type="file" accept="image/*" className="hidden" onChange={(e) => setImageFile(e.target.files?.[0] ?? null)} />
          </label>
        </div>

        {[
          { field: "name", label: "ユーザー名" },
          { field: "postal_code", label: "郵便番号" },
          { field: "address", label: "住所" },
          { field: "building", label: "建物名・部屋番号" },
        ].map(({ field, label }) => (
          <div key={field}>
            <label className="text-sm font-medium block mb-1">{label}</label>
            <input
              value={form[field as keyof typeof form]}
              onChange={set(field)}
              className="w-full border rounded px-3 py-2 text-sm"
            />
          </div>
        ))}

        <button
          type="submit"
          disabled={submitting}
          className="w-full bg-indigo-600 text-white py-2 rounded font-bold hover:bg-indigo-700 disabled:opacity-50"
        >
          {submitting ? "保存中..." : "保存する"}
        </button>
      </form>
    </div>
  );
}
