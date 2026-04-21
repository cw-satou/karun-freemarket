<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;
    protected $guarded = [
        'id',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class)
        ->latest();
    }

    public function rating()
    {
        return $this->hasOne(Rating::class);
    }

    // 複数評価（両者分すべて取得、件数チェック用）
    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }
}
