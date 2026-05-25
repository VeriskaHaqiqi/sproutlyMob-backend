<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
#use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel; // tambah ini

class ArticleCategory extends BaseModel
{
    use HasFactory;

    protected $fillable = ['name'];

    // Relasi ke articles
    public function articles()
    {
        return $this->hasMany(Article::class, 'category_id');
    }
}