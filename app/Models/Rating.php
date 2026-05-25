<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
#use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel; // tambah ini

class Rating extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'consultation_id',
        'user_id',
        'expert_id',
        'score',
        'comment',
    ];

    // Relasi ke consultation
    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    // Relasi ke user (pemberi rating)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi ke expert (penerima rating)
    public function expert()
    {
        return $this->belongsTo(User::class, 'expert_id');
    }
}