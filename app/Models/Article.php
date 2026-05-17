<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'content',
        'cover_image',
        'status',
    ];

    // Relasi ke user (penulis)
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi ke category
    public function category()
    {
        return $this->belongsTo(ArticleCategory::class, 'category_id');
    }

    // Relasi ke bookmarks
    public function bookmarks()
    {
        return $this->hasMany(BookmarkedArticle::class, 'article_id');
    }
}