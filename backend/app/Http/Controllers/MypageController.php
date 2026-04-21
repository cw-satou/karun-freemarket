<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Transaction;
use App\Models\Rating;


class MypageController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $profile = $user->profile;
        $averageRating = Rating::where('rated_user_id', $user->id)
            ->avg('score');
        $averageRating = $averageRating ? round($averageRating) : null;

        $viewType = $request->query('page', 'sell');
        $userId = $user->id;
        $totalUnreadCount = \App\Models\Message::whereNull('read_at')
            ->where('user_id', '!=', $userId)
            ->whereHas('transaction', function ($query) use ($userId) {
                $query->where('status', 'trading')
                    ->where(function ($q) use ($userId) {
                        $q->where('seller_id', $userId)
                            ->orWhereHas('order', fn($orderQuery) =>
                                $orderQuery->where('user_id', $userId)
                            );
                    });
            })
            ->count();
        if ($viewType === 'buy') {
            $data = [
                'orders' => $user->orders()
                ->with('product')
                ->orderByDesc('created_at')
                ->get()];
        } elseif ($viewType === 'trading') {
            $transactions = Transaction::with(['order.product'])
                ->where('status', 'trading')
                ->where(function($query) use ($userId) {
                    $query->where('seller_id', $userId)
                        ->orWhereHas('order', fn($orderQuery) =>
                            $orderQuery->where('user_id', $userId)
                        );
                })
                ->withMax(['messages' => fn($q) => $q->where('user_id', '!=', $userId)], 'created_at')
                ->orderByDesc('messages_max_created_at')
                ->orderByDesc('transactions.created_at')
                ->get();
            $unreadCounts = [];
            foreach ($transactions as $transaction) {
                $unreadCounts[$transaction->id] = $transaction->messages()
                    ->whereNull('read_at')
                    ->where('user_id', '!=', $userId)
                    ->count();
            }
            $data = [
                'transactions' => $transactions,
                'unreadCounts'  => $unreadCounts,
            ];
        } else {
            $data = [
                'products' => $user->products()
                    ->orderByDesc('created_at')
                    ->get()];
        }
        return view('mypage', array_merge([
            'user' => $user,
            'profile' => $profile,
            'viewType' => $viewType,
            'totalUnreadCount' => $totalUnreadCount,
            'averageRating' => $averageRating,
        ], $data));
    }

    public function edit()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        if (!$user->profile) {
            $user->profile()->create([
                'postal_code' => '',
                'address' => '',
                'building' => '',
                'image_path' => null,
            ]);
        }
        $user->load('profile');
        return view('profile_edit', [
            'user' => $user,
            'profile' => $user->profile,
        ]);
    }

    public function update(ProfileRequest $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $profile = $user->profile;

        if ($request->hasFile('profile_image')) {
            if ($profile->image_path && Storage::disk('public')->exists($profile->image_path)) {
                Storage::disk('public')->delete($profile->image_path);
            }

            $profile->image_path = $request->file('profile_image')->store('profile_images', 'public');
        }
        $user->update(['name' => $request->name]);
        $profile->update([
            'postal_code' => $request->postal_code,
            'address' => $request->address,
            'building' => $request->building,
        ]);
        return redirect('/?tab=mylist')->with('success', 'プロフィールを更新しました。');
    }
}
