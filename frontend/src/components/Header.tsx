"use client";

import Link from "next/link";
import { useAuth } from "@/lib/auth";
import { useRouter } from "next/navigation";

export default function Header() {
  const { user, logout } = useAuth();
  const router = useRouter();

  const handleLogout = async () => {
    await logout();
    router.push("/login");
  };

  return (
    <header className="bg-white border-b shadow-sm">
      <div className="max-w-5xl mx-auto px-4 h-14 flex items-center justify-between">
        <Link href="/" className="text-xl font-bold text-indigo-600">
          フリマ
        </Link>
        <nav className="flex items-center gap-4 text-sm">
          {user ? (
            <>
              <Link href="/mypage" className="hover:text-indigo-600">
                マイページ
              </Link>
              <Link href="/sell" className="hover:text-indigo-600">
                出品
              </Link>
              <button
                onClick={handleLogout}
                className="text-gray-500 hover:text-red-500"
              >
                ログアウト
              </button>
            </>
          ) : (
            <>
              <Link href="/login" className="hover:text-indigo-600">
                ログイン
              </Link>
              <Link
                href="/register"
                className="bg-indigo-600 text-white px-3 py-1 rounded hover:bg-indigo-700"
              >
                会員登録
              </Link>
            </>
          )}
        </nav>
      </div>
    </header>
  );
}
