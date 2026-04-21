<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rating;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class RatingController extends Controller
{
    public function store(Request $request, $transaction_id)
    {
        $user = Auth::user();
        $transaction = Transaction::with('order', 'order.user', 'ratings')
            ->findOrFail($transaction_id);
        $score = $request->input('score');
        if (!$score) {
            return back()->withErrors(['score' => '評価の数を選択してください'])->withInput();
        }
        $score = (int) $score;
        $score = max(1, min(5, $score));
        $existing = Rating::where('transaction_id', $transaction_id)
            ->where('rater_id', $user->id)
            ->exists();
        if ($existing) {
            return redirect()->route('items.index');
        }
        Rating::create([
            'transaction_id' => $transaction->id,
            'rater_id' => $user->id,
            'rated_user_id' => $transaction->order->user->id,
            'score' => $score,
        ]);
        if ($transaction->ratings()->count() === 2) {
            $transaction->update(['status' => 'completed']);
        }
        return redirect()->route('items.index')->with('success', '購入者への評価を完了しました。');
    }
}
