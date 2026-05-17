<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Specialization extends Model
{
    use HasFactory;

    protected $fillable = [
        'expert_id',
        'name',
    ];

    // Relasi ke expert
    public function expert()
    {
        return $this->belongsTo(User::class, 'expert_id');
    }
}