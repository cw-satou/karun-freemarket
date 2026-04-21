<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function index(Request $request, $transaction_id)
    {
        $user = Auth::user();
        $transaction = Transaction::with(['order.product', 'order.user', 'seller'])
            ->findOrFail($transaction_id);

        $this->authorizeTransaction($transaction, $user->id);

        $transaction->messages()
            ->whereNull('read_at')
            ->where('user_id', '!=', $user->id)
            ->update(['read_at' => now()]);

        $messages = Message::with('user.profile')
            ->where('transaction_id', $transaction->id)
            ->orderBy('updated_at', 'asc')
            ->get()
            ->map(fn($m) => $this->formatMessage($m));

        $partner = $transaction->seller_id === $user->id
            ? $transaction->order->user
            : $transaction->seller;

        $partner->load('profile');

        return response()->json([
            'messages'    => $messages,
            'transaction' => [
                'id'      => $transaction->id,
                'status'  => $transaction->status,
                'product' => [
                    'id'        => $transaction->order->product->id,
                    'item_name' => $transaction->order->product->item_name,
                    'image_url' => $transaction->order->product->image_path
                        ? asset('storage/' . $transaction->order->product->image_path)
                        : null,
                ],
            ],
            'partner' => [
                'id'               => $partner->id,
                'name'             => $partner->name,
                'profile_image_url' => $partner->profile?->image_path
                    ? asset('storage/' . $partner->profile->image_path)
                    : null,
            ],
            'is_buyer' => $transaction->order->user_id === $user->id,
        ]);
    }

    public function store(Request $request, $transaction_id)
    {
        $request->validate([
            'message' => 'nullable|string|max:1000',
            'image'   => 'nullable|image|max:2048',
        ]);

        $transaction = Transaction::findOrFail($transaction_id);
        $this->authorizeTransaction($transaction, Auth::id());

        $message = new Message();
        $message->transaction_id = $transaction->id;
        $message->user_id        = Auth::id();
        $message->content        = $request->input('message');

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('messages', 'public');
            $message->image_path = $path;
        }
        $message->save();
        $message->load('user.profile');

        return response()->json(['message' => $this->formatMessage($message)], 201);
    }

    public function update(Request $request, $message_id)
    {
        $message = Message::findOrFail($message_id);
        if ($message->user_id !== Auth::id()) {
            abort(403);
        }
        $request->validate(['message' => 'required|string|max:1000']);
        $message->content   = $request->message;
        $message->edited_at = now();
        $message->save();

        return response()->json(['message' => $this->formatMessage($message->load('user.profile'))]);
    }

    public function destroy($message_id)
    {
        $message = Message::findOrFail($message_id);
        if ($message->user_id !== Auth::id()) {
            abort(403);
        }
        $message->delete();
        return response()->json(['message' => 'メッセージを削除しました。']);
    }

    private function formatMessage(Message $m): array
    {
        return [
            'id'          => $m->id,
            'content'     => $m->content,
            'image_url'   => $m->image_path ? asset('storage/' . $m->image_path) : null,
            'edited_at'   => $m->edited_at,
            'created_at'  => $m->created_at,
            'user_id'     => $m->user_id,
            'user'        => [
                'id'               => $m->user->id,
                'name'             => $m->user->name,
                'profile_image_url' => $m->user->profile?->image_path
                    ? asset('storage/' . $m->user->profile->image_path)
                    : null,
            ],
        ];
    }

    private function authorizeTransaction(Transaction $transaction, int $userId): void
    {
        $isSeller = $transaction->seller_id === $userId;
        $isBuyer  = $transaction->order?->user_id === $userId;
        if (!$isSeller && !$isBuyer) {
            abort(403);
        }
    }
}
