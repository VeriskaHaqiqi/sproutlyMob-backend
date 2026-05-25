<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
#use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel; // tambah ini

class BookmarkedArticle extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'article_id',
    ];

    // Relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke article
    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}