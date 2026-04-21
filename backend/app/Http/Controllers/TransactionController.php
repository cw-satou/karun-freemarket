<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Message;
use App\Models\Rating;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;


class TransactionController extends Controller
{
    public function show($transaction_id)
    {
        $user = auth()->user();

        $transaction = Transaction::with(['order.product'])
            ->findOrFail($transaction_id);

        $transaction->messages()
            ->whereNull('read_at')
            ->where('user_id', '!=', $user->id)
            ->update(['read_at' => now()]);

        $partner = $transaction->seller_id === $user->id
            ? $transaction->order->user
            : $transaction->seller;

        $partner->profile_image_url =
            $partner->profile && $partner->profile->image_path
                ? asset('storage/' . $partner->profile->image_path)
                : null;

        $isBuyer = $transaction->order->user_id === $user->id;
        $messages = Message::with('user.profile')
            ->where('transaction_id', $transaction->id)
            ->orderBy('updated_at', 'asc')
            ->get()
            ->map(function ($message) {
                $message->profile_image_url =
                    $message->user->profile && $message->user->profile->image_path
                        ? asset('storage/' . $message->user->profile->image_path)
                        : null;
                return $message;
            });

        $sidebarTransactions = Transaction::with(['order.product'])
            ->where('status', 'trading')
            ->where(function ($query) use ($user) {
                $query->where('seller_id', $user->id)
                    ->orWhereHas('order', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            })
            ->withMax(
                ['messages' => function ($query) use ($user) {
                    $query->where('user_id', '!=', $user->id);
                }],
                'created_at'
            )
            ->orderByDesc('messages_max_created_at')
            ->orderByDesc('transactions.created_at')
            ->get();

        return view('transaction', compact(
            'transaction',
            'partner',
            'isBuyer',
            'messages',
            'user',
            'sidebarTransactions'
        ));
    }

    public function complete(Request $request, $transaction_id)
    {
        $user = Auth::user();
        $transaction = Transaction::with('order.product', 'seller', 'ratings')
        ->findOrFail($transaction_id);
        $score = $request->input('score');
        if (!$score) {
            return back()->withErrors(['score' => '評価の数を選択してください'])->withInput();
        }
        $score = (int) $score;
        $score = max(1, min(5, $score));
        $alreadyRated = Rating::where('transaction_id', $transaction->id)
        ->where('rater_id', $user->id)
        ->exists();

        if ($alreadyRated) {
            return redirect()->route('items.index');
        }
        Rating::create([
            'transaction_id' => $transaction->id,
            'rater_id'       => $user->id,
            'rated_user_id'  => $transaction->seller_id,
            'score'          => $score,
        ]);

        if ($transaction->ratings()->count() === 2) {
        $transaction->update(['status' => 'completed']);
        }
        $seller = $transaction->seller;
        Mail::send('emails.transaction_complete', ['transaction' => $transaction], function ($mail) use ($seller) {
            $mail->to($seller->email, $seller->name)
                ->subject('購入者から評価が届きました');
        });
        return redirect()->route('items.index')->with('success', '評価を送信しました');
    }
}
