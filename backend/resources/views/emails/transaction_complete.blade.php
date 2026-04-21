{{-- resources/views/emails/transaction_complete.blade.php --}}
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>取引完了通知</title>
    <style>
        body { font-family: sans-serif; line-height: 1.5; background-color: #f8f9fa; color: #333; }
        .container { max-width: 600px; margin: 40px auto; padding: 20px; border: 1px solid #ccc; background-color: #fff; border-radius: 8px; }
        h1 { color: #2c3e50; margin-bottom: 20px; }
        p { margin-bottom: 15px; }
        .highlight { color: #e67e22; font-weight: bold; }
        a { color: #2980b9; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>取引が完了しました</h1>
        <p>{{ $transaction->seller->name }} さん</p>
        <p>購入者が <span class="highlight">{{ $transaction->order->product->item_name }}</span> の取引を完了しました。</p>
        <p>取引内容の詳細は、下記のリンクよりアプリにログインし、取引画面にて評価を完了してください。</p>
        <p><a href="{{ route('transaction.show', ['transaction_id' => $transaction->id]) }}" target="_blank">取引画面はこちら</a></p>
        <p>このメールは自動送信ですので、返信できません。</p>
        <p>今後とも coachtechフリマ をよろしくお願いいたします。</p>
    </div>
</body>
</html>

