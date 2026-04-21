<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $guarded = [
        'id',
    ];

    public function seller()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function condition()
    {
        return $this->belongsTo(Condition::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_product');
    }
    public function favoritedByUsers()
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function order()
    {
        return $this->hasOne(Order::class);
    }

    public function getIsSoldAttribute()
    {
        return $this->order()->exists();
    }
    public function scopeSearchByName($query, $keyword)
    {
        if (!empty($keyword)) {
            $query->where('item_name', 'like', "%{$keyword}%");
        }
        return $query;
    }

}
