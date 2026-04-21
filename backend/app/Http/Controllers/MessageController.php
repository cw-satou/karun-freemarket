<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Requests\StoreMessageRequest;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function store(StoreMessageRequest $request, $transaction_id)
    {
        $transaction = Transaction::findOrFail($transaction_id);
        $message = new Message();
        $message->transaction_id = $transaction->id;
        $message->user_id = Auth::id();
        $message->content = $request->input('message');

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('messages', 'public');
            $message->image_path = $path;
        }
        $message->save();
        return redirect()->route('transaction.show', ['transaction_id' => $transaction->id])
            ->with('success', 'メッセージが送信されました');
    }

    public function update(Request $request)
    {
        $message = Message::findOrFail($request->message_id);
        if ($message->user_id !== auth()->id()) {
            abort(403);
        }
        $newContent = $request->input('message');

        if (!empty($newContent)) {
            $message->content = $newContent;
            $message->edited_at = now();
            $message->save();
        }
        return redirect()->back()->with('success', 'メッセージを更新しました');
    }

    public function destroy(Request $request)
    {
        $message = Message::findOrFail($request->message_id);
        if ($message->user_id !== auth()->id()) {
            abort(403);
        }
        $message->delete();
        return redirect()->back()->with('success', 'メッセージを削除しました');
    }
}
