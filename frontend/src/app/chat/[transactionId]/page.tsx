"use client";

import { useEffect, useRef, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { api, type Message } from "@/lib/api";
import { useAuth } from "@/lib/auth";

type ChatData = {
  messages: Message[];
  transaction: {
    id: number;
    status: string;
    product: { id: number; item_name: string; image_url: string | null };
  };
  partner: { id: number; name: string; profile_image_url: string | null };
  is_buyer: boolean;
};

export default function ChatPage() {
  const { transactionId } = useParams<{ transactionId: string }>();
  const router = useRouter();
  const { user, loading: authLoading } = useAuth();
  const [data, setData] = useState<ChatData | null>(null);
  const [text, setText] = useState("");
  const [image, setImage] = useState<File | null>(null);
  const [editId, setEditId] = useState<number | null>(null);
  const [editText, setEditText] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const bottomRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!authLoading && !user) router.push("/login");
  }, [authLoading, user, router]);

  const fetchMessages = () =>
    api
      .get<ChatData>(`/transactions/${transactionId}/messages`)
      .then(setData)
      .catch(console.error);

  useEffect(() => {
    fetchMessages();
    const id = setInterval(fetchMessages, 5000);
    return () => clearInterval(id);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [transactionId]);

  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [data?.messages.length]);

  const sendMessage = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!text && !image) return;
    setSubmitting(true);
    try {
      const body = new FormData();
      if (text) body.append("message", text);
      if (image) body.append("image", image);
      await api.post(`/transactions/${transactionId}/messages`, body);
      setText("");
      setImage(null);
      fetchMessages();
    } catch (err) {
      alert((err as Error).message);
    } finally {
      setSubmitting(false);
    }
  };

  const updateMessage = async (id: number) => {
    try {
      await api.patch(`/messages/${id}`, { message: editText });
      setEditId(null);
      fetchMessages();
    } catch (err) {
      alert((err as Error).message);
    }
  };

  const deleteMessage = async (id: number) => {
    if (!confirm("削除しますか？")) return;
    try {
      await api.delete(`/messages/${id}`);
      fetchMessages();
    } catch (err) {
      alert((err as Error).message);
    }
  };

  if (authLoading || !data) {
    return <p className="text-center py-12 text-gray-400">読み込み中...</p>;
  }

  return (
    <div className="max-w-2xl mx-auto flex flex-col h-[calc(100vh-8rem)]">
      <div className="bg-white border-b px-4 py-3 flex items-center gap-3 flex-shrink-0">
        {data.transaction.product.image_url && (
          <img src={data.transaction.product.image_url} alt="" className="w-10 h-10 object-cover rounded" />
        )}
        <div>
          <p className="text-sm font-medium">{data.transaction.product.item_name}</p>
          <p className="text-xs text-gray-500">{data.partner.name} さんとのやりとり</p>
        </div>
      </div>

      <div className="flex-1 overflow-y-auto px-4 py-4 space-y-4">
        {data.messages.map((m) => {
          const isMe = m.user_id === user?.id;
          return (
            <div key={m.id} className={`flex gap-2 ${isMe ? "flex-row-reverse" : ""}`}>
              <div className="w-8 h-8 rounded-full bg-gray-200 flex-shrink-0 overflow-hidden">
                {m.user.profile_image_url ? (
                  <img src={m.user.profile_image_url} alt="" className="w-full h-full object-cover" />
                ) : (
                  <span className="flex items-center justify-center h-full text-xs">👤</span>
                )}
              </div>
              <div className={`max-w-xs ${isMe ? "items-end" : "items-start"} flex flex-col`}>
                {editId === m.id ? (
                  <div className="flex gap-2">
                    <input
                      value={editText}
                      onChange={(e) => setEditText(e.target.value)}
                      className="border rounded px-2 py-1 text-sm"
                    />
                    <button onClick={() => updateMessage(m.id)} className="text-xs text-indigo-600">保存</button>
                    <button onClick={() => setEditId(null)} className="text-xs text-gray-400">取消</button>
                  </div>
                ) : (
                  <>
                    {m.content && (
                      <div className={`rounded-lg px-3 py-2 text-sm ${isMe ? "bg-indigo-500 text-white" : "bg-white border"}`}>
                        {m.content}
                        {m.edited_at && <span className="text-xs opacity-70 ml-1">(編集済)</span>}
                      </div>
                    )}
                    {m.image_url && (
                      <img src={m.image_url} alt="" className="rounded max-w-xs mt-1" />
                    )}
                    {isMe && (
                      <div className="flex gap-2 mt-1">
                        <button
                          onClick={() => { setEditId(m.id); setEditText(m.content ?? ""); }}
                          className="text-xs text-gray-400 hover:text-indigo-500"
                        >
                          編集
                        </button>
                        <button
                          onClick={() => deleteMessage(m.id)}
                          className="text-xs text-gray-400 hover:text-red-500"
                        >
                          削除
                        </button>
                      </div>
                    )}
                  </>
                )}
                <p className="text-xs text-gray-400 mt-1">
                  {new Date(m.created_at).toLocaleTimeString("ja-JP", { hour: "2-digit", minute: "2-digit" })}
                </p>
              </div>
            </div>
          );
        })}
        <div ref={bottomRef} />
      </div>

      <form onSubmit={sendMessage} className="border-t bg-white px-4 py-3 flex gap-2 flex-shrink-0">
        <label className="cursor-pointer text-gray-400 hover:text-indigo-500 flex items-center">
          📎
          <input type="file" accept="image/*" className="hidden" onChange={(e) => setImage(e.target.files?.[0] ?? null)} />
        </label>
        {image && <span className="text-xs text-gray-500 self-center">{image.name}</span>}
        <input
          value={text}
          onChange={(e) => setText(e.target.value)}
          placeholder="メッセージを入力..."
          className="flex-1 border rounded px-3 py-2 text-sm"
        />
        <button
          type="submit"
          disabled={submitting || (!text && !image)}
          className="bg-indigo-600 text-white px-4 py-2 rounded text-sm hover:bg-indigo-700 disabled:opacity-50"
        >
          送信
        </button>
      </form>
    </div>
  );
}
