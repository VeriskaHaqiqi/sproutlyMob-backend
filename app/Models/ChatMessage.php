<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
#use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel; // tambah ini

class ChatMessage extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'consultation_id',
        'sender_id',
        'message',
        'attachment',
        'message_type',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    // Relasi ke consultation
    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    // Relasi ke sender (user)
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}