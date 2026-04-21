<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Rating;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MypageController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user    = $request->user()->load('profile');
        $userId  = $user->id;
        $viewType = $request->query('page', 'sell');

        $averageRating = Rating::where('rated_user_id', $userId)->avg('score');
        $averageRating = $averageRating ? round($averageRating) : null;

        $totalUnreadCount = Message::whereNull('read_at')
            ->where('user_id', '!=', $userId)
            ->whereHas('transaction', fn($q) => $q
                ->where('status', 'trading')
                ->where(fn($q2) => $q2
                    ->where('seller_id', $userId)
                    ->orWhereHas('order', fn($oq) => $oq->where('user_id', $userId))
                )
            )->count();

        $data = match ($viewType) {
            'buy'     => ['orders' => $user->orders()->with('product')->orderByDesc('created_at')->get()
                ->map(fn($o) => [
                    'id'         => $o->id,
                    'product'    => ['id' => $o->product->id, 'item_name' => $o->product->item_name, 'price' => $o->product->price,
                        'image_url' => $o->product->image_path ? asset('storage/' . $o->product->image_path) : null],
                    'created_at' => $o->created_at,
                ])],
            'trading' => $this->getTradingData($userId),
            default   => ['products' => $user->products()->orderByDesc('created_at')->get()
                ->map(fn($p) => [
                    'id'        => $p->id,
                    'item_name' => $p->item_name,
                    'price'     => $p->price,
                    'is_sold'   => $p->is_sold,
                    'image_url' => $p->image_path ? asset('storage/' . $p->image_path) : null,
                ])],
        };

        return response()->json(array_merge([
            'user'              => [
                'id'               => $user->id,
                'name'             => $user->name,
                'email'            => $user->email,
                'profile_image_url' => $user->profile?->image_path
                    ? asset('storage/' . $user->profile->image_path)
                    : null,
                'postal_code'      => $user->profile?->postal_code,
                'address'          => $user->profile?->address,
                'building'         => $user->profile?->building,
            ],
            'average_rating'    => $averageRating,
            'total_unread_count' => $totalUnreadCount,
            'view_type'          => $viewType,
        ], $data));
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'postal_code'   => 'nullable|string|max:8',
            'address'       => 'nullable|string|max:255',
            'building'      => 'nullable|string|max:255',
            'profile_image' => 'nullable|image|max:2048',
        ]);

        /** @var \App\Models\User $user */
        $user    = $request->user();
        $profile = $user->profile ?? $user->profile()->create([
            'postal_code' => '',
            'address'     => '',
            'building'    => '',
            'image_path'  => null,
        ]);

        if ($request->hasFile('profile_image')) {
            if ($profile->image_path && Storage::disk('public')->exists($profile->image_path)) {
                Storage::disk('public')->delete($profile->image_path);
            }
            $profile->image_path = $request->file('profile_image')->store('profile_images', 'public');
        }

        $user->update(['name' => $request->name]);
        $profile->update([
            'postal_code' => $request->postal_code,
            'address'     => $request->address,
            'building'    => $request->building,
        ]);

        return response()->json(['message' => 'プロフィールを更新しました。']);
    }

    private function getTradingData(int $userId): array
    {
        $transactions = Transaction::with(['order.product'])
            ->where('status', 'trading')
            ->where(fn($q) => $q
                ->where('seller_id', $userId)
                ->orWhereHas('order', fn($oq) => $oq->where('user_id', $userId))
            )
            ->withMax(['messages' => fn($q) => $q->where('user_id', '!=', $userId)], 'created_at')
            ->orderByDesc('messages_max_created_at')
            ->orderByDesc('transactions.created_at')
            ->get();

        return [
            'transactions' => $transactions->map(fn($t) => [
                'id'           => $t->id,
                'status'       => $t->status,
                'unread_count' => $t->messages()->whereNull('read_at')->where('user_id', '!=', $userId)->count(),
                'product'      => [
                    'id'        => $t->order->product->id,
                    'item_name' => $t->order->product->item_name,
                    'image_url' => $t->order->product->image_path
                        ? asset('storage/' . $t->order->product->image_path)
                        : null,
                ],
            ]),
        ];
    }
}
