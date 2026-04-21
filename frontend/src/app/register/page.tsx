"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import { api } from "@/lib/api";

export default function RegisterPage() {
  const router = useRouter();
  const [form, setForm] = useState({ name: "", email: "", password: "", password_confirmation: "" });
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const set = (field: string) => (e: React.ChangeEvent<HTMLInputElement>) =>
    setForm((f) => ({ ...f, [field]: e.target.value }));

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    if (form.password !== form.password_confirmation) {
      setError("パスワードが一致しません");
      return;
    }
    setLoading(true);
    try {
      const data = await api.post<{ token: string; message: string }>("/register", form);
      localStorage.setItem("token", data.token);
      alert(data.message);
      router.push("/");
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="max-w-sm mx-auto mt-12">
      <h1 className="text-2xl font-bold mb-6 text-center">会員登録</h1>
      <form onSubmit={handleSubmit} className="bg-white rounded shadow p-6 space-y-4">
        {error && <p className="text-red-500 text-sm text-center">{error}</p>}
        {(["name", "email", "password", "password_confirmation"] as const).map((f) => (
          <div key={f}>
            <label className="text-sm font-medium block mb-1">
              {{ name: "ユーザー名", email: "メールアドレス", password: "パスワード", password_confirmation: "パスワード（確認）" }[f]}
            </label>
            <input
              type={f.includes("password") ? "password" : f === "email" ? "email" : "text"}
              value={form[f]}
              onChange={set(f)}
              required
              className="w-full border rounded px-3 py-2 text-sm"
            />
          </div>
        ))}
        <button
          type="submit"
          disabled={loading}
          className="w-full bg-indigo-600 text-white py-2 rounded font-bold hover:bg-indigo-700 disabled:opacity-50"
        >
          {loading ? "登録中..." : "登録する"}
        </button>
      </form>
      <p className="text-center text-sm mt-4 text-gray-500">
        すでにアカウントをお持ちの方は{" "}
        <Link href="/login" className="text-indigo-600 hover:underline">
          ログイン
        </Link>
      </p>
    </div>
  );
}
