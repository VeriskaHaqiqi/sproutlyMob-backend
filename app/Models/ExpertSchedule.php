<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpertSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'expert_id',
        'day',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relasi ke expert
    public function expert()
    {
        return $this->belongsTo(User::class, 'expert_id');
    }
}