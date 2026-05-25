<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
#use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel; // tambah ini

class Consultation extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'expert_id',
        'topic',
        'fee',
        'status',
        'started_at',
        'scheduled_end_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'scheduled_end_at' => 'datetime',
        'ended_at' => 'datetime',
        'fee' => 'decimal:2',
    ];

    // Relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi ke expert
    public function expert()
    {
        return $this->belongsTo(User::class, 'expert_id');
    }

    // Relasi ke payment
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    // Relasi ke chat messages
    public function messages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    // Relasi ke rating
    public function rating()
    {
        return $this->hasOne(Rating::class);
    }
}